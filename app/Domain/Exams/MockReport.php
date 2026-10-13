<?php

namespace App\Domain\Exams;

use App\Domain\Grading\AnswerKeyMask;
use App\Domain\Grading\GraderRegistry;
use App\Http\Resources\ItemForStudentResource;
use App\Models\PracticeSession;
use Illuminate\Http\Request;

/**
 * Rezultatul unei simulări, pentru elev.
 *
 * Abia aici, după predare, pleacă spre client cheile de corectare și
 * explicațiile itemilor de holdout — doar celui care i-a rezolvat. Itemii nu
 * ajung NICIODATĂ pe paginile publice (§14), iar MockItemPicker nu i-i mai
 * servește aceluiași elev cât timp are alții nevăzuți.
 */
final class MockReport
{
    public function __construct(
        private FinishMock $finish,
        private AnswerKeyMask $mask,
        private GraderRegistry $graders,
    ) {}

    public function build(PracticeSession $session, Request $request): array
    {
        $config = $session->config ?? [];
        $score = $this->finish->score($session);
        $byPosition = $this->finish->scoresByPosition($session);

        $sectionOf = [];
        foreach ($config['sections'] ?? [] as $section) {
            foreach ($section['positions'] as $p) {
                $sectionOf[$p] = $section['name'];
            }
        }

        $review = $session->sessionItems()->with('item.skills')->get()->map(function ($si) use ($byPosition, $sectionOf, $request) {
            $item = $si->item;
            $result = $byPosition->get($si->position);
            $attempt = $result['attempt'] ?? null;

            return [
                'position' => $si->position,
                'section' => $sectionOf[$si->position] ?? null,
                'item' => (new ItemForStudentResource($item, hintsAllowed: false, sessionItem: $si))->toArray($request),
                'answer' => $attempt ? $this->mask->maskAnswer($item, (array) $attempt->answer) : null,
                'feedback' => [
                    'is_correct' => (bool) ($result['is_correct'] ?? false),
                    'score' => round((float) ($result['score'] ?? 0), 3),
                    // Breakdown-ul nu se păstrează în attempts; graderii sunt
                    // funcții pure, deci îl recalculăm din răspunsul salvat.
                    'breakdown' => $attempt
                        ? $this->mask->maskKey($item, $this->graders->for($item->type)->grade($item, (array) $attempt->answer)->breakdown)
                        : [],
                    'correct_answer' => $this->mask->maskKey($item, $item->gradingKey()),
                    'explanation' => $item->explanation,
                ],
            ];
        })->values();

        $autoPoints = (int) ($config['auto_points'] ?? 0);

        return [
            'uuid' => $session->uuid,
            'blueprint' => $session->blueprint_id ? [
                'session' => $session->loadMissing('blueprint')->blueprint?->session,
                'profile_label' => $session->blueprint?->profile->label(),
            ] : null,
            'earned' => $score['earned'],
            'auto_points' => $autoPoints,
            'total_points' => (int) ($config['total_points'] ?? $autoPoints),
            'percent' => $autoPoints > 0 ? (int) round($score['earned'] / $autoPoints * 100) : 0,
            'sections' => $score['sections'],
            'manual_sections' => $config['manual_sections'] ?? [],
            'minutes' => (int) ($config['minutes'] ?? 0),
            'used_seconds' => $session->completed_at
                ? (int) $session->started_at->diffInSeconds($session->completed_at)
                : null,
            'reused' => (int) ($config['reused'] ?? 0),
            'answered' => $byPosition->count(),
            'total_items' => $session->total_items,
            'review' => $review,
        ];
    }
}
