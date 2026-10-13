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
                    ? self::sameNumber($given, $variant)
                    : ($caseSensitive
                    ? $given === $variant
                    : mb_strtolower($given) === mb_strtolower($variant));

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

    private static function sameNumber(string $given, string $expected): bool
    {
        // „p = 2" în spațiul pentru p: elevul a scris și numele.
        $given = preg_replace('/^[a-zA-Zα-ω]\w*\s*=\s*/u', '', $given) ?? $given;
        $a = NumberExpression::evaluate(str_replace(',', '.', $given));
        $b = NumberExpression::evaluate($expected);

        return $a !== null && $b !== null && abs($a - $b) <= 1e-6;
    }
}
