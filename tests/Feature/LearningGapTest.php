<?php

use App\Domain\Learning\MarkGap;
use App\Domain\Sessions\ServeNextItem;
use App\Domain\Sessions\StartSession;
use App\Enums\SessionMode;
use App\Models\Item;
use App\Models\LearningGap;
use App\Models\Lesson;
use App\Models\SessionItem;
use App\Models\Skill;
use App\Models\Subject;
use App\Models\SubjectStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->matematica = Subject::where('slug', 'matematica')->firstOrFail();
    $this->skill = Skill::where('subject_id', $this->matematica->id)->where('slug', 'derivate')->firstOrFail();
    $this->lesson = Lesson::where('skill_id', $this->skill->id)->firstOrFail();

    $this->student = User::factory()->create();
    $this->student->subjects()->attach($this->matematica->id, [
        'is_primary' => true, 'sort_order' => 0, 'added_at' => now(),
    ]);
});

/**
 * Fără identificatori stabili, un bloc inserat la mijlocul unei lecții ar face
 * ca tot ce a marcat elevul să arate brusc spre alt conținut.
 */
it('fiecare bloc de lecție are un identificator stabil', function () {
    $ids = collect($this->lesson->blocks)->pluck('id');

    expect($ids)->not->toContain(null)
        ->and($ids->unique())->toHaveCount($ids->count());

    // Id-urile existente nu se schimbă la o nouă salvare.
    $inainte = $ids->all();
    $this->lesson->touch();
    $this->lesson->refresh();

    expect(collect($this->lesson->blocks)->pluck('id')->all())->toBe($inainte);
});

it('marchează un bloc și păstrează o copie a conținutului', function () {
    $block = collect($this->lesson->blocks)->firstWhere('type', 'formula');

    app(MarkGap::class)(
        user: $this->student,
        skill: $this->skill,
        lesson: $this->lesson,
        blockId: $block['id'],
        note: 'Nu înțeleg de unde apare 2ab.',
    );

    $gap = LearningGap::where('user_id', $this->student->id)->firstOrFail();

    expect($gap->block_type)->toBe('formula')
        ->and($gap->skill_id)->toBe($this->skill->id)
        ->and($gap->note)->toBe('Nu înțeleg de unde apare 2ab.')
        // Fotografia conținutului: lecția se poate edita, istoricul trebuie să
        // rămână inteligibil.
        ->and($gap->excerpt)->not->toBeNull();
});

it('istoricul supraviețuiește ștergerii lecției', function () {
    $block = collect($this->lesson->blocks)->firstWhere('type', 'example');

    app(MarkGap::class)($this->student, $this->skill, $this->lesson, $block['id']);
    $gap = LearningGap::where('user_id', $this->student->id)->firstOrFail();
    $textul = $gap->excerpt;

    $this->lesson->delete();
    $gap->refresh();

    // Referința la lecție dispare, dar elevul vede în continuare CE marcase.
    expect($gap->lesson_id)->toBeNull()
        ->and($gap->excerpt)->toBe($textul)
        ->and($gap->skill_id)->toBe($this->skill->id);
});

it('nu marchează același bloc de două ori', function () {
    $block = collect($this->lesson->blocks)->firstWhere('type', 'warning');

    foreach (range(1, 3) as $i) {
        $this->actingAs($this->student)->post(route('gaps.store'), [
            'skill_id' => $this->skill->id,
            'lesson_id' => $this->lesson->id,
            'block_id' => $block['id'],
        ]);
    }

    expect(LearningGap::where('user_id', $this->student->id)->count())->toBe(1);
});

it('permite marcarea unei teme întregi, fără bloc', function () {
    // Cazul care vine dinspre un exercițiu greșit, nu dinspre lecție.
    $this->actingAs($this->student)->post(route('gaps.store'), [
        'skill_id' => $this->skill->id,
        'source' => 'practice',
    ])->assertRedirect();

    $gap = LearningGap::where('user_id', $this->student->id)->firstOrFail();

    expect($gap->block_id)->toBeNull()
        ->and($gap->source)->toBe('practice')
        ->and($gap->blockLabel())->toBe('Toată tema');
});

