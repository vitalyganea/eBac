<?php

use App\Domain\Exams\BlueprintResolver;
use App\Domain\Exams\MockUnavailable;
use App\Domain\Exams\StartMock;
use App\Enums\ExamProfile;
use App\Enums\SessionMode;
use App\Models\Attempt;
use App\Models\Item;
use App\Models\PracticeSession;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->user = User::factory()->create(['profile' => ExamProfile::Real]);

    foreach (Subject::all() as $i => $subject) {
        $this->user->subjects()->attach($subject->id, ['is_primary' => $i === 0, 'sort_order' => $i, 'added_at' => now()]);
    }

    $this->subject = fn (string $slug) => Subject::where('slug', $slug)->firstOrFail();

    /** Răspunsul corect, în forma în care l-ar trimite browserul. */
    $this->correctAnswerFor = function (Item $item): array {
        $key = $item->gradingKey();

        return match (true) {
            isset($key['correct']) => ['choice' => $key['correct'][0]],
            isset($key['order']) => ['order' => $key['order']],
            isset($key['pairs']) => ['pairs' => $key['pairs']],
            isset($key['values']) => ['values' => array_map(fn ($v) => is_array($v) ? $v[0] : $v, $key['values'])],
            default => ['value' => (string) $key['value']],
        };
    };

    $this->answer = function (PracticeSession $session, Item $item, array $answer) {
        return $this->actingAs($this->user)->post(route('practice.answer', $session->uuid), [
            'client_uuid' => (string) Str::uuid(),
            'item_uuid' => $item->uuid,
            'session_uuid' => $session->uuid,
            'answer' => $answer,
        ]);
    };

    /** Servește itemul curent (ștampilează served_at), ca în browser. */
    $this->current = function (PracticeSession $session): Item {
        $page = $this->actingAs($this->user)->get(route('practice.play', $session->uuid));

        return Item::where('uuid', $page->viewData('page')['props']['item']['uuid'])->firstOrFail();
    };
});

it('pornește o simulare completă la fiecare disciplină cu conținut', function (string $slug) {
    $subject = ($this->subject)($slug);
    $plan = app(BlueprintResolver::class)->planFor($this->user, $subject->id);

    $session = app(StartMock::class)($this->user, $subject->id);

    expect($session->mode)->toBe(SessionMode::Mock)
        ->and($session->total_items)->toBe($plan->itemCount())
        ->and($session->blueprint_id)->toBe($plan->blueprint->id)
        // Termenul e fixat pe server, la pornire.
        ->and((int) round(now()->diffInMinutes($session->expires_at)))->toBe($plan->minutes());

    // Exclusiv itemi de holdout (§8), fiecare o singură dată.
    $items = $session->sessionItems->pluck('item');
    expect($items->every(fn (Item $i) => $i->is_exam_holdout))->toBeTrue()
        ->and($items->pluck('id')->unique())->toHaveCount($items->count());

    // Fiecare item stă în secțiunea al cărei tip — și, unde e declarat,
    // capitol — îl acceptă. La matematică toate secțiunile au aceleași tipuri,
    // deci doar capitolul împiedică o „Geometrie" plină de algebră.
    foreach ($session->config['sections'] as $i => $section) {
        $types = $plan->autoSections[$i]['types'];
        $skills = $plan->autoSections[$i]['skills'];
        foreach ($section['positions'] as $position) {
            $item = $session->sessionItems->firstWhere('position', $position)->item->load('skills.parent');
            expect($types)->toContain($item->type->value);

            if ($skills !== []) {
                $slugs = $item->skills->flatMap(fn ($s) => [$s->slug, $s->parent?->slug])->filter()->all();
                expect(array_intersect($slugs, $skills))->not->toBeEmpty("{$item->source_ref} nu e din „{$section['name']}”");
            }
        }
    }
})->with(['matematica', 'informatica', 'limba-engleza']);

it('refuză cinstit simularea unei probe integral cu răspuns deschis', function (string $slug) {
    // Limba română și istoria: fiecare sarcină ANCE se corectează pe barem,
    // deci simularea n-ar avea ce nota — chiar dacă banca are itemi de
    // antrenament.
    expect(fn () => app(StartMock::class)($this->user, ($this->subject)($slug)->id))
        ->toThrow(MockUnavailable::class, 'integral pe barem');

    $this->actingAs($this->user)
        ->get(route('subject.show', $slug))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sections.mock.available', false)
            ->where('sections.mock.reason', fn (string $r) => str_contains($r, 'integral pe barem')));
})->with(['limba-romana', 'istoria']);

