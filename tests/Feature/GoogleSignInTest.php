<?php

use App\Domain\Auth\GoogleOAuth;
use App\Mail\DeletionCodeMail;
use App\Models\SocialAccount;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    config([
        'services.google.client_id' => 'test-client',
        'services.google.client_secret' => 'test-secret',
        'services.google.redirect' => 'http://localhost:8000/intra/google/revenire',
    ]);

    // Identitatea pe care o „întoarce" Google. Stub-ul se înregistrează O
    // SINGURĂ DATĂ: `Http::fake()` ADAUGĂ stub-uri, nu le înlocuiește, iar primul
    // care se potrivește câștigă — un al doilea apel ar fi ignorat în tăcere.
    $this->googleIdentity = ['sub' => 'google-1', 'email' => 'elev@example.md', 'email_verified' => true];

    Http::fake([
        'oauth2.googleapis.com/token' => Http::response(['access_token' => 'tok']),
        'openidconnect.googleapis.com/v1/userinfo' => fn () => Http::response($this->googleIdentity),
    ]);

    $this->googleReturns = function (string $sub, string $email, bool $verified = true): void {
        $this->googleIdentity = ['sub' => $sub, 'email' => $email, 'email_verified' => $verified];
    };

    /** Parcurge redirectul ca să obțină un `state` valid în sesiune. */
    $this->callbackWithState = function (string $code = 'cod-valid') {
        $this->get(route('google.redirect'))->assertRedirectContains('accounts.google.com');

        return $this->get(route('google.callback', [
            'code' => $code,
            'state' => session(GoogleOAuth::STATE_KEY),
        ]));
    };
});

it('trimite elevul la Google cu parametrii ceruți', function () {
    $response = $this->get(route('google.redirect'));

    $url = $response->headers->get('Location');

    expect($url)->toStartWith('https://accounts.google.com/o/oauth2/v2/auth')
        ->and($url)->toContain('client_id=test-client')
        ->and($url)->toContain('response_type=code')
        // Strict minimul: numele real nu ne trebuie și nu-l cerem (§9).
        ->and($url)->toContain('scope=openid+email')
        ->and($url)->not->toContain('profile')
        // Fără asta, un elev logat pe telefonul familiei ar intra tăcut cu alt cont.
        ->and($url)->toContain('prompt=select_account');

    expect(session(GoogleOAuth::STATE_KEY))->not->toBeEmpty();
});

/**
 * `state` e singura protecție împotriva unui flux pornit de altcineva: fără el,
 * un atacator trimite victimei un link de revenire cu propriul cod, iar contul
 * victimei ajunge legat de identitatea atacatorului.
 */
it('refuză revenirea fără state valid', function () {
    ($this->googleReturns)('google-1', 'elev@example.md');

    // Fără state deloc.
    $this->get(route('google.callback', ['code' => 'x']))
        ->assertRedirect(route('login'));
    $this->assertGuest();

    // Cu state greșit, deși sesiunea are unul.
    $this->get(route('google.redirect'));
    $this->get(route('google.callback', ['code' => 'x', 'state' => 'altceva']))
        ->assertRedirect(route('login'));
    $this->assertGuest();

    // Nu s-a atins niciodată Google: cererea a fost oprită înainte.
    Http::assertNothingSent();
});

it('creează un cont nou și cere numele public', function () {
    ($this->googleReturns)('google-1', 'elev.nou@example.md');

    ($this->callbackWithState)()->assertRedirect(route('auth.username'));

    $user = User::where('email', 'elev.nou@example.md')->firstOrFail();

    expect($user->username)->toBeNull()
        ->and($user->password)->toBeNull()
        // Google a confirmat adresa; un al doilea email de verificare n-ar avea rost.
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->public_alias)->not->toBeNull();

    $this->assertAuthenticatedAs($user);
});

/**
 * Regula din §10g: numele se filtrează în TOATE locurile unde poate fi setat.
 * Înscrierea prin Google e al treilea loc, după formular și Setări.
 */
it('trece numele ales prin filtru și prin coada de moderare', function () {
    ($this->googleReturns)('google-1', 'elev.nou@example.md');
    ($this->callbackWithState)();

    $this->post(route('auth.username.store'), ['username' => 'admin'])
        ->assertSessionHasErrors('username');

    $this->post(route('auth.username.store'), ['username' => 'elev_bun_2027'])
        ->assertRedirect(route('subjects.edit'));

    $user = User::where('email', 'elev.nou@example.md')->firstOrFail();

    expect($user->username)->toBe('elev_bun_2027')
        ->and($user->username_status)->toBe('pending')
        // Ceilalți îl văd sub pseudonim până la aprobare.
        ->and($user->publicName())->toBe($user->public_alias);
});

