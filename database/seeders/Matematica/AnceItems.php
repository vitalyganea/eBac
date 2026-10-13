<?php

namespace Database\Seeders\Matematica;

use App\Enums\ItemType;

/**
 * Constructorii itemilor de matematică luați din testele ANCE (§11b).
 *
 * Fiecare item e o problemă dintr-un test oficial: enunțul e cel din test,
 * răspunsul e cel din coloana „Răspuns corect" a baremului, iar indiciile și
 * explicația urmează „Etapele rezolvării" din același barem — punctate acolo,
 * deci exact pașii pe care îi caută corectorul.
 *
 * Tipul itemului depinde de forma răspunsului din barem, nu de preferința
 * noastră:
 *   - un număr (și „9√2π", „500√3/3")          → numeric
 *   - mai multe valori („p = 2, q = −14")        → fill_blanks numeric
 *   - o mulțime sau un interval („S = (3; +∞)") → mcq; variantele greșite sunt
 *     greșelile pe care baremul le depunctează explicit (o condiție uitată,
 *     sensul inegalității), declarate în explicație
 *   - o demonstrație („Arătați că…")             → order_steps, cu pașii din barem
 *
 * La examen se punctează și pașii, nu doar răspunsul. Aici se verifică
 * răspunsul final, iar pașii vin în indicii și în explicație.
 */
trait AnceItems
{
    private const ANCE = 'https://ance.gov.md/sites/default/files/';

    /**
     * `r_sb26` → baremul de la profil real, sesiunea de bază 2026.
     *
     * @return array{source: string, ref: string, label: string}
     */
    private static function doc(string $code, int|string $item): array
    {
        [$profile, $session] = explode('_', $code);
        $file = "12_mat_barem_{$profile}_ro_{$session}";

        return [
            'source' => "ance:{$file}",
            'ref' => self::ANCE."{$file}.pdf#item{$item}",
            'label' => sprintf(
                'BAC 20%s, sesiunea %s, profil %s, itemul %s',
                substr($session, 2),
                str_starts_with($session, 'sb') ? 'de bază' : 'suplimentară',
                $profile === 'r' ? 'real' : 'umanist',
                $item,
            ),
        ];
    }

    /**
     * Variantă pentru simulare: aceeași problemă ANCE, alte date.
     *
     * Problemele oficiale sunt publice — oricine le găsește cu barem cu tot —
     * deci nu pot măsura nimic într-o simulare (§8). Varianta păstrează
     * formularea și pașii baremului, iar răspunsul e calculat de noi; explicația
     * o spune explicit.
     *
     * @param  array{source: string, ref: string, label: string}  $doc
     */
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
            'holdout' => $variant,
            'source' => $doc['source'],
            'skill' => $skill,
            'points' => $points,
            // 5 puncte la examen = item scurt; 8 = rezolvare în mai mulți pași.
            'difficulty' => $points >= 8 ? 3 : 2,
            'hints' => $hints,
            'explanation' => $variant
                ? "{$explanation} (Variantă cu alte date a problemei din testul ANCE, {$doc['label']}; rezolvarea urmează etapele baremului.)"
                : "{$explanation} (Baremul ANCE, {$doc['label']}.)",
        ];
    }

    /**
     * @param  list<string>  $hints
     */
    private static function num(array $doc, string $skill, int $points, string $stem, float $value,
        array $hints, string $explanation, ?string $unit = null): array
    {
        $exact = floor($value) === $value;

        return [
            ...self::base($doc, $skill, $points, $hints, $explanation),
            'type' => ItemType::Numeric,
            'payload' => array_filter(['stem' => $stem, 'unit' => $unit], fn ($v) => $v !== null),
            // Forma exactă („2/3", „9√2π") se evaluează; o aproximare zecimală
            // corectă la două zecimale e tot răspunsul bun, doar rotunjit.
            'answer' => $exact
                ? ['value' => $value, 'tolerance' => 0]
                : ['value' => $value, 'tolerance' => max(0.005, abs($value) * 0.001)],
        ];
    }

    /**
     * @param  array<string, array{0: string, 1: string}>  $blanks  cheie => [etichetă, valoare]
     * @param  list<string>  $hints
     */
    private static function blanks(array $doc, string $skill, int $points, string $stem, array $blanks,
        array $hints, string $explanation): array
    {
        return [
            ...self::base($doc, $skill, $points, $hints, $explanation),
            'type' => ItemType::FillBlanks,
            'payload' => [
                'stem' => $stem,
                'blanks' => array_map(
                    fn (string $key, array $b) => ['key' => $key, 'label' => $b[0]],
                    array_keys($blanks), $blanks,
                ),
            ],
            'answer' => [
                'values' => array_map(fn (array $b) => [$b[1]], $blanks),
                'numeric' => true,
            ],
        ];
    }

    /**
     * Prima variantă e cea din barem; se amestecă la afișare.
     *
     * @param  list<string>  $options
     * @param  list<string>  $hints
     */
    private static function choice(array $doc, string $skill, int $points, string $stem, array $options,
        array $hints, string $explanation): array
    {
        return [
            ...self::base($doc, $skill, $points, $hints, $explanation),
            'type' => ItemType::Mcq,
            'payload' => [
                'stem' => $stem,
                'shuffle' => true,
                'choices' => array_map(
                    fn (int $i, string $text) => ['key' => chr(ord('a') + $i), 'text' => $text],
                    array_keys($options), $options,
                ),
            ],
            'answer' => ['correct' => ['a']],
        ];
    }

    /**
     * Pașii, în ordinea din barem.
     *
     * @param  list<string>  $steps
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
            'payload' => [
                'stem' => $stem,
                'steps' => $keyed,
            ],
            'answer' => ['order' => array_column($keyed, 'key')],
        ];
    }
}
