<?php

namespace App\Domain\Exams;

use App\Enums\SessionMode;
use App\Models\Attempt;
use App\Models\PracticeSession;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Închide simularea și calculează punctajul pe structura examenului.
 *
 * Fiecare secțiune își păstrează ponderea din examen: punctele ei se împart
 * egal între itemii care o reprezintă, iar fiecare item contribuie cu scorul
 * lui (0..1, cu punctaj parțial unde graderul îl dă — exact ca baremul
 * analitic). Un item fără răspuns valorează zero.
 *
 * Punctajul e calculat din `attempts` (sursa de adevăr), nu din contoarele
 * sesiunii, deci se poate recalcula oricând.
 */
final class FinishMock
{
    public function __invoke(PracticeSession $session): PracticeSession
    {
        if ($session->mode !== SessionMode::Mock) {
            throw new InvalidArgumentException('Doar simulările se închid cu FinishMock.');
        }

        if ($session->isComplete()) {
            return $session;
        }

        $session->forceFill([
            'score' => $this->score($session)['earned'],
            // Predarea după termen (elevul revine a doua zi) se înregistrează la
            // termen: timpul simulării nu crește cu cât a stat tabul deschis.
            'completed_at' => $session->expires_at && $session->expires_at->isPast()
                ? $session->expires_at
                : now(),
        ])->save();

        return $session;
    }

    /**
     * @return array{earned: float, sections: list<array{name: string, points: int, earned: float, items: int, answered: int, correct: int}>}
     */
    public function score(PracticeSession $session): array
    {
        $scores = $this->scoresByPosition($session);
        $sections = [];

        foreach ($session->config['sections'] ?? [] as $section) {
            $positions = $section['positions'];
            $perItem = count($positions) > 0 ? $section['points'] / count($positions) : 0;

            $answered = collect($positions)->filter(fn ($p) => $scores->has($p));

            $sections[] = [
                'name' => $section['name'],
                'points' => (int) $section['points'],
                'earned' => round($answered->sum(fn ($p) => $perItem * $scores[$p]['score']), 2),
                'items' => count($positions),
                'answered' => $answered->count(),
                'correct' => $answered->filter(fn ($p) => $scores[$p]['is_correct'])->count(),
            ];
        }

        return [
            'earned' => round(array_sum(array_column($sections, 'earned')), 2),
            'sections' => $sections,
        ];
    }

    /**
     * Primul răspuns la fiecare poziție. Într-o simulare un item primește un
     * singur răspuns; dacă rețeaua a dublat cererea, idempotența din
     * SubmitAnswer a păstrat oricum un singur fapt.
     *
     * @return Collection<int, array{score: float, is_correct: bool, attempt: Attempt}>
     */
    public function scoresByPosition(PracticeSession $session): Collection
    {
        $attempts = Attempt::query()
            ->where('session_id', $session->id)
            ->orderBy('id')
            ->get()
            ->unique('item_id')
            ->keyBy('item_id');

        return $session->sessionItems()->get()
            ->filter(fn ($si) => $attempts->has($si->item_id))
            ->mapWithKeys(fn ($si) => [$si->position => [
                'score' => (float) $attempts[$si->item_id]->score,
                'is_correct' => (bool) $attempts[$si->item_id]->is_correct,
                'attempt' => $attempts[$si->item_id],
            ]]);
    }
}
