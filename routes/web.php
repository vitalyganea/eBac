<?php

use App\Http\Controllers\Web\Auth\AuthController;
use App\Http\Controllers\Web\Auth\GoogleController;
use App\Http\Controllers\Web\Auth\UsernameController;
use App\Http\Controllers\Web\CourseController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\GapController;
use App\Http\Controllers\Web\MockExamController;
use App\Http\Controllers\Web\PracticeController;
use App\Http\Controllers\Web\Pub\ExamController;
use App\Http\Controllers\Web\Pub\PublicController;
use App\Http\Controllers\Web\Pub\SitemapController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\SubjectController;
use App\Http\Controllers\Web\SubjectHubController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public — indexabil
|--------------------------------------------------------------------------
| Randate cu Blade, fără JavaScript. Rutele cu parametru liber (/{disciplina})
| sunt înregistrate LA FINAL, ca să nu înghită rutele aplicației.
*/

Route::get('/', [PublicController::class, 'home'])->name('public.home');
Route::get('/sitemap.xml', SitemapController::class)->name('public.sitemap');

Route::get('/bac-{year}', [ExamController::class, 'index'])
    ->where('year', '\d{4}')
    ->name('public.exam');

/*
| Fiecare `throttle:N,M` are un prefix propriu (al treilea parametru). Fără el,
| Laravel numără TOATE rutele limitate pe aceeași cheie — utilizatorul sau IP-ul.
| Zece răspunsuri date repede la antrenament blocau astfel „Începe simularea"
| (10/min) cu 429, iar încercările de login și de înregistrare se adunau.
*/

/*
|--------------------------------------------------------------------------
| Autentificare
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/intra', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/inregistrare', [AuthController::class, 'showRegister'])->name('register');

    // Autentificarea cu Google. Limitată ca rată la fel ca formularul: redirectul
    // e ieftin, dar revenirea face două cereri HTTP către Google.
    Route::middleware('throttle:10,1,google')->group(function () {
        Route::get('/intra/google', [GoogleController::class, 'redirect'])->name('google.redirect');
        Route::get('/intra/google/revenire', [GoogleController::class, 'callback'])->name('google.callback');
    });

    // Limitare de rată pe autentificare: 5 încercări/minut per IP.
    Route::middleware('throttle:5,1,autentificare')->group(function () {
        Route::post('/intra', [AuthController::class, 'login']);
        Route::post('/inregistrare', [AuthController::class, 'register']);
    });
});

/*
|--------------------------------------------------------------------------
| Aplicația — NU se indexează
|--------------------------------------------------------------------------
| Pagini personale, cu sesiuni identificate prin UUID. Vezi și public/robots.txt.
*/

