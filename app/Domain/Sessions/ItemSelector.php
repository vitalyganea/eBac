<?php

namespace App\Domain\Sessions;

use App\Domain\Rating\Elo;
use App\Enums\SessionMode;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Alege itemii pentru o sesiune.
 *
 * Ținta e ~70% șansă de succes — zona în care elevul învață cel mai eficient:
 * suficient de greu ca să conteze, suficient de ușor ca să nu abandoneze.
 * Rating-ul Elo pe item e ce face asta posibil; un simplu „difficulty: 1-5"
 * scris de autor nu ar ajunge.
 *
 * Amestecul e deliberat: o parte din itemi sunt repetiții programate (competențe
 * ajunse la scadență), restul sunt itemi noi calibrați pe nivelul elevului. Fără
 * partea de repetiție, elevul învață și uită în paralel.
 */
final class ItemSelector
{
    private const TARGET_SUCCESS_RATE = 0.70;

    private const REVIEW_SHARE = 0.35;

    private const SEEN_COOLDOWN_DAYS = 3;

    public function __construct(private Elo $elo) {}

    /**
     * @param  list<int>  $skillIds  Competențele țintă; gol = toată disciplina.
     * @return Collection<int, Item>
     */
    public function select(User $user, int $subjectId, SessionMode $mode, int $count, array $skillIds = []): Collection
    {
        // Rating-ul de item care produce ~70% șansă de succes pentru acest elev.
        $targetRating = $user->rating - 147;

        $reviewCount = $mode === SessionMode::Practice
            ? (int) round($count * self::REVIEW_SHARE)
            : 0;

        $review = $reviewCount > 0
            ? $this->dueForReview($user, $subjectId, $reviewCount, $skillIds)
            : collect();

        $fresh = $this->adaptive(
            $user,
            $subjectId,
            $mode,
            $count - $review->count(),
            $skillIds,
            $targetRating,
            $review->pluck('id')->all(),
        );

        return $review->concat($fresh)->shuffle()->values();
    }

    /** Itemi din competențe ajunse la scadență în repetiția spațiată. */
    private function dueForReview(User $user, int $subjectId, int $count, array $skillIds): Collection
    {
        $dueSkills = DB::table('mastery')
            ->where('user_id', $user->id)
            ->whereNotNull('next_review_at')
            ->where('next_review_at', '<=', now())
            ->when($skillIds !== [], fn ($q) => $q->whereIn('skill_id', $skillIds))
            ->orderBy('next_review_at')
            ->limit(20)
            ->pluck('skill_id');

        if ($dueSkills->isEmpty()) {
            return collect();
        }

        return Item::query()
            ->practicePool()
            ->where('subject_id', $subjectId)
            ->whereHas('skills', fn ($q) => $q->whereIn('skills.id', $dueSkills))
            ->with(['skills', 'stats'])
            ->inRandomOrder()
            ->limit($count)
            ->get();
    }

    /** Itemi calibrați pe nivelul elevului, excluzând ce a rezolvat recent. */
    private function adaptive(
        User $user,
        int $subjectId,
        SessionMode $mode,
        int $count,
        array $skillIds,
        float $targetRating,
        array $exclude,
    ): Collection {
        if ($count <= 0) {
            return collect();
        }

        // Itemii rezolvați corect recent nu se mai servesc: nu dau XP și nu învață
        // nimic pe nimeni.
        $recentlySolved = DB::table('attempts')
            ->where('user_id', $user->id)
            ->where('is_correct', true)
            ->where('answered_at', '>=', now()->subDays(self::SEEN_COOLDOWN_DAYS))
            ->pluck('item_id');

        return Item::query()
            ->when(
                $mode->usesExamHoldout(),
                // Simularea consumă itemii de holdout, ținuți deoparte special ca
                // estimarea de notă să rămână validă.
                fn ($q) => $q->examPool(),
                fn ($q) => $q->practicePool(),
            )
            ->where('subject_id', $subjectId)
            ->where('language', $user->locale ?? 'ro')
            ->when($skillIds !== [], fn ($q) => $q->whereHas('skills', fn ($s) => $s->whereIn('skills.id', $skillIds)))
            ->whereNotIn('items.id', $recentlySolved->concat($exclude)->unique()->all())
            ->leftJoin('item_stats', 'item_stats.item_id', '=', 'items.id')
            // Itemii semnalați automat ca posibil stricați nu ajung la elevi.
            ->where(fn ($q) => $q->where('item_stats.needs_review', false)->orWhereNull('item_stats.needs_review'))
            ->select('items.*')
            // Ordonăm după apropierea de rating-ul țintă; itemii necalibrați
            // (fără statistici) primesc prioritate medie, ca să se calibreze.
            ->orderByRaw('abs(coalesce(item_stats.rating, 1200) - ?) asc', [$targetRating])
            ->with(['skills', 'stats'])
            ->limit($count * 3)
            ->get()
            // Din cei mai apropiați de țintă alegem aleator, ca doi elevi cu
            // același rating să nu primească exact aceeași sesiune.
            ->shuffle()
            ->take($count);
    }
}
