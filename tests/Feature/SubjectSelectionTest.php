<?php

use App\Domain\Subjects\SubjectCatalog;
use App\Domain\Subjects\UpdateUserSubjects;
use App\Models\Mastery;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->user = User::factory()->create(['profile' => 'real']);
});

it('panoul trimite la selecție când elevul nu are discipline', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertRedirect(route('subjects.edit'));
});

/**
 * Cerința de fond: profilul NU e o poartă de acces.
 *
 * Bacalaureatul e din mai multe probe, iar un elev de la real trebuie să poată
 * exersa istorie fără să-și schimbe profilul. Profilul adaugă doar o etichetă.
 */
it('elevul vede TOATE disciplinele, indiferent de profil', function () {
    $catalog = app(SubjectCatalog::class)->for($this->user);

    expect($catalog)->toHaveCount(Subject::count())
        ->and($catalog->pluck('slug'))->toContain('matematica', 'istoria', 'chimia', 'limba-engleza');

    // Eticheta de rol există, dar e informativă.
    $matematica = $catalog->firstWhere('slug', 'matematica');
    expect($matematica['role'])->toBe('profil');
});

it('poate alege o disciplină din afara profilului său', function () {
    $istoria = Subject::where('slug', 'istoria')->firstOrFail();

    // Profil real, disciplină de profil umanist — trebuie să fie permis.
    $this->actingAs($this->user)
        ->put(route('subjects.update'), ['subject_ids' => [$istoria->id]])
        ->assertRedirect(route('dashboard'));

    expect($this->user->refresh()->subjects->pluck('slug'))->toContain('istoria');
});

it('poate alege mai multe discipline și le reordonează', function () {
    $ids = Subject::whereIn('slug', ['matematica', 'limba-romana', 'istoria'])
        ->orderByRaw("array_position(array['limba-romana','matematica','istoria']::text[], slug)")
        ->pluck('id')->all();

    $this->actingAs($this->user)->put(route('subjects.update'), [
        'subject_ids' => $ids,
        'primary_id' => $ids[1],
    ]);

    $subjects = $this->user->refresh()->subjects;

    expect($subjects)->toHaveCount(3)
        // Ordinea trimisă se păstrează.
        ->and($subjects->pluck('id')->all())->toBe($ids)
        ->and($subjects->firstWhere('pivot.is_primary', true)->id)->toBe($ids[1]);
});

it('marchează onboarded_at abia la prima selecție', function () {
    $this->user->forceFill(['onboarded_at' => null])->save();

    $this->actingAs($this->user)->put(route('subjects.update'), [
        'subject_ids' => [Subject::where('slug', 'matematica')->value('id')],
    ]);

    expect($this->user->refresh()->onboarded_at)->not->toBeNull();
});

it('deselectarea nu șterge progresul', function () {
    $matematica = Subject::where('slug', 'matematica')->firstOrFail();
    $update = app(UpdateUserSubjects::class);

    $update($this->user, [$matematica->id]);

    // Simulăm progres: un rând de mastery pe o competență de matematică.
    $skillId = $matematica->skills()->where('is_leaf', true)->value('id');
    Mastery::create([
        'user_id' => $this->user->id, 'skill_id' => $skillId, 'mastery' => 0.7, 'attempts_count' => 10,
    ]);

    // Renunță la matematică...
    $update($this->user->refresh(), []);
    expect($this->user->refresh()->subjects)->toHaveCount(0);

    // ...dar progresul rămâne, deci se poate întoarce oricând.
    $this->assertDatabaseHas('mastery', ['user_id' => $this->user->id, 'skill_id' => $skillId]);
});

it('raportează cinstit care disciplină are conținut', function () {
    $catalog = app(SubjectCatalog::class)->for($this->user);

    expect($catalog->firstWhere('slug', 'matematica')['is_playable'])->toBeTrue()
        ->and($catalog->firstWhere('slug', 'matematica')['items_count'])->toBeGreaterThan(0)
        // Disciplinele fără itemi sunt selectabile, dar marcate ca fiind în pregătire.
        ->and($catalog->firstWhere('slug', 'chimia')['is_playable'])->toBeFalse()
        ->and($catalog->firstWhere('slug', 'chimia')['items_count'])->toBe(0);
});
