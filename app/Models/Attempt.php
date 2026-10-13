<?php

namespace App\Models;

use App\Enums\ClientSource;
use App\Enums\SessionMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Log append-only de fapte imuabile. Sursa de adevăr a întregii aplicații.
 *
 * Modelul refuză activ UPDATE și DELETE: dacă o dată XP-ul, mastery-ul sau
 * rating-ul se pot recalcula din acest tabel, atunci tabelul nu are voie să se
 * schimbe niciodată sub picioarele lor.
 */
#[Fillable(['client_uuid', 'user_id', 'item_id', 'session_id', 'skill_id', 'subject_id', 'answer',
    'is_correct', 'score', 'client_ms', 'server_ms', 'hints_used', 'answered_at',
    'synced_at', 'context', 'source', 'app_version', 'is_ranked', 'xp_awarded',
    'rating_delta', 'item_rating_before', 'user_rating_before'])]
class Attempt extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'answer' => 'array',
            'context' => SessionMode::class,
            'source' => ClientSource::class,
            'is_correct' => 'boolean',
            'is_ranked' => 'boolean',
            'score' => 'float',
            'item_rating_before' => 'float',
            'user_rating_before' => 'float',
            'answered_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new RuntimeException(
                'attempts este append-only: un fapt înregistrat nu se modifică. '
                .'Corectează starea derivată prin recalculare, nu prin UPDATE.'
            );
        });

        static::deleting(function (): never {
            throw new RuntimeException(
                'attempts este append-only: un fapt înregistrat nu se șterge. '
                .'Pentru ștergerea datelor unui utilizator (GDPR) folosește '
                .'App\Domain\Attempts\PurgeUserData, care rulează la nivel de utilizator.'
            );
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PracticeSession::class, 'session_id');
    }
}
