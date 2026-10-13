<?php

namespace App\Domain\Attempts;

use App\Domain\Exams\MockClosed;
use App\Domain\Grading\AnswerKeyMask;
use App\Domain\Grading\GradeResult;
use App\Domain\Grading\GraderRegistry;
use App\Domain\Leaderboards\Leaderboard;
use App\Domain\Rating\Elo;
use App\Domain\Rating\EloResult;
use App\Domain\Xp\Level;
use App\Domain\Xp\XpAward;
use App\Domain\Xp\XpCalculator;
use App\Enums\SessionMode;
use App\Jobs\RecalibrateItem;
use App\Jobs\UpdateMastery;
use App\Models\Attempt;
use App\Models\Item;
use App\Models\ItemStat;
use App\Models\PracticeSession;
use App\Models\SessionItem;
use App\Models\SubjectStat;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Acțiunea centrală a aplicației. Nu are nimic de-a face cu HTTP.
 *
 * Web-ul (controller Inertia) și viitorul API mobil (controller JSON) apelează
 * exact această clasă, cu exact aceleași argumente. De asta stratul de transport
 * nu trebuie să conțină logică: nu vrem să reimplementăm corectarea și XP-ul a
 * doua oară pentru mobil.
 *
 * Împărțirea sincron / asincron e deliberată:
 *
 *   SINCRON (ce vede elevul imediat, < ~30 ms)
 *     - INSERT în attempts             ← faptul, sursa de adevăr
 *     - corectare + XP + delta de rating
 *     - ZINCRBY pe clasament           ← microsecunde
 *
 *   ASINCRON (Horizon)
 *     - recalcul mastery + repetiție spațiată
 *     - recalibrare Elo pe item + detectare de itemi stricați
 *     - verificare achievements
 *
 * Dacă recalculul de mastery ar sta în request, la trafic de vârf aplicația moare.
 */
final class SubmitAnswer
{
    public function __construct(
        private GraderRegistry $graders,
        private XpCalculator $xpCalculator,
        private Elo $elo,
        private Leaderboard $leaderboard,
        private AnswerKeyMask $mask,
    ) {}

