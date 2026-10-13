<?php

namespace App\Jobs;

use App\Models\ItemStat;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Actualizează statisticile itemului și îi recalibrează rating-ul Elo.
 *
 * Aici se face și DETECTAREA AUTOMATĂ DE ITEMI STRICAȚI, al doilea mecanism de
 * control al calității după semnalările elevilor: dacă rata observată de succes
 * se abate mult de la ce prezice rating-ul itemului, itemul e probabil ambiguu
 * sau are cheia de corectare greșită. Îl marcăm `needs_review` și intră în coada
 * de validare din panoul de administrare.
 *
 * Fără acest mecanism, calitatea unei bănci de 10.000 de itemi nu e verificabilă
 * manual.
 */
class RecalibrateItem implements ShouldQueue
{
    use Queueable;

    /** Sub acest număr de răspunsuri, statistica e prea zgomotoasă pentru concluzii. */
    private const MIN_ATTEMPTS_FOR_FLAG = 25;

    public function __construct(
        public int $itemId,
        public float $score,
        public ?float $newRating = null,
    ) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping("item-stats:{$this->itemId}"))->expireAfter(60)];
    }

    public function handle(): void
    {
        $agg = DB::table('attempts')
            ->where('item_id', $this->itemId)
            ->where('is_ranked', true)
            ->selectRaw('count(*) as c, sum(case when is_correct then 1 else 0 end) as ok, avg(server_ms) as ms')
            ->first();

        $count = (int) ($agg->c ?? 0);
        $pCorrect = $count > 0 ? (int) $agg->ok / $count : null;

        $stat = ItemStat::firstOrNew(['item_id' => $this->itemId]);
        $stat->attempts_count = $count;
        $stat->correct_count = (int) ($agg->ok ?? 0);
        $stat->p_correct = $pCorrect !== null ? round($pCorrect, 4) : null;
        $stat->avg_ms = $agg->ms !== null ? (int) round($agg->ms) : null;

        if ($this->newRating !== null) {
            $stat->rating = round($this->newRating, 2);
            // Deviația scade cu volumul: cu cât mai multe răspunsuri, cu atât mai
            // sigur suntem de dificultatea reală.
            $stat->rating_deviation = max(50, 350 - min(300, $count * 3));
        }

        $stat->needs_review = $this->looksBroken($count, $pCorrect, (float) $stat->rating);
        $stat->last_calibrated_at = now();
        $stat->save();

        if ($stat->needs_review && $stat->wasChanged('needs_review')) {
            Log::warning('Item semnalat automat pentru revizuire', [
                'item_id' => $this->itemId,
                'p_correct' => $pCorrect,
                'rating' => $stat->rating,
                'attempts' => $count,
            ]);
        }
    }

    private function looksBroken(int $count, ?float $pCorrect, float $rating): bool
    {
        if ($count < self::MIN_ATTEMPTS_FOR_FLAG || $pCorrect === null) {
            return false;
        }

        // Aproape nimeni nu îl nimerește: cheie greșită sau enunț de nerezolvat.
        if ($pCorrect < 0.08) {
            return true;
        }

        // Îl nimeresc toți, deși e cotat ca dificil: probabil răspunsul e evident
        // din enunț sau distractorii sunt inutilizabili.
        if ($pCorrect > 0.97 && $rating > 1400) {
            return true;
        }

        return false;
    }
}
