<?php

namespace App\Domain\Grading\Graders;

use App\Domain\Grading\Grader;
use App\Domain\Grading\GradeResult;
use App\Models\Item;

/**
 * Ordonare — order_steps, timeline, build_formula.
 *
 * payload: {stem, steps: [{key, text}]}
 * answer:  {order: ["s2", "s1", "s3"]}
 *
 * Punctajul se dă pe ORDINEA RELATIVĂ corectă (perechi concordante), nu pe
 * poziția absolută. Un elev care a inversat doar primii doi pași dintr-o
 * rezolvare de șase pași a înțeles structura și merită aproape tot punctajul —
 * exact logica baremului analitic.
 */
final class OrderingGrader implements Grader
{
    public function grade(Item $item, array $submitted): GradeResult
    {
        $expected = array_values(array_map('strval', $item->gradingKey()['order'] ?? []));
        $actual = array_values(array_map('strval', (array) ($submitted['order'] ?? [])));

        if ($expected === []) {
            return GradeResult::wrong(['error' => 'item_without_key']);
        }

        if ($actual === $expected) {
            return GradeResult::correct();
        }

        // Elevul trebuie să folosească exact aceleași elemente.
        if (count($actual) !== count($expected) || array_diff($expected, $actual) !== []) {
            return GradeResult::wrong(['error' => 'incomplete_ordering']);
        }

        $rank = array_flip($actual);
        $n = count($expected);
        $totalPairs = $n * ($n - 1) / 2;
        $concordant = 0;

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                if ($rank[$expected[$i]] < $rank[$expected[$j]]) {
                    $concordant++;
                }
            }
        }

        return GradeResult::partial(
            $totalPairs > 0 ? $concordant / $totalPairs : 0.0,
            ['concordant_pairs' => $concordant, 'total_pairs' => (int) $totalPairs]
        );
    }
}
