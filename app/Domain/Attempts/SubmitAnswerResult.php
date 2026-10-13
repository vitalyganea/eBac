<?php

namespace App\Domain\Attempts;

use App\Domain\Grading\GradeResult;
use App\Domain\Rating\EloResult;
use App\Domain\Xp\XpAward;
use App\Models\Attempt;

final readonly class SubmitAnswerResult
{
    public function __construct(
        public Attempt $attempt,
        public GradeResult $grade,
        public XpAward $xp,
        public ?EloResult $elo,
        /** Se dezvăluie DOAR acum, după ce elevul a trimis răspunsul. */
        public ?array $correctAnswer,
        public ?string $explanation,
        public array $totals,
        /** true dacă răspunsul fusese deja înregistrat (retry de rețea). */
        public bool $isDuplicate = false,
    ) {}
}
