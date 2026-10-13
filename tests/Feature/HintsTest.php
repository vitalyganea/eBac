<?php

use App\Domain\Attempts\SubmitAnswer;
use App\Domain\Attempts\SubmitAnswerData;
use App\Domain\Sessions\RevealHint;
use App\Domain\Sessions\ServeNextItem;
use App\Domain\Sessions\StartSession;
use App\Enums\SessionMode;
use App\Models\Item;
use App\Models\SessionItem;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->user = User::factory()->create();
    $this->subject = Subject::where('slug', 'matematica')->firstOrFail();

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

it('fiecare întrebare publicată are indicii', function () {
    $fara = Item::servable()->get()->filter(fn (Item $i) => $i->hintCount() === 0);

    expect($fara)->toBeEmpty(
        'Itemi fără indicii: '.$fara->pluck('source_ref')->implode(', ')
    );
});

/**
 * Invariantul central al indiciilor.
 *
 * Dacă textele ar veni odată cu itemul, elevul le-ar putea citi din props și
 * apoi pretinde că n-a folosit niciunul — păstrând tot XP-ul. Trimitem doar
 * numărătoarea.
 */
it('nu trimite TEXTUL indiciilor împreună cu itemul', function () {
    $session = app(StartSession::class)($this->user, $this->subject->id, SessionMode::Practice, 3);
    $sessionItem = app(ServeNextItem::class)->next($session);
    $item = $sessionItem->item;

    expect($item->hintCount())->toBeGreaterThan(0);

    $response = $this->actingAs($this->user)->get(route('practice.play', $session->uuid));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('item.hints.total', $item->hintCount())
        ->where('item.hints.revealed', 0)
        ->where('item.hints.allowed', true)
    );

    // Niciun text de indiciu nu apare în HTML-ul paginii.
    foreach ($item->hintList() as $hint) {
        expect($response->getContent())->not->toContain($hint);
    }
});

it('dezvăluie indiciile pe rând și contorizează pe server', function () {
    $session = app(StartSession::class)($this->user, $this->subject->id, SessionMode::Practice, 3);
    $sessionItem = app(ServeNextItem::class)->next($session);
    $item = $sessionItem->item;

    $reveal = app(RevealHint::class);

    $first = $reveal->next($session, $item->uuid);
    expect($first['index'])->toBe(0)
        ->and($first['text'])->toBe($item->hintAt(0))
        ->and($first['revealed'])->toBe(1)
        ->and($first['has_more'])->toBeTrue();

    $second = $reveal->next($session, $item->uuid);
    expect($second['text'])->toBe($item->hintAt(1))
        ->and($second['revealed'])->toBe(2);

    // Contorul e persistat, nu ținut în sesiunea HTTP.
    expect($sessionItem->refresh()->hints_used)->toBe(2);
});

it('nu dă mai multe indicii decât există', function () {
    $session = app(StartSession::class)($this->user, $this->subject->id, SessionMode::Practice, 3);
    $item = app(ServeNextItem::class)->next($session)->item;
    $reveal = app(RevealHint::class);

    for ($i = 0; $i < $item->hintCount(); $i++) {
        $reveal->next($session, $item->uuid);
    }

    expect(fn () => $reveal->next($session, $item->uuid))
        ->toThrow(RuntimeException::class);
});

it('nu permite indicii în simulare', function () {
    $session = app(StartSession::class)($this->user, $this->subject->id, SessionMode::Mock, 2);
    $item = app(ServeNextItem::class)->next($session)->item;

    // În simulare indiciile ar denatura măsurătoarea.
    expect(fn () => app(RevealHint::class)->next($session, $item->uuid))
        ->toThrow(RuntimeException::class);
});

it('penalizează XP-ul în trepte, după contorul de pe server', function () {
    $reveal = app(RevealHint::class);
    $submit = app(SubmitAnswer::class);
    $rezultate = [];

    // ACELAȘI item în toate cele trei cazuri. Comparația între itemi diferiți ar
    // fi lipsită de sens: bonusul de viteză se acordă doar la tipurile de
    // recunoaștere, deci XP-ul de bază diferă între tipuri.
    $item = Item::servable()
        ->where('is_exam_holdout', false)
        ->get()
        ->firstWhere(fn (Item $i) => $i->hintCount() >= 3);

    expect($item)->not->toBeNull();

    // Trei sesiuni identice, cu 0 / 1 / 2 indicii cerute.
    foreach ([0, 1, 2] as $hints) {
        $user = User::factory()->create();
        $session = app(StartSession::class)($user, $this->subject->id, SessionMode::Practice, 1);

        // Fixăm itemul sesiunii, apoi îl servim (ștampilează served_at).
        SessionItem::where('session_id', $session->id)->update(['item_id' => $item->id]);
        app(ServeNextItem::class)->next($session->refresh());

        for ($i = 0; $i < $hints; $i++) {
            $reveal->next($session, $item->uuid);
        }

        $rezultate[$hints] = $submit($user, new SubmitAnswerData(
            clientUuid: (string) Str::uuid(),
            itemId: $item->id,
            answer: ($this->correctAnswerFor)($item),
            sessionUuid: $session->uuid,
        ))->xp->amount;
    }

    expect($rezultate[0])->toBeGreaterThan($rezultate[1])
        ->and($rezultate[1])->toBeGreaterThan($rezultate[2])
        // Nu ajunge la zero: cine a cerut ajutor și a rezolvat a învățat ceva.
        ->and($rezultate[2])->toBeGreaterThan(0);
});

/**
 * Regresie pe cea mai importantă schimbare: `hints_used` venea de la client.
 */
it('ignoră un hints_used trimis din browser', function () {
    $session = app(StartSession::class)($this->user, $this->subject->id, SessionMode::Practice, 1);
    $item = app(ServeNextItem::class)->next($session)->item;

    // Elevul cere două indicii...
    app(RevealHint::class)->next($session, $item->uuid);
    app(RevealHint::class)->next($session, $item->uuid);

    // ...apoi trimite răspunsul pretinzând că nu a folosit niciunul.
    $this->actingAs($this->user)->post(route('practice.answer', $session->uuid), [
        'client_uuid' => (string) Str::uuid(),
        'item_uuid' => $item->uuid,
        'session_uuid' => $session->uuid,
        'answer' => ($this->correctAnswerFor)($item),
        'hints_used' => 0,
    ])->assertOk();

    // Serverul a folosit propriul contor.
    expect($this->user->attempts()->latest('id')->first()->hints_used)->toBe(2);
});

it('refuză un indiciu pentru un item care nu e în sesiune', function () {
    $session = app(StartSession::class)($this->user, $this->subject->id, SessionMode::Practice, 1);
    $servit = SessionItem::where('session_id', $session->id)->pluck('item_id');
    $strain = Item::servable()->whereNotIn('id', $servit)->firstOrFail();

    expect(fn () => app(RevealHint::class)->next($session, $strain->uuid))
        ->toThrow(RuntimeException::class);
});

it('nu permite cererea de indicii în sesiunea altui elev', function () {
    $session = app(StartSession::class)($this->user, $this->subject->id, SessionMode::Practice, 1);
    $item = app(ServeNextItem::class)->next($session)->item;
    $altul = User::factory()->create();

    $this->actingAs($altul)
        ->post(route('practice.hint', $session->uuid), ['item_uuid' => $item->uuid])
        ->assertForbidden();
});
