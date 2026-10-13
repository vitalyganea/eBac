<?php

namespace App\Providers;

use App\Enums\UserRole;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            // Lista de emailuri din schela Laravel rămâne goală, deci în afara
            // mediului local NIMENI nu putea deschide Horizon — nici
            // administratorul. Coada e infrastructură (§4): dacă mastery-ul sau
            // recalibrarea Elo se blochează în producție, cineva trebuie să
            // poată vedea de ce.
            //
            // Doar Admin, nu `canAccessAdmin()`: editorii validează itemi, n-au
            // treabă cu joburile eșuate și cu payload-urile lor.
            return $user?->role === UserRole::Admin;
        });
    }
}