Route::middleware(['auth', 'username.ok'])->group(function () {
    Route::post('/iesi', [AuthController::class, 'logout'])->name('logout');

    // Raportarea conținutului public al altui utilizator.
    Route::post('/raportare', [ReportController::class, 'store'])
        ->middleware('throttle:10,60,raportare')
        ->name('report.store');

    Route::get('/panou', DashboardController::class)->name('dashboard');

    // „Ce nu am înțeles" — istoric privat, de dus la profesor.
    Route::get('/ce-nu-am-inteles', [GapController::class, 'index'])->name('gaps.index');
    Route::post('/ce-nu-am-inteles', [GapController::class, 'store'])
        ->middleware('throttle:60,1,nelamuriri')
        ->name('gaps.store');
    Route::put('/ce-nu-am-inteles/{gap}', [GapController::class, 'update'])->name('gaps.update');
    Route::delete('/ce-nu-am-inteles/{gap}', [GapController::class, 'destroy'])->name('gaps.destroy');

    // Alegerea numelui public, după prima autentificare cu Google. Nu poate veni
    // de la furnizor: ar fi numele real, public, și ar ocoli filtrul (§9, §10g).
    Route::get('/alege-nume', [UsernameController::class, 'edit'])->name('auth.username');
    Route::post('/alege-nume', [UsernameController::class, 'store'])
        ->middleware('throttle:20,1,nume')
        ->name('auth.username.store');

    // Ștergerea contului fără parolă (cont creat prin Google) cere un cod pe
    // email. Limită strictă: e un email trimis la cererea oricui e autentificat.
    Route::post('/setari/cont/cod', [SettingsController::class, 'sendDeletionCode'])
        ->middleware('throttle:deletion-code')
        ->name('settings.destroy.code');

    Route::get('/setari', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/setari', [SettingsController::class, 'update'])->name('settings.update');
    // Încărcarea imaginii de profil: limitată ca rată, pentru că fiecare cerere
    // decodează și reencodează o imagine.
    Route::post('/setari/avatar', [SettingsController::class, 'updateAvatar'])
        ->middleware('throttle:10,1,avatar')
        ->name('settings.avatar');
    Route::delete('/setari/avatar', [SettingsController::class, 'destroyAvatar'])
        ->name('settings.avatar.destroy');

    Route::put('/setari/parola', [SettingsController::class, 'updatePassword'])
        ->middleware('throttle:6,1,parola')
        ->name('settings.password');
    // Ștergerea contului: ireversibilă, deci cere parola și e limitată ca rată.
    Route::delete('/setari/cont', [SettingsController::class, 'destroy'])
        ->middleware('throttle:account-deletion')
        ->name('settings.destroy');

    Route::get('/disciplinele-mele', [SubjectController::class, 'edit'])->name('subjects.edit');
    Route::put('/disciplinele-mele', [SubjectController::class, 'update'])->name('subjects.update');

    // Hub-ul unei discipline: Curs / Teste / Clasament.
    Route::get('/disciplina/{slug}', SubjectHubController::class)->name('subject.show');

    // Curs: teoria pe competențe.
    Route::get('/disciplina/{slug}/curs', [CourseController::class, 'index'])->name('course.index');
    Route::get('/disciplina/{slug}/curs/{skill}', [CourseController::class, 'show'])->name('course.show');
    Route::post('/disciplina/{slug}/curs/{skill}/parcurs', [CourseController::class, 'complete'])
        ->name('course.complete');

    // Simularea BAC: structura examenului, itemi de holdout, termen pe server.
    Route::post('/simulare', [MockExamController::class, 'start'])
        ->middleware('throttle:10,1,simulare')
        ->name('mock.start');
    Route::post('/antrenament/{session:uuid}/preda', [MockExamController::class, 'submit'])
        ->name('mock.submit');

    Route::post('/antrenament', [PracticeController::class, 'start'])->name('practice.start');
    Route::get('/antrenament/{session:uuid}', [PracticeController::class, 'play'])->name('practice.play');

    // Indiciile trec prin server ca penalizarea de XP să fie reală.
    Route::post('/antrenament/{session:uuid}/indiciu', [PracticeController::class, 'hint'])
        ->middleware('throttle:60,1,indiciu')
        ->name('practice.hint');

    // 30 de răspunsuri pe minut: un script care trimite 1000 nu e elev.
    Route::post('/antrenament/{session:uuid}/raspuns', [PracticeController::class, 'answer'])
        ->middleware('throttle:30,1,raspuns')
        ->name('practice.answer');
});

/*
|--------------------------------------------------------------------------
| Public, cu parametru liber — OBLIGATORIU ultimele
|--------------------------------------------------------------------------
| URL-uri scurte și în română, pentru că așa se caută: /matematica/derivate,
| nu /discipline/matematica/competente/derivate.
*/

Route::get('/{subject}', [PublicController::class, 'subject'])
    ->where('subject', '[a-z][a-z0-9-]{2,60}')
    ->name('public.subject');

Route::get('/{subject}/{skill}', [PublicController::class, 'skill'])
    ->where(['subject' => '[a-z][a-z0-9-]{2,60}', 'skill' => '[a-z][a-z0-9-]{2,60}'])
    ->name('public.skill');
