<?php

namespace App\Models;

use App\Enums\ClientSource;
use App\Enums\SessionMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['uuid', 'user_id', 'subject_id', 'mode', 'config', 'ghost_session_id',
    'blueprint_id', 'total_items', 'is_ranked', 'source', 'app_version',
    'expires_at', 'started_at'])]
class PracticeSession extends Model
{
    use HasUuids;

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected function casts(): array
    {
        return [
            'mode' => SessionMode::class,
            'source' => ClientSource::class,
            'config' => 'array',
            'is_ranked' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function sessionItems(): HasMany
    {
        return $this->hasMany(SessionItem::class, 'session_id')->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class, 'session_id');
    }

    /** Sesiunea înregistrată împotriva căreia jucăm (duel-fantomă). */
    public function ghostSession(): BelongsTo
    {
        return $this->belongsTo(self::class, 'ghost_session_id');
    }

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
