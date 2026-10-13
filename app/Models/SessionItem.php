<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * `served_at` e ștampilat pe server în momentul în care itemul pleacă spre client
 * și e singura bază legitimă de cronometraj. Timpul raportat de client rămâne
 * informativ: pe mobil clientul e complet în mâna atacatorului.
 */
#[Fillable(['session_id', 'item_id', 'position', 'served_at', 'answered_at', 'hints_used'])]
class SessionItem extends Model
{
    protected function casts(): array
    {
        return [
            'served_at' => 'datetime',
            'answered_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PracticeSession::class, 'session_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
