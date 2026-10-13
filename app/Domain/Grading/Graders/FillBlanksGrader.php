<?php

namespace App\Domain\Grading\Graders;

use App\Domain\Grading\Grader;
use App\Domain\Grading\GradeResult;
use App\Models\Item;

/**
 * Completare de spații — fill_blanks și balance_equation.
 *
 * payload: {stem_template: "S = ___ · h", blanks: [{key, options?: []}]}
 * answer:  {values: {"b1": "a/2"}, case_sensitive?: false}
 */
final class FillBlanksGrader implements Grader
{
    public function grade(Item $item, array $submitted): GradeResult
    {
        $key = $item->gradingKey();
        $expected = (array) ($key['values'] ?? []);
        $actual = (array) ($submitted['values'] ?? []);
        $caseSensitive = (bool) ($key['case_sensitive'] ?? false);

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
                $matches = $caseSensitive
                    ? $given === $variant
                    : mb_strtolower($given) === mb_strtolower($variant);

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
}
