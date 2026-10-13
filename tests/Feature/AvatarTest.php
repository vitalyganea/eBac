<?php

use App\Domain\Attempts\PurgeUserData;
use App\Domain\Leaderboards\Leaderboard;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->user = User::factory()->create();

    /** Creează o imagine JPEG reală, cu conținut, la dimensiunea cerută. */
    $this->makeImage = function (int $w, int $h, string $extra = ''): UploadedFile {
        $img = imagecreatetruecolor($w, $h);
        imagefilledrectangle($img, 0, 0, $w, $h, imagecolorallocate($img, 30, 90, 200));

        $path = tempnam(sys_get_temp_dir(), 'av').'.jpg';
        imagejpeg($img, $path, 90);
        imagedestroy($img);

        // Bytes adăugați după sfârșitul imaginii — trucul clasic de fișier
        // „poliglot": imagine validă care transportă și altceva.
        if ($extra !== '') {
            file_put_contents($path, $extra, FILE_APPEND);
        }

        return new UploadedFile($path, 'poza.jpg', 'image/jpeg', null, true);
    };
});

it('încarcă avatarul și generează două dimensiuni', function () {
    $this->actingAs($this->user)
        ->post(route('settings.avatar'), ['avatar' => ($this->makeImage)(600, 600)])
        ->assertRedirect();

    $path = $this->user->refresh()->avatar_path;

    expect($path)->not->toBeNull();

    // O imagine de 256px servită într-o casetă de 24px din antet ar fi risipă
    // pe date mobile — exact publicul nostru.
    Storage::disk('public')->assertExists("{$path}-64.webp");
    Storage::disk('public')->assertExists("{$path}-256.webp");

    // Imaginea intră în moderare, deci nu e încă publică — dar fișierele există.
    expect($this->user->avatar_status)->toBe('pending')
        ->and($this->user->avatarUrl(64))->toBeNull();

    $this->user->forceFill(['avatar_status' => 'approved'])->save();
    expect($this->user->refresh()->avatarUrl(64))->toContain('-64.webp');
});

/**
 * Motivul central pentru care nu stocăm niciodată fișierul primit.
 */
it('elimină metadatele și orice conținut atașat imaginii', function () {
    $secret = 'GPS:47.0105,28.8638 <?php system($_GET["c"]); ?>';

    $this->actingAs($this->user)
        ->post(route('settings.avatar'), ['avatar' => ($this->makeImage)(400, 400, $secret)])
        ->assertRedirect();

    $path = $this->user->refresh()->avatar_path;

    foreach ([64, 256] as $size) {
        $bytes = Storage::disk('public')->get("{$path}-{$size}.webp");

        // Fișierul scris e o imagine NOUĂ, construită din pixeli. Nu poate
        // conține nici coordonate GPS din EXIF, nici cod atașat după imagine.
        expect($bytes)->not->toContain($secret)
            ->and($bytes)->not->toContain('<?php')
            ->and($bytes)->not->toContain('GPS')
            // Ieșirea e WebP, indiferent ce format a fost la intrare.
            ->and(substr($bytes, 8, 4))->toBe('WEBP');
    }
});

it('decupează la pătrat o imagine dreptunghiulară', function () {
    $this->actingAs($this->user)
        ->post(route('settings.avatar'), ['avatar' => ($this->makeImage)(800, 300)])
        ->assertRedirect();

    $path = $this->user->refresh()->avatar_path;
    $bytes = Storage::disk('public')->get("{$path}-256.webp");

    $tmp = tempnam(sys_get_temp_dir(), 'out').'.webp';
    file_put_contents($tmp, $bytes);
    [$w, $h] = getimagesize($tmp);

    expect($w)->toBe(256)->and($h)->toBe(256);
});

it('respinge un fișier care nu este imagine', function () {
    $fake = UploadedFile::fake()->createWithContent('virus.jpg', '<?php echo "nu sunt o imagine"; ?>');

    $this->actingAs($this->user)
        ->post(route('settings.avatar'), ['avatar' => $fake])
        ->assertSessionHasErrors('avatar');

    expect($this->user->refresh()->avatar_path)->toBeNull();
});

it('respinge o imagine prea mică', function () {
    $this->actingAs($this->user)
        ->post(route('settings.avatar'), ['avatar' => ($this->makeImage)(32, 32)])
        ->assertSessionHasErrors('avatar');
});

it('șterge imaginea veche la o încărcare nouă', function () {
    $this->actingAs($this->user)->post(route('settings.avatar'), ['avatar' => ($this->makeImage)(300, 300)]);
    $vechi = $this->user->refresh()->avatar_path;

    $this->actingAs($this->user)->post(route('settings.avatar'), ['avatar' => ($this->makeImage)(300, 300)]);
    $nou = $this->user->refresh()->avatar_path;

    expect($nou)->not->toBe($vechi);

    // Fișierele nu se acumulează la fiecare schimbare de poză.
    Storage::disk('public')->assertMissing("{$vechi}-64.webp");
    Storage::disk('public')->assertMissing("{$vechi}-256.webp");
    Storage::disk('public')->assertExists("{$nou}-256.webp");
});

it('șterge imaginea la cerere și revine la emoji', function () {
    $this->actingAs($this->user)->post(route('settings.avatar'), ['avatar' => ($this->makeImage)(300, 300)]);
    $path = $this->user->refresh()->avatar_path;
    $this->user->forceFill(['avatar_status' => 'approved'])->save();

    $this->actingAs($this->user)->delete(route('settings.avatar.destroy'))->assertRedirect();

    expect($this->user->refresh()->avatar_path)->toBeNull()
        ->and($this->user->avatarUrl())->toBeNull();

    Storage::disk('public')->assertMissing("{$path}-256.webp");
});

it('șterge fișierele odată cu contul', function () {
    $this->actingAs($this->user)->post(route('settings.avatar'), ['avatar' => ($this->makeImage)(300, 300)]);
    $path = $this->user->refresh()->avatar_path;

    // Fișierele nu stau în baza de date, deci nu dispar odată cu rândurile.
    app(PurgeUserData::class)($this->user);

    Storage::disk('public')->assertMissing("{$path}-64.webp");
    Storage::disk('public')->assertMissing("{$path}-256.webp");
});

/**
 * `avatar_path` e o cale pe disc. Dacă ar veni din formular, utilizatorul ar
 * controla ce fișier se afișează — sau ce cale se șterge.
 */
it('nu permite setarea căii avatarului prin formular', function () {
    $this->actingAs($this->user)->put(route('settings.update'), [
        'username' => 'test_user',
        'timezone' => 'Europe/Chisinau',
        'leaderboard_opt_in' => true,
        'avatar_path' => 'avatars/aa/altcineva',
    ]);

    expect($this->user->refresh()->avatar_path)->toBeNull();
});

it('clasamentul poartă URL-ul avatarului, nu doar emoji', function () {
    $this->seed(SubjectSeeder::class);
    $mate = Subject::where('slug', 'matematica')->firstOrFail();

    $this->actingAs($this->user)->post(route('settings.avatar'), ['avatar' => ($this->makeImage)(300, 300)]);
    $this->user->refresh()->forceFill(['avatar_status' => 'approved'])->save();
    $this->user->refresh();

    // Îl punem manual în clasament, ca să apară în listă fără să răspundă la itemi.
    app(Leaderboard::class)->addXp($this->user, 100, $mate->id);

    $board = app(Leaderboard::class)->subjectBoard($this->user, $mate->id);
    $eu = collect($board['entries'])->firstWhere('is_me', true);

    expect($eu)->not->toBeNull()
        ->and($eu['avatar_url'])->toContain('.webp');
});
