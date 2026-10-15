<?php

use App\Domain\Attempts\PurgeUserData;
use App\Models\EssayAttempt;
use App\Models\EssayPrompt;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->engleza = Subject::where('slug', 'limba-engleza')->firstOrFail();

    $this->student = User::factory()->create();
    $this->student->subjects()->attach($this->engleza->id, [
        'is_primary' => true, 'sort_order' => 0, 'added_at' => now(),
    ]);

    $this->prompt = EssayPrompt::where('slug', 'engleza-sb26-eseu')->firstOrFail();
});

/** Punctajul maxim la fiecare criteriu bifat. */
function maxCriteria(EssayPrompt $p): array
{
    return collect($p->rubric['criteria'])->mapWithKeys(fn ($c) => [$c['key'] => max(array_column($c['options'], 0))])->all();
}

it('apare pe pagina disciplinei doar unde proba are producere scrisă', function () {
    $this->actingAs($this->student)
        ->get(route('subject.show', 'limba-engleza'))
        ->assertInertia(fn ($page) => $page->where('sections.essays.count', 18));

    $mate = Subject::where('slug', 'matematica')->firstOrFail();
    $this->student->subjects()->attach($mate->id, ['is_primary' => false, 'sort_order' => 1, 'added_at' => now()]);

    $this->actingAs($this->student)
        ->get(route('subject.show', 'matematica'))
        ->assertInertia(fn ($page) => $page->where('sections.essays.count', 0));
});

it('listează cerințele și deschide una cu grila baremului', function () {
    $this->actingAs($this->student)->get(route('essays.index', 'limba-engleza'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Essays/Index')->has('prompts', 18));

    $this->actingAs($this->student)->get(route('essays.show', $this->prompt))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Essays/Write')
            ->where('prompt.words', [180, 200])
            ->where('prompt.rubric.total', 40)
            ->where('prompt.session', 'BAC 2026, sesiunea de bază')
        );
});

it('calculează nota pe server, numărând cuvintele din text', function () {
    $body = implode(' ', array_fill(0, 190, 'word'));

    $this->actingAs($this->student)->post(route('essays.store', $this->prompt), [
        'body' => $body,
        // Ignorat: când există text, cuvintele le numără serverul.
        'word_count' => 999,
        'criteria' => maxCriteria($this->prompt),
        'errors' => ['limba' => 7],
    ])->assertRedirect();

    $attempt = EssayAttempt::firstOrFail();

    // 20 (conținut) + 4 (semantică) + 11 (12 − 1 pentru 7 greșeli) + 4 (volum).
    expect($attempt->word_count)->toBe(190)
        ->and($attempt->score)->toBe(39)
        ->and($attempt->max_score)->toBe(40)
        ->and($this->student->fresh()->xp)->toBe(0);
});

it('acceptă eseul scris pe hârtie, după numărul de cuvinte introdus', function () {
    $this->actingAs($this->student)->post(route('essays.store', $this->prompt), [
        'word_count' => 120,
        'criteria' => maxCriteria($this->prompt),
        'errors' => ['limba' => 0],
    ])->assertRedirect();

    // 120 de cuvinte: plafon 6, volum 2.
    expect(EssayAttempt::firstOrFail()->score)->toBe(20 + 4 + 6 + 2);
});

it('respinge un nivel care nu există în barem', function () {
    $criteria = maxCriteria($this->prompt);
    $criteria['a'] = 3; // nivelurile sunt 0 / 2 / 4

    $this->actingAs($this->student)->post(route('essays.store', $this->prompt), [
        'word_count' => 190,
        'criteria' => $criteria,
        'errors' => ['limba' => 0],
    ])->assertSessionHasErrors('criteria.a');

    expect(EssayAttempt::count())->toBe(0);
});

it('ține eseurile private: altcineva primește 404', function () {
    $this->actingAs($this->student)->post(route('essays.store', $this->prompt), [
        'word_count' => 190, 'criteria' => maxCriteria($this->prompt), 'errors' => ['limba' => 0],
    ]);
    $attempt = EssayAttempt::firstOrFail();

    $this->actingAs($this->student)->get(route('essays.attempt', $attempt))->assertOk();

    $other = User::factory()->create();
    $other->subjects()->attach($this->engleza->id, ['is_primary' => true, 'sort_order' => 0, 'added_at' => now()]);
    $this->actingAs($other)->get(route('essays.attempt', $attempt))->assertNotFound();
});

it('nu deschide cerințele unei discipline nealese', function () {
    $other = User::factory()->create();

    $this->actingAs($other)->get(route('essays.index', 'limba-engleza'))->assertNotFound();
    $this->actingAs($other)->get(route('essays.show', $this->prompt))->assertNotFound();
});

it('șterge eseurile odată cu contul', function () {
    $this->actingAs($this->student)->post(route('essays.store', $this->prompt), [
        'word_count' => 190, 'criteria' => maxCriteria($this->prompt), 'errors' => ['limba' => 0],
    ]);

    app(PurgeUserData::class)($this->student);

    expect(EssayAttempt::count())->toBe(0);
});

it('fiecare cerință trimite la baremul ANCE al testului ei', function () {
    foreach (EssayPrompt::all() as $p) {
        expect($p->source)->toStartWith('ance:')
            ->and($p->source_ref)->toMatch('~^https://ance\.gov\.md/sites/default/files/12_(len|llro)_barem_[\w]+\.pdf#\S+$~');
    }
});
