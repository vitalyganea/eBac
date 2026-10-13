<?php

namespace App\Domain\Attempts;

use App\Domain\Users\ProcessAvatar;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Ștergerea datelor unui utilizator, la cerere.
 *
 * Există separat pentru că modelul Attempt refuză DELETE: log-ul de fapte e
 * imuabil prin construcție. Ștergerea la cerere e însă o obligație legală
 * (Legea nr. 133/2011 privind protecția datelor cu caracter personal), iar
 * utilizatorii noștri sunt în mare parte minori.
 *
 * Rulează la nivel de utilizator, într-o singură tranzacție, ocolind modelul —
 * exact ca să fie o operațiune deliberată și auditabilă, nu un `$attempt->delete()`
 * scăpat undeva într-un controller.
 */
final class PurgeUserData
{
    public function __construct(private ProcessAvatar $avatars) {}

    public function __invoke(User $user, bool $keepAggregates = false): void
    {
        // Fișierele nu stau în baza de date, deci nu dispar odată cu rândurile.
        $this->avatars->delete($user);

        DB::transaction(function () use ($user, $keepAggregates) {
            // Ordinea respectă cheile străine.
            DB::table('attempts')->where('user_id', $user->id)->delete();
            DB::table('session_items')
                ->whereIn('session_id', DB::table('practice_sessions')->where('user_id', $user->id)->select('id'))
                ->delete();
            DB::table('practice_sessions')->where('user_id', $user->id)->delete();
            DB::table('mastery')->where('user_id', $user->id)->delete();
            DB::table('user_achievements')->where('user_id', $user->id)->delete();
            DB::table('devices')->where('user_id', $user->id)->delete();
            DB::table('leaderboard_snapshots')->where('user_id', $user->id)->delete();
            DB::table('item_reports')->where('user_id', $user->id)->update(['user_id' => null]);

            if ($keepAggregates) {
                // Contul rămâne, dar golit: util când elevul vrea să repornească
                // de la zero fără să-și pierdă accesul.
                $user->forceFill([
                    'xp' => 0, 'level' => 1, 'rating' => 1000, 'rating_peak' => 1000,
                    'streak_days' => 0, 'longest_streak' => 0, 'last_active_date' => null,
                    'xp_today' => 0, 'xp_today_date' => null,
                ])->save();

                return;
            }

            $user->tokens()->delete();
            $user->delete();
        });
    }
}
