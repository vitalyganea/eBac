<?php

namespace App\Domain\Grading\Graders;

use App\Domain\Grading\Grader;
use App\Domain\Grading\GradeResult;
use App\Models\Item;

/**
 * Potrivire (formulă ↔ definiție, organit ↔ funcție, formulă chimică ↔ nume).
 *
 * payload: {stem, left: [{key, text}], right: [{key, text}]}
 * answer:  {pairs: {"l1": "r3", "l2": "r1"}}
 */
final class MatchingGrader implements Grader
{
    public function grade(Item $item, array $submitted): GradeResult
    {
        $expected = (array) ($item->gradingKey()['pairs'] ?? []);
        $actual = (array) ($submitted['pairs'] ?? []);

        if ($expected === []) {
            return GradeResult::wrong(['error' => 'item_without_key']);
        }

        $correctKeys = [];
        foreach ($expected as $left => $right) {
            if (isset($actual[$left]) && (string) $actual[$left] === (string) $right) {
                $correctKeys[] = (string) $left;
            }
        }

        return GradeResult::partial(
            count($correctKeys) / count($expected),
            ['correct_pairs' => $correctKeys, 'total_pairs' => count($expected)]
        );
    }
}
