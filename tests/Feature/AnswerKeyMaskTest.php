<?php

use App\Domain\Grading\AnswerKeyMask;
use App\Domain\Sessions\StartSession;
use App\Enums\ItemType;
use App\Enums\SessionMode;
use App\Http\Resources\ItemForStudentResource;
use App\Models\Attempt;
use App\Models\Item;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->user = User::factory()->create();
    $this->mask = app(AnswerKeyMask::class);
});

/**
 * Autorii scriu varianta corectă prima și pașii în ordinea bună. Dacă forma din
 * bancă ar ajunge la client, structura datelor ar fi chiar răspunsul (§2).
 */
function servedPayload(Item $item, string $seed = '1'): array
{
    return app(AnswerKeyMask::class)->maskPayload($item, $item->payload, $seed);
}

it('nu trimite cheile originale ale variantelor sau ale pașilor', function () {
    $probleme = [];

    $items = Item::servable()->whereIn('type', [
        ItemType::Mcq, ItemType::NextStep, ItemType::FindError, ItemType::CodeOutput,
        ItemType::OrderSteps, ItemType::Timeline,
    ])->get();

    foreach ($items as $item) {
        $original = collect([...($item->payload['choices'] ?? []), ...($item->payload['steps'] ?? [])])
            ->pluck('key')->map('strval');
        $served = collect([...servedPayload($item)['choices'] ?? [], ...servedPayload($item)['steps'] ?? []])
            ->pluck('key')->map('strval');

        if ($served->intersect($original)->isNotEmpty()) {
            $probleme[] = "{$item->source_ref}: cheie originală trimisă clientului";
        }
        if ($served->count() !== $original->count()) {
            $probleme[] = "{$item->source_ref}: număr diferit de variante";
        }
    }

    expect($probleme)->toBeEmpty(implode("\n", $probleme));
});

it('itemii de ordonare nu sosesc în ordinea corectă', function () {
    $items = Item::servable()->whereIn('type', [ItemType::OrderSteps, ItemType::Timeline])->get();
    expect($items)->not->toBeEmpty();

    $inOrdineaCorecta = $items->filter(function (Item $item) {
        $served = collect(servedPayload($item)['steps'])->pluck('key')->all();
        $correct = $this->mask->maskKey($item, $item->gradingKey())['order'];

        return $served === $correct;
    });

    // O permutare aleatoare poate nimeri ordinea corectă (1/120 la 5 pași), dar
    // nu la toți itemii deodată — cum se întâmpla înainte.
    expect($inOrdineaCorecta->count())->toBeLessThan(max(2, intdiv($items->count(), 4)));
});

it('păstrează aceeași ordine pentru același rând din sesiune', function () {
    $item = Item::servable()->where('type', ItemType::Mcq)->firstOrFail();

    expect(servedPayload($item, '42')['choices'])->toBe(servedPayload($item, '42')['choices']);
});

it('corectează răspunsul trimis cu jetoane și scrie cheile originale în attempts', function () {
    $subject = Subject::where('slug', 'matematica')->first();
    $session = app(StartSession::class)($this->user, $subject->id, SessionMode::Practice, 10);

    $page = $this->actingAs($this->user)->get(route('practice.play', $session->uuid));
    $served = $page->viewData('page')['props']['item'];
    $item = Item::where('uuid', $served['uuid'])->firstOrFail();

    $key = $item->gradingKey();
    $answer = match (true) {
        isset($key['correct']) => ['choice' => $this->mask->token($item, $key['correct'][0])],
        isset($key['order']) => ['order' => array_map(fn ($k) => $this->mask->token($item, $k), $key['order'])],
        isset($key['pairs']) => ['pairs' => $key['pairs']],
        isset($key['values']) => ['values' => array_map(fn ($v) => is_array($v) ? $v[0] : $v, $key['values'])],
        default => ['value' => (string) $key['value']],
    };

    $this->actingAs($this->user)
        ->post(route('practice.answer', $session->uuid), [
            'client_uuid' => (string) Str::uuid(),
            'item_uuid' => $item->uuid,
            'session_uuid' => $session->uuid,
            'answer' => $answer,
        ])
        ->assertInertia(fn (AssertableInertia $p) => $p
            ->where('feedback.is_correct', true)
            // Cheia dezvăluită folosește aceleași jetoane ca variantele afișate.
            ->where('feedback.correct_answer', $this->mask->maskKey($item, $key)));

    $stored = Attempt::where('user_id', $this->user->id)->latest('id')->first()->answer;

    if (isset($key['correct'])) {
        expect($stored['choice'])->toBe($key['correct'][0]);
    }
    if (isset($key['order'])) {
        expect($stored['order'])->toBe($key['order']);
    }
});

it('resursa itemului nu conține cheile originale', function () {
    $item = Item::servable()->where('type', ItemType::OrderSteps)->firstOrFail();

    $json = json_encode((new ItemForStudentResource($item))->toArray(request()));

    foreach ($item->payload['steps'] as $step) {
        expect($json)->not->toContain('"key":"'.$step['key'].'"');
    }
});
