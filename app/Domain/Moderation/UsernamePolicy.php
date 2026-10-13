<?php

namespace App\Domain\Moderation;

use Illuminate\Support\Str;

/**
 * Filtrul automat pentru numele publice.
 *
 * Numele e singurul conținut public care se poate verifica programatic — e text,
 * deci regulile se pot scrie. Filtrarea se face la ÎNSCRIERE și la fiecare
 * schimbare, nu prin pre-moderare: a bloca înscrierea până când cineva aprobă un
 * nume ar omorî conversia pentru ceva ce în marea majoritate a cazurilor e
 * inofensiv.
 *
 * Filtrul NU înlocuiește moderarea umană. El reduce volumul care ajunge acolo.
 */
final class UsernamePolicy
{
    /**
     * Normalizează numele înainte de comparație.
     *
     * Fără pasul ăsta, filtrul se ocolește în trei secunde: „adm1n", „a_d_m_i_n",
     * „ADMÎN" sau „@dmin" ar trece toate.
     */
    public function normalize(string $username): string
    {
        $value = Str::lower(trim($username));

        // Diacriticele românești și chirilicele rămân, dar formele latine
        // echivalente se unifică.
        $value = strtr($value, [
            'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's',
            'ț' => 't', 'ţ' => 't',
        ]);

        // Leetspeak: cifre și simboluri folosite ca litere. Caracterele cu o
        // singură citire evidentă se traduc aici; cele ambigue sunt tratate în
        // normalizeVariants().
        $value = strtr($value, [
            '0' => 'o', '3' => 'e', '4' => 'a',
            '5' => 's', '7' => 't', '@' => 'a', '$' => 's',
        ]);

        // Separatoarele dispar: „f-u-c-k" devine „fuck".
        $value = preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? '';

        // Repetițiile lungi se colapsează: „fuuuuck" devine „fuck".
        return preg_replace('/(.)\1{2,}/u', '$1', $value) ?? '';
    }

    /**
     * @return array{allowed: bool, reason: string|null}
     */
    /**
     * Variantele de citire ale unui nume.
     *
     * Cifra `1` poate sta și pentru `i`, și pentru `l` — „pu1a" e o citire, dar
     * și cealaltă. O singură normalizare nu le poate acoperi pe amândouă, deci
     * generăm setul și verificăm fiecare variantă.
     *
     * Numărul de variante e plafonat: un nume cu douăzeci de cifre ambigue ar
     * produce un milion de combinații, iar filtrul ar deveni un vector de
     * atac prin consum de procesor.
     *
     * @return list<string>
     */
    public function normalizeVariants(string $username): array
    {
        $base = $this->normalize($username);
        $variants = [$base];

        foreach (['1', '!'] as $ambiguous) {
            if (! str_contains(Str::lower($username), $ambiguous)) {
                continue;
            }

            $next = [];
            foreach ($variants as $variant) {
                foreach (['i', 'l'] as $letter) {
                    $next[] = $this->normalize(str_replace($ambiguous, $letter, $username));
                }
            }
            $variants = array_slice(array_unique($next), 0, 32);
        }

        return array_values(array_unique($variants));
    }

    public function check(string $username): array
    {
        $variants = $this->normalizeVariants($username);
        $normalized = $variants[0] ?? '';

        if ($normalized === '') {
            return $this->deny('Numele public trebuie să conțină litere sau cifre.');
        }

        foreach ($variants as $variant) {
            if ($this->isReserved($variant)) {
                return $this->deny('Acest nume seamănă cu un rol oficial și nu poate fi folosit.');
            }

            if ($this->containsBlockedTerm($variant)) {
                // Nu spunem CARE cuvânt a declanșat filtrul: ar fi o instrucțiune
                // de ocolire.
                return $this->deny('Acest nume public nu poate fi folosit. Alege altul.');
            }
        }

        if ($this->looksLikeContactInfo($username)) {
            return $this->deny('Numele public nu poate conține date de contact sau adrese web.');
        }

        return ['allowed' => true, 'reason' => null];
    }

    public function allows(string $username): bool
    {
        return $this->check($username)['allowed'];
    }

    /**
     * Două liste, pentru că riscul de fals-pozitiv diferă.
     *
     * Termenii scurți și comuni („mod", „test") se compară doar ca nume întreg —
     * ca substring ar bloca „model" sau „testarescu". Marca și rolurile („ebac",
     * „admin", „moderator") se caută oriunde în nume, pentru că impersonarea
     * arată exact ca o compunere: „ebac_oficial", „admin_ebac".
     */
    private function isReserved(string $normalized): bool
    {
        if (in_array($normalized, config('moderation.reserved_exact', []), true)) {
            return true;
        }

        foreach (config('moderation.reserved_contains', []) as $term) {
            if (str_contains($normalized, $this->normalize($term))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Căutare de termeni interziși, cu grijă la fals-pozitive.
     *
     * Termenii scurți se caută doar ca nume ÎNTREG sau la margini, nu oriunde în
     * șir — altfel un nume legitim care conține din întâmplare trei litere ar fi
     * respins fără explicație. E problema clasică „Scunthorpe": filtre prea
     * lacome blochează oameni nevinovați, iar ei nu înțeleg de ce.
     */
    private function containsBlockedTerm(string $normalized): bool
    {
        foreach (config('moderation.blocked', []) as $term) {
            $term = $this->normalize($term);

            if ($term === '') {
                continue;
            }

            $matched = mb_strlen($term) >= 5
                ? str_contains($normalized, $term)
                : $normalized === $term
                    || str_starts_with($normalized, $term)
                    || str_ends_with($normalized, $term);

            if ($matched) {
                return true;
            }
        }

        return false;
    }

    /**
     * Numele publice nu sunt un loc pentru numere de telefon sau linkuri.
     *
     * Elevii sunt minori, iar un clasament public în care cineva își pune
     * numărul de telefon e o problemă de siguranță, nu de estetică.
     */
    private function looksLikeContactInfo(string $raw): bool
    {
        $patterns = [
            '/\d{6,}/',                        // șir lung de cifre — telefon
            '/(www|http|\.com|\.md|\.ru|\.net)/i',
            '/@[a-z]/i',                       // adresă de email sau handle
            '/(telegram|whatsapp|viber|insta)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $raw)) {
                return true;
            }
        }

        return false;
    }

    /** @return array{allowed: false, reason: string} */
    private function deny(string $reason): array
    {
        return ['allowed' => false, 'reason' => $reason];
    }
}
