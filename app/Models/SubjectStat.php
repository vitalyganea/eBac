<?php

namespace App\Models;

use App\Domain\Xp\Level;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * XP și rating per utilizator × disciplină.
 *
 * Stare DERIVATĂ din `attempts`, ca și `mastery` — reconstruibilă cu
 * `php artisan ebac:recompute-user`. Există pentru că întrebarea „al câtelea
 * sunt la matematică" nu se poate răspunde dintr-un XP global.
 */
#[Fillable(['user_id', 'subject_id', 'xp', 'rating', 'attempts_count', 'correct_count', 'last_activity_at'])]
class SubjectStat extends Model
{
    protected $table = 'subject_stats';

    public $incrementing = false;

    protected $primaryKey = null;

    protected function casts(): array
    {
        return ['last_activity_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** Nivelul la ACEASTĂ disciplină. Derivat din xp, nu stocat. */
    public function level(): int
    {
        return Level::forXp((int) $this->xp);
    }

    /** @return array{level: int, xp: int, current: int, needed: int, percent: int} */
    public function progress(): array
    {
        return Level::progress((int) $this->xp);
    }

    public function accuracy(): ?float
    {
        return $this->attempts_count > 0
            ? round($this->correct_count / $this->attempts_count, 4)
            : null;
    }
}
