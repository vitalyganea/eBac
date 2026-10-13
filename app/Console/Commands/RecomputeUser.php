<?php

namespace App\Console\Commands;

use App\Domain\Xp\Level;
use App\Jobs\UpdateMastery;
use App\Models\Attempt;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reconstruiește toată starea derivată a unui utilizator, exclusiv din `attempts`.
 *
 * Ăsta e testul care dovedește că arhitectura ține: dacă această comandă poate
 * reconstrui XP, nivel, streak și mastery de la zero, atunci `attempts` e
 * într-adevăr singura sursă de adevăr — iar formula de XP se poate schimba
 * oricând printr-o recalculare, nu printr-o migrație distructivă.
 *
 * Rulează-o și după orice import de răspunsuri sincronizate din aplicația mobilă.
 */
class RecomputeUser extends Command
{
    protected $signature = 'ebac:recompute-user
                            {user? : ID-ul utilizatorului (gol = toți)}
                            {--chunk=1000 : Câte attempts se procesează odată}';

    protected $description = 'Reconstruiește XP, nivel, streak și mastery din log-ul de attempts';

    public function handle(): int
    {
        $users = $this->argument('user')
            ? User::whereKey($this->argument('user'))->get()
            : User::query()->get();

        if ($users->isEmpty()) {
            $this->error('Niciun utilizator găsit.');

            return self::FAILURE;
        }

        foreach ($users as $user) {
            $this->recompute($user);
            $this->line("  ✔ {$user->publicName()} — {$user->xp} XP, nivel {$user->level}, streak {$user->streak_days}");
        }

        $this->info("Reconstruit pentru {$users->count()} utilizator(i).");

        return self::SUCCESS;
    }

    private function recompute(User $user): void
    {
        $totals = Attempt::where('user_id', $user->id)
            ->selectRaw('coalesce(sum(xp_awarded), 0) as xp')
            ->first();

        $xp = (int) $totals->xp;

        // Streak-ul se reconstruiește din zilele DISTINCTE de activitate, în fusul
        // elevului, pe `answered_at` — nu pe momentul sincronizării.
        $tz = $user->timezone ?? config('ebac.default_timezone');
        $days = Attempt::where('user_id', $user->id)
            ->selectRaw('distinct date(answered_at at time zone ?) as d', [$tz])
            ->orderByDesc('d')
            ->pluck('d')
            ->map(fn ($d) => Carbon::parse($d));

        [$current, $longest] = $this->streaks($days->all());

        $user->forceFill([
            'xp' => $xp,
            'level' => Level::forXp($xp),
            'streak_days' => $current,
            'longest_streak' => $longest,
            'last_active_date' => $days->first(),
        ])->save();

        // XP și rating pe disciplină — baza clasamentelor pe disciplină.
        DB::table('subject_stats')->where('user_id', $user->id)->delete();
        DB::statement('
            INSERT INTO subject_stats
                (user_id, subject_id, xp, attempts_count, correct_count, last_activity_at, created_at, updated_at)
            SELECT user_id, subject_id,
                   coalesce(sum(xp_awarded), 0),
                   count(*),
                   sum(case when is_correct then 1 else 0 end),
                   max(answered_at), now(), now()
            FROM attempts
            WHERE user_id = ? AND subject_id IS NOT NULL
            GROUP BY user_id, subject_id
        ', [$user->id]);

        // Mastery-ul se reconstruiește prin aceleași joburi folosite în producție,
        // ca să nu existe două implementări care pot divergea.
        DB::table('mastery')->where('user_id', $user->id)->delete();

        Attempt::where('user_id', $user->id)
            ->whereNotNull('skill_id')
            ->distinct()
            ->pluck('skill_id')
            ->each(fn ($skillId) => UpdateMastery::dispatchSync($user->id, $skillId, 0));
    }

    /**
     * @param  list<Carbon>  $days  ordonate descrescător
     * @return array{0: int,1: int}
     */
    private function streaks(array $days): array
    {
        if ($days === []) {
            return [0, 0];
        }

        $longest = 1;
        $run = 1;

        for ($i = 1; $i < count($days); $i++) {
            if ($days[$i]->diffInDays($days[$i - 1]) === 1) {
                $run++;
                $longest = max($longest, $run);
            } else {
                $run = 1;
            }
        }

        // Streak-ul curent contează doar dacă include azi sau ieri.
        $daysSinceLast = $days[0]->diffInDays(now($days[0]->timezone)->startOfDay());
        $current = $daysSinceLast <= 1 ? $this->runFromStart($days) : 0;

        return [$current, $longest];
    }

    /** @param list<Carbon> $days */
    private function runFromStart(array $days): int
    {
        $run = 1;
        for ($i = 1; $i < count($days); $i++) {
            if ($days[$i]->diffInDays($days[$i - 1]) !== 1) {
                break;
            }
            $run++;
        }

        return $run;
    }
}
