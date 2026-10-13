<?php

namespace App\Domain\Moderation;

use App\Models\ContentReport;
use App\Models\User;

/**
 * Înregistrează o raportare asupra conținutului public al unui utilizator.
 *
 * Raportarea nu e o pedeapsă și nu presupune vinovăție — e un semnal. Acțiunea o
 * ia un om, din coada de moderare.
 *
 * Excepția: dacă mai mulți elevi raportează INDEPENDENT același avatar,
 * probabilitatea unei probleme reale e mare, iar el se ascunde automat până când
 * cineva se uită. Ascunderea nu șterge fișierul și nu afectează contul —
 * moderatorul poate reveni asupra ei.
 */
final class ReportContent
{
    /** @param 'avatar'|'username' $subject */
    public function __invoke(
        User $reporter,
        User $target,
        string $subject,
        string $reason,
        ?string $comment = null,
    ): ContentReport {
        if ($reporter->id === $target->id) {
            throw new ReportRejected('Nu îți poți raporta propriul conținut.');
        }

        if ($subject === 'avatar' && blank($target->avatar_path)) {
            throw new ReportRejected('Utilizatorul nu are o imagine de profil.');
        }

        // Unicitatea e garantată și de un index în baza de date: un singur
        // raportor nu poate umfla numărul de semnalări.
        $report = ContentReport::firstOrCreate([
            'reportable_type' => $target->getMorphClass(),
            'reportable_id' => $target->id,
            'subject_type' => $subject,
            'reported_by' => $reporter->id,
        ], [
            'reason' => $reason,
            'comment' => $comment,
        ]);

        if ($subject === 'avatar') {
            $this->autoHideIfNeeded($target);
        }

        return $report;
    }

    /**
     * Ascunde avatarul dacă pragul de raportări distincte e atins.
     *
     * `distinct('reported_by')` contează OAMENI, nu semnalări — altfel un singur
     * cont ar putea, prin repetare, să ascundă avatarul oricui.
     */
    private function autoHideIfNeeded(User $target): void
    {
        // Deja în coadă — nu are rost să-l retrimitem.
        if ($target->avatar_status !== 'approved') {
            return;
        }

        $distincti = ContentReport::open()
            ->where('reportable_type', $target->getMorphClass())
            ->where('reportable_id', $target->id)
            ->where('subject_type', 'avatar')
            ->distinct('reported_by')
            ->count('reported_by');

        if ($distincti >= config('moderation.auto_hide_threshold', 3)) {
            // Se întoarce la „în așteptare", nu într-o stare specială: ajunge în
            // aceeași coadă, la aceeași persoană, cu aceleași acțiuni.
            $target->forceFill([
                'avatar_status' => 'pending',
                'avatar_hidden_at' => now(),
            ])->save();
        }
    }
}
