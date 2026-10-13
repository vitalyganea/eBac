<?php

namespace App\Domain\Sessions;

use App\Models\PracticeSession;
use App\Models\SessionItem;
use RuntimeException;

/**
 * Dezvăluie indiciul următor — pe SERVER.
 *
 * Textul indiciilor nu pleacă niciodată împreună cu itemul. Motivul e același
 * pentru care nu trimitem cheia de corectare: dacă toate indiciile ar fi în
 * props-urile paginii, elevul le-ar putea citi și trimite `hints_used = 0`,
 * păstrând tot XP-ul. Aici serverul contorizează, deci penalizarea e reală.
 *
 * Contorul e monoton: reîncărcarea paginii sau cererea aceluiași indiciu de
 * două ori nu-l resetează și nu-l crește artificial.
 */
final class RevealHint
{
    /**
     * @return array{index: int, text: string, revealed: int, total: int, has_more: bool}
     */
    public function next(PracticeSession $session, string $itemUuid): array
    {
        if (! $session->mode->allowsHints()) {
            // În simulare și în duel nu există indicii — ar denatura măsurătoarea.
            throw new RuntimeException('Modul acesta nu permite indicii.');
        }

        $sessionItem = SessionItem::query()
            ->where('session_id', $session->id)
            ->whereNull('answered_at')
            ->whereHas('item', fn ($q) => $q->where('uuid', $itemUuid))
            ->orderByRaw('served_at is null')
            ->orderBy('position')
            ->with('item')
            ->first();

        if ($sessionItem === null) {
            throw new RuntimeException('Itemul nu face parte din sesiune sau are deja răspuns.');
        }

        $hints = $sessionItem->item->hintList();
        $total = count($hints);
        $index = $sessionItem->hints_used;

        if ($index >= $total) {
            throw new RuntimeException('Nu mai există indicii pentru acest item.');
        }

        $sessionItem->increment('hints_used');

        return [
            'index' => $index,
            'text' => $hints[$index],
            'revealed' => $index + 1,
            'total' => $total,
            'has_more' => $index + 1 < $total,
        ];
    }
}