/**
 * A recunoaște că nu înțelegi trebuie să fie gratuit în ambele sensuri.
 */
it('marcarea nu acordă XP și nu penalizează nimic', function () {
    $xpInainte = $this->student->xp;

    $this->actingAs($this->student)->post(route('gaps.store'), [
        'skill_id' => $this->skill->id,
    ]);

    $this->student->refresh();

    expect($this->student->xp)->toBe($xpInainte)
        ->and($this->student->rating)->toBe(1000)
        ->and(SubjectStat::where('user_id', $this->student->id)->exists())->toBeFalse();
});

it('lista se micșorează pe măsură ce elevul înțelege', function () {
    app(MarkGap::class)($this->student, $this->skill);
    $gap = LearningGap::where('user_id', $this->student->id)->firstOrFail();

    $this->actingAs($this->student)->put(route('gaps.update', $gap->id), ['action' => 'resolve']);
    expect($gap->refresh()->isResolved())->toBeTrue();

    // …dar se poate redeschide, dacă a fost prea optimist.
    $this->actingAs($this->student)->put(route('gaps.update', $gap->id), ['action' => 'reopen']);
    expect($gap->refresh()->isResolved())->toBeFalse();
});

it('grupează istoricul pe disciplină și competență', function () {
    $altSkill = Skill::where('subject_id', $this->matematica->id)->where('slug', 'integrale')->firstOrFail();

    app(MarkGap::class)($this->student, $this->skill);
    app(MarkGap::class)($this->student, $altSkill);

    $this->actingAs($this->student)->get(route('gaps.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Gaps/Index')
            ->where('open_total', 2)
            ->where('groups.0.subject', 'Matematică')
            ->has('groups.0.skills', 2)
        );
});

/**
 * Dacă elevul ar bănui că e urmărit, ar înceta să recunoască ce nu înțelege.
 */
it('lista e strict privată', function () {
    app(MarkGap::class)($this->student, $this->skill, note: 'secretul meu');
    $gap = LearningGap::where('user_id', $this->student->id)->firstOrFail();

    $altul = User::factory()->create();

    // Nu o poate vedea…
    $continut = $this->actingAs($altul)->get(route('gaps.index'))->getContent();
    expect($continut)->not->toContain('secretul meu');

    // …și nu o poate modifica.
    $this->actingAs($altul)->put(route('gaps.update', $gap->id), ['action' => 'resolve'])->assertForbidden();
    $this->actingAs($altul)->delete(route('gaps.destroy', $gap->id))->assertForbidden();
});

it('lecția afișează ce a marcat deja elevul', function () {
    $block = collect($this->lesson->blocks)->firstWhere('type', 'formula');
    app(MarkGap::class)($this->student, $this->skill, $this->lesson, $block['id']);

    $this->actingAs($this->student)
        ->get(route('course.show', ['matematica', 'derivate']))
        ->assertInertia(fn ($page) => $page->where('marked_blocks', [$block['id']]));
});

// ==================== MARCARE DIN EXERCIȚII ====================

/**
 * „Nu înțeleg tema" e prea gros ca să folosească unui profesor. După un răspuns
 * greșit, elevul trebuie să poată arăta exact ce parte din lecție îl blochează.
 */
it('ecranul de răspuns trimite blocurile lecției temei', function () {
    $item = Item::practicePool()
        ->whereHas('skills', fn ($q) => $q->whereKey($this->skill->id))
        ->firstOrFail();

    $session = app(StartSession::class)(
        $this->student, $this->matematica->id, SessionMode::Practice, 1
    );
    SessionItem::where('session_id', $session->id)->update(['item_id' => $item->id]);
    app(ServeNextItem::class)->next($session->refresh());

    $this->actingAs($this->student)->post(route('practice.answer', $session->uuid), [
        'client_uuid' => (string) Str::uuid(),
        'item_uuid' => $item->uuid,
        'session_uuid' => $session->uuid,
        'answer' => ['choice' => 'raspuns_gresit_intentionat'],
    ])->assertInertia(fn ($page) => $page
        ->where('lesson.skill_slug', 'derivate')
        ->has('lesson.blocks')
        // Doar id, tip și un fragment — nu tot conținutul lecției.
        ->has('lesson.blocks.0.id')
        ->has('lesson.blocks.0.type')
        ->has('lesson.blocks.0.label')
    );
});

