<?php

namespace App\Domain\Essays;

/**
 * Punctajul unei autoevaluări, pe grila baremului ANCE.
 *
 * Elevul bifează nivelurile și numără greșelile; totalul îl calculează
 * serverul, nu browserul. Un punctaj care nu există în barem (5 la un
 * criteriu cu niveluri 0/2/4) e respins, nu rotunjit.
 *
 * Două părți se calculează din numărul de cuvinte, exact ca în barem:
 *  - volumul, pe benzi („180–200 de cuvinte — 4 p.");
 *  - plafonul criteriilor de greșeli, din care se scade 1 punct la fiecare
 *    `per` greșeli („se va scădea câte 1 punct pentru fiecare 5 greșeli").
 */
final class ScoreEssay
{
    /** „Orice semn separat prin spații albe se consideră cuvânt." (baremul ANCE) */
    public static function countWords(string $text): int
    {
        $text = trim($text);

        return $text === '' ? 0 : count(preg_split('/\s+/u', $text) ?: []);
    }

    /**
     * @param  array<string, mixed>  $rubric
     * @param  array<string, int>  $criteria  cheie => punctele bifate
     * @param  array<string, int>  $errors  cheie => numărul de greșeli numărate
     * @return array{score: int, max: int, breakdown: list<array{key: string, label: string, points: int, max: int}>}
     */
    public function __invoke(array $rubric, array $criteria, array $errors, ?int $words): array
    {
        $breakdown = [];

        foreach ($rubric['criteria'] ?? [] as $c) {
            $allowed = array_map(fn (array $o) => (int) $o[0], $c['options']);
            $given = $criteria[$c['key']] ?? null;

            if ($given === null || ! in_array((int) $given, $allowed, true)) {
                throw new InvalidSelfAssessment("criteria.{$c['key']}", "Alege un nivel pentru „{$c['label']}”.");
            }

            $breakdown[] = ['key' => $c['key'], 'label' => $c['label'], 'points' => (int) $given, 'max' => max($allowed)];
        }

        $hasLanguage = ($rubric['errors'] ?? []) !== [] || isset($rubric['volume']);

        if ($hasLanguage && $words === null) {
            throw new InvalidSelfAssessment('word_count', 'Scrie textul sau introdu numărul de cuvinte.');
        }

        foreach ($rubric['errors'] ?? [] as $e) {
            $count = $errors[$e['key']] ?? null;

            if (! is_int($count) || $count < 0) {
                throw new InvalidSelfAssessment("errors.{$e['key']}", "Numără greșelile: {$e['label']}.");
            }

            $cap = $e['caps'] === null ? $e['max'] : self::band($e['caps'], $words, overMax: 'max');
            $breakdown[] = [
                'key' => $e['key'],
                'label' => $e['label'],
                'points' => max(0, $cap - intdiv($count, $e['per'])),
                'max' => $e['max'],
            ];
        }

        if (isset($rubric['volume'])) {
            $v = $rubric['volume'];
            $breakdown[] = [
                'key' => 'volum',
                'label' => 'Volumul textului',
                'points' => self::band($v['bands'], $words, overMax: $v['over_max']),
                'max' => $v['max'],
            ];
        }

        return [
            'score' => array_sum(array_column($breakdown, 'points')),
            'max' => array_sum(array_column($breakdown, 'max')),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Punctele benzii în care cade numărul de cuvinte.
     *
     * Benzile sunt în ordine descrescătoare; prima are limita maximă. Peste ea,
     * baremele vechi dau 0 la volum („puncte nu se acordă"), cel din 2026 nu
     * mai adaugă nimic („puncte nu se adaugă") — adică păstrează maximul.
     *
     * @param  list<array{0: int, 1: int, 2: int}>  $bands
     */
    private static function band(array $bands, int $words, int|string $overMax): int
    {
        if ($words > $bands[0][1]) {
            return $overMax === 'max' ? $bands[0][2] : (int) $overMax;
        }

        foreach ($bands as [$min, $max, $points]) {
            if ($words >= $min && $words <= $max) {
                return $points;
            }
        }

        return 0;
    }
}
