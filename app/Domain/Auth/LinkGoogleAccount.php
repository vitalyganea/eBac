<?php

namespace App\Domain\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Ce se întâmplă când cineva se întoarce de la Google.
 *
 * Trei situații, în ordinea în care se verifică:
 *
 *  1. Contul Google e deja legat → autentificare, gata.
 *  2. Există un utilizator cu același email → se leagă, DAR numai dacă Google
 *     confirmă emailul. Altfel, oricine își poate pune adresa victimei într-un
 *     cont de la un furnizor care nu verifică și ar prelua contul.
 *  3. Nimic → cont nou, FĂRĂ nume public. Numele îl alege elevul, trecând prin
 *     `AcceptableUsername` ca la înscrierea obișnuită (§10g) — altfel
 *     autentificarea cu Google ar deveni al treilea loc de setat un nume, cel
 *     prin care filtrul se ocolește.
 *
 * Numele real întors de Google nu e cerut deloc (scope-ul e `openid email`) și
 * nu ajunge nicăieri: în clasamente apare doar `username` (§9).
 */
final class LinkGoogleAccount
{
    /**
     * @param  array{id: string, email: string, verified: bool}  $identity
     */
    public function __invoke(array $identity): LinkResult
    {
        $legatura = SocialAccount::where('provider', 'google')
            ->where('provider_id', $identity['id'])
            ->first();

        if ($legatura !== null) {
            return new LinkResult($legatura->user, isNew: false);
        }

        $existent = User::where('email', $identity['email'])->first();

        if ($existent !== null) {
            if (! $identity['verified']) {
                throw new UnverifiedGoogleEmail;
            }

            $this->attach($existent, $identity);

            return new LinkResult($existent, isNew: false);
        }

        return new LinkResult($this->create($identity), isNew: true);
    }

    private function create(array $identity): User
    {
        return DB::transaction(function () use ($identity) {
            $user = new User;

            // `name` e obligatoriu în schemă, dar nu-l cerem de la Google: nu ne
            // trebuie numele real ca să funcționeze platforma, iar ce nu ceri nu
            // poate scăpa public. Elevul îl poate completa în Setări.
            $user->fill([
                'name' => '',
                'email' => $identity['email'],
            ]);

            // Fără parolă și FĂRĂ nume public: ambele se rezolvă după prima
            // autentificare. `username` null îl trimite prin
            // RequireAcceptableUsername spre ecranul de alegere.
            $user->password = null;
            $user->username = null;
            $user->public_alias = User::generatePublicAlias();
            $user->username_status = 'pending';
            $user->exam_target = config('ebac.default_exam_target');

            // Google ne-a confirmat adresa; nu mai are rost un al doilea email de
            // verificare peste cel pe care tocmai l-a verificat furnizorul.
            $user->email_verified_at = $identity['verified'] ? now() : null;

            $user->save();

            $this->attach($user, $identity);

            return $user;
        });
    }

    private function attach(User $user, array $identity): void
    {
        SocialAccount::updateOrCreate(
            ['provider' => 'google', 'provider_id' => $identity['id']],
            [
                'user_id' => $user->id,
                'provider_email' => $identity['email'],
                'linked_at' => now(),
            ],
        );

        // Dacă elevul se autentifica până acum cu parolă și emailul nu era
        // confirmat, autorizarea Google îl confirmă.
        if ($identity['verified'] && $user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }
    }
}
