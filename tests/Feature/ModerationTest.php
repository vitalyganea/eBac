<?php

use App\Domain\Leaderboards\Leaderboard;
use App\Domain\Moderation\ReportContent;
use App\Domain\Moderation\UsernamePolicy;
use App\Models\ContentReport;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->policy = app(UsernamePolicy::class);

    $this->makeImage = function (): UploadedFile {
        $img = imagecreatetruecolor(300, 300);
        imagefilledrectangle($img, 0, 0, 300, 300, imagecolorallocate($img, 20, 80, 180));
        $path = tempnam(sys_get_temp_dir(), 'av').'.jpg';
        imagejpeg($img, $path, 88);
        imagedestroy($img);

        return new UploadedFile($path, 'a.jpg', 'image/jpeg', null, true);
    };
});

// ==================== FILTRUL DE NUME ====================

it('permite numele legitime, inclusiv cu diacritice', function () {
    $legitime = ['vitalie', 'Ștefan_cel_Mare', 'maria2027', 'ion-creanga', 'model',
        'modest', 'testarescu', 'Constanta', 'puia', 'AnaMaria', 'elev_real'];

    foreach ($legitime as $nume) {
        expect($this->policy->allows($nume))->toBeTrue("Respins pe nedrept: $nume");
    }
});

/**
 * Fără normalizare, filtrul se ocolește în câteva secunde.
 */
it('vede prin leetspeak, separatoare și majuscule', function () {
    foreach (['adm1n', '@dmin', 'a-d-m-i-n', 'A_D_M_I_N', 'MoDeRaToR', '0ficial'] as $nume) {
        expect($this->policy->allows($nume))->toBeFalse("Scăpat: $nume");
    }
});

it('prinde impersonarea prin compunere', function () {
    // Compararea exactă ar rata toate variantele astea.
    foreach (['ebac_oficial', 'admin_ebac', 'suport-ebac', 'EBACmd'] as $nume) {
        expect($this->policy->allows($nume))->toBeFalse("Scăpat: $nume");
    }
});

/**
 * Cifra 1 poate sta și pentru „i", și pentru „l" — o singură normalizare nu le
 * acoperă pe amândouă.
 */
it('verifică ambele citiri ale caracterelor ambigue', function () {
    expect($this->policy->allows('pu1a'))->toBeFalse()
        // Dar cuvântul scris cu „i" e un nume legitim și trebuie să treacă.
        ->and($this->policy->allows('puia'))->toBeTrue();
});

it('respinge datele de contact în numele public', function () {
    // Elevii sunt minori; un clasament public nu e loc pentru numere de telefon.
    foreach (['ion069123456', 'scrie_pe_insta', 'test@mail.md', 'www_ceva_md'] as $nume) {
        expect($this->policy->allows($nume))->toBeFalse("Scăpat: $nume");
    }
});

it('aplică filtrul la înscriere', function () {
    $this->post(route('register'), [
        'name' => 'Cineva',
        'username' => 'ebac_oficial',
        'email' => 'x@example.md',
        'password' => 'parola-sigura-123',
        'password_confirmation' => 'parola-sigura-123',
    ])->assertSessionHasErrors('username');

    expect(User::where('username', 'ebac_oficial')->exists())->toBeFalse();
});

it('aplică filtrul și la schimbarea numelui din setări', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('settings.update'), [
        'username' => 'adm1n',
        'timezone' => 'Europe/Chisinau',
        'leaderboard_opt_in' => true,
    ])->assertSessionHasErrors('username');
});

// ==================== RAPORTARE ====================

it('înregistrează o semnalare', function () {
    $raportor = User::factory()->create();
    $tinta = User::factory()->create();
    $this->actingAs($tinta)->post(route('settings.avatar'), ['avatar' => ($this->makeImage)()]);

    $this->actingAs($raportor)->post(route('report.store'), [
        'username' => $tinta->username,
        'subject' => 'avatar',
        'reason' => 'ofensator',
    ])->assertRedirect();

    expect(ContentReport::open()->count())->toBe(1);
});

it('nu permite raportarea propriului conținut', function () {
    $user = User::factory()->create();

    expect(fn () => app(ReportContent::class)($user, $user, 'username', 'spam'))
        ->toThrow(RuntimeException::class);
});

/**
 * Altfel un singur cont ar putea, prin repetare, să ascundă avatarul oricui.
 */
