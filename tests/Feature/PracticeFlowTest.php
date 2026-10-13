<?php

use App\Domain\Attempts\SubmitAnswer;
use App\Domain\Attempts\SubmitAnswerData;
use App\Domain\Sessions\ServeNextItem;
use App\Domain\Sessions\StartSession;
use App\Enums\ItemType;
use App\Enums\SessionMode;
use App\Models\Attempt;
use App\Models\Item;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->user = User::factory()->create();
    $this->subject = Subject::where('slug', 'matematica')->first();

    /**
     * Construiește răspunsul corect pentru orice tip de item, ca testele să nu
     * depindă de un singur tip.
     */
    $this->correctAnswerFor = function (Item $item): array {
        $key = $item->gradingKey();

        return match (true) {
            isset($key['correct']) => ['choice' => $key['correct'][0]],
            isset($key['order']) => ['order' => $key['order']],
            isset($key['pairs']) => ['pairs' => $key['pairs']],
            isset($key['values']) => ['values' => array_map(
                fn ($v) => is_array($v) ? $v[0] : $v,
                $key['values'],
            )],
            array_key_exists('value', $key) => ['value' => (string) $key['value']],
            default => [],
        };
    };
});

it('parcurge o sesiune de antrenament cap-coadă', function () {
    $session = app(StartSession::class)(
        user: $this->user,
        subjectId: $this->subject->id,
        mode: SessionMode::Practice,
        count: 5,
    );

    expect($session->total_items)->toBe(5)
        ->and($session->sessionItems)->toHaveCount(5);

    // Itemii de holdout sunt rezervați simulărilor și nu apar în antrenament.
    $served = $session->sessionItems->pluck('item_id');
    expect(Item::whereIn('id', $served)->where('is_exam_holdout', true)->count())->toBe(0);
});

it('nu trimite NICIODATĂ cheia de corectare înainte de răspuns', function () {
    $session = app(StartSession::class)(
        $this->user, $this->subject->id, SessionMode::Practice, 3
    );

    $response = $this->actingAs($this->user)
        ->get(route('practice.play', $session->uuid));

    $response->assertOk();

    // Invariantul central: itemul ajunge la client fără answer/explanation.
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Practice/Play')
        ->has('item.uuid')
        ->has('item.payload')
        ->missing('item.answer')
        ->missing('item.explanation')
        ->where('feedback', null)
    );

    // Și nu apar nici în HTML-ul brut al paginii, sub nicio formă.
    $item = $session->sessionItems->first()->item;
    $key = json_encode($item->gradingKey());

    expect($response->getContent())->not->toContain($key);
});

it('corectează, acordă XP și scrie faptul în attempts', function () {
    $session = app(StartSession::class)($this->user, $this->subject->id, SessionMode::Practice, 5);

    // Urmăm fluxul real: serverul decide ce item se servește și ștampilează served_at.
    $sessionItem = app(ServeNextItem::class)->next($session);
    $item = $sessionItem->item;

    expect($sessionItem->served_at)->not->toBeNull();

    $correct = ($this->correctAnswerFor)($item);

    $result = app(SubmitAnswer::class)($this->user, new SubmitAnswerData(
        clientUuid: (string) Str::uuid(),
        itemId: $item->id,
        answer: $correct,
        sessionUuid: $session->uuid,
    ));

    expect($result->grade->isCorrect)->toBeTrue()
        ->and($result->xp->amount)->toBeGreaterThan(0)
        // Cronometrajul vine de la server, nu de la client.
        ->and($result->attempt->server_ms)->not->toBeNull()
        // Cheia de corectare se dezvăluie abia acum.
        ->and($result->correctAnswer)->not->toBeNull();

    $this->assertDatabaseHas('attempts', [
        'user_id' => $this->user->id,
        'item_id' => $item->id,
        'is_correct' => true,
    ]);

    // Starea derivată s-a actualizat pe user.
    $this->user->refresh();
    expect($this->user->xp)->toBe($result->xp->amount)
        ->and($this->user->streak_days)->toBe(1);

    // Jobul de mastery a rulat sincron (QUEUE_CONNECTION=sync în teste).
    $this->assertDatabaseHas('mastery', [
        'user_id' => $this->user->id,
        'skill_id' => $result->attempt->skill_id,
    ]);
});

