<?php

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Skill;
use App\Models\Subject;
use App\Models\SubjectStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->matematica = Subject::where('slug', 'matematica')->firstOrFail();

    $this->student = User::factory()->create();
    $this->student->subjects()->attach($this->matematica->id, [
        'is_primary' => true, 'sort_order' => 0, 'added_at' => now(),
    ]);
});

it('afișează cursul cu lecțiile disponibile', function () {
    $this->actingAs($this->student)
        ->get(route('course.index', 'matematica'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Course/Index')->has('chapters'));
});

it('randează o lecție cu blocurile ei', function () {
    $this->actingAs($this->student)
        ->get(route('course.show', ['matematica', 'derivate']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Course/Show')
            ->where('lesson.title', 'Derivate')
            ->has('lesson.blocks')
            ->has('lesson.objectives')
            ->has('prerequisites')
        );
});

it('arată prerechizitele lecției, din graful de competențe', function () {
    $response = $this->actingAs($this->student)
        ->get(route('course.show', ['matematica', 'derivate']));

    $prereqs = collect($response->viewData('page')['props']['prerequisites'])->pluck('name');

    // Derivatele cer limite și funcții — muchii din graful de prerechizite.
    expect($prereqs)->toContain('Limite de funcții', 'Funcții');
});

/**
 * Invariantul central al secțiunii „Curs".
 */
it('nu acordă XP pentru citirea teoriei', function () {
    $xpInainte = $this->student->xp;

    $this->actingAs($this->student)
        ->post(route('course.complete', ['matematica', 'derivate']))
        ->assertRedirect();

    $this->student->refresh();

    // A citi nu înseamnă a ști: progresul se câștigă din răspunsuri.
    expect($this->student->xp)->toBe($xpInainte)
        ->and($this->student->level)->toBe(1)
        ->and(SubjectStat::where('user_id', $this->student->id)->exists())->toBeFalse();

    // Dar parcurgerea se înregistrează, ca elevul să știe de unde reia.
    expect(LessonProgress::where('user_id', $this->student->id)->whereNotNull('completed_at')->count())->toBe(1);
});

it('nu dă acces la cursul unei discipline nealese', function () {
    $strain = User::factory()->create();

    $this->actingAs($strain)->get(route('course.index', 'matematica'))->assertNotFound();
    $this->actingAs($strain)->get(route('course.show', ['matematica', 'derivate']))->assertNotFound();
});

it('întoarce 404 pentru o competență fără lecție publicată', function () {
    // Competențele se creează AICI, nu se caută printre cele existente. Varianta
    // dinamică s-a rupt în momentul în care matematica a ajuns la acoperire
    // completă: nu mai exista nicio competență fără lecție de găsit, iar testul
    // a început să pice tocmai pentru că am scris destul conținut.
    $faraLectie = Skill::create([
        'subject_id' => $this->matematica->id,
        'slug' => 'competenta-fara-lectie',
        'name' => 'Competență fără lecție',
        'is_leaf' => true,
    ]);

    $cuCiorna = Skill::create([
        'subject_id' => $this->matematica->id,
        'slug' => 'competenta-cu-ciorna',
        'name' => 'Competență cu lecție în lucru',
        'is_leaf' => true,
    ]);

    Lesson::create([
        'skill_id' => $cuCiorna->id,
        'title' => 'Ciornă nepublicată',
        'summary' => 'Încă se scrie.',
        'objectives' => ['Primul obiectiv', 'Al doilea obiectiv'],
        'blocks' => [['type' => 'text', 'text' => 'Text provizoriu.']],
        'status' => 'draft',
        'language' => 'ro',
    ]);

    // Lipsa lecției și lecția nepublicată trebuie să ducă la același rezultat:
    // altfel o ciornă ar deveni vizibilă elevilor printr-un link direct.
    foreach ([$faraLectie, $cuCiorna] as $skill) {
        $this->actingAs($this->student)
            ->get(route('course.show', ['matematica', $skill->slug]))
            ->assertNotFound();
    }
});

it('activează cardul Curs în hub când există lecții', function () {
    $this->actingAs($this->student)
        ->get(route('subject.show', 'matematica'))
        ->assertInertia(fn ($page) => $page
            ->where('sections.course.available', true)
            ->where('sections.course.lessons_count', Lesson::published()
                ->whereHas('skill', fn ($q) => $q->where('subject_id', $this->matematica->id))
                ->count())
        );
});

/**
 * Poartă de calitate pe conținutul lecțiilor, ca `ContentIntegrityTest` pentru itemi.
 */
it('lecțiile au blocuri valide structural', function () {
    $probleme = [];

    foreach (Lesson::with('skill')->get() as $lesson) {
        $ref = $lesson->skill->slug;

        if (blank($lesson->summary)) {
            $probleme[] = "$ref: fără rezumat";
        }
        if (count($lesson->objectives ?? []) < 2) {
            $probleme[] = "$ref: sub 2 obiective";
        }

        foreach ($lesson->blocks as $i => $block) {
            $type = $block['type'] ?? null;
            $at = "$ref, blocul $i";

            match ($type) {
                'heading', 'text' => blank($block['text'] ?? null) && $probleme[] = "$at: text gol",

                'formula' => blank($block['latex'] ?? null) && $probleme[] = "$at: formulă goală",

                // Un grafic fără puncte nu se poate desena.
                'plot' => (function () use ($block, $at, &$probleme) {
                    $series = $block['series'] ?? [];
                    if ($series === []) {
                        $probleme[] = "$at: grafic fără serii";
                    }
                    foreach ($series as $s) {
                        if (count($s['points'] ?? []) < 2) {
                            $probleme[] = "$at: serie cu sub 2 puncte";
                        }
                    }
                })(),

                'example' => count($block['steps'] ?? []) < 2 && $probleme[] = "$at: exemplu cu sub 2 pași",

                'warning' => blank($block['text'] ?? null) && $probleme[] = "$at: avertisment gol",

                // Un tabel cu rânduri de lățimi diferite se randează strâmb.
                'table' => (function () use ($block, $at, &$probleme) {
                    $headers = $block['headers'] ?? [];
                    $rows = $block['rows'] ?? [];

                    if (count($headers) < 2) {
                        $probleme[] = "$at: tabel cu sub 2 coloane";
                    }
                    if ($rows === []) {
                        $probleme[] = "$at: tabel fără rânduri";
                    }
                    foreach ($rows as $i => $row) {
                        if (count($row) !== count($headers)) {
                            $probleme[] = "$at: rândul $i are ".count($row).' celule pentru '.count($headers).' coloane';
                        }
                    }
                })(),

                'video' => blank($block['provider_id'] ?? null) && $probleme[] = "$at: video fără id",

                // La informatică, examenul acceptă Pascal SAU C/C++, la alegerea
                // candidatului. Un bloc de cod într-un singur limbaj
                // dezavantajează jumătate din candidați.
                'code' => (function () use ($block, $at, &$probleme) {
                    $variants = $block['variants'] ?? null;

                    if (! is_array($variants)) {
                        blank($block['code'] ?? null) && $probleme[] = "$at: bloc de cod gol";

                        return;
                    }

                    foreach (['cpp', 'pascal'] as $lang) {
                        if (blank($variants[$lang] ?? null)) {
                            $probleme[] = "$at: lipsește varianta '$lang'";
                        }
                    }
                })(),

                default => $probleme[] = "$at: tip necunoscut [$type]",
            };
        }
    }

    expect($probleme)->toBeEmpty(implode("\n", $probleme));
});

it('graficele sunt desenate din puncte, nu încărcate ca imagini', function () {
    $lesson = Lesson::whereHas('skill', fn ($q) => $q->where('slug', 'derivate'))->firstOrFail();

    $plots = collect($lesson->blocks)->where('type', 'plot');

    expect($plots)->not->toBeEmpty();

    foreach ($plots as $plot) {
        foreach ($plot['series'] as $series) {
            // Fiecare punct e o pereche [x, y] numerică — nicio referință la fișier.
            expect($series['points'][0])->toHaveCount(2)
                ->and($series['points'][0][0])->toBeNumeric();
        }
    }

    // Nicio lecție nu referă un fișier imagine: nu există pipeline de assets.
    expect(json_encode($lesson->blocks))->not->toContain('.png')
        ->and(json_encode($lesson->blocks))->not->toContain('.jpg');
});