it('numără oameni distincți, nu semnalări repetate', function () {
    $raportor = User::factory()->create();
    $tinta = User::factory()->create();
    $this->actingAs($tinta)->post(route('settings.avatar'), ['avatar' => ($this->makeImage)()]);

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($raportor)->post(route('report.store'), [
            'username' => $tinta->username, 'subject' => 'avatar', 'reason' => 'ofensator',
        ]);
    }

    expect(ContentReport::open()->count())->toBe(1)
        ->and($tinta->refresh()->avatar_hidden_at)->toBeNull();
});

it('ascunde avatarul după trei semnalări independente', function () {
    $tinta = User::factory()->create();
    $this->actingAs($tinta)->post(route('settings.avatar'), ['avatar' => ($this->makeImage)()]);

    // Imaginea trece prin moderare la încărcare; o aprobăm ca să testăm ce se
    // întâmplă DUPĂ ce a devenit publică.
    $tinta->refresh()->forceFill(['avatar_status' => 'approved'])->save();
    $tinta->refresh();

    expect($tinta->avatarUrl())->not->toBeNull();

    foreach (range(1, 3) as $i) {
        $this->actingAs(User::factory()->create())->post(route('report.store'), [
            'username' => $tinta->username, 'subject' => 'avatar', 'reason' => 'ofensator',
        ]);
    }

    $tinta->refresh();

    // Ascunderea e o măsură de siguranță temporară, nu un verdict: fișierul
    // rămâne pe disc, decizia o ia un om.
    expect($tinta->avatar_hidden_at)->not->toBeNull()
        ->and($tinta->avatarUrl())->toBeNull()
        ->and($tinta->avatar_path)->not->toBeNull();

    Storage::disk('public')->assertExists("{$tinta->avatar_path}-256.webp");
});

// ==================== REDENUMIRE FORȚATĂ ====================

it('blochează navigarea până la alegerea altui nume', function () {
    $user = User::factory()->create();
    $user->forceFill(['username_reset_required' => true])->save();

    // Panoul redirecționează…
    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('settings.edit'));

    // …dar setările și deconectarea rămân accesibile, altfel n-ar avea cum să
    // rezolve situația.
    $this->actingAs($user)->get(route('settings.edit'))->assertOk();
});

it('deblochează contul după alegerea unui nume acceptabil', function () {
    $user = User::factory()->create();
    $user->forceFill(['username_reset_required' => true])->save();

    $this->actingAs($user)->put(route('settings.update'), [
        'username' => 'nume_nou_ok',
        'timezone' => 'Europe/Chisinau',
        'leaderboard_opt_in' => true,
    ])->assertRedirect();

    // Contul și progresul rămân intacte — nu pierdem un elev pentru un nume.
    expect($user->refresh()->username)->toBe('nume_nou_ok')
        ->and($user->username_reset_required)->toBeFalse();
});

// ==================== PRE-MODERARE ====================

/**
 * Cerința de fond: elevul folosește platforma imediat, dar nimic ales de el nu
 * ajunge în fața altora înainte să vadă un om.
 */
it('la înscriere, numele ales rămâne privat până la aprobare', function () {
    $this->post(route('register'), [
        'name' => 'Ion Popescu',
        'username' => 'ion_tare',
        'email' => 'ion@example.md',
        'password' => 'parola-sigura-123',
        'password_confirmation' => 'parola-sigura-123',
    ])->assertRedirect();

    $user = User::where('username', 'ion_tare')->firstOrFail();

    expect($user->username_status)->toBe('pending')
        ->and($user->public_alias)->toStartWith('elev_')
        // Ceilalți văd pseudonimul…
        ->and($user->publicName())->toBe($user->public_alias)
        ->and($user->publicName())->not->toBe('ion_tare');
});

it('navigarea nu e blocată de statusul în așteptare', function () {
    $user = User::factory()->pending()->create();

    // Spre deosebire de numele RESPINS, care blochează până la redenumire.
    $this->actingAs($user)->get(route('settings.edit'))->assertOk();
    $this->actingAs($user)->get(route('subjects.edit'))->assertOk();
});

