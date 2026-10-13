<?php

namespace App\Domain\Grading\Graders;

use App\Domain\Grading\Grader;
use App\Domain\Grading\GradeResult;
use App\Models\Item;

/**
 * Răspuns numeric cu toleranță.
 *
 * payload: {stem, unit?: "N", decimals?: 2}
 * answer:  {value: 3.14, tolerance?: 0.01, accept?: [3.1416]}
 *
 * Toleranța există pentru că un elev care scrie 3.14 în loc de π nu a greșit
 * matematica; a rotunjit.
 */
final class NumericGrader implements Grader
{
    public function grade(Item $item, array $submitted): GradeResult
    {
        $key = $item->gradingKey();

        if (! array_key_exists('value', $key)) {
            return GradeResult::wrong(['error' => 'item_without_key']);
        }

        $raw = $submitted['value'] ?? null;

        if ($raw === null || $raw === '' || ! is_numeric(str_replace(',', '.', (string) $raw))) {
            return GradeResult::wrong(['error' => 'not_numeric']);
        }

        // Elevii din Moldova scriu virgula ca separator zecimal.
        $value = (float) str_replace(',', '.', (string) $raw);
        $tolerance = (float) ($key['tolerance'] ?? 0.0);

        $candidates = array_merge([(float) $key['value']], array_map('floatval', $key['accept'] ?? []));

        foreach ($candidates as $candidate) {
            if (abs($value - $candidate) <= $tolerance + 1e-9) {
                return GradeResult::correct();
            }
        }

        return GradeResult::wrong(['submitted' => $value]);
    }
}
