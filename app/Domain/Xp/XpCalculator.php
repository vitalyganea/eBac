<?php

namespace App\Domain\Xp;

use App\Domain\Grading\GradeResult;
use App\Enums\SessionMode;
use App\Models\Attempt;
use App\Models\Item;
use App\Models\User;

/**
 * XP-ul premiază ÎNVĂȚAREA, nu volumul.
 *
 * Gamificarea prost făcută produce elevi care dau click repede și memorează
 * tipare. Regulile de mai jos sunt scrise anume ca „mai multe click-uri" să nu
 * fie o strategie:
 *
 *   1. Un item la care ai răspuns corect recent nu mai dă XP.
 *      Plătim pentru informație nouă sau uitată, nu pentru repetare mecanică.
 *   2. Bonus mare pentru RETENȚIE: răspunzi corect la un item pe care l-ai
 *      greșit acum ≥7 zile. Asta e învățare reală.
 *   3. Bonusul de viteză se acordă doar la itemii de recunoaștere (formule,
 *      definiții). La raționament viteza nu se premiază — vrem să gândească.
 *   4. Cap zilnic: leaderboard-ul măsoară consecvență, nu timp liber.
 *   5. Indiciile reduc XP-ul, dar nu îl anulează — cerem ajutor, nu renunțăm.
 */
final class XpCalculator
{
    public const DAILY_CAP = 800;

    private const BASE_CORRECT = 100;

    private const BASE_EFFORT = 15;          // și un răspuns greșit e efort

    private const SPEED_BONUS_MAX = 25;

    private const RETENTION_BONUS = 60;

    private const RETENTION_WINDOW_DAYS = 7;

    private const COOLDOWN_DAYS = 3;         // fereastra în care un item nu mai dă XP

    /**
     * Penalizarea pe indicii, în trepte.
     *
     * Nu ajunge niciodată la 100%: un elev care a cerut trei indicii și apoi a
     * rezolvat corect a învățat ceva. Vrem să ceară ajutor, nu să abandoneze —
     * de asta indiciile costă, dar nu anulează.
     */
    private const HINT_PENALTY = [1 => 0.20, 2 => 0.35, 3 => 0.50];

    private const HINT_PENALTY_MAX = 0.60;

    public function calculate(
        User $user,
        Item $item,
        GradeResult $grade,
        SessionMode $mode,
        ?int $serverMs,
        int $hintsUsed = 0,
    ): XpAward {
        $reasons = [];

        // 1. Cooldown pe item: dacă a fost rezolvat corect recent, nu mai dă XP.
        if ($grade->isCorrect && $this->answeredCorrectlyRecently($user, $item)) {
            return new XpAward(0, ['cooldown' => 'item rezolvat corect recent']);
        }

        $xp = $grade->isCorrect
            ? (int) round(self::BASE_CORRECT * $grade->score)
            : (int) round(self::BASE_EFFORT + (self::BASE_CORRECT * 0.4 * $grade->score));

        $reasons['base'] = $xp;

        // 2. Bonus de retenție — cel mai valoros semnal de învățare.
        if ($grade->isCorrect && $this->recoveredFromPastMistake($user, $item)) {
            $xp += self::RETENTION_BONUS;
            $reasons['retention'] = self::RETENTION_BONUS;
        }

        // 3. Viteza, doar unde are sens pedagogic.
        if ($grade->isCorrect && $item->type->rewardsSpeed() && $serverMs !== null) {
            $bonus = $this->speedBonus($serverMs);
            if ($bonus > 0) {
                $xp += $bonus;
                $reasons['speed'] = $bonus;
            }
        }

        // 5. Penalizare pentru indicii — proporțională cu câte au fost cerute.
        if ($hintsUsed > 0) {
            $rate = self::HINT_PENALTY[$hintsUsed] ?? self::HINT_PENALTY_MAX;
            $penalty = (int) round($xp * $rate);
            $xp -= $penalty;
            $reasons['hints'] = -$penalty;
            $reasons['hints_used'] = $hintsUsed;
        }

        // Modurile competitive dau puțin mai mult, ca să merite intrarea în arenă.
        if ($mode->isRanked()) {
            $bonus = (int) round($xp * 0.15);
            $xp += $bonus;
            $reasons['ranked'] = $bonus;
        }

        // 4. Cap zilnic.
        $usedToday = $this->xpUsedToday($user);
        $remaining = max(0, self::DAILY_CAP - $usedToday);

        if ($xp > $remaining) {
            return new XpAward($remaining, $reasons + ['daily_cap' => self::DAILY_CAP], true);
        }

        return new XpAward(max(0, $xp), $reasons);
    }

    private function speedBonus(int $serverMs): int
    {
        // Sub 3 secunde: bonus complet. Peste 15: zero. Liniar între ele.
        if ($serverMs <= 3_000) {
            return self::SPEED_BONUS_MAX;
        }
        if ($serverMs >= 15_000) {
            return 0;
        }

        return (int) round(self::SPEED_BONUS_MAX * (15_000 - $serverMs) / 12_000);
    }

    private function answeredCorrectlyRecently(User $user, Item $item): bool
    {
        return Attempt::query()
            ->where('user_id', $user->id)
            ->where('item_id', $item->id)
            ->where('is_correct', true)
            ->where('answered_at', '>=', now()->subDays(self::COOLDOWN_DAYS))
            ->exists();
    }

    private function recoveredFromPastMistake(User $user, Item $item): bool
    {
        return Attempt::query()
            ->where('user_id', $user->id)
            ->where('item_id', $item->id)
            ->where('is_correct', false)
            ->where('answered_at', '<=', now()->subDays(self::RETENTION_WINDOW_DAYS))
            ->exists();
    }

    private function xpUsedToday(User $user): int
    {
        // Ziua se socotește în fusul elevului, nu al serverului.
        $today = now($user->timezone ?? config('ebac.default_timezone'))->toDateString();

        return $user->xp_today_date?->toDateString() === $today ? $user->xp_today : 0;
    }
}