it('marchează mai multe blocuri deodată, dintr-un exercițiu', function () {
    $blocuri = collect($this->lesson->blocks)
        ->whereIn('type', ['formula', 'example'])
        ->pluck('id')
        ->take(2)
        ->values();

    expect($blocuri)->toHaveCount(2);

    $this->actingAs($this->student)->post(route('gaps.store'), [
        'skill_id' => $this->skill->id,
        'lesson_id' => $this->lesson->id,
        'block_ids' => $blocuri->all(),
        'note' => 'Nu văd legătura dintre ele.',
        'source' => 'practice',
    ])->assertRedirect();

    $gaps = LearningGap::where('user_id', $this->student->id)->get();

    expect($gaps)->toHaveCount(2)
        ->and($gaps->pluck('block_id')->sort()->values()->all())->toBe($blocuri->sort()->values()->all())
        ->and($gaps->pluck('source')->unique()->all())->toBe(['practice'])
        // Nota se atașează o singură dată: e o întrebare, chiar dacă trimite
        // spre mai multe locuri din lecție.
        ->and($gaps->whereNotNull('note'))->toHaveCount(1);
});

it('lista goală de blocuri marchează toată tema', function () {
    $this->actingAs($this->student)->post(route('gaps.store'), [
        'skill_id' => $this->skill->id,
        'block_ids' => [],
        'source' => 'practice',
    ])->assertRedirect();

    $gap = LearningGap::where('user_id', $this->student->id)->firstOrFail();

    expect($gap->block_id)->toBeNull()
        ->and($gap->blockLabel())->toBe('Toată tema');
});

it('marcajele din lecție și din exerciții ajung în același grup', function () {
    $block = collect($this->lesson->blocks)->firstWhere('type', 'formula');

    // Unul dinspre lecție…
    app(MarkGap::class)($this->student, $this->skill, $this->lesson, $block['id'], source: 'lesson');
    // …altul dinspre un exercițiu greșit.
    app(MarkGap::class)($this->student, $this->skill, source: 'practice');

    $this->actingAs($this->student)->get(route('gaps.index'))
        ->assertInertia(fn ($page) => $page
            ->where('open_total', 2)
            // Aceeași competență, deci un singur grup — exact ce vede profesorul.
            ->has('groups.0.skills', 1)
            ->has('groups.0.skills.0.items', 2)
        );
});

// ==================== VIZIBILITATE ====================

it('numărul de nelămuriri apare pe fiecare pagină', function () {
    app(MarkGap::class)($this->student, $this->skill);

    $this->actingAs($this->student)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('open_gaps', 1));

    // Lămurite nu se mai numără — lista trebuie să se micșoreze, nu să crească.
    $gap = LearningGap::where('user_id', $this->student->id)->firstOrFail();
    $this->actingAs($this->student)->put(route('gaps.update', $gap->id), ['action' => 'resolve']);

    $this->actingAs($this->student)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('open_gaps', 0));
});

it('panoul arată nelămuririle pe fiecare disciplină', function () {
    app(MarkGap::class)($this->student, $this->skill);

    $this->actingAs($this->student)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('subjects.0.open_gaps', 1));
});

it('pagina disciplinei arată propriile nelămuriri', function () {
    app(MarkGap::class)($this->student, $this->skill);

    $this->actingAs($this->student)->get(route('subject.show', 'matematica'))
        ->assertInertia(fn ($page) => $page->where('open_gaps', 1));
});

/**
 * Se poate nimeri răspunsul fără să înțelegi de ce — iar exact acela e cazul în
 * care marcarea contează cel mai mult.
 */