it('blochează navigarea până la alegerea numelui', function () {
    ($this->googleReturns)('google-1', 'elev.nou@example.md');
    ($this->callbackWithState)();

    $this->get(route('dashboard'))->assertRedirect(route('auth.username'));
    $this->get(route('gaps.index'))->assertRedirect(route('auth.username'));

    // Ieșirea și ecranul propriu-zis rămân accesibile.
    $this->get(route('auth.username'))->assertOk();
    $this->post(route('logout'))->assertStatus(302);
});

it('recunoaște contul la a doua autentificare', function () {
    ($this->googleReturns)('google-1', 'elev@example.md');
    ($this->callbackWithState)();

    $user = User::where('email', 'elev@example.md')->firstOrFail();
    $this->post(route('auth.username.store'), ['username' => 'elev_x']);
    $this->post(route('logout'));

    ($this->callbackWithState)();

    $this->assertAuthenticatedAs($user->fresh());
    expect(User::count())->toBe(1)
        ->and(SocialAccount::count())->toBe(1);
});

it('leagă un cont existent doar dacă Google a confirmat emailul', function () {
    $existent = User::factory()->create([
        'email' => 'deja@example.md',
        'password' => Hash::make('parola-sigura-123'),
    ]);

    // Email NEconfirmat: legarea ar însemna preluarea contului de către oricine
    // își poate declara adresa altcuiva la un furnizor permisiv.
    ($this->googleReturns)('google-9', 'deja@example.md', verified: false);
    ($this->callbackWithState)()->assertRedirect(route('login'));

    $this->assertGuest();
    expect(SocialAccount::count())->toBe(0);

    // Confirmat: se leagă de contul existent, fără să creeze altul.
    ($this->googleReturns)('google-9', 'deja@example.md', verified: true);
    ($this->callbackWithState)();

    $this->assertAuthenticatedAs($existent->fresh());
    expect(User::count())->toBe(1)
        ->and(SocialAccount::where('user_id', $existent->id)->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Contul fără parolă
|--------------------------------------------------------------------------
*/

it('permite ștergerea contului fără parolă, cu un cod pe email', function () {
    Mail::fake();

    ($this->googleReturns)('google-1', 'elev@example.md');
    ($this->callbackWithState)();
    $this->post(route('auth.username.store'), ['username' => 'elev_y']);

    $user = User::where('email', 'elev@example.md')->firstOrFail();

    // Fără cod, ștergerea nu trece — dar nici nu eșuează tăcut.
    $this->delete(route('settings.destroy'), ['code' => '000000'])
        ->assertSessionHasErrors('code');
    expect(User::find($user->id))->not->toBeNull();

    $this->post(route('settings.destroy.code'))->assertSessionHas('success');
    Mail::assertSent(DeletionCodeMail::class);

    // Codul real e cel din cache; îl reconstruim testând toate variantele ar fi
    // absurd, deci punem un hash cunoscut, ca și cum tocmai ar fi fost trimis.
    Cache::put("account-deletion:{$user->id}", Hash::make('123456'), now()->addMinutes(15));

    // Cerere obișnuită, nu Inertia: acolo `Inertia::location()` întoarce 302.
    $this->delete(route('settings.destroy'), ['code' => '123456'])
        ->assertRedirect(route('public.home'));

    expect(User::find($user->id))->toBeNull();
    $this->assertGuest();
});

it('nu trimite cod de ștergere pentru un cont care are parolă', function () {
    $user = User::factory()->create(['password' => Hash::make('parola-sigura-123')]);

    // Ar fi o a doua cale de atac asupra aceluiași cont, fără niciun câștig.
    $this->actingAs($user)->post(route('settings.destroy.code'))->assertStatus(400);
});

it('lasă un cont fără parolă să-și seteze una', function () {
    ($this->googleReturns)('google-1', 'elev@example.md');
    ($this->callbackWithState)();
    $this->post(route('auth.username.store'), ['username' => 'elev_z']);

    $user = User::where('email', 'elev@example.md')->firstOrFail();
    expect($user->hasPassword())->toBeFalse();

    // Fără asta, cine s-a înscris cu Google ar rămâne legat de el pentru
    // totdeauna — niciun acces dacă pierde contul Google.
    $this->put(route('settings.password'), [
        'password' => 'parola-noua-2027',
        'password_confirmation' => 'parola-noua-2027',
    ])->assertSessionHasNoErrors();

    expect($user->fresh()->hasPassword())->toBeTrue();
});

it('nu lasă un cont cu parolă să o schimbe fără cea veche', function () {
    $user = User::factory()->create(['password' => Hash::make('parola-sigura-123')]);
    $user->subjects()->attach(Subject::where('slug', 'matematica')->value('id'), [
        'is_primary' => true, 'sort_order' => 0, 'added_at' => now(),
    ]);

    $this->actingAs($user)->put(route('settings.password'), [
        'current_password' => 'gresita',
        'password' => 'parola-noua-2027',
        'password_confirmation' => 'parola-noua-2027',
    ])->assertSessionHasErrors('current_password');
});
