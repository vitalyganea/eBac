<?php

namespace App\Domain\Grading\Graders;

use App\Domain\Grading\Grader;
use App\Domain\Grading\GradeResult;
use App\Domain\Grading\NumberExpression;
use App\Models\Item;

/**
 * Completare de spații — fill_blanks și balance_equation.
 *
 * payload: {stem_template: "S = ___ · h", blanks: [{key, options?: []}]}
 * answer:  {values: {"b1": "a/2"}, case_sensitive?: false, numeric?: false}
 *
 * Cu `numeric`, spațiile se compară ca VALORI, nu ca text: la „p = ___,
 * q = ___" din baremul ANCE, „−14", „-14" și „-28/2" sunt același răspuns.
 *
 * `tolerance: {"cald": 1}` e „eroarea admisibilă" din barem, pe spațiu: la
 * geografie, temperatura citită de pe climogramă se acceptă cu ±1°.
 */
final class FillBlanksGrader implements Grader
{
    public function grade(Item $item, array $submitted): GradeResult
    {
        $key = $item->gradingKey();
        $expected = (array) ($key['values'] ?? []);
        $actual = (array) ($submitted['values'] ?? []);
        $caseSensitive = (bool) ($key['case_sensitive'] ?? false);
        $numeric = (bool) ($key['numeric'] ?? false);

        if ($expected === []) {
            return GradeResult::wrong(['error' => 'item_without_key']);
        }

        $correctBlanks = [];
        foreach ($expected as $blank => $accepted) {
            $given = trim((string) ($actual[$blank] ?? ''));

            // O valoare acceptată poate fi un scalar sau o listă de variante
            // echivalente („a/2" și „0.5a").
            foreach ((array) $accepted as $variant) {
                $variant = trim((string) $variant);
                // Cu `numeric`, un spațiu cu răspuns NUMERIC se compară ca
                // valoare; unul cu răspuns text („while", „xx") rămâne text.
                $matches = $numeric && NumberExpression::evaluate($variant) !== null
                    ? self::sameNumber($given, $variant, (float) ($key['tolerance'][$blank] ?? 1e-6))
                    : ($caseSensitive
                    ? $given === $variant
                    : self::text($given) === self::text($variant));

                if ($matches && $given !== '') {
                    $correctBlanks[] = (string) $blank;
                    break;
                }
            }
        }

        return GradeResult::partial(
            count($correctBlanks) / count($expected),
            ['correct_blanks' => $correctBlanks, 'total_blanks' => count($expected)]
        );
    }

    /**
     * Forma unui răspuns scris nu e conținutul lui: „Stephen Ritz.", „stephen
     * ritz" și „‘Stephen Ritz’" sunt același răspuns transcris din text, iar
     * apostroful curbat din PDF (O’Donnell) nu se poate tasta pe orice telefon.
     */
    private static function text(string $value): string
    {
        $value = strtr(mb_strtolower($value), ['’' => "'", '‘' => "'", '“' => '"', '”' => '"', '„' => '"']);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value, " \t.,;:!?\"'");
    }

    private static function sameNumber(string $given, string $expected, float $tolerance): bool
    {
        // „p = 2" în spațiul pentru p: elevul a scris și numele.
        $given = preg_replace('/^[a-zA-Zα-ω]\w*\s*=\s*/u', '', $given) ?? $given;
        $a = NumberExpression::evaluate(str_replace(',', '.', $given));
        $b = NumberExpression::evaluate($expected);

        return $a !== null && $b !== null && abs($a - $b) <= $tolerance + 1e-9;
    }
}
