<?php

use App\Http\Controllers\Api\V1\AttemptController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — pentru viitoarele aplicații iOS / Android
|--------------------------------------------------------------------------
|
| Prefixul /v1 există de la primul endpoint, nu „când o să avem nevoie".
| Motivul: pe mobil nu poți forța update-ul. Versiunea de acum 8 luni rulează
| încă pe telefonul cuiva, deci în v1 nu ștergem și nu redenumim niciodată un
| câmp — doar adăugăm. Schimbările incompatibile merg în v2, iar clienții prea
| vechi primesc 426 din EnsureMinimumAppVersion.
|
| Momentan expunem strict minimul care demonstrează arhitectura. Restul (sync în
| lot, manifest de conținut delta, înregistrare de device pentru push) se adaugă
| când chiar construim aplicația — pe același strat de domeniu, deci fără să
| rescriem nimic.
*/

Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', fn (Request $request) => [
            'id' => $request->user()->id,
            'username' => $request->user()->publicName(),
            'xp' => $request->user()->xp,
            'level' => $request->user()->level,
            'rating' => $request->user()->rating,
            'streak_days' => $request->user()->streak_days,
        ])->name('api.v1.me');

        // Același SubmitAnswer pe care îl apelează și clientul web.
        Route::post('/attempts', [AttemptController::class, 'store'])
            ->middleware('throttle:60,1,api-raspuns')
            ->name('api.v1.attempts.store');
    });
});
