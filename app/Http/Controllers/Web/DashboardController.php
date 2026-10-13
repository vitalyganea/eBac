<?php

namespace App\Http\Controllers\Web;

use App\Domain\Leaderboards\Leaderboard;
use App\Models\LearningGap;
use App\Models\Season;
use App\Models\Subject;
use App\Models\SubjectStat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, Leaderboard $leaderboard): Response|RedirectResponse
    {
        $user = $request->user();

        // Fără discipline alese nu avem ce afișa: îl trimitem să aleagă.
        if ($user->subjects()->doesntExist()) {
            return to_route('subjects.edit');
        }

        // XP-ul pe disciplină, într-o singură interogare.
        $stats = SubjectStat::where('user_id', $user->id)
            ->get()
            ->keyBy('subject_id');

        // Nelămuririle, numărate pe disciplină, într-o singură interogare.
        $gaps = LearningGap::query()
            ->where('learning_gaps.user_id', $user->id)
            ->whereNull('resolved_at')
            ->join('skills', 'skills.id', '=', 'learning_gaps.skill_id')
            ->selectRaw('skills.subject_id, count(*) as total')
            ->groupBy('skills.subject_id')
            ->pluck('total', 'subject_id');

        $subjects = $user->subjects()
            ->withCount(['items as practice_items_count' => fn ($q) => $q->practicePool()])
            ->get()
            ->map(function (Subject $s) use ($stats, $gaps, $leaderboard, $user) {
                $stat = $stats->get($s->id);

                return [
                    'slug' => $s->slug,
                    'name' => $s->localizedName($user->locale ?? 'ro'),
                    'icon' => $s->iconName(),
                    'is_primary' => (bool) $s->pivot->is_primary,
                    'items_count' => $s->practice_items_count,
                    'is_playable' => $s->practice_items_count > 0,

                    'xp' => (int) ($stat->xp ?? 0),
                    'accuracy' => $stat?->accuracy(),

                    // „Al 34-lea din 938." Vine din Redis: ZREVRANK + ZCARD,
                    // deci nu costă nimic să fie afișat pe fiecare card.
                    // null = încă niciun răspuns la disciplina asta.
                    'rank' => $leaderboard->subjectRank($user, $s->id),

                    // Vizibil pe cardul disciplinei: altfel lista se umple și
                    // elevul uită că există.
                    'open_gaps' => (int) ($gaps[$s->id] ?? 0),
                ];
            });

        $season = Season::where('is_active', true)->first();

        return Inertia::render('Dashboard', [
            'subjects' => $subjects,

            // Ancora planului invers: cu 200 de zile rămase învățăm materie nouă,
            // cu 10 zile rămase reparăm doar găurile.
            'exam' => $season ? [
                'name' => $season->name,
                'days_left' => $season->daysUntilExam(),
                'phase' => $this->phaseFor($season->daysUntilExam()),
            ] : null,

            'leaderboard_opt_in' => (bool) $user->leaderboard_opt_in,
        ]);
    }

    private function phaseFor(?int $daysLeft): ?string
    {
        return match (true) {
            $daysLeft === null => null,
            $daysLeft > 90 => 'acumulare',
            $daysLeft > 45 => 'consolidare',
            $daysLeft > 14 => 'simulari',
            $daysLeft > 3 => 'puncte-slabe',
            default => 'formule',
        };
    }
}
