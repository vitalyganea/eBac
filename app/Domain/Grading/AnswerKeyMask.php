<?php

namespace App\Domain\Grading;

use App\Models\Item;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Cheile variantelor și ale pașilor NU pleacă spre client în forma în care sunt
 * scrise în bancă.
 *
 * De ce: autorii scriu varianta corectă prima (`a`) și pașii în ordinea bună
 * (`s1`…`s5`). La auditul din octombrie 2026, 175 din 176 de itemi cu variante
 * aveau cheia corectă `a`, iar toți cei 17 itemi de ordonare soseau în browser
 * deja în ordinea corectă. Pentru cine se uita în props-urile paginii, cheia de
 * corectare era, practic, trimisă înainte de submit (§2).
 *
 * Amestecarea singură nu ajungea: după shuffle, cheia `a` tot arăta varianta
 * corectă. De aceea cheile se înlocuiesc cu jetoane opace (HMAC pe uuid-ul
 * itemului), deterministe — același item dă aceleași jetoane la refresh și pe
 * mobil, deci un răspuns dat offline se poate traduce înapoi oricând.
 *
 * Traducerea înapoi se face în `SubmitAnswer`, deci web și API o împart. În
 * `attempts` se scriu cheile ORIGINALE: faptul rămâne independent de mască.
 *
 * Cheile de la potrivire (`l1`/`r2`) rămân neschimbate: perechile corecte nu se
 * pot deduce din ele, iar coloana dreaptă se amestecă oricum.
 */
final class AnswerKeyMask
{
    /** Câmpurile din payload ale căror chei se maschează. */
    private const MASKED_LISTS = ['choices', 'steps'];

    public function token(Item $item, string $key): string
    {
        $secret = (string) config('app.key');

        return 'k'.substr(hash_hmac('sha256', $item->uuid.'|'.$key, $secret), 0, 10);
    }

    /**
     * Payload-ul pentru client: chei mascate, variantele și pașii amestecați.
     *
     * Ordinea e deterministă pentru același `$seed` (de regulă: elevul + rândul
     * din sesiune), ca variantele să nu sară de pe loc după ce elevul răspunde
     * și pagina se randează din nou cu feedback.
     */
    public function maskPayload(Item $item, array $payload, string $seed): array
    {
        $randomizer = new Randomizer(new Mt19937(crc32($item->uuid.'|'.$seed)));

        foreach (self::MASKED_LISTS as $list) {
            if (empty($payload[$list]) || ! is_array($payload[$list])) {
                continue;
            }

            $entries = array_map(
                fn (array $entry) => ['key' => $this->token($item, (string) $entry['key'])] + $entry,
                array_values($payload[$list]),
            );

            // `shuffle: false` rămâne posibil pentru variantele a căror ordine
            // contează („toate cele de mai sus"). Pașii de ordonare se
            // amestecă ÎNTOTDEAUNA — altfel itemul e rezolvat de la sine.
            $keepOrder = $list === 'choices' && ($payload['shuffle'] ?? null) === false;

            $payload[$list] = $keepOrder ? $entries : $randomizer->shuffleArray($entries);
        }

        if (! empty($payload['right']) && is_array($payload['right'])) {
            $payload['right'] = $randomizer->shuffleArray(array_values($payload['right']));
        }

        unset($payload['shuffle']);

        return $payload;
    }

    /**
     * Răspunsul primit de la client → cheile originale, pentru corectare.
     *
     * Un jeton necunoscut rămâne neschimbat, deci și o cheie originală trimisă
     * direct (teste, clienți vechi) se corectează corect. Asta nu slăbește
     * nimic: problema era AFLAREA cheii corecte, nu trimiterea ei.
     */
    public function unmaskAnswer(Item $item, array $answer): array
    {
        $map = $this->reverseMap($item);

        if ($map === []) {
            return $answer;
        }

        $translate = fn ($v) => is_string($v) ? ($map[$v] ?? $v) : $v;

        foreach (['choice', 'choices', 'order'] as $field) {
            if (! array_key_exists($field, $answer)) {
                continue;
            }

            $answer[$field] = is_array($answer[$field])
                ? array_map($translate, $answer[$field])
                : $translate($answer[$field]);
        }

        return $answer;
    }

    /**
     * Cheia de corectare (sau un breakdown) → jetoanele pe care le vede clientul.
     */
    public function maskKey(Item $item, ?array $key): ?array
    {
        if ($key === null) {
            return null;
        }

        foreach (['correct', 'order', 'expected'] as $field) {
            if (isset($key[$field]) && is_array($key[$field])) {
                $key[$field] = array_map(
                    fn ($k) => $this->token($item, (string) $k),
                    $key[$field],
                );
            }
        }

        return $key;
    }

    /**
     * Un răspuns salvat în `attempts` (chei originale) → jetoanele clientului,
     * ca să poată fi reafișat peste variantele mascate (revizuirea simulării).
     */
    public function maskAnswer(Item $item, array $answer): array
    {
        $tokens = fn ($v) => is_string($v) && $this->isMaskedKey($item, $v) ? $this->token($item, $v) : $v;

        foreach (['choice', 'choices', 'order'] as $field) {
            if (array_key_exists($field, $answer)) {
                $answer[$field] = is_array($answer[$field])
                    ? array_map($tokens, $answer[$field])
                    : $tokens($answer[$field]);
            }
        }

        return $answer;
    }

    private function isMaskedKey(Item $item, string $key): bool
    {
        return in_array($key, $this->reverseMap($item), true);
    }

    /** @return array<string, string> jeton → cheie originală */
    private function reverseMap(Item $item): array
    {
        $map = [];

        foreach (self::MASKED_LISTS as $list) {
            foreach ((array) ($item->payload[$list] ?? []) as $entry) {
                if (isset($entry['key'])) {
                    $map[$this->token($item, (string) $entry['key'])] = (string) $entry['key'];
                }
            }
        }

        return $map;
    }
}
