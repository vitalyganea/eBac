<?php

namespace App\Domain\Exams;

use App\Domain\Sessions\StartSession;
use App\Enums\SessionMode;
use App\Models\PracticeSession;
use App\Models\User;

/**
 * Pornește o simulare de examen pe structura oficială (`exam_blueprints`).
 *
 * Itemii se servesc în ordinea secțiunilor, ca la examen. Termenul e fixat
 * ACUM, pe server, în `expires_at`; timerul din browser doar îl afișează.
 */
final class StartMock
{
    public function __construct(
        private BlueprintResolver $blueprints,
        private MockItemPicker $picker,
        private StartSession $sessions,
    ) {}

    /** @throws MockUnavailable */
    public function __invoke(User $user, int $subjectId): PracticeSession
    {
        $plan = $this->blueprints->planFor($user, $subjectId)
            ?? throw new MockUnavailable('Structura examenului la această disciplină nu e încă publicată.');

        $picked = $this->picker->pick($user, $subjectId, $plan);

        $items = collect();
        $sections = [];

        foreach ($picked['sections'] as ['section' => $section, 'items' => $sectionItems]) {
            $first = $items->count() + 1;
            $items = $items->concat($sectionItems);

            $sections[] = [
                'name' => $section['name'],
                'points' => $section['points'],
                'positions' => range($first, $items->count()),
                'note' => $section['note'],
            ];
        }

        return $this->sessions->create($user, $subjectId, SessionMode::Mock, $items, [
            'blueprint_id' => $plan->blueprint->id,
            'is_ranked' => false,
            'expires_at' => now()->addMinutes($plan->minutes()),
            'config' => [
                'minutes' => $plan->minutes(),
                'sections' => $sections,
                'manual_sections' => $plan->manualSections,
                'auto_points' => $plan->autoPoints(),
                'total_points' => $plan->totalPoints(),
                'reused' => $picked['reused'],
            ],
        ]);
    }
}
