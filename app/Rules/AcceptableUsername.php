<?php

namespace App\Rules;

use App\Domain\Moderation\UsernamePolicy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Regulă de validare pentru numele public.
 *
 * Se aplică în TOATE locurile unde numele poate fi setat — la înscriere și în
 * setări. Dacă apare un al treilea loc, trebuie adăugată și acolo; altfel
 * filtrul devine ocolibil printr-un formular uitat.
 */
class AcceptableUsername implements ValidationRule
{
    public function __construct(private ?UsernamePolicy $policy = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $result = ($this->policy ?? app(UsernamePolicy::class))->check((string) $value);

        if (! $result['allowed']) {
            $fail($result['reason']);
        }
    }
}