it('profilul alege structura, dar nu blochează accesul (§2a)', function () {
    $istoria = ($this->subject)('istoria');
    $math = ($this->subject)('matematica');

    $umanist = User::factory()->create(['profile' => ExamProfile::Umanist]);
    $arte = User::factory()->create(['profile' => ExamProfile::Arte]);

    expect(app(BlueprintResolver::class)->for($umanist, $istoria->id)->profile)->toBe(ExamProfile::Umanist)
        ->and(app(BlueprintResolver::class)->for($this->user, $istoria->id)->profile)->toBe(ExamProfile::Real)
        ->and(app(BlueprintResolver::class)->for($umanist, $math->id)->profile)->toBe(ExamProfile::Umanist)
        // Nu există structură de matematică pentru arte: primește cea disponibilă.
        ->and(app(BlueprintResolver::class)->for($arte, $math->id))->not->toBeNull();
});

it('nu arată corectarea pe parcurs și nu trimite cheia înainte de final', function () {
    $session = app(StartMock::class)($this->user, ($this->subject)('matematica')->id);

    $page = $this->actingAs($this->user)->get(route('practice.play', $session->uuid));
    $page->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Practice/Play')
        ->where('item.hints.allowed', false)
        ->whereNot('exam', null)
        ->has('exam.deadline_at')
        ->missing('item.answer'));

    $item = ($this->current)($session);

    // Răspunsul se înregistrează și elevul trece mai departe, fără feedback.
    ($this->answer)($session, $item, ($this->correctAnswerFor)($item))
        ->assertRedirect(route('practice.play', $session->uuid));

    expect(Attempt::where('session_id', $session->id)->count())->toBe(1);
});

it('primește un singur răspuns pe întrebare', function () {
    $session = app(StartMock::class)($this->user, ($this->subject)('matematica')->id);
    $item = ($this->current)($session);

    ($this->answer)($session, $item, ['choice' => 'z', 'value' => '0']);
    ($this->answer)($session, $item, ($this->correctAnswerFor)($item))->assertSessionHas('error');

    expect(Attempt::where('session_id', $session->id)->count())->toBe(1);
});

it('nu mai primește răspunsuri după termen, chiar dacă timerul din browser e oprit (§5)', function () {
    $session = app(StartMock::class)($this->user, ($this->subject)('matematica')->id);
    $item = ($this->current)($session);

    $this->travel(2)->hours();

    ($this->answer)($session, $item, ($this->correctAnswerFor)($item))->assertSessionHas('error');
    expect(Attempt::where('session_id', $session->id)->count())->toBe(0);

    // La revenire, lucrarea e închisă la termen și se arată rezultatul.
    $this->actingAs($this->user)->get(route('practice.play', $session->uuid))
        ->assertInertia(fn (AssertableInertia $p) => $p
            ->component('Exam/Result')
            ->where('result.earned', fn ($v) => (float) $v === 0.0));

    expect($session->refresh()->completed_at->equalTo($session->expires_at))->toBeTrue();
});

it('calculează punctajul pe secțiuni, cu ponderea din examen', function () {
    $session = app(StartMock::class)($this->user, ($this->subject)('informatica')->id);
    $sections = $session->config['sections'];

    // Răspunde corect la toată prima secțiune, greșit la a doua, nimic în rest.
    foreach ($session->sessionItems as $sessionItem) {
        $item = ($this->current)($session);
        $position = $session->sessionItems->firstWhere('item_id', $item->id)->position;

        if (in_array($position, $sections[0]['positions'], true)) {
            ($this->answer)($session, $item, ($this->correctAnswerFor)($item));
        } elseif (in_array($position, $sections[1]['positions'], true)) {
            ($this->answer)($session, $item, ['choice' => 'gresit']);
        } else {
            break;
        }
    }

    $this->actingAs($this->user)->post(route('mock.submit', $session->uuid))
        ->assertRedirect(route('practice.play', $session->uuid));

    expect((float) $session->refresh()->score)->toBe((float) $sections[0]['points']);

    $this->actingAs($this->user)->get(route('practice.play', $session->uuid))
        ->assertInertia(fn (AssertableInertia $p) => $p
            ->component('Exam/Result')
            // JSON nu păstrează „25.0" ca float: comparăm numeric.
            ->where('result.earned', fn ($v) => (float) $v === (float) $sections[0]['points'])
            ->where('result.sections.0.earned', fn ($v) => (float) $v === (float) $sections[0]['points'])
            ->where('result.sections.1.earned', fn ($v) => (float) $v === 0.0)
            ->where('result.sections.1.answered', count($sections[1]['positions']))
            ->has('result.review', $session->total_items)
            // După predare, cheia și explicația ajung la elev, în jetoanele
            // cu care au fost afișate variantele.
            ->whereType('result.review.0.feedback.correct_answer', 'array')
            ->whereType('result.review.0.feedback.explanation', 'string'));
});

it('declară cinstit ce nu se corectează automat (§13)', function () {
    $session = app(StartMock::class)($this->user, ($this->subject)('limba-engleza')->id);
    $this->actingAs($this->user)->post(route('mock.submit', $session->uuid));

    $this->actingAs($this->user)->get(route('practice.play', $session->uuid))
        ->assertInertia(fn (AssertableInertia $p) => $p
            ->where('result.auto_points', 30)
            ->where('result.total_points', 100)
            ->has('result.manual_sections', 2));
});

