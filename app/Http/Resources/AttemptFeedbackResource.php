<?php

namespace App\Http\Resources;

use App\Domain\Attempts\SubmitAnswerResult;
use App\Domain\Grading\AnswerKeyMask;
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

        // Clientul vede variantele sub jetoane opace (AnswerKeyMask), deci și
        // cheia dezvăluită după răspuns trebuie tradusă în aceleași jetoane.
        $mask = app(AnswerKeyMask::class);
        $item = $r->attempt->item;

        return [
            'is_correct' => $r->grade->isCorrect,
            'score' => round($r->grade->score, 3),
            'breakdown' => $item ? $mask->maskKey($item, $r->grade->breakdown) : $r->grade->breakdown,
            'correct_answer' => $item ? $mask->maskKey($item, $r->correctAnswer) : $r->correctAnswer,
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
