<?php

use Illuminate\Support\Facades\Route;

/**
 * Numele de rute nu se verifică singure.
 *
 * `route('x')` și `to_route('x')` se rezolvă abia la execuție: un nume greșit
 * trece de teste, de Pint și de PHPStan, apoi dă RouteNotFoundException în fața
 * elevului. Așa a scăpat `to_route('home')` în `AuthController::logout()` —
 * ruta publică se numește `public.home` (§14), iar butonul „ieși" deconecta
 * utilizatorul și abia apoi arăta o pagină de eroare.
 *
 * Verificarea e statică, pe toate fișierele care pot apela un nume de rută.
 */

/** Numele de rută citate literal, cu locul lor. @return array<string, list<string>> */
function routeReferences(string $dir, array $extensions): array
{
    $found = [];

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($files as $file) {
        if (! in_array(strtolower($file->getExtension()), $extensions, true)) {
            continue;
        }

        foreach (file($file->getPathname()) as $number => $line) {
            // `$request->route('id')` întoarce un PARAMETRU, nu o rută — dacă
            // apare vreodată, nu are ce căuta în verificarea asta.
            if (preg_match('/(\$\w+|\$this)->route\(/', $line)) {
                continue;
            }

            if (! preg_match_all(
                "/\b(?:to_route|route|signedRoute|temporarySignedRoute)\(\s*'([a-zA-Z0-9_.\-]+)'/",
                $line, $matches
            )) {
                continue;
            }

            $where = str_replace(base_path().'/', '', $file->getPathname()).':'.($number + 1);

            foreach ($matches[1] as $name) {
                $found[$name][] = $where;
            }
        }
    }

    return $found;
}

it('nu apelează niciun nume de rută inexistent', function () {
    $registered = collect(Route::getRoutes()->getRoutesByName())->keys();

    $referenced = array_merge_recursive(
        routeReferences(app_path(), ['php']),
        routeReferences(base_path('routes'), ['php']),
        routeReferences(resource_path('views'), ['php']),
        routeReferences(resource_path('js'), ['vue', 'ts', 'js']),
    );

    expect($referenced)->not->toBeEmpty();

    $lipsa = [];

    foreach ($referenced as $name => $locations) {
        if (! $registered->contains($name)) {
            $lipsa[] = "  '{$name}' — ".implode(', ', array_unique((array) $locations));
        }
    }

    expect($lipsa)->toBeEmpty(
        "Nume de rută care nu există:\n".implode("\n", $lipsa)
    );
});

it('expune prin Ziggy exact rutele apelate din Vue', function () {
    $only = config('ziggy.only');
    $fromVue = array_keys(routeReferences(resource_path('js'), ['vue', 'ts', 'js']));

    // Lipsă în `only` => `route()` aruncă în browser, unde nu se vede în logurile
    // serverului. Prezentă degeaba => greutate în HTML-ul FIECĂREI pagini (§14).
    expect(array_diff($fromVue, $only))->toBeEmpty('Rute folosite în Vue, dar neexpuse în config/ziggy.php')
        ->and(array_diff($only, $fromVue))->toBeEmpty('Rute expuse în config/ziggy.php, dar nefolosite în Vue');
});

it('nu expune rute administrative prin Ziggy', function () {
    // Lista albă e o decizie de securitate, nu o optimizare: /admin, /horizon,
    // /pulse și ruta PUT de upload nu au ce căuta în sursa paginii.
    foreach (config('ziggy.only') as $name) {
        expect($name)->not->toStartWith('filament.')
            ->and($name)->not->toStartWith('horizon.')
            ->and($name)->not->toStartWith('storage.')
            ->and($name)->not->toBe('pulse');
    }
});

/**
 * Ieșirea din zona Inertia spre zona publică Blade cere `Inertia::location()`.
 *
 * Un `to_route('public.…')` obișnuit întoarce 302. Cererea a plecat prin XHR,
 * deci XHR-ul urmează redirectul și primește o pagină Blade fără anteturi
 * Inertia — clientul nu o poate monta și deschide modalul de eroare, un
 * `<iframe srcdoc>` cu origine `null` în care până și `@vite` pică pe CORS.
 *
 * 409 cu X-Inertia-Location e singurul mod de a-i spune clientului să facă o
 * navigare reală a browserului. Cele două granițe sunt deconectarea și ștergerea
 * contului; a treia va apărea fără ca cineva să se gândească la asta.
 */