    public function __invoke(User $user, SubmitAnswerData $data): SubmitAnswerResult
    {
        // --- Idempotency ---------------------------------------------------
        // Rețelele mobile duplică request-uri. Fără asta, un elev care intră în
        // tunel și retrimite lotul primește XP de două ori.
        if ($existing = Attempt::where('client_uuid', $data->clientUuid)->first()) {
            return $this->replayExisting($user, $existing);
        }

        $item = Item::with(['skills', 'stats'])->findOrFail($data->itemId);
        $session = $data->sessionUuid
            ? PracticeSession::where('uuid', $data->sessionUuid)->where('user_id', $user->id)->first()
            : null;

        $mode = $session?->mode ?? SessionMode::Practice;

        // --- Cronometraj autoritar -----------------------------------------
        // Diferența dintre momentul în care serverul a servit itemul și acum.
        // Timpul raportat de client rămâne doar informativ.
        // Același item poate apărea pe mai multe poziții într-o sesiune. Ne
        // interesează ocurența care a fost SERVITĂ și încă nu are răspuns —
        // altfel am putea nimeri un rând neservit și am pierde cronometrajul.
        $sessionItem = $session
            ? SessionItem::where('session_id', $session->id)
                ->where('item_id', $item->id)
                ->whereNull('answered_at')
                ->orderByRaw('served_at is null')
                ->orderBy('position')
                ->first()
            : null;

        // Simularea are termen și o singură încercare pe întrebare. Termenul e
        // al serverului (§5); timerul din browser se poate opri din DevTools.
        if ($mode === SessionMode::Mock) {
            $this->guardMock($session, $sessionItem);
        }

        $serverMs = $sessionItem?->served_at
            ? (int) max(0, $sessionItem->served_at->diffInMilliseconds(now()))
            : null;

        // Un răspuns dat offline nu poate fi cronometrat pe server, deci nu intră
        // în rating-ul competitiv — altfel oricine trimite 500 de răspunsuri
        // „rezolvate în 1.2 secunde" și e Master într-o zi.
        $isRanked = $mode->isRanked()
            && ! $data->source->isMobile()
            && $serverMs !== null;

        // --- Corectare (exclusiv pe server) --------------------------------
        // Clientul a văzut variantele sub jetoane opace; corectarea și faptul
        // din `attempts` folosesc cheile originale din bancă.
        $answer = $this->mask->unmaskAnswer($item, $data->answer);
        $grade = $this->graders->for($item->type)->grade($item, $answer);

        // Numărul de indicii vine din `session_items`, ștampilat de RevealHint —
        // NU din request. Altfel elevul citește indiciile și raportează zero,
        // păstrând tot XP-ul.
        $hintsUsed = $mode->allowsHints() ? (int) ($sessionItem->hints_used ?? 0) : 0;

        $xp = $this->xpCalculator->calculate($user, $item, $grade, $mode, $serverMs, $hintsUsed);

        $stats = $item->stats ?? new ItemStat(['item_id' => $item->id, 'rating' => 1200]);

        $eloResult = $isRanked
            ? $this->elo->apply(
                userRating: $user->rating,
                itemRating: (float) $stats->rating,
                score: $grade->score,
                userAttempts: (int) $user->getAttribute('ranked_attempts_count') ?: $stats->attempts_count,
                itemAttempts: (int) $stats->attempts_count,
            )
            : null;

        $attempt = DB::transaction(function () use (
            $user, $item, $session, $sessionItem, $data, $answer, $grade, $xp, $eloResult, $mode, $serverMs, $hintsUsed, $isRanked, $stats
        ) {
            $attempt = Attempt::create([
                'client_uuid' => $data->clientUuid,
                'user_id' => $user->id,
                'item_id' => $item->id,
                'session_id' => $session?->id,
                'skill_id' => $item->primarySkillId(),
                // Denormalizat din item: clasamentele și recalcularea per
                // disciplină nu trebuie să facă join pe un tabel de zeci de
                // milioane de rânduri.
                'subject_id' => $item->subject_id,
                'answer' => $answer,
                'is_correct' => $grade->isCorrect,
                'score' => $grade->score,
                'client_ms' => $data->clientMs,
                'server_ms' => $serverMs,
                'hints_used' => $hintsUsed,
                'answered_at' => $data->answeredAt(),
                'synced_at' => now(),
                'context' => $mode,
                'source' => $data->source,
                'app_version' => $data->appVersion,
                'is_ranked' => $isRanked,
                'xp_awarded' => $xp->amount,
                'rating_delta' => $eloResult?->userDelta(),
                'item_rating_before' => $stats->rating,
                'user_rating_before' => $user->rating,
            ]);

            $this->applyDerivedState($user, $xp, $eloResult, $data);
            $this->applySubjectStats($user, $item, $grade, $xp, $eloResult);

            if ($sessionItem && $sessionItem->answered_at === null) {
                $sessionItem->update(['answered_at' => now()]);
            }

            if ($session) {
                $session->increment('answered_items');
                if ($grade->isCorrect) {
                    $session->increment('correct_items');
                }
                $session->increment('xp_awarded', $xp->amount);
            }

            return $attempt;
        });

        // Clasamentul: O(log n), sigur de făcut sincron.
        //
        // XP-ul intră și în clasamentul disciplinei — acolo are sens comparația.
        // Un clasament global amestecă matematica cu engleza, care nu se compară.
        $this->leaderboard->addXp($user, $xp->amount, $item->subject_id);

        // Ratingul Elo NU mai pleacă într-un sorted set separat: nimic nu-l
        // citea de acolo. Valoarea care se afișează vine din
        // `subject_stats.rating`, scris în același pas de mai sus.

        // Tot ce e greu pleacă în coadă.
        UpdateMastery::dispatch($user->id, $attempt->skill_id, $attempt->id)->afterCommit();
        RecalibrateItem::dispatch($item->id, $grade->score, $eloResult?->itemRatingAfter)->afterCommit();

        return new SubmitAnswerResult(
            attempt: $attempt,
            grade: $grade,
            xp: $xp,
            elo: $eloResult,
            correctAnswer: $item->gradingKey(),
            explanation: $item->explanation,
            totals: $this->totals($user->refresh()),
        );
    }

    /**
     * Câteva secunde de toleranță pentru latența rețelei: un răspuns trimis în
     * ultima secundă nu trebuie pierdut pentru că a ajuns cu 300 ms mai târziu.
     */
    private const MOCK_GRACE_SECONDS = 30;

