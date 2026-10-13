<?php

use App\Domain\Sessions\StartSession;
use App\Enums\SessionMode;
use App\Models\Skill;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->user = User::factory()->create();

    // Fără disciplinele alese, `subject.show` face abort(404) și tot testul ar
    // trece pe lângă paginile pe care ar trebui să le verifice (§2a).
    foreach (Subject::all() as $i => $subject) {
        $this->user->subjects()->attach($subject->id, [
            'is_primary' => $i === 0,
            'sort_order' => $i,
            'added_at' => now(),
        ]);
    }

    $this->assertNoServerError = function (TestResponse $r, string $url): void {
        expect($r->status())->toBeLessThan(
            500,
            "GET {$url} a întors {$r->status()}"
        );
    };
});

/**
 * Plasa de siguranță pentru clasa de erori care nu se vede decât în browser:
 * un nume de rută greșit, o cheie lipsă într-o Collection, o coloană neselectată
 * pe care o citește un accesor. Toate trec de tipuri și de Pint.
 */
it('nu întoarce 5xx pe nicio pagină publică', function () {
    $urls = ['/', '/sitemap.xml', '/bac-2027'];

    foreach (Subject::all() as $subject) {
        $urls[] = route('public.subject', $subject->slug, false);

        foreach (Skill::where('subject_id', $subject->id)->get() as $skill) {
            $urls[] = route('public.skill', [$subject->slug, $skill->slug], false);
        }
    }

    foreach ($urls as $url) {
        ($this->assertNoServerError)($this->get($url), $url);
    }

    expect($urls)->not->toBeEmpty();
});

it('nu întoarce 5xx pe nicio pagină a aplicației', function () {
    $urls = ['/panou', '/ce-nu-am-inteles', '/setari', '/disciplinele-mele'];

    foreach (Subject::all() as $subject) {
        $urls[] = route('subject.show', $subject->slug, false);
        $urls[] = route('course.index', $subject->slug, false);

        foreach (Skill::where('subject_id', $subject->id)->get() as $skill) {
            $urls[] = route('course.show', [$subject->slug, $skill->slug], false);
        }
    }

    foreach ($urls as $url) {
        ($this->assertNoServerError)($this->actingAs($this->user)->get($url), $url);
    }
});

it('nu întoarce 5xx pe pagina de antrenament', function () {
    $subject = Subject::where('slug', 'matematica')->firstOrFail();

    $session = app(StartSession::class)(
        user: $this->user,
        subjectId: $subject->id,
        mode: SessionMode::Practice,
        count: 3,
    );

    $url = route('practice.play', $session->uuid, false);

    ($this->assertNoServerError)($this->actingAs($this->user)->get($url), $url);
});

/**
 * Props-urile `Inertia::defer` NU se evaluează la un GET obișnuit — pleacă abia
 * la cererea parțială care urmează. Un test care cere doar pagina trece verde
 * peste o excepție din interiorul lor.
 *
 * Exact așa a scăpat clasamentul: `subjectBoard()` pica pe un membru Redis fără
 * cont în baza de date, dar suita era verde.
 */
it('evaluează props-urile amânate, nu doar pagina care le conține', function () {
    $deferred = [
        ['component' => 'Subjects/Show', 'prop' => 'board', 'route' => 'subject.show'],
    ];

    foreach ($deferred as $case) {
        foreach (Subject::all() as $subject) {
            $url = route($case['route'], $subject->slug, false);

            $headers = [
                'X-Inertia' => 'true',
                'X-Inertia-Partial-Component' => $case['component'],
                'X-Inertia-Partial-Data' => $case['prop'],
            ];

            $response = $this->actingAs($this->user)->get($url, $headers);

            // Inertia răspunde 409 când versiunea de assets nu se potrivește și
            // anunță în antet versiunea corectă. În teste nu există manifest de
            // build, deci o aflăm de la server și repetăm cererea.
            if ($response->status() === 409) {
                $headers['X-Inertia-Version'] = $response->headers->get('X-Inertia-Version');
                $response = $this->actingAs($this->user)->get($url, $headers);
            }

            ($this->assertNoServerError)($response, $url.' ['.$case['prop'].']');
            $response->assertJsonPath('props.'.$case['prop'].'.total', fn ($t) => is_int($t));
        }
    }
});
