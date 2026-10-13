<?php

namespace App\Domain\Sessions;

use App\Models\PracticeSession;
use App\Models\SessionItem;

/**
 * Livrează itemul următor și ștampilează `served_at` pe server.
 *
 * Ăsta e momentul în care pornește cronometrul autoritar. Timerul din browser e
 * doar decor: dacă l-am crede, „Speed Demon" s-ar obține cu DevTools.
 */
final class ServeNextItem
{
    public function next(PracticeSession $session): ?SessionItem
    {
        $sessionItem = SessionItem::query()
            ->where('session_id', $session->id)
            ->whereNull('answered_at')
            ->orderBy('position')
            ->with('item.skills')
            ->first();

        if ($sessionItem === null) {
            return null;
        }

        // Re-servirea aceluiași item (refresh de pagină) nu resetează cronometrul —
        // altfel ai timp infinit dând F5.
        if ($sessionItem->served_at === null) {
            $sessionItem->update(['served_at' => now()]);
        }

        return $sessionItem;
    }

    public function progress(PracticeSession $session): array
    {
        return [
            'total' => $session->total_items,
            'answered' => $session->answered_items,
            'correct' => $session->correct_items,
            'xp' => $session->xp_awarded,
        ];
    }
}