it('imaginea încărcată e vizibilă doar proprietarului până la aprobare', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('settings.avatar'), ['avatar' => ($this->makeImage)()]);
    $user->refresh();

    expect($user->avatar_status)->toBe('pending')
        ->and($user->avatar_path)->not->toBeNull()
        // Ceilalți nu o văd…
        ->and($user->avatarUrl())->toBeNull();

    // …dar în setări proprietarul o vede, altfel n-ar ști ce a încărcat.
    $this->actingAs($user)->get(route('settings.edit'))
        ->assertInertia(fn ($page) => $page
            ->where('settings.avatar_pending', true)
            ->whereNot('settings.avatar_url', null)
        );
});

it('emoji-ul nu trece prin moderare', function () {
    // Vine dintr-o listă fixă de 12 — nu are ce modera, iar majoritatea
    // utilizatorilor nu observă nicio întârziere.
    $user = User::factory()->create(['avatar' => '🦊']);

    expect($user->avatar_status)->toBe('approved')
        ->and($user->publicName())->toBe($user->username);
});

it('aprobarea face numele și imaginea publice', function () {
    $user = User::factory()->pending()->create(['username' => 'nume_bun']);
    $this->actingAs($user)->post(route('settings.avatar'), ['avatar' => ($this->makeImage)()]);

    $user->refresh()->forceFill([
        'username_status' => 'approved',
        'avatar_status' => 'approved',
        'moderated_at' => now(),
    ])->save();

    expect($user->publicName())->toBe('nume_bun')
        ->and($user->avatarUrl())->toContain('.webp');
});

it('schimbarea numelui îl retrimite la moderare', function () {
    $user = User::factory()->create(['username' => 'nume_vechi']);

    expect($user->publicName())->toBe('nume_vechi');

    $this->actingAs($user)->put(route('settings.update'), [
        'username' => 'nume_nou',
        'timezone' => 'Europe/Chisinau',
        'leaderboard_opt_in' => true,
    ])->assertRedirect();

    $user->refresh();

    // Un nume aprobat nu e un permis pentru orice urmează.
    expect($user->username)->toBe('nume_nou')
        ->and($user->username_status)->toBe('pending')
        ->and($user->publicName())->toBe($user->public_alias);
});

it('înlocuirea imaginii o retrimite la moderare', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('settings.avatar'), ['avatar' => ($this->makeImage)()]);
    $user->refresh()->forceFill(['avatar_status' => 'approved'])->save();

    expect($user->refresh()->avatarUrl())->not->toBeNull();

    $this->actingAs($user)->post(route('settings.avatar'), ['avatar' => ($this->makeImage)()]);

    expect($user->refresh()->avatar_status)->toBe('pending')
        ->and($user->avatarUrl())->toBeNull();
});

it('clasamentul arată pseudonimul pentru conturile neaprobate', function () {
    $this->seed(SubjectSeeder::class);
    $mate = Subject::where('slug', 'matematica')->firstOrFail();

    $asteptare = User::factory()->pending()->create(['username' => 'nume_neverificat']);
    $aprobat = User::factory()->create(['username' => 'nume_verificat']);

    $lb = app(Leaderboard::class);
    $lb->addXp($asteptare, 200, $mate->id);
    $lb->addXp($aprobat, 100, $mate->id);

    $board = $lb->subjectBoard($aprobat, $mate->id);
    $nume = collect($board['entries'])->pluck('username');

    expect($nume)->toContain('nume_verificat')
        ->and($nume)->not->toContain('nume_neverificat')
        ->and($nume)->toContain($asteptare->public_alias);
});

it('semnalările readuc o imagine aprobată în coada de moderare', function () {
    $tinta = User::factory()->create();
    $this->actingAs($tinta)->post(route('settings.avatar'), ['avatar' => ($this->makeImage)()]);
    $tinta->refresh()->forceFill(['avatar_status' => 'approved'])->save();

    foreach (range(1, 3) as $i) {
        $this->actingAs(User::factory()->create())->post(route('report.store'), [
            'username' => $tinta->username, 'subject' => 'avatar', 'reason' => 'ofensator',
        ]);
    }

    $tinta->refresh();

    // Ajunge în aceeași coadă, la aceeași persoană, cu aceleași acțiuni.
    expect($tinta->avatar_status)->toBe('pending')
        ->and($tinta->avatar_hidden_at)->not->toBeNull()
        ->and($tinta->avatarUrl())->toBeNull()
        // Fișierul rămâne: decizia e reversibilă.
        ->and($tinta->avatar_path)->not->toBeNull();
});
