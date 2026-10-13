<?php

namespace Database\Seeders\Informatica;

use App\Enums\ItemType;

/**
 * Constructorii itemilor de informatică din testele ANCE (§11b).
 *
 * Testul are patru subiecte, cu sarcini compuse: un „Subiectul III.1" cere
 * numărul parametrilor, numele unei instrucțiuni, o valoare și ce afișează
 * programul. Fiecare item de aici e o astfel de sarcină, cu enunțul și
 * programul din test (Pascal ȘI C++, §12) și cu răspunsurile din barem.
 *
 * Ce afișează un program se SCRIE, ca la examen — nu se alege dintr-o listă:
 * variantele greșite ar fi inventate de noi. Programele de scris de la zero
 * (Subiectul II.4, III.2) și interogările Access nu au răspuns unic și rămân
 * pe barem.
 */
trait AnceItems
{
    private const ANCE = 'https://ance.gov.md/sites/default/files/';

    /** @return array{source: string, ref: string, label: string} */
    private static function doc(string $session, string $item): array
    {
        $file = "12_inf_barem_r_ro_{$session}";

        return [
            'source' => "ance:{$file}",
            'ref' => self::ANCE."{$file}.pdf#item{$item}",
            'label' => sprintf(
                'BAC 20%s, sesiunea %s, itemul %s',
                substr($session, 2),
                str_starts_with($session, 'sb') ? 'de bază' : 'suplimentară',
                $item,
            ),
        ];
    }

    /** Variantă cu alte date, rezervată simulării (§8). */
    private static function variant(array $doc, int $k = 1): array
    {
        return [...$doc, 'ref' => "{$doc['ref']}-varianta{$k}", 'variant' => true];
    }

    /** @param  list<string>  $hints */
    private static function base(array $doc, string $skill, int $points, array $hints, string $explanation): array
    {
        $variant = $doc['variant'] ?? false;

        return [
            'ref' => $doc['ref'],
            'source' => $doc['source'],
            'holdout' => $variant,
            'skill' => $skill,
            'points' => $points,
            'difficulty' => $points >= 4 ? 3 : 2,
            'hints' => $hints,
            'explanation' => $variant
                ? "{$explanation} (Variantă cu alte date a sarcinii din testul ANCE, {$doc['label']}; răspunsul e calculat de noi după etapele baremului.)"
                : "{$explanation} (Baremul ANCE, {$doc['label']}.)",
        ];
    }

    /** @return array<string, string> */
    private static function code(?string $pascal, ?string $cpp): array
    {
        return $pascal === null ? [] : ['code_variants' => ['pascal' => trim($pascal), 'cpp' => trim($cpp)]];
    }

    /** @param  list<string>  $hints */
    private static function num(array $doc, string $skill, int $points, string $stem, float $value,
        array $hints, string $explanation, ?string $unit = null): array
    {
        return [
            ...self::base($doc, $skill, $points, $hints, $explanation),
            'type' => ItemType::Numeric,
            'payload' => array_filter(['stem' => $stem, 'unit' => $unit], fn ($v) => $v !== null),
            'answer' => ['value' => $value, 'tolerance' => floor($value) === $value ? 0 : 0.001],
        ];
    }

    /**
     * Spații de completat. Fiecare: cheie => [etichetă, răspunsuri acceptate, variante?].
     * Un răspuns numeric se compară ca valoare, unul text ca text.
     *
     * @param  array<string, array{0: string, 1: string|list<string>, 2?: list<string>}>  $blanks
     * @param  list<string>  $hints
     */
    private static function blanks(array $doc, string $skill, int $points, string $stem, array $blanks,
        array $hints, string $explanation, ?string $pascal = null, ?string $cpp = null): array
    {
        return [
            ...self::base($doc, $skill, $points, $hints, $explanation),
            'type' => ItemType::FillBlanks,
            'payload' => [
                'stem' => $stem,
                ...self::code($pascal, $cpp),
                'blanks' => array_map(
                    fn (string $key, array $b) => array_filter(
                        ['key' => $key, 'label' => $b[0], 'options' => $b[2] ?? null],
                        fn ($v) => $v !== null,
                    ),
                    array_keys($blanks), $blanks,
                ),
            ],
            'answer' => [
                'values' => array_map(fn (array $b) => (array) $b[1], $blanks),
                'numeric' => true,
            ],
        ];
    }

    /**
     * Prima variantă e cea din barem. Variantele sunt cele din test.
     *
     * @param  list<string>  $options
     * @param  list<int>  $correct  indicii variantelor acceptate (implicit prima)
     * @param  list<string>  $hints
     */
    private static function choice(array $doc, string $skill, int $points, string $stem, array $options,
        array $hints, string $explanation, array $correct = [0], ?string $pascal = null, ?string $cpp = null): array
    {
        $keys = array_map(fn (int $i) => chr(ord('a') + $i), $correct);

        return [
            ...self::base($doc, $skill, $points, $hints, $explanation),
            'type' => ItemType::Mcq,
            'payload' => [
                'stem' => $stem,
                ...self::code($pascal, $cpp),
                'shuffle' => true,
                'choices' => array_map(
                    fn (int $i, string $text) => ['key' => chr(ord('a') + $i), 'text' => $text],
                    array_keys($options), $options,
                ),
            ],
            'answer' => count($keys) > 1 ? ['correct' => $keys, 'any' => true] : ['correct' => $keys],
        ];
    }

    /**
     * @param  list<string>  $steps  în ordinea corectă
     * @param  list<string>  $hints
     */
    private static function order(array $doc, string $skill, int $points, string $stem, array $steps,
        array $hints, string $explanation): array
    {
        $keyed = array_map(fn (int $i, string $text) => ['key' => 's'.($i + 1), 'text' => $text],
            array_keys($steps), $steps);

        return [
            ...self::base($doc, $skill, $points, $hints, $explanation),
            'type' => ItemType::OrderSteps,
            'payload' => ['stem' => $stem, 'steps' => $keyed],
            'answer' => ['order' => array_column($keyed, 'key')],
        ];
    }

    /**
     * @param  array<string, string>  $pairs  stânga => dreapta corectă
     * @param  list<string>  $extra  variante în plus în dreapta, din test
     * @param  list<string>  $hints
     */
    private static function match(array $doc, string $skill, int $points, string $stem, array $pairs,
        array $extra, array $hints, string $explanation): array
    {
        $left = [];
        $right = [];
        $key = [];
        $i = 0;
        foreach ($pairs as $l => $r) {
            $i++;
            $left[] = ['key' => "l{$i}", 'text' => $l];
            $right[] = ['key' => "r{$i}", 'text' => $r];
            $key["l{$i}"] = "r{$i}";
        }
        foreach ($extra as $text) {
            $i++;
            $right[] = ['key' => "r{$i}", 'text' => $text];
        }

        return [
            ...self::base($doc, $skill, $points, $hints, $explanation),
            'type' => ItemType::Match,
            'payload' => ['stem' => $stem, 'left' => $left, 'right' => $right],
            'answer' => ['pairs' => $key],
        ];
    }
}
