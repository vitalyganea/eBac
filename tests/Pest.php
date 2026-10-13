<?php

use Illuminate\Support\Facades\Redis;
use Livewire\Livewire;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Configurarea testelor
|--------------------------------------------------------------------------
|
| Testele de tip Feature au nevoie de aplicația Laravel bootstrap-ată.
| Cele din Unit rămân pure — corectarea (GradingTest) nu atinge baza de date
| și trebuie să rămână așa: e cea mai rapidă plasă de siguranță pe care o avem.
*/

pest()->extend(TestCase::class)
    ->beforeEach(function () {
        // RefreshDatabase golește baza, dar NU Redis. Clasamentele rămâneau din
        // testul anterior și falsificau pozițiile — o categorie de eșecuri care
        // apar doar când rulezi toată suita, nu un fișier singur.
        Redis::flushdb();

        // Livewire ține în STATIC faptul că a randat o componentă, iar la
        // sfârșitul oricărui răspuns HTML 200 își injectează singur JS-ul.
        // Un test care deschide /admin (Filament e pe Livewire) lăsa steagul
        // ridicat, iar paginile publice de după primeau `livewire.js` — exact ce
        // interzice §14. În producție nu se întâmplă: PHP-FPM pornește proces
        // nou, iar Octane rulează PrepareLivewireForNextOperation între cereri.
        // Aici facem același lucru, ca ordinea testelor să nu decidă rezultatul.
        Livewire::flushState();
    })
    ->in('Feature');
