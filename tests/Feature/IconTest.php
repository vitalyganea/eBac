<?php

use App\Models\Achievement;
use App\Models\Subject;
use App\Support\Icons;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

/**
 * Poarta care ține setul de iconițe și utilizările lui sincronizate.
 *
 * Un nume greșit de iconiță NU dă eroare: componentul nu randează nimic, iar
 * rezultatul e o casetă goală pe care nimeni nu o observă până o vede un elev.
 * De asta verificarea e automată, ca la conținut (vezi `ContentIntegrityTest`).
 */

/**
 * Toate fișierele de interfață, cu cale relativă la `resources/`.
 *
 * `glob` nu e recursiv în PHP — `**` se comportă ca un singur nivel, deci ar
 * rata tăcut exact fișierele adăugate mai târziu, în subdirectoare noi.
 *
 * @return array<string, string> cale relativă => cale absolută
 */
function viewSourceFiles(): array
{
    $out = [];

    foreach ([resource_path('js'), resource_path('views')] as $dir) {
        foreach (File::allFiles($dir) as $file) {
            if (in_array($file->getExtension(), ['vue', 'php'], true)) {
                $out[str_replace(resource_path().'/', '', $file->getPathname())] = $file->getPathname();
            }
        }
    }

    return $out;
}

/** Numele folosite ca literal în `<Icon name="...">` și `<x-ebac-icon name="...">`. */
function iconNamesUsedInViews(): array
{
    $files = viewSourceFiles();

    $used = [];

    foreach ($files as $relative => $file) {
        $source = file_get_contents($file);

        // Doar numele scrise ca literal. Cele dinamice (`:name="subject.icon"`)
        // vin din baza de date și se verifică separat, mai jos.
        preg_match_all(
            '/<(?:Icon|x-ebac-icon)\b[^>]*?\bname="([a-z0-9-]+)"/s',
            $source,
            $matches,
        );

        foreach ($matches[1] as $name) {
            $used[$name][] = $relative;
        }
    }

    return $used;
}

it('are un set de iconițe generat și nevid', function () {
    expect(Icons::names())->not->toBeEmpty();

    foreach (Icons::all() as $name => $body) {
        expect($name)->toMatch('/^[a-z0-9-]+$/');
        expect($body)->toMatch('/^<(path|circle|line|polyline|polygon|rect|ellipse)\b/');
    }
});

it('folosește în interfață doar iconițe care există în set', function () {
    $used = iconNamesUsedInViews();

    expect($used)->not->toBeEmpty('Nu am găsit nicio utilizare de <Icon> — regexul s-a rupt?');

    foreach ($used as $name => $files) {
        expect(Icons::has($name))->toBeTrue(
            "Iconița „{$name}” nu există în resources/icons/icons.json. "
            .'Folosită în: '.implode(', ', array_unique($files)).'. '
            .'Adaug-o în lista din bin/icons.mjs și rulează `node bin/icons.mjs`.'
        );
    }
});

it('are o iconiță validă pentru fiecare disciplină', function () {
    $this->seed();

    foreach (Subject::all() as $subject) {
        expect($subject->icon)->not->toBeNull("Disciplina „{$subject->slug}” nu are iconiță.");
        expect(Icons::has($subject->icon))->toBeTrue(
            "Disciplina „{$subject->slug}” are iconița „{$subject->icon}”, care nu există în set."
        );
    }
});

it('are o iconiță validă pentru fiecare realizare', function () {
    $this->seed();

    foreach (Achievement::all() as $achievement) {
        expect($achievement->icon)->not->toBeNull("Realizarea „{$achievement->slug}” nu are iconiță.");
        expect(Icons::has($achievement->icon))->toBeTrue(
            "Realizarea „{$achievement->slug}” are iconița „{$achievement->icon}”, care nu există în set."
        );
    }
});

/**
 * Emoji-urile nu se strecoară înapoi în interfață.
 *
 * Motivul nu e estetic: emoji-urile de steag apar pe Windows ca două litere, iar
 * desenul și lățimea celorlalte variază de la un Android la altul — publicul
 * platformei. În plus, nu se pot colora în tema aplicației.
 *
 * Avatarurile sunt EXCEPTATE deliberat (vezi invariante §10f/§10g): lista fixă
 * de 12 emoji e opțiunea implicită tocmai pentru că nu ocupă spațiu, nu cere
 * moderare și e disponibilă instant.
 */
it('nu mai are emoji în interfață, în afara avatarurilor', function () {
    $exempt = [
        'js/components/Avatar.vue',
        'js/pages/Settings/Edit.vue',
    ];

    $files = viewSourceFiles();

    // Pictograme și simboluri, emoticoane, transport, indicatori regionali
    // (steagurile) și simbolurile diverse. Deliberat NU include punctuația
    // tipografică — „—”, „…”, „→” și „·” sunt text, nu iconițe.
    $emoji = '/[\x{1F300}-\x{1FAFF}\x{1F600}-\x{1F64F}\x{1F680}-\x{1F6FF}\x{1F1E6}-\x{1F1FF}\x{2600}-\x{26FF}\x{2705}\x{274C}\x{2611}\x{2610}\x{2691}]/u';

    $offenders = [];

    foreach ($files as $relative => $file) {
        if (in_array($relative, $exempt, true)) {
            continue;
        }

        if (preg_match_all($emoji, file_get_contents($file), $found)) {
            $offenders[$relative] = implode(' ', array_unique($found[0]));
        }
    }

    expect($offenders)->toBe([], 'Emoji găsite în interfață: '.json_encode($offenders, JSON_UNESCAPED_UNICODE)
        .'. Folosește <Icon> / <x-ebac-icon> — vezi resources/icons/icons.json.');
});
