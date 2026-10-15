<?php

use App\Domain\Attempts\PurgeUserData;
use App\Enums\ExamProfile;
use App\Models\Formula;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->matematica = Subject::where('slug', 'matematica')->firstOrFail();

    $this->student = User::factory()->create(['profile' => ExamProfile::Real]);
    $this->student->subjects()->attach($this->matematica->id, [
        'is_primary' => true, 'sort_order' => 0, 'added_at' => now(),
    ]);
});

it('apare ca a patra zonă pe pagina disciplinei', function () {
    $this->actingAs($this->student)
        ->get(route('subject.show', 'matematica'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('sections.formulas.available', true)
            ->where('sections.formulas.count', Formula::where('subject_id', $this->matematica->id)->count())
        );
});

it('numără aparițiile în anexă pe profilul elevului', function () {
    $response = $this->actingAs($this->student)->get(route('formulas.index', 'matematica'))->assertOk();
    $props = $response->viewData('page')['props'];

    // 12 teste la real, 11 la umanist (testul de bază din 2022 nu e publicat).
    expect($props['profile'])->toBe('r')
        ->and($props['tests'])->toBe(['r' => 12, 'u' => 11]);

    $binom = collect($props['formulas'])->firstWhere('name', 'Binomul lui Newton');
    expect($binom['annex'])->toBe(['r' => 10, 'u' => 0]);

    // Ce n-a apărut niciodată în anexă e „de știut pe de rost".
    $viete = collect($props['formulas'])->firstWhere('name', 'Relațiile lui Viète');
    expect($viete['annex'])->toBe(['r' => 0, 'u' => 0]);
});

it('salvează ce știe elevul, fără XP și fără răspunsuri', function () {
    $formula = Formula::where('slug', 'viete')->firstOrFail();
    $xp = $this->student->xp;

    $this->actingAs($this->student)
        ->post(route('formulas.progress', $formula), ['status' => 'review'])
        ->assertRedirect();
    $this->actingAs($this->student)
        ->post(route('formulas.progress', $formula), ['status' => 'known'])
        ->assertRedirect();

    expect(DB::table('formula_progress')->where('user_id', $this->student->id)->pluck('status')->all())->toBe(['known'])
        ->and($this->student->fresh()->xp)->toBe($xp)
        ->and(DB::table('attempts')->where('user_id', $this->student->id)->count())->toBe(0);

    $this->actingAs($this->student)
        ->get(route('formulas.index', 'matematica'))
        ->assertInertia(fn ($page) => $page->where(
            'formulas',
            fn ($all) => collect($all)->firstWhere('id', $formula->id)['status'] === 'known',
        ));
});

it('refuză o stare inventată', function () {
    $formula = Formula::firstOrFail();

    $this->actingAs($this->student)
        ->post(route('formulas.progress', $formula), ['status' => 'master'])
        ->assertSessionHasErrors('status');
});

it('nu arată formulele unei discipline nealese', function () {
    $other = User::factory()->create();

    $this->actingAs($other)->get(route('formulas.index', 'matematica'))->assertNotFound();
    $this->actingAs($other)
        ->post(route('formulas.progress', Formula::firstOrFail()), ['status' => 'known'])
        ->assertNotFound();
});

it('șterge bifele odată cu contul', function () {
    DB::table('formula_progress')->insert([
        'user_id' => $this->student->id,
        'formula_id' => Formula::firstOrFail()->id,
        'status' => 'known',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    app(PurgeUserData::class)($this->student);

    expect(DB::table('formula_progress')->count())->toBe(0);
});

it('fiecare formulă trimite la un document ANCE și la o competență', function () {
    $probleme = [];

    foreach (Formula::all() as $f) {
        if (! str_starts_with($f->source, 'ance:')) {
            $probleme[] = "{$f->slug}: sursa „{$f->source}” nu e un document ANCE";
        }
        if (! preg_match('~^https://ance\.gov\.md/sites/default/files/[\w.-]+\.pdf#\S+$~', $f->source_ref)) {
            $probleme[] = "{$f->slug}: source_ref nu duce la un PDF ANCE";
        }
        if ($f->skill_id === null || trim($f->latex) === '' || trim($f->body) === '') {
            $probleme[] = "{$f->slug}: fără competență, formulă sau explicație";
        }
        foreach ($f->annex ?? [] as $code) {
            if (! preg_match('/^[ru]_s[bs]2\d$/', $code)) {
                $probleme[] = "{$f->slug}: cod de test necunoscut „{$code}”";
            }
        }
    }

    expect($probleme)->toBeEmpty(implode("\n", $probleme));
});
