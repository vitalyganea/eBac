<?php

use App\Models\Subject;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed());

/**
 * Regresie: props-urile partajate Inertia citesc `$user->role->value`.
 *
 * Înainte de defaults-urile din `User::$attributes`, `register()` crea un
 * utilizator fără `role` în memorie (coloana avea doar DEFAULT în schemă), iar
 * primul request de după înregistrare returna 500.
 */
it('înregistrarea duce la alegerea disciplinelor, fără eroare', function () {
    $response = $this->post(route('register'), [
        'name' => 'Ion Popescu',
        'username' => 'ion_p',
        'email' => 'ion@example.md',
        'password' => 'parola-sigura-123',
        'password_confirmation' => 'parola-sigura-123',
        'profile' => 'real',
    ]);

    // Fără discipline alese nu are ce exersa, deci primul pas e selecția —
    // nu panoul.
    $response->assertRedirect(route('subjects.edit'));

    $user = User::where('username', 'ion_p')->firstOrFail();
    expect($user->role->value)->toBe('student')
        ->and($user->exam_target)->toBe('bac-2027')
        ->and($user->rating)->toBe(1000)
        // Participarea la clasamente e activă implicit: un clasament la care nu
        // participă nimeni nu e un clasament. Protecția care contează rămâne —
        // public apare doar `username`, niciodată numele real.
        ->and($user->leaderboard_opt_in)->toBeTrue()
        // `onboarded_at` se setează abia când chiar alege disciplinele.
        ->and($user->onboarded_at)->toBeNull();

    $this->actingAs($user)->get(route('subjects.edit'))->assertOk();
});

it('profilul este opțional la înregistrare', function () {
    // Profilul e doar ghidaj pentru sugestii. Un elev care nu-l știe trebuie să
    // se poată înregistra oricum.
    $this->post(route('register'), [
        'name' => 'Fără Profil',
        'username' => 'fara_profil',
        'email' => 'fp@example.md',
        'password' => 'parola-sigura-123',
        'password_confirmation' => 'parola-sigura-123',
    ])->assertRedirect(route('subjects.edit'));

    expect(User::where('username', 'fara_profil')->firstOrFail()->profile)->toBeNull();
});

it('numele real nu apare niciodată în props-urile publice', function () {
    $user = User::factory()->create(['name' => 'Nume Real Complet', 'username' => 'pseudonim']);
    $user->subjects()->attach(Subject::where('slug', 'matematica')->value('id'), [
        'is_primary' => true, 'sort_order' => 0, 'added_at' => now(),
    ]);

    $content = $this->actingAs($user)->get(route('dashboard'))->getContent();

    expect($content)->toContain('pseudonim')
        ->and($content)->not->toContain('Nume Real Complet')
        ->and($content)->not->toContain($user->email);
});

it('nu permite escaladarea rolului prin formularul de înregistrare', function () {
    $this->post(route('register'), [
        'name' => 'Atacator',
        'username' => 'atacator',
        'email' => 'atac@example.md',
        'password' => 'parola-sigura-123',
        'password_confirmation' => 'parola-sigura-123',
        'profile' => 'real',
        // Injectat în payload — `role` nu e în #[Fillable], deci trebuie ignorat.
        'role' => 'admin',
    ]);

    $user = User::where('username', 'atacator')->firstOrFail();

    expect($user->role->value)->toBe('student')
        ->and($user->canAccessPanel(app(Panel::class)))->toBeFalse();
});

/**
 * Regresie: `logout()` redirecta spre `to_route('home')`, dar ruta publică se
 * numește `public.home` (§14). Numele greșit nu se vede la înregistrarea
 * rutelor — doar la apăsarea butonului, ca RouteNotFoundException.
 *
 * Perfid pentru că `Auth::logout()` rulează ÎNAINTE de redirect: elevul era
 * deconectat cu adevărat, dar primea o pagină de eroare în loc de pagina de
 * pornire, deci arăta ca o deconectare eșuată.
 */
it('deconectarea duce la pagina publică, nu la o eroare', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('public.home'));
    $this->assertGuest();

    $this->get(route('public.home'))->assertOk();
});

/**
 * O cerere Inertia trebuie să primească un răspuns Inertia.
 *
 * `/iesi` e apăsat dintr-o pagină Inertia, deci pleacă prin XHR. Un 302 spre `/`
 * e urmat de XHR, care primește pagina publică Blade (§14: două produse, două
 * tehnologii) — fără anteturi Inertia. Clientul nu știe ce să facă cu ea și
 * deschide modalul de eroare, un `<iframe srcdoc>` cu origine `null`. De acolo
 * vin în consolă „CORS ... /@vite/client" și eroarea din web-vitals: sunt
 * simptome ale modalului, nu cauze.
 *
 * `Inertia::location()` întoarce 409 cu X-Inertia-Location, iar clientul face o
 * navigare reală a browserului — singurul mod de a ieși din SPA spre Blade.
 */
it('deconectarea cere clientului o navigare reală, nu un răspuns Inertia', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'), [], ['X-Inertia' => 'true']);

    $response->assertStatus(409)
        ->assertHeader('X-Inertia-Location', route('public.home'));

    $this->assertGuest();
});

it('ștergerea contului cere la fel o navigare reală', function () {
    $user = User::factory()->create(['password' => Hash::make('parola-sigura-123')]);

    $response = $this->actingAs($user)->delete(route('settings.destroy'), [
        'password' => 'parola-sigura-123',
    ], ['X-Inertia' => 'true']);

    $response->assertStatus(409)
        ->assertHeader('X-Inertia-Location', route('public.home'));

    $this->assertGuest();
    expect(User::find($user->id))->toBeNull();
});