it('permite marcarea și după un răspuns corect', function () {
    $item = Item::practicePool()
        ->whereHas('skills', fn ($q) => $q->whereKey($this->skill->id))
        ->where('type', 'mcq')
        ->firstOrFail();

    $session = app(StartSession::class)(
        $this->student, $this->matematica->id, SessionMode::Practice, 1
    );
    SessionItem::where('session_id', $session->id)->update(['item_id' => $item->id]);
    app(ServeNextItem::class)->next($session->refresh());

    $this->actingAs($this->student)->post(route('practice.answer', $session->uuid), [
        'client_uuid' => (string) Str::uuid(),
        'item_uuid' => $item->uuid,
        'session_uuid' => $session->uuid,
        'answer' => ['choice' => $item->gradingKey()['correct'][0]],
    ])->assertInertia(fn ($page) => $page
        ->where('feedback.is_correct', true)
        // Blocurile lecției se trimit indiferent de rezultat.
        ->has('lesson.blocks')
    );
});

// ==================== ÎNTOARCEREA ÎN LECȚIE ====================

it('lista trimite blocul și dacă tema are lecție', function () {
    $block = collect($this->lesson->blocks)->firstWhere('type', 'formula');
    app(MarkGap::class)($this->student, $this->skill, $this->lesson, $block['id']);

    $this->actingAs($this->student)->get(route('gaps.index'))
        ->assertInertia(fn ($page) => $page
            ->where('groups.0.skills.0.has_lesson', true)
            ->where('groups.0.skills.0.items.0.block_id', $block['id'])
        );
});

it('deschide lecția cu blocul evidențiat', function () {
    $block = collect($this->lesson->blocks)->firstWhere('type', 'warning');
    $gap = app(MarkGap::class)($this->student, $this->skill, $this->lesson, $block['id']);

    $this->actingAs($this->student)
        ->get(route('course.show', ['matematica', 'derivate']).'?bloc='.$block['id'])
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('highlight', $block['id'])
            // Nelămurirea corespunzătoare, ca să poată fi închisă direct de aici.
            ->where('highlighted_gap', $gap->id)
        );
});

/**
 * Lecțiile se editează. O evidențiere spre un bloc care nu mai există ar arăta
 * ca o defecțiune.
 */
it('ignoră evidențierea unui bloc care nu mai există', function () {
    $this->actingAs($this->student)
        ->get(route('course.show', ['matematica', 'derivate']).'?bloc=inexistent')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('highlight', null)
            ->where('highlighted_gap', null)
        );
});

it('închide bucla: marcat, recitit, lămurit', function () {
    $block = collect($this->lesson->blocks)->firstWhere('type', 'example');
    $gap = app(MarkGap::class)($this->student, $this->skill, $this->lesson, $block['id']);

    // Se întoarce în lecție…
    $this->actingAs($this->student)
        ->get(route('course.show', ['matematica', 'derivate']).'?bloc='.$block['id'])
        ->assertInertia(fn ($page) => $page->where('highlighted_gap', $gap->id));

    // …și închide direct de acolo, fără să revină în listă.
    $this->actingAs($this->student)->put(route('gaps.update', $gap->id), ['action' => 'resolve']);

    expect($gap->refresh()->isResolved())->toBeTrue();

    // A doua vizită nu mai oferă acțiunea — nu mai e nimic de închis.
    $this->actingAs($this->student)
        ->get(route('course.show', ['matematica', 'derivate']).'?bloc='.$block['id'])
        ->assertInertia(fn ($page) => $page
            ->where('highlight', $block['id'])
            ->where('highlighted_gap', null)
        );
});

it('nu evidențiază nelămurirea altui elev', function () {
    $block = collect($this->lesson->blocks)->firstWhere('type', 'formula');
    app(MarkGap::class)($this->student, $this->skill, $this->lesson, $block['id']);

    $altul = User::factory()->create();
    $altul->subjects()->attach($this->matematica->id, [
        'is_primary' => true, 'sort_order' => 0, 'added_at' => now(),
    ]);

    // Blocul se evidențiază — e conținut public — dar nu e nimic de închis.
    $this->actingAs($altul)
        ->get(route('course.show', ['matematica', 'derivate']).'?bloc='.$block['id'])
        ->assertInertia(fn ($page) => $page->where('highlighted_gap', null));
});
