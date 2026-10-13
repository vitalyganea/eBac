<?php

use App\Http\Middleware\EnsureMinimumAppVersion;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequireAcceptableUsername;
use App\Support\Appearance;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Livewire\Exceptions\ComponentNotFoundException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Tema și mărimea textului se scriu din browser, deci nu pot fi criptate
        // de Laravel — iar paginile publice trebuie să le citească fără nicio
        // linie de JavaScript (§14). Nu conțin nimic de protejat: două cuvinte
        // dintr-o listă albă, validate în App\Support\Appearance.
        $middleware->encryptCookies(except: [
            Appearance::THEME_COOKIE,
            Appearance::FONT_COOKIE,
        ]);

        // API-ul folosește tokenuri Sanctum, nu sesiuni. Gate-ul de versiune stă
        // în față, ca un client mobil vechi să primească 426 înainte de orice logică.
        $middleware->alias([
            'username.ok' => RequireAcceptableUsername::class,
        ]);

        $middleware->api(prepend: [
            EnsureMinimumAppVersion::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Livewire servește JS/CSS per componentă la `/livewire-*/js/{component}.js`.
        // Un nume inexistent în URL arunca o excepție netratată: 500 și o
        // intrare în log pentru orice URL inventat. E o resursă care nu există,
        // deci 404.
        $exceptions->render(fn (ComponentNotFoundException $e) => abort(404));
        $exceptions->dontReport(ComponentNotFoundException::class);
    })->create();