it('preferă întrebările nevăzute și declară reutilizarea', function () {
    $subject = ($this->subject)('informatica');

    $first = app(StartMock::class)($this->user, $subject->id);
    $this->actingAs($this->user)->post(route('mock.submit', $first->uuid));

    $second = app(StartMock::class)($this->user, $subject->id);
    $seen = $first->sessionItems->pluck('item_id');
    $fresh = $second->sessionItems->pluck('item_id')->diff($seen);
    $pool = Item::examPool()->where('subject_id', $subject->id)->count();

    // Câte nevăzute există, atâtea primește; restul sunt declarate.
    expect($fresh->count())->toBe(min($second->total_items, $pool - $seen->count()))
        ->and($second->config['reused'])->toBe($second->total_items - $fresh->count());
});

it('arată rezultatul și istoricul doar elevului care a dat simularea', function () {
    $session = app(StartMock::class)($this->user, ($this->subject)('matematica')->id);
    $this->actingAs($this->user)->post(route('mock.submit', $session->uuid));

    $this->actingAs($this->user)->get(route('subject.show', 'matematica'))
        ->assertInertia(fn (AssertableInertia $p) => $p
            ->where('sections.mock.available', true)
            ->has('sections.mock.history', 1)
            ->where('sections.mock.history.0.uuid', $session->uuid));

    $altul = User::factory()->create();
    $this->actingAs($altul)->get(route('practice.play', $session->uuid))->assertForbidden();
    $this->actingAs($altul)->post(route('mock.submit', $session->uuid))->assertForbidden();
});

it('nu închide o sesiune de antrenament ca pe o simulare', function () {
    $mate = ($this->subject)('matematica');
    $this->actingAs($this->user)->post(route('practice.start'), ['subject' => $mate->slug]);
    $practice = PracticeSession::where('user_id', $this->user->id)->latest('id')->first();

    $this->actingAs($this->user)->post(route('mock.submit', $practice->uuid))->assertNotFound();

    // Și simularea nu se mai poate porni prin ruta de antrenament.
    $this->actingAs($this->user)
        ->post(route('practice.start'), ['subject' => $mate->slug, 'mode' => 'mock'])
        ->assertSessionHasErrors('mode');
});

it('pornește simularea din interfață și redirecționează spre prima întrebare', function () {
    $this->actingAs($this->user)
        ->post(route('mock.start'), ['subject' => 'matematica'])
        ->assertRedirect();

    $session = PracticeSession::where('user_id', $this->user->id)->where('mode', SessionMode::Mock)->firstOrFail();

    $this->actingAs($this->user)
        ->post(route('mock.start'), ['subject' => 'limba-romana'])
        ->assertSessionHas('error');

    expect($session->sessionItems)->not->toBeEmpty();
});

it('pornește simularea și după multe răspunsuri date repede la antrenament', function () {
    $this->actingAs($this->user)->post(route('practice.start'), ['subject' => 'matematica']);
    $practice = PracticeSession::where('user_id', $this->user->id)->latest('id')->first();

    foreach (range(1, 12) as $_) {
        $this->actingAs($this->user)->post(route('practice.answer', $practice->uuid), []);
    }

    $this->actingAs($this->user)
        ->post(route('mock.start'), ['subject' => 'matematica'])
        ->assertStatus(302)
        ->assertSessionMissing('error');
});

it('pornește oricâte simulări la rând, la fiecare disciplină', function (string $slug) {
    // Prima versiune a selecției completa cu itemi deja văzuți fără să țină
    // cont de tipurile cerute de secțiunile următoare: a doua simulare de
    // informatică nu mai pornea, deși banca avea destui itemi. Selecția e
    // aleatoare, deci repetăm destul cât eșecul să nu depindă de noroc.
    $subject = ($this->subject)($slug);

    foreach (range(1, 8) as $_) {
        app(StartMock::class)($this->user, $subject->id);
    }

    expect(PracticeSession::where('user_id', $this->user->id)->where('mode', SessionMode::Mock)->count())->toBe(8);
})->with(['matematica', 'informatica', 'limba-engleza']);

it('simulează matematica și la profilul umanist, cu structura lui', function () {
    // Testul ANCE de la umanist are alte capitole decât cel de la real
    // („Funcții" în loc de „Analiză matematică"), dar banca rezervată e comună.
    $umanist = User::factory()->create(['profile' => ExamProfile::Umanist]);
    $math = ($this->subject)('matematica');
    $umanist->subjects()->attach($math->id, ['is_primary' => true, 'sort_order' => 0, 'added_at' => now()]);

    foreach (range(1, 4) as $_) {
        $session = app(StartMock::class)($umanist, $math->id);
    }

    expect(collect($session->config['sections'])->pluck('name'))->toContain('Funcții')
        ->and($session->total_items)->toBe(14);
});