it('nu redirectează din aplicație spre zona publică fără Inertia::location', function () {
    $controllers = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(app_path('Http/Controllers/Web'), FilesystemIterator::SKIP_DOTS)
    );

    $vinovate = [];

    foreach ($controllers as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        // `Web\Pub\*` SUNT paginile Blade: acolo redirectul obișnuit e corect.
        if (str_contains($file->getPathname(), '/Pub/')) {
            continue;
        }

        // Instrucțiunea se poate întinde pe mai multe linii, deci normalizăm
        // spațiile înainte de a căuta — altfel `Inertia::location(` de pe rândul
        // de deasupra ar scăpa verificării.
        $cod = preg_replace('/\s+/', ' ', file_get_contents($file->getPathname()));

        if (preg_match("/return (?:to_route|redirect\(\)->route)\(\s*'public\./", $cod)) {
            $vinovate[] = str_replace(base_path().'/', '', $file->getPathname());
        }
    }

    expect($vinovate)->toBeEmpty(
        "Redirect simplu spre zona publică (folosește Inertia::location):\n  ".implode("\n  ", $vinovate)
    );
});

/**
 * Aceeași graniță, cealaltă jumătate: `<Link>` spre o rută publică.
 *
 * Testul de mai sus păzește serverul, dar clientul poate porni singur XHR-ul.
 * `<Link>` din Inertia NU e un `<a>`: interceptează clicul și cere pagina prin
 * XHR. Dacă ținta e zona publică Blade (§14), răspunsul vine fără anteturi
 * Inertia și se deschide același modal de eroare — `<iframe sandbox>` cu origine
 * `null`, al cărui `@vite/client` e respins de CORS. Simptomul nu seamănă deloc
 * cu cauza, ceea ce l-a ținut ascuns: sigla din antet era `<Link>` spre
 * `public.home` pentru vizitator, deci pica la fiecare click pe siglă din
 * paginile de autentificare.
 *
 * Traversarea graniței se face cu `<a>` obișnuit — o navigare reală a
 * browserului. `<component :is="user ? Link : \'a\'">` rămâne permis: acolo
 * `Link` e folosit exclusiv pentru ruta din aplicație.
 */
it('nu leagă cu <Link> nicio rută din zona publică', function () {
    $fisiere = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('js'), FilesystemIterator::SKIP_DOTS)
    );

    $vinovate = [];

    foreach ($fisiere as $file) {
        if ($file->getExtension() !== 'vue') {
            continue;
        }

        $cale = str_replace(base_path().'/', '', $file->getPathname());

        // Atributele se întind pe mai multe rânduri, deci normalizăm spațiile
        // înainte de a decupa eticheta de deschidere.
        $cod = preg_replace('/\s+/', ' ', file_get_contents($file->getPathname()));

        preg_match_all('/<Link\b[^>]*>/', $cod, $linkuri);

        foreach ($linkuri[0] as $tag) {
            if (str_contains($tag, "route('public.")) {
                $vinovate[] = $cale.' — '.trim($tag);
            }
        }

        // Navigarea programatică are exact aceeași problemă.
        if (preg_match("/router\.(?:visit|get|post|reload)\(\s*route\(\s*'public\./", $cod)) {
            $vinovate[] = $cale.' — router.* spre o rută publică';
        }
    }

    expect($vinovate)->toBeEmpty(
        "Zona publică e Blade: leagă cu <a>, nu cu <Link> (§14):\n  ".implode("\n  ", $vinovate)
    );
});

/**
 * `back()` fără rezervă cade pe `/` — pagina publică Blade.
 *
 * Browserul trimite de obicei `Referer`, deci nu se vede la clic; dispare cu
 * `Referrer-Policy: no-referrer`, într-o sesiune proaspătă sau la un retry după
 * expirare. Atunci XHR-ul Inertia primește Blade și deschide modalul de eroare —
 * aceeași capcană ca la deconectare, doar mai rară.
 */
it('nu lasă niciun back() din aplicație fără rezervă', function () {
    $fisiere = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(app_path('Http/Controllers/Web'), FilesystemIterator::SKIP_DOTS)
    );

    $vinovate = [];

    foreach ($fisiere as $file) {
        if ($file->getExtension() !== 'php' || str_contains($file->getPathname(), '/Pub/')) {
            continue;
        }

        foreach (file($file->getPathname()) as $numar => $linie) {
            if (preg_match('/\bback\(\s*\)/', $linie)) {
                $vinovate[] = str_replace(base_path().'/', '', $file->getPathname()).':'.($numar + 1);
            }
        }
    }

    expect($vinovate)->toBeEmpty(
        "`back()` fără `fallback:` (ar cădea pe pagina publică):\n  ".implode("\n  ", $vinovate)
    );
});
