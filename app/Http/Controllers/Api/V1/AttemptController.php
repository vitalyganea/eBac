<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Attempts\SubmitAnswer;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitAnswerRequest;
use App\Http\Resources\AttemptFeedbackResource;
use Illuminate\Http\JsonResponse;

/**
 * Versiunea de API a trimiterii unui răspuns.
 *
 * Compară-l cu Web\PracticeController::answer(): aceeași validare, aceeași
 * acțiune de domeniu, aceeași resursă de feedback. Diferă doar transportul.
 *
 * Ăsta e tot ce înseamnă „aplicația mobilă folosește aceeași bază de date":
 * mobilul nu vorbește cu Postgres, vorbește cu acest controller, care apelează
 * exact logica pe care o rulează și web-ul.
 */
class AttemptController extends Controller
{
    public function store(SubmitAnswerRequest $request, SubmitAnswer $submit): JsonResponse
    {
        $result = $submit($request->user(), $request->toData());

        return (new AttemptFeedbackResource($result))
            ->response()
            // 200 la reluarea unui răspuns deja înregistrat (retry de rețea),
            // 201 la un fapt nou.
            ->setStatusCode($result->isDuplicate ? 200 : 201);
    }
}
