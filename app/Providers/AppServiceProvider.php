<?php

namespace App\Providers;

use App\Enums\UserRole;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Parametrii de rută care ajung într-o coloană bigint. Postgres e strict
        // cu tipurile: `where id = 'x'` nu întoarce zero rânduri, ci aruncă
        // eroare — un URL modificat de mână dădea 500 în loc de 404.
        //
        // Stă în register(), nu în boot(): tiparele globale se aplică rutelor
        // create DUPĂ ele, iar Filament își înregistrează rutele (`{record}`,
        // `{export}`, `{import}`) în propriul provider.
        Route::pattern('gap', '[0-9]+');
        Route::pattern('record', '[0-9]+');
        Route::pattern('export', '[0-9]+');
        Route::pattern('import', '[0-9]+');
    }

    public function boot(): void
    {
        $this->configureModels();
        $this->configureDevServer();
        $this->configureUrls();
        $this->configureAuthorization();
        $this->configureRateLimiters();
    }

    /**
     * Limitatoare NUMITE pentru ștergerea contului.
     *
     * `throttle:3,60` scris direct pe rută pare per-rută, dar nu e: pentru un
     * utilizator autentificat, Laravel construiește cheia doar din id-ul lui
     * (ThrottleRequests::resolveRequestSignature). Toate rutele cu `throttle`
     * împart astfel un singur contor, iar cea mai strictă dintre ele se consumă
     * din activitate care n-are legătură.
     *
     * Aici asta conta concret: un elev care a exersat câteva minute ar fi găsit
     * ștergerea contului blocată o oră — o acțiune pe care Legea nr. 133/2011 o
     * face obligatorie (§9). Un limitator numit intră în cheie cu numele lui,
     * deci are găleata lui.
     */
    private function configureRateLimiters(): void
    {
        $perUser = fn (Request $request) => $request->user()?->getAuthIdentifier() ?: $request->ip();

        // Confirmarea ștergerii. Generoasă: greșeala de tastare la un cod de
        // șase cifre e frecventă, iar codul expiră oricum în 15 minute.
        RateLimiter::for('account-deletion', fn (Request $request) => Limit::perHour(10)->by($perUser($request)));

        // Trimiterea codului: aici fiecare încercare costă un email.
        RateLimiter::for('deletion-code', fn (Request $request) => Limit::perMinutes(10, 3)->by($perUser($request)));
    }

    /**
     * Cine vede tablourile de bord operaționale.
     *
     * Implicit, Pulse se deschide doar în mediul local, deci în producție era
     * inaccesibil oricui — inclusiv administratorului. Îl aliniem la rolul
     * existent, ca `/admin` (Filament) și `/horizon`.
     *
     * Doar Admin, nu `canAccessAdmin()`: editorii validează itemi. Pulse arată
     * interogări lente cu tot cu parametri și utilizatorii cei mai activi — date
     * despre minori care n-au de ce să treacă pe lângă rolul editorial (§9).
     */
    private function configureAuthorization(): void
    {
        Gate::define('viewPulse', fn ($user = null) => $user?->role === UserRole::Admin);
    }

    private function configureModels(): void
    {
        // Prinde N+1 în dezvoltare, nu în producție. Paginile de profil și
        // clasament sunt exact locul unde N+1 apare primul.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Un `fill()` cu un câmp care nu e în #[Fillable] trebuie să explodeze
        // local, nu să fie ignorat în silence — altfel descoperi luni mai târziu
        // că un update „a funcționat" fără să scrie nimic.
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }

    /**
     * În dezvoltare rulăm pe `artisan serve`, nu pe Octane.
     *
     * Octane (instalat) își înregistrează propria comandă de dev
     * (`octane:start --watch`, vezi Octane::registerDevCommands()), care implicit
     * caută RoadRunner. Dar mai important: sub Octane workerii persistă între
     * request-uri, iar state-ul static supraviețuiește — exact genul de
     * comportament pe care nu-l vrei cât timp scrii cod.
     *
     * Octane rămâne pentru PRODUCȚIE:
     *   php artisan octane:install --server=frankenphp
     *   php artisan octane:start --workers=8
     */
    private function configureDevServer(): void
    {
        if ($this->app->isProduction() || ! class_exists(DevCommands::class)) {
            return;
        }

        DevCommands::artisan('serve', 'server');
    }

    private function configureUrls(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
