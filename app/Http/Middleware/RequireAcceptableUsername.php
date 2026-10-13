<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blochează navigarea dacă un moderator a respins numele public.
 *
 * Nu ștergem contul și nu pierdem progresul pentru un nume nepotrivit — cerem
 * doar alegerea altuia. Elevul rămâne cu tot ce a învățat.
 */
class RequireAcceptableUsername
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        // Cont creat prin Google: are email și sesiune, dar încă niciun nume
        // public. Nu poate apărea în clasamente și nu poate fi raportat până nu
        // alege unul, deci navigarea se oprește aici.
        if (blank($user->username)) {
            $permiseFaraNume = ['auth.username', 'auth.username.store', 'logout'];

            return in_array($request->route()?->getName(), $permiseFaraNume, true)
                ? $next($request)
                : redirect()->route('auth.username');
        }

        if (! $user->username_reset_required) {
            return $next($request);
        }

        // Setările și deconectarea rămân accesibile — altfel nu ar avea cum să
        // rezolve situația.
        $permise = ['settings.edit', 'settings.update', 'logout'];

        if (in_array($request->route()?->getName(), $permise, true)) {
            return $next($request);
        }

        return redirect()->route('settings.edit')
            ->with('error', 'Numele tău public a fost respins la moderare. Alege altul pentru a continua.');
    }
}
