<?php

namespace App\Domain\Users;

use App\Mail\DeletionCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Codul care confirmă ștergerea contului atunci când nu există parolă.
 *
 * Ștergerea e ireversibilă și obligatorie legal (Legea nr. 133/2011, §9), deci
 * are nevoie de o confirmare reală. Conturile create prin Google n-au parolă, iar
 * `Hash::check` întoarce `false` pentru un hash null — adică, fără asta, un
 * asemenea cont n-ar putea fi șters NICIODATĂ, și ar eșua tăcut.
 *
 * Codul stă în cache, nu într-un tabel: expiră singur, iar dacă se pierde la un
 * flush de Redis consecința e că elevul cere altul. Nimic de reconstruit.
 */
final class AccountDeletionCode
{
    private const TTL_MINUTES = 15;

    public function send(User $user): void
    {
        // Șase cifre, nu un token lung: se citește de pe telefon și se tastează
        // pe altul. Spațiul mic e acoperit de expirare și de limitarea de rată.
        $cod = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->key($user), Hash::make($cod), now()->addMinutes(self::TTL_MINUTES));

        Mail::to($user->email)->send(new DeletionCodeMail($cod, self::TTL_MINUTES));
    }

    public function check(User $user, ?string $cod): bool
    {
        $hash = Cache::get($this->key($user));

        if (! is_string($hash) || blank($cod)) {
            return false;
        }

        if (! Hash::check($cod, $hash)) {
            return false;
        }

        // Un cod folosit nu mai e valabil: altfel ar rămâne 15 minute într-o
        // căsuță de email deschisă pe un calculator din laboratorul școlii.
        Cache::forget($this->key($user));

        return true;
    }

    private function key(User $user): string
    {
        return "account-deletion:{$user->id}";
    }
}