    private function guardMock(PracticeSession $session, ?SessionItem $sessionItem): void
    {
        if ($session->isComplete()) {
            throw new MockClosed('Lucrarea a fost deja predată.');
        }

        if ($session->expires_at?->copy()->addSeconds(self::MOCK_GRACE_SECONDS)->isPast()) {
            throw new MockClosed('Timpul simulării a expirat.');
        }

        // Un item deja răspuns nu mai are rând deschis: la examen nu primești
        // foaia înapoi ca să schimbi un răspuns după ce l-ai predat.
        if ($sessionItem === null) {
            throw new MockClosed('Întrebarea nu mai poate primi răspuns în această simulare.');
        }
    }

    /**
     * Actualizează starea derivată denormalizată pe user.
     * Rămâne reconstruibilă integral din `attempts`.
     */
    private function applyDerivedState(User $user, XpAward $xp, ?EloResult $elo, SubmitAnswerData $data): void
    {
        // Ziua se socotește în fusul elevului. Streak-ul se calculează pe
        // answered_at, nu pe momentul sincronizării: elevul care a învățat în
        // autobuz și a sincronizat dimineața nu trebuie să-și pierdă streak-ul.
        $tz = $user->timezone ?? config('ebac.default_timezone');
        $day = $data->answeredAt()->copy()->setTimezone($tz)->startOfDay();
        $today = $day->toDateString();
        $last = $user->last_active_date?->toDateString();

        if ($last !== $today) {
            $user->streak_days = $last === $day->copy()->subDay()->toDateString()
                ? $user->streak_days + 1
                : 1;
            $user->longest_streak = max($user->longest_streak, $user->streak_days);
            $user->last_active_date = $day;
        }

        if ($user->xp_today_date?->toDateString() !== $today) {
            $user->xp_today = 0;
            $user->xp_today_date = $day;
        }

        $user->xp += $xp->amount;
        $user->xp_today += $xp->amount;
        $user->level = Level::forXp($user->xp);

        if ($elo) {
            $user->rating = $elo->userRatingAfter;
            $user->rating_peak = max($user->rating_peak, $user->rating);
        }

        $user->save();
    }

    /** Curbă de nivel care încetinește progresiv: 100 XP pentru nivelul 2, ~24k pentru 50. */
    /**
     * XP, rating și acuratețe pe DISCIPLINĂ.
     *
     * Stare derivată, ca `mastery`: reconstruibilă din `attempts`. Există pentru
     * că întrebarea „al câtelea sunt la matematică" nu are răspuns dintr-un XP
     * global — acela amestecă discipline care nu se compară între ele.
     */
    private function applySubjectStats(
        User $user,
        Item $item,
        GradeResult $grade,
        XpAward $xp,
        ?EloResult $elo,
    ): void {
        if ($item->subject_id === null) {
            return;
        }

        $existing = SubjectStat::query()
            ->where('user_id', $user->id)
            ->where('subject_id', $item->subject_id)
            ->first();

        SubjectStat::query()->upsert([[
            'user_id' => $user->id,
            'subject_id' => $item->subject_id,
            'xp' => (int) ($existing->xp ?? 0) + $xp->amount,
            'rating' => $elo?->userRatingAfter ?? (int) ($existing->rating ?? 1000),
            'attempts_count' => (int) ($existing->attempts_count ?? 0) + 1,
            'correct_count' => (int) ($existing->correct_count ?? 0) + ($grade->isCorrect ? 1 : 0),
            'last_activity_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]], ['user_id', 'subject_id'], [
            'xp', 'rating', 'attempts_count', 'correct_count', 'last_activity_at', 'updated_at',
        ]);
    }

    private function totals(User $user): array
    {
        return [
            'xp' => $user->xp,
            'level' => $user->level,
            'rating' => $user->rating,
            'streak_days' => $user->streak_days,
            'xp_today' => $user->xp_today,
            'daily_cap' => XpCalculator::DAILY_CAP,
        ];
    }

    /**
     * Un retry de rețea nu trebuie să acorde XP de două ori, dar clientul are
     * totuși nevoie de rezultat ca să afișeze ecranul de feedback.
     */
    private function replayExisting(User $user, Attempt $attempt): SubmitAnswerResult
    {
        $item = $attempt->item;

        return new SubmitAnswerResult(
            attempt: $attempt,
            grade: new GradeResult($attempt->is_correct, $attempt->score),
            xp: new XpAward($attempt->xp_awarded, ['replay' => true]),
            elo: null,
            correctAnswer: $item?->gradingKey(),
            explanation: $item?->explanation,
            totals: $this->totals($user),
            isDuplicate: true,
        );
    }
}
