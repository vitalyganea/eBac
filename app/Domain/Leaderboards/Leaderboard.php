<?php

namespace App\Domain\Leaderboards;

use App\Domain\Xp\Level;
use App\Models\SubjectStat;
use App\Models\User;
use Illuminate\Support\Facades\Redis;

/**
 * Clasamentele live, în sorted sets Redis.
 *
 * `ORDER BY xp DESC LIMIT 100` pe zeci de mii de rânduri, la fiecare afișare de
 * profil, e una dintre primele lucruri care se rup în producție. ZINCRBY /
 * ZREVRANK / ZCARD sunt O(log n) sau O(1) — microsecunde.
 *
 * Redis e CACHE, nu sursă de adevăr: totul se poate reconstrui din
 * `subject_stats`, care la rândul lui se reconstruiește din `attempts`.
 */
final class Leaderboard
{
    /**
     * Adaugă XP în clasamentul disciplinei.
     *
     * O singură scriere, pentru că un singur clasament se și citește: „la
     * matematică sunt al 34-lea din 938" e o informație despre elev, în timp ce
     * un XP global amestecă discipline care nu se compară între ele (§10b).
     *
     * Au existat aici și scrieri pe `global`, `daily` și `weekly`. Le-am scos
     * pentru că nimic nu le citea: trei ZINCRBY pe calea SINCRONĂ a fiecărui
     * răspuns (§4), care umpleau Redis cu sorted sets pe care nu-i deschidea
     * nimeni — inclusiv câte unul nou pe zi, la nesfârșit, fără expirare.
     */
    public function addXp(User $user, int $xp, int $subjectId): void
    {
        if ($xp <= 0 || ! $user->leaderboard_opt_in) {
            return;
        }

        Redis::zincrby($this->subjectKey($subjectId), $xp, (string) $user->id);
    }

    /**
     * Poziția elevului la o disciplină, cu totalul participanților.
     *
     * Întoarce null dacă nu are încă XP acolo — nu îl arătăm „ultimul din 938"
     * înainte să fi răspuns la ceva.
     *
     * @return array{rank: int, total: int, xp: int}|null
     */
    public function subjectRank(User $user, int $subjectId): ?array
    {
        $key = $this->subjectKey($subjectId);
        $member = (string) $user->id;

        $rank = Redis::zrevrank($key, $member);

        if ($rank === null || $rank === false) {
            return null;
        }

        return [
            'rank' => (int) $rank + 1,
            'total' => (int) Redis::zcard($key),
            'xp' => (int) Redis::zscore($key, $member),
        ];
    }

    /**
     * Clasamentul afișabil al unei discipline: primii N, plus rândul propriu
     * dacă elevul e în afara lor.
     *
     * Fără al doilea rând, un elev de pe locul 340 vede doar necunoscuți și
     * închide pagina. Cu el, vede exact unde se află și cât îl desparte de
     * următorul.
     *
     * @return array{entries: list<array>, me: array|null, total: int}
     */
    public function subjectBoard(User $me, int $subjectId, int $limit = 10): array
    {
        $key = $this->subjectKey($subjectId);
        $top = $this->top($key, $limit);

        $myRank = $this->subjectRank($me, $subjectId);
        $topIds = collect($top)->pluck('user_id');
        $inTop = $topIds->contains($me->id);

        $ids = $myRank !== null && ! $inTop ? $topIds->push($me->id) : $topIds;

        // O singură interogare pentru toate numele — nu N+1 în buclă.
        // Selecția restrânsă trebuie să includă TOT ce citesc publicName() și
        // avatarUrl(). O coloană lipsă nu dă eroare — metoda o vede ca null și
        // returnează tăcut altceva decât trebuie.
        $users = User::whereKey($ids)
            ->get(['id', 'username', 'public_alias', 'username_status',
                'avatar', 'avatar_path', 'avatar_status'])
            ->keyBy('id');

        // `$users->get(...)`, nu `$users[...]`: Collection::offsetGet face acces
        // brut la array, deci o cheie lipsă e un warning („Undefined array key"),
        // nu null — iar `?->` nu are ce prinde și pagina dă 500.
        //
        // Cheia lipsește ori de câte ori Redis e mai vechi decât baza: un cont
        // șters prin PurgeUserData, sau un `migrate:fresh` peste un Redis care
        // persistă. Redis e cache, nu sursă de adevăr (§10b) — citirea trebuie
        // să supraviețuiască faptului că a rămas în urmă.
        $present = function (array $row) use ($users, $me): ?array {
            $user = $users->get($row['user_id']);

            // Un cont care nu mai există nu primește rând-fantomă: ar arăta ca
            // un concurent pe care elevul nu-l poate ajunge niciodată, iar
            // scorul unui cont șters n-are ce căuta afișat. Dispare la
            // următorul rebuildSubject().
            if ($user === null) {
                return null;
            }

            return [
                'rank' => $row['rank'],
                'username' => $user->publicName(),
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatarUrl(64),
                'xp' => $row['score'],
                'level' => Level::forXp($row['score']),
                'is_me' => $row['user_id'] === $me->id,
            ];
        };

        return [
            // `array_values` după filtrare: props-ul pleacă spre Inertia ca JSON,
            // iar un array cu goluri în chei s-ar serializa ca obiect, nu listă.
            'entries' => array_values(array_filter(array_map($present, $top))),
            // Rândul propriu apare separat DOAR dacă elevul nu e deja în top.
            'me' => $myRank !== null && ! $inTop
                ? $present(['rank' => $myRank['rank'], 'user_id' => $me->id, 'score' => $myRank['xp']])
                : null,
            'total' => (int) Redis::zcard($key),
        ];
    }

    /**
     * Reconstruiește clasamentul unei discipline din baza de date.
     *
     * Redis se poate goli oricând — la repornire, la evacuare din memorie, la o
     * greșeală de operare. Clasamentul nu are voie să depindă de asta.
     */
    public function rebuildSubject(int $subjectId): int
    {
        $key = $this->subjectKey($subjectId);
        Redis::del($key);

        $count = 0;

        SubjectStat::query()
            ->where('subject_id', $subjectId)
            ->where('xp', '>', 0)
            ->whereHas('user', fn ($q) => $q->where('leaderboard_opt_in', true))
            ->chunkById(1000, function ($rows) use ($key, &$count) {
                foreach ($rows as $row) {
                    Redis::zadd($key, $row->xp, (string) $row->user_id);
                    $count++;
                }
            }, 'user_id');

        return $count;
    }

    /** @return list<array{user_id: int, score: int, rank: int}> */
    private function top(string $key, int $limit): array
    {
        $rows = Redis::zrevrange($key, 0, $limit - 1, ['withscores' => true]);

        $out = [];
        $rank = 1;
        foreach ($rows as $userId => $score) {
            $out[] = ['user_id' => (int) $userId, 'score' => (int) $score, 'rank' => $rank++];
        }

        return $out;
    }

    /**
     * Singura cheie rămasă. Forma păstrează prefixele vechi (`lb:xp:subject:`),
     * ca instalările existente să nu-și piardă clasamentele la deploy.
     */
    private function subjectKey(int $subjectId): string
    {
        return "lb:xp:subject:{$subjectId}";
    }
}
