<?php

namespace App\Jobs;

use App\Models\Attempt;
use App\Models\Mastery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;

/**
 * Recalculează mastery-ul și programarea repetiției spațiate pentru o competență.
 *
 * Rulează asincron: nu are ce căuta în request-ul de răspuns, unde ar transforma
 * 30 ms în 300 ms la trafic de vârf.
 *
 * Mastery-ul e o medie PONDERATĂ CU TIMPUL a corectitudinii: răspunsurile
 * recente cântăresc mai mult, ca profilul elevului să reflecte ce știe ACUM, nu
 * ce știa în septembrie.
 */
class UpdateMastery implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId,
        public ?int $skillId,
        public int $attemptId,
    ) {}

    public function middleware(): array
    {
        // Două răspunsuri consecutive pe aceeași competență nu trebuie să se
        // calce reciproc pe rând.
        return [(new WithoutOverlapping("mastery:{$this->userId}:{$this->skillId}"))->expireAfter(60)];
    }

    public function handle(): void
    {
        if ($this->skillId === null) {
            return;
        }

        // Ultimele 20 de răspunsuri pe competență, cele mai recente cu greutate mai mare.
        $attempts = Attempt::query()
            ->where('user_id', $this->userId)
            ->where('skill_id', $this->skillId)
            ->orderByDesc('answered_at')
            ->limit(20)
            ->get(['is_correct', 'score', 'answered_at']);

        if ($attempts->isEmpty()) {
            return;
        }

        $weightSum = 0.0;
        $scoreSum = 0.0;
        foreach ($attempts as $i => $attempt) {
            $weight = 0.9 ** $i;           // decădere geometrică
            $weightSum += $weight;
            $scoreSum += $weight * $attempt->score;
        }

        $mastery = $weightSum > 0 ? $scoreSum / $weightSum : 0.0;

        $totals = Attempt::query()
            ->where('user_id', $this->userId)
            ->where('skill_id', $this->skillId)
            ->selectRaw('count(*) as c, sum(case when is_correct then 1 else 0 end) as ok')
            ->first();

        // Încrederea crește cu volumul: 8 răspunsuri ≈ 0.55, 30 ≈ 0.95.
        $confidence = min(1.0, sqrt(min(30, (int) $totals->c) / 30));

        $streak = 0;
        foreach ($attempts as $attempt) {
            if (! $attempt->is_correct) {
                break;
            }
            $streak++;
        }

        $existing = DB::table('mastery')
            ->where('user_id', $this->userId)
            ->where('skill_id', $this->skillId)
            ->first();

        [$ease, $interval] = $this->schedule(
            (float) ($existing->ease_factor ?? 2.5),
            (int) ($existing->interval_days ?? 0),
            $attempts->first()->score,
        );

        Mastery::query()->upsert([[
            'user_id' => $this->userId,
            'skill_id' => $this->skillId,
            'mastery' => round($mastery, 4),
            'confidence' => round($confidence, 4),
            'attempts_count' => (int) $totals->c,
            'correct_count' => (int) $totals->ok,
            'correct_streak' => $streak,
            'ease_factor' => $ease,
            'interval_days' => $interval,
            'next_review_at' => now()->addDays($interval),
            'last_practiced_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]], ['user_id', 'skill_id'], [
            'mastery', 'confidence', 'attempts_count', 'correct_count', 'correct_streak',
            'ease_factor', 'interval_days', 'next_review_at', 'last_practiced_at', 'updated_at',
        ]);
    }

    /**
     * SM-2 simplificat. Intervalul crește când elevul răspunde corect și se
     * resetează la 1 zi când greșește.
     *
     * @return array{0: float, 1: int}
     */
    private function schedule(float $ease, int $interval, float $score): array
    {
        if ($score < 0.6) {
            return [max(1.3, $ease - 0.20), 1];
        }

        $ease = min(2.8, $ease + ($score >= 0.99 ? 0.10 : 0.0));

        $next = match (true) {
            $interval === 0 => 1,
            $interval === 1 => 3,
            default => (int) round($interval * $ease),
        };

        // Nu programăm repetiții peste orizontul examenului.
        return [$ease, min(120, $next)];
    }
}
