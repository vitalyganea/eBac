<?php

namespace App\Domain\Grading\Graders;

use App\Domain\Grading\Grader;
use App\Domain\Grading\GradeResult;
use App\Models\Item;

/**
 * Alegere dintre variante — acoperă cele mai multe tipuri de item:
 * mcq, next_step, find_error, code_output, label_diagram.
 *
 * payload: {stem, choices: [{key, text, latex?}], shuffle?: bool}
 * answer:  {correct: ["b"], multiple?: bool}
 */
final class SingleChoiceGrader implements Grader
{
    public function grade(Item $item, array $submitted): GradeResult
    {
        $key = $item->gradingKey();
        $correct = array_map('strval', $key['correct'] ?? []);
        $chosen = array_map('strval', (array) ($submitted['choices'] ?? $submitted['choice'] ?? []));

        sort($correct);
        sort($chosen);

        if ($correct === []) {
            return GradeResult::wrong(['error' => 'item_without_key']);
        }

        // Cu variante multiple acordăm punctaj parțial, dar penalizăm bifele greșite,
        // ca „bifez tot" să nu fie o strategie câștigătoare.
        if (($key['multiple'] ?? false) && count($correct) > 1) {
            $hits = count(array_intersect($correct, $chosen));
            $falsePositives = count(array_diff($chosen, $correct));
            $score = (count($correct) > 0)
                ? max(0, $hits - $falsePositives) / count($correct)
                : 0;

            return GradeResult::partial($score, ['hits' => $hits, 'false_positives' => $falsePositives]);
        }

        return $correct === $chosen
            ? GradeResult::correct()
            : GradeResult::wrong(['expected' => $correct]);
    }
}
