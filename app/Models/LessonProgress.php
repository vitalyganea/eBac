<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ce a parcurs elevul dintr-o lecție.
 *
 * Deliberat separat de `mastery`: a citi teoria nu înseamnă a ști. Mastery-ul se
 * câștigă din răspunsuri, nu din derulare.
 */
#[Fillable(['user_id', 'lesson_id', 'last_block', 'completed_at'])]
class LessonProgress extends Model
{
    protected $table = 'lesson_progress';

    public $incrementing = false;

    protected $primaryKey = null;

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
