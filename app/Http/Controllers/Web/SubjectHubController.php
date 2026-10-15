<?php

namespace App\Http\Controllers\Web;

use App\Domain\Exams\BlueprintResolver;
use App\Domain\Exams\MockItemPicker;
use App\Domain\Leaderboards\Leaderboard;
use App\Domain\Xp\Level;
use App\Enums\SessionMode;
use App\Models\Formula;
use App\Models\LearningGap;
use App\Models\Lesson;
use App\Models\PracticeSession;
use App\Models\Subject;
use App\Models\SubjectStat;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pagina unei discipline pentru elevul autentificat.
 *
 * Nu confunda cu `Web\Pub\PublicController::subject()` — aceea e pagina publică,
 * indexabilă, pentru vizitatori anonimi. Aceasta e spațiul de lucru: progresul
 * tău, ce poți face aici și unde te afli față de ceilalți.
 *
 * Zonele, în ordinea în care le folosește elevul:
 *   Curs       — teoria (în construcție)
 *   Teste      — antrenamentul, funcțional
 *   Formule    — de consultat și de învățat („ascunde și verifică")
 *   Simulare   — structura examenului pe itemi de holdout, cu termen
 *   Clasament  — poziția la ACEASTĂ disciplină
 */
class SubjectHubController extends Controller
{
    public function __invoke(
        Request $request,
        string $slug,
        Leaderboard $leaderboard,
        BlueprintResolver $blueprints,
        MockItemPicker $picker,
    ): Response {
        $user = $request->user();
        $subject = Subject::where('slug', $slug)->firstOrFail();

        // Disciplinele se aleg explicit; una nealeasă nu are progres de arătat.
        abort_unless($user->hasSubject($subject->id), 404);

        $stat = SubjectStat::where('user_id', $user->id)
            ->where('subject_id', $subject->id)
            ->first();

        $xp = (int) ($stat->xp ?? 0);

        $itemCount = $subject->items()->practicePool()->count();

        $lessonCount = Lesson::published()
            ->whereHas('skill', fn ($q) => $q->where('subject_id', $subject->id))
            ->count();

        $formulaCount = Formula::where('subject_id', $subject->id)->count();

        return Inertia::render('Subjects/Show', [
            'subject' => [
                'slug' => $subject->slug,
                'name' => $subject->localizedName($user->locale ?? 'ro'),
                'icon' => $subject->iconName(),
            ],

            // XP și nivel PE DISCIPLINĂ. Un total global ar amesteca matematica
            // cu engleza, care nu se compară.
            'progress' => Level::progress($xp),

            'stats' => [
                'attempts' => (int) ($stat->attempts_count ?? 0),
                'correct' => (int) ($stat->correct_count ?? 0),
                'accuracy' => $stat?->accuracy(),
                'rating' => (int) ($stat->rating ?? 1000),
                'last_activity_at' => $stat?->last_activity_at?->toIso8601String(),
            ],

            'rank' => $leaderboard->subjectRank($user, $subject->id),

            'open_gaps' => LearningGap::where('user_id', $user->id)
                ->whereNull('resolved_at')
                ->whereHas('skill', fn ($q) => $q->where('subject_id', $subject->id))
                ->count(),

            'sections' => [
                // Cursul apare când există cel puțin o lecție publicată la
                // disciplină; altfel rămâne vizibil, dar marcat ca în pregătire.
                'course' => [
                    'available' => $lessonCount > 0,
                    'lessons_count' => $lessonCount,
                ],
                'tests' => ['available' => $itemCount > 0, 'items_count' => $itemCount],
                'formulas' => ['available' => $formulaCount > 0, 'count' => $formulaCount],
                'mock' => $this->mockSection($user, $subject->id, $blueprints, $picker),
            ],

            'board' => Inertia::defer(fn () => $leaderboard->subjectBoard($user, $subject->id)),
        ]);
    }

    /**
     * Simularea: ce acoperă, dacă se poate porni și rezultatele anterioare.
     *
     * Ca și cursul, apare și când nu se poate porni — cu motivul real, nu
     * ascunsă (§2a): elevul vede unde merge produsul.
     */
    private function mockSection(User $user, int $subjectId, BlueprintResolver $blueprints, MockItemPicker $picker): array
    {
        $plan = $blueprints->planFor($user, $subjectId);

        if ($plan === null) {
            return ['available' => false, 'reason' => 'Structura examenului e în pregătire.', 'plan' => null, 'history' => []];
        }

        $reason = $picker->check($user, $subjectId, $plan);

        $history = PracticeSession::query()
            ->where('user_id', $user->id)
            ->where('subject_id', $subjectId)
            ->where('mode', SessionMode::Mock)
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->limit(5)
            ->get(['uuid', 'score', 'config', 'completed_at'])
            ->map(fn (PracticeSession $s) => [
                'uuid' => $s->uuid,
                'score' => (float) $s->score,
                'auto_points' => (int) ($s->config['auto_points'] ?? 0),
                'completed_at' => $s->completed_at->toIso8601String(),
            ]);

        return [
            'available' => $reason === null,
            'reason' => $reason,
            'plan' => $plan->summary(),
            'history' => $history,
        ];
    }
}
