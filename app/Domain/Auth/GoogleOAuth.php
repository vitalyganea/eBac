<?php

namespace App\Domain\Auth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Fluxul OAuth 2.0 cu Google, în trei cereri.
 *
 * Scris direct, fără Socialite: pachetul cere încă guzzle ^6|^7, iar aplicația e
 * pe 8. A coborî un major la biblioteca HTTP a întregii aplicații, ca să adaugi
 * un furnizor, costă mai mult decât secvența de mai jos.
 *
 * Nu e „autentificare scrisă de mână": parolele, sesiunile și cookie-urile rămân
 * ale Laravel. Aici sunt doar trei apeluri HTTP documentate de Google. Partea
 * care contează pentru securitate e `state` — fără el, cineva poate păcăli
 * browserul victimei să finalizeze un flux pornit de atacator, iar contul ajunge
 * legat de identitatea greșită.
 */
final class GoogleOAuth
{
    private const AUTHORIZE = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN = 'https://oauth2.googleapis.com/token';

    private const USERINFO = 'https://openidconnect.googleapis.com/v1/userinfo';

    public const STATE_KEY = 'google_oauth_state';

    public function configured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    /**
     * URL-ul către care trimitem elevul, plus `state`-ul de pus în sesiune.
     *
     * @return array{url: string, state: string}
     */
    public function authorizationUrl(): array
    {
        $state = Str::random(40);

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',

            // Strict minimul. `profile` ar aduce poza și numele complet; nu ne
            // trebuie niciunul — numele public îl alege elevul (§9), iar avatarul
            // trece prin ProcessAvatar (§10f). Cerem doar ce folosim.
            'scope' => 'openid email',

            'state' => $state,

            // Fără `select_account`, un elev logat pe telefonul familiei ar fi
            // conectat tăcut cu contul altcuiva.
            'prompt' => 'select_account',
        ]);

        return ['url' => self::AUTHORIZE.'?'.$query, 'state' => $state];
    }

    /**
     * Schimbă codul primit pe un token, apoi citește identitatea.
     *
     * Profilul se cere de la `userinfo`, nu se decodează din `id_token`: tokenul
     * a venit direct de la Google, pe TLS, într-un răspuns la cererea noastră
     * semnată cu `client_secret`. Nu are cine să-l fi înlocuit pe drum, deci nu e
     * nevoie de verificarea semnăturii JWT — și nici de o bibliotecă pentru ea.
     *
     * @return array{id: string, email: string, verified: bool}
     */
    public function identity(string $code): array
    {
        $token = Http::asForm()
            ->timeout(10)
            ->post(self::TOKEN, [
                'code' => $code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => config('services.google.redirect'),
                'grant_type' => 'authorization_code',
            ]);

        if ($token->failed() || blank($token->json('access_token'))) {
            throw new RuntimeException('Schimbul de cod cu Google a eșuat.');
        }

        $profile = Http::withToken($token->json('access_token'))
            ->timeout(10)
            ->get(self::USERINFO);

        if ($profile->failed() || blank($profile->json('sub')) || blank($profile->json('email'))) {
            throw new RuntimeException('Google nu a întors o identitate utilizabilă.');
        }

        return [
            'id' => (string) $profile->json('sub'),
            'email' => (string) $profile->json('email'),

            // Pentru adresele @gmail.com e mereu true, dar pe domenii Workspace
            // poate fi false. Diferența decide dacă avem voie să legăm un cont
            // existent — vezi LinkGoogleAccount.
            'verified' => $profile->json('email_verified') === true,
        ];
    }
}
