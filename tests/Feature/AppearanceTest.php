<?php

use App\Models\Subject;
use App\Models\User;
use App\Support\Appearance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Vite;

uses(RefreshDatabase::class);

/**
 * Tema și mărimea textului.
 *
 * Preferințele stau în cookie tocmai ca să poată fi citite pe SERVER: paginile
 * publice n-au voie să conțină JavaScript (§14), deci localStorage nu e o opțiune
 * pentru ele. Testele de aici verifică drumul complet — cookie → atribut în
 * `<html>` → regulă CSS — pe ambele zone ale aplicației.
 */
function appearanceFile(string $path): string
{
    return file_get_contents(base_path($path));
}

it('aplică tema pe paginile publice, fără JavaScript', function () {
    $this->seed();

    // Ca în SeoTest: în dezvoltare, Vite își injectează legitim clientul de
    // hot-reload, iar aserțiunea de mai jos ar pica fără niciun motiv real.
    Vite::useHotFile(base_path('tests/.no-hot'));

    $html = $this->withUnencryptedCookie(Appearance::THEME_COOKIE, 'light')
        ->get(route('public.home'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('data-theme="light"');

    // Motivul întregii construcții: pagina rămâne fără niciun script executabil.
    expect($html)->not->toMatch('/<script(?![^>]*type="application\/ld\+json")/');
});

it('aplică tema și mărimea și în aplicație', function () {
    $this->seed();
    $user = User::factory()->create();
    $user->subjects()->attach(Subject::where('slug', 'matematica')->value('id'), [
        'is_primary' => true, 'sort_order' => 0, 'added_at' => now(),
    ]);

    $html = $this->actingAs($user)
        ->withUnencryptedCookies([
            Appearance::THEME_COOKIE => 'light',
            Appearance::FONT_COOKIE => 'lg',
        ])
        ->get(route('dashboard'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('data-theme="light"')
        ->and($html)->toContain('data-font="lg"')
        ->and($html)->toContain('content="#f8fafc"');
});

it('rămâne pe tema întunecată fără cookie', function () {
    $this->seed();

    $html = $this->get(route('public.home'))->getContent();

    // Comportamentul actual nu se schimbă pentru cine nu intră în Setări.
    expect($html)->toContain('data-theme="dark"')
        ->and($html)->not->toContain('data-font=');
});

it('nu scrie în atribut o valoare venită din cookie fără să o verifice', function () {
    $this->seed();

    $html = $this->withUnencryptedCookie(Appearance::THEME_COOKIE, '" onload="alert(1)')
        ->get(route('public.home'))
        ->getContent();

    // Cookie-ul e text arbitrar, controlat de client, și ajunge direct într-un
    // atribut. Lista albă din Appearance e singura barieră.
    expect($html)->toContain('data-theme="dark"')
        ->and($html)->not->toContain('onload=');
});

it('lasă „system" să fie rezolvat de CSS, nu de server', function () {
    $this->seed();

    $html = $this->withUnencryptedCookie(Appearance::THEME_COOKIE, 'system')
        ->get(route('public.home'))
        ->getContent();

    // Serverul nu are de unde ști setarea sistemului de operare. Dacă ar încerca
    // să ghicească, ar nimeri greșit pentru jumătate din vizitatori.
    expect($html)->toContain('data-theme="system"');

    expect(appearanceFile('resources/css/app.css'))
        ->toContain('@media (prefers-color-scheme: light)');
});

it('folosește aceleași nume de cookie în PHP și în TypeScript', function () {
    $composable = appearanceFile('resources/js/composables/useAppearance.ts');

    foreach ([Appearance::THEME_COOKIE, Appearance::FONT_COOKIE] as $cookie) {
        $this->assertStringContainsString(
            "'{$cookie}'", $composable,
            "useAppearance.ts nu mai scrie cookie-ul „{$cookie}”, pe care îl citește App\\Support\\Appearance."
        );
    }

    // Fără excepția de la criptare, Laravel n-ar putea citi un cookie scris din
    // browser, iar tema s-ar reseta la fiecare încărcare.
    $this->assertStringContainsString(
        'encryptCookies', appearanceFile('bootstrap/app.php'),
        'Cookie-urile de aspect trebuie exceptate de la criptare.'
    );
});

it('are o regulă CSS pentru fiecare mărime oferită în interfață', function () {
    $css = appearanceFile('resources/css/app.css');
    $composable = appearanceFile('resources/js/composables/useAppearance.ts');

    preg_match_all("/\{ value: '(sm|md|lg|xl)'/", $composable, $m);
    expect($m[1])->not->toBeEmpty();

    foreach (array_diff($m[1], ['md']) as $marime) {
        $this->assertStringContainsString(
            "html[data-font='{$marime}']", $css,
            "Mărimea „{$marime}” e oferită în Setări, dar nu are regulă în app.css."
        );
    }

    $this->assertStringNotContainsString(
        "html[data-font='md']", $css,
        'Mărimea normală nu trebuie să aibă regulă: atributul lipsește cu totul.'
    );
});

/**
 * Culorile care nu au voie să se inverseze odată cu tema.
 *
 * Tema luminoasă redefinește variabilele Tailwind, deci `text-white` devine
 * închis și `text-slate-950` devine deschis. Pe un fundal de accent — buton
 * albastru, insignă chihlimbarie — asta produce text invizibil. Cele două cazuri
 * au tokenuri proprii, `text-on-accent` și `text-on-warning`.
 */
it('nu pune text care se inversează peste un fundal care nu se inversează', function () {
    $probleme = [];

    $fisiere = new AppendIterator;
    foreach ([resource_path('js'), resource_path('views')] as $dir) {
        $fisiere->append(new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        ));
    }

    foreach ($fisiere as $file) {
        if (! in_array($file->getExtension(), ['vue', 'php'], true)) {
            continue;
        }

        foreach (file($file->getPathname()) as $numar => $linie) {
            // Suprafețe pe care tema NU le schimbă: accent SOLID (fără alfa —
            // cu alfa e o tentă peste fundalul temei, care se inversează) și
            // negrul, cu sau fără alfa.
            if (! preg_match('/bg-(brand|rose|amber|emerald)-[456]00(?![\/\[])|bg-black\b/', $linie)) {
                continue;
            }

            // Orice nuanță neutră se inversează. Variantele cu alfa
            // (`text-white/[0.06]`) sunt excluse: acolo inversarea e dorită.
            if (preg_match('/\btext-(white|slate-\d+)\b(?!\/)/', $linie)) {
                $probleme[] = str_replace(base_path().'/', '', $file->getPathname()).':'.($numar + 1);
            }
        }
    }

    expect($probleme)->toBeEmpty(
        "Folosește `text-on-accent`, `text-on-warning` sau `text-on-dark`:\n  ".implode("\n  ", $probleme)
    );
});