it('este idempotent: același client_uuid nu acordă XP de două ori', function () {
    $item = Item::servable()->where('type', ItemType::Mcq)->firstOrFail();
    $uuid = (string) Str::uuid();

    $submit = app(SubmitAnswer::class);
    $data = new SubmitAnswerData(
        clientUuid: $uuid,
        itemId: $item->id,
        answer: ['choice' => $item->gradingKey()['correct'][0]],
    );

    $first = $submit($this->user, $data);
    $second = $submit($this->user->refresh(), $data);

    expect($first->isDuplicate)->toBeFalse()
        ->and($second->isDuplicate)->toBeTrue()
        ->and(Attempt::where('client_uuid', $uuid)->count())->toBe(1);

    // XP-ul s-a acordat o singură dată.
    expect($this->user->refresh()->xp)->toBe($first->xp->amount);
});

it('nu mai acordă XP pentru un item rezolvat corect recent', function () {
    $item = Item::servable()->where('type', ItemType::Mcq)->firstOrFail();
    $correct = $item->gradingKey()['correct'][0];
    $submit = app(SubmitAnswer::class);

    $first = $submit($this->user, new SubmitAnswerData(
        clientUuid: (string) Str::uuid(),
        itemId: $item->id,
        answer: ['choice' => $correct],
    ));

    // Alt client_uuid, deci un fapt nou — dar itemul e în cooldown.
    $second = $submit($this->user->refresh(), new SubmitAnswerData(
        clientUuid: (string) Str::uuid(),
        itemId: $item->id,
        answer: ['choice' => $correct],
    ));

    expect($first->xp->amount)->toBeGreaterThan(0)
        ->and($second->xp->amount)->toBe(0)
        ->and($second->xp->reasons)->toHaveKey('cooldown');
});

it('respectă capul zilnic de XP', function () {
    // xp_today / xp_today_date NU sunt fillable — sunt stare derivată, scrisă
    // doar de SubmitAnswer. În test le forțăm explicit.
    $this->user->forceFill([
        'xp_today' => 795,
        'xp_today_date' => now($this->user->timezone)->toDateString(),
    ])->save();

    $item = Item::servable()->where('type', ItemType::Mcq)->firstOrFail();

    $result = app(SubmitAnswer::class)($this->user, new SubmitAnswerData(
        clientUuid: (string) Str::uuid(),
        itemId: $item->id,
        answer: ['choice' => $item->gradingKey()['correct'][0]],
    ));

    expect($result->xp->amount)->toBe(5)
        ->and($result->xp->cappedByDailyLimit)->toBeTrue();
});

it('refuză modificarea sau ștergerea unui attempt', function () {
    $item = Item::servable()->firstOrFail();

    $result = app(SubmitAnswer::class)($this->user, new SubmitAnswerData(
        clientUuid: (string) Str::uuid(),
        itemId: $item->id,
        answer: ['choice' => 'a'],
    ));

    // attempts e append-only prin construcție.
    expect(fn () => $result->attempt->update(['xp_awarded' => 99999]))
        ->toThrow(RuntimeException::class);

    expect(fn () => $result->attempt->delete())
        ->toThrow(RuntimeException::class);
});

it('nu permite accesul la sesiunea altui elev', function () {
    $session = app(StartSession::class)($this->user, $this->subject->id, SessionMode::Practice, 3);
    $altul = User::factory()->create();

    $this->actingAs($altul)
        ->get(route('practice.play', $session->uuid))
        ->assertForbidden();
});

it('răspunsurile din antrenament nu intră în rating-ul competitiv', function () {
    $item = Item::servable()->firstOrFail();

    $result = app(SubmitAnswer::class)($this->user, new SubmitAnswerData(
        clientUuid: (string) Str::uuid(),
        itemId: $item->id,
        answer: ['choice' => $item->gradingKey()['correct'][0] ?? 'a'],
    ));

    // Doar modurile competitive (duel, provocarea zilei) mișcă rating-ul.
    expect($result->elo)->toBeNull()
        ->and($result->attempt->is_ranked)->toBeFalse()
        ->and($this->user->refresh()->rating)->toBe(1000);
});

it('calculează ziua în fusul elevului, nu în UTC', function () {
    // Aplicația rulează în UTC, dar granițele de zi trebuie să fie locale.
    // 00:30 în Chișinău e încă 21:30 (ziua precedentă) în UTC iarna.
    $this->travelTo(Carbon::parse('2027-01-15 22:30:00', 'UTC'));

    $item = Item::servable()->firstOrFail();

    $result = app(SubmitAnswer::class)($this->user, new SubmitAnswerData(
        clientUuid: (string) Str::uuid(),
        itemId: $item->id,
        answer: ($this->correctAnswerFor)($item),
    ));

    $this->user->refresh();

    // În Chișinău (UTC+2 iarna) e deja 16 ianuarie.
    expect($this->user->last_active_date->toDateString())->toBe('2027-01-16')
        ->and($result->attempt->answered_at)->not->toBeNull();

    $this->travelBack();
});
