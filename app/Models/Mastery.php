<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Stare derivată din `attempts`. Reconstruibilă integral. */
#[Fillable(['user_id', 'skill_id', 'mastery', 'confidence', 'attempts_count', 'correct_count',
    'correct_streak', 'ease_factor', 'interval_days', 'next_review_at', 'last_practiced_at'])]
class Mastery extends Model
{
    protected $table = 'mastery';

    public $incrementing = false;

    protected $primaryKey = null;

    protected function casts(): array
    {
        return [
            'mastery' => 'float',
            'confidence' => 'float',
            'ease_factor' => 'float',
            'next_review_at' => 'datetime',
            'last_practiced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    /** Prag peste care considerăm competența stăpânită (nodul devine verde). */
    public function isMastered(): bool
    {
        return $this->mastery >= 0.85 && $this->attempts_count >= 8;
    }

    public function isDue(): bool
    {
        return $this->next_review_at !== null && $this->next_review_at->isPast();
    }
}
