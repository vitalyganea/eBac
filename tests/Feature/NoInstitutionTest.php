<?php

use App\Domain\Leaderboards\Leaderboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * Platforma nu cere apartenența la o instituție (§9).
 *
 * Decizia e veche și deliberată: tabelul `schools` a existat și a fost șters
 * printr-o migrație proprie. Motivul nu e tehnic — platforma poate servi
 * candidați care repetă examenul sau care învață pe cont propriu, iar datele de
 * școală cerute unor minori fără o utilizare clară sunt un cost fără beneficiu.
 *
 * Testul există pentru că ștergerea schemei NU a șters și promisiunea din
 * interfață: pagina de Setări a continuat să anunțe „clasamentele pe liceu"
 * mult după ce ele deveniseră imposibile. Structura și textul trebuie verificate
 * împreună, altfel al doilea rămâne în urmă fără ca nimeni să observe.
 */
it('nu are structură pentru instituții', function () {
    expect(Schema::hasTable('schools'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'school_id'))->toBeFalse();
});

it('nu promite în interfață clasamente pe care nu le poate construi', function () {
    $probleme = [];

    $fisiere = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('js'), FilesystemIterator::SKIP_DOTS)
    );

    foreach ($fisiere as $file) {
        if ($file->getExtension() !== 'vue') {
            continue;
        }

        foreach (file($file->getPathname()) as $numar => $linie) {
            // Un clasament legat de o instituție nu se poate construi fără date
            // pe care am decis să nu le cerem.
            if (preg_match('/\b(liceu|liceel|liceele|școal|scoal|clasă|clase)\w*\b/iu', $linie)
                && preg_match('/clasament/iu', $linie)) {
                $probleme[] = str_replace(base_path().'/', '', $file->getPathname()).':'.($numar + 1);
            }
        }
    }

    expect($probleme)->toBeEmpty(
        'Interfața promite un clasament pe instituție, dar §9 spune că nu cerem '
        ."apartenența la una:\n  ".implode("\n  ", $probleme)
    );
});

/**
 * Clasamentele care chiar ajung la elev sunt cele PE DISCIPLINĂ (§10b).
 *
 * `Leaderboard` a expus și `globalRank()`, `topGlobal()`, `topBySubject()` și
 * `setSubjectRating()` — toate fără niciun apelant, iar `addXp` scria în patru
 * sorted sets din care se citea unul singur. Au fost șterse.
 *
 * Testul rămâne ca declanșator: dacă reapare un clasament global, textul din
 * Setări — care descrie doar clasamentul pe disciplină — trebuie actualizat
 * odată cu el.
 */
it('nu are niciun clasament global', function () {
    $metode = get_class_methods(Leaderboard::class);

    foreach ($metode as $metoda) {
        expect($metoda)->not->toMatch('/global/i');
    }
});

/**
 * Fiecare răspuns trece prin `addXp` pe calea SINCRONĂ (§4), deci fiecare
 * scriere în plus se plătește la fiecare item rezolvat de fiecare elev.
 */
it('scrie o singură cheie Redis la înregistrarea XP-ului', function () {
    $sursa = file_get_contents(base_path('app/Domain/Leaderboards/Leaderboard.php'));

    preg_match('/public function addXp\(.*?\n    \}/s', $sursa, $m);
    expect($m)->not->toBeEmpty('Nu am găsit metoda addXp — s-a redenumit?');

    expect(substr_count($m[0], 'Redis::'))->toBe(
        1,
        'addXp scrie în mai multe chei. Verifică dacă toate sunt și citite undeva — '
        .'`global`, `daily` și `weekly` au fost scrise luni fără ca nimeni să le deschidă.'
    );
});
