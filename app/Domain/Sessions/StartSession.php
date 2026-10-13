<?php

namespace App\Domain\Sessions;

use App\Enums\ClientSource;
use App\Enums\SessionMode;
use App\Models\PracticeSession;
use App\Models\SessionItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class StartSession
{
    public function __construct(private ItemSelector $selector) {}

    /**
     * @param  list<int>  $skillIds
     */
    public function __invoke(
        User $user,
        int $subjectId,
        SessionMode $mode,
        int $count = 10,
        array $skillIds = [],
        ?int $ghostSessionId = null,
        ClientSource $source = ClientSource::Web,
    ): PracticeSession {
        $items = $this->selector->select($user, $subjectId, $mode, $count, $skillIds);

        if ($items->isEmpty()) {
            throw new RuntimeException('Nu există itemi publicați disponibili pentru această selecție.');
        }

        return DB::transaction(function () use ($user, $subjectId, $mode, $items, $ghostSessionId, $source) {
            $session = PracticeSession::create([
                'user_id' => $user->id,
                'subject_id' => $subjectId,
                'mode' => $mode,
                'total_items' => $items->count(),
                'ghost_session_id' => $ghostSessionId,
                'is_ranked' => $mode->isRanked() && $source === ClientSource::Web,
                'source' => $source,
                'started_at' => now(),
                // Sesiunile abandonate nu blochează itemii pe termen nelimitat.
                'expires_at' => now()->addHours($mode === SessionMode::Mock ? 5 : 3),
            ]);

            // Itemii sunt REZERVAȚI acum, dar `served_at` se ștampilează abia când
            // fiecare pleacă efectiv spre client — altfel cronometrajul ar porni
            // pentru itemul 10 în momentul în care elevul citește itemul 1.
            $rows = $items->values()->map(fn ($item, $i) => [
                'session_id' => $session->id,
                'item_id' => $item->id,
                'position' => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            SessionItem::insert($rows);

            return $session->load('sessionItems.item');
        });
    }
}
