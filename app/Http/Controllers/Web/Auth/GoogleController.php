<?php

namespace App\Http\Controllers\Web\Auth;

use App\Domain\Auth\GoogleOAuth;
use App\Domain\Auth\LinkGoogleAccount;
use App\Domain\Auth\UnverifiedGoogleEmail;
use App\Http\Controllers\Web\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Autentificarea cu Google.
 *
 * Controllerul nu conține logică (§3): construiește URL-ul, verifică `state`,
 * apoi predă domeniului. Decizia „leg sau creez" e în LinkGoogleAccount, ca să
 * poată fi testată fără HTTP.
 */
class GoogleController extends Controller
{
    public function redirect(Request $request, GoogleOAuth $oauth): RedirectResponse
    {
        if (! $oauth->configured()) {
            return to_route('login')->withErrors([
                'email' => 'Autentificarea cu Google nu e disponibilă momentan.',
            ]);
        }

        ['url' => $url, 'state' => $state] = $oauth->authorizationUrl();

        // `state` leagă cererea de sesiunea ACESTUI browser. Fără el, cineva
        // poate trimite victimei un link de revenire cu propriul cod și îi leagă
        // contul de identitatea atacatorului.
        $request->session()->put(GoogleOAuth::STATE_KEY, $state);

        return redirect()->away($url);
    }

    public function callback(Request $request, GoogleOAuth $oauth, LinkGoogleAccount $link): RedirectResponse
    {
        $asteptat = $request->session()->pull(GoogleOAuth::STATE_KEY);

        // Comparație în timp constant și refuz dacă lipsește oricare parte:
        // un `state` gol nu are voie să se potrivească cu un `state` gol.
        if (blank($asteptat) || ! is_string($request->query('state'))
            || ! hash_equals($asteptat, $request->query('state'))) {
            return to_route('login')->withErrors([
                'email' => 'Cererea a expirat sau nu a venit de la acest browser. Încearcă din nou.',
            ]);
        }

        // Elevul a apăsat „Anulează" la Google, sau Google a respins cererea.
        if (! is_string($request->query('code'))) {
            return to_route('login');
        }

        try {
            $result = $link($oauth->identity($request->query('code')));
        } catch (UnverifiedGoogleEmail $e) {
            // Prins SEPARAT de RuntimeException: e singurul caz în care mesajul
            // trebuie să ajungă la elev ca instrucțiune, nu ca eroare generică.
            return to_route('login')->withErrors(['email' => $e->getMessage()]);
        } catch (RuntimeException) {
            return to_route('login')->withErrors([
                'email' => 'Nu am putut confirma contul Google. Încearcă din nou.',
            ]);
        }

        Auth::login($result->user, remember: true);
        $request->session()->regenerate();

        // Contul nou n-are încă nume public. Middleware-ul l-ar trimite oricum
        // acolo, dar redirecționarea explicită evită un salt în plus.
        if (blank($result->user->username)) {
            return to_route('auth.username');
        }

        return to_route($result->user->subjects()->doesntExist() ? 'subjects.edit' : 'dashboard');
    }
}
