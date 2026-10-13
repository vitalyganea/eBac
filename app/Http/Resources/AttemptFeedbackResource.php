<?php

namespace App\Http\Resources;

use App\Domain\Attempts\SubmitAnswerResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Feedback-ul de DUPĂ trimiterea răspunsului. Abia aici se dezvăluie cheia de
 * corectare și explicația.
 *
 * @property-read SubmitAnswerResult $resource
 */
class AttemptFeedbackResource extends JsonResource
{
    /**
     * Fără împachetare în `data`.
     *
     * Props-urile Inertia și răspunsurile de API trebuie să aibă exact aceeași
     * formă, altfel clientul web ar citi `item.uuid`, iar cel mobil
     * `item.data.uuid` — două contracte pentru aceleași date.
     */
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        /** @var SubmitAnswerResult $r */
        $r = $this->resource;

        return [
            'is_correct' => $r->grade->isCorrect,
            'score' => round($r->grade->score, 3),
            'breakdown' => $r->grade->breakdown,
            'correct_answer' => $r->correctAnswer,
            'explanation' => $r->explanation,
            'xp' => [
                'amount' => $r->xp->amount,
                'reasons' => $r->xp->reasons,
                'capped' => $r->xp->cappedByDailyLimit,
            ],
            'rating_delta' => $r->elo?->userDelta(),
            'server_ms' => $r->attempt->server_ms,
            'totals' => $r->totals,
            'is_duplicate' => $r->isDuplicate,
        ];
    }
}
