<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un lucru pe care elevul a marcat că nu îl înțelege.
 *
 * Deliberat separat de `mastery`: mastery-ul măsoară ce poate face elevul,
 * dedus din răspunsuri. Asta înregistrează ce SIMTE că nu înțelege — două
 * lucruri diferite, ambele utile. Un elev poate răspunde corect din intuiție și
 * totuși să nu înțeleagă, sau invers.
 */
#[Fillable(['user_id', 'skill_id', 'lesson_id', 'block_id', 'block_type', 'excerpt', 'note', 'source'])]
class LearningGap extends Model
{
    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNull('resolved_at');
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /** Eticheta tipului de conținut marcat, pentru afișare. */
    public function blockLabel(): string
    {
        return match ($this->block_type) {
            'formula' => 'Formulă',
            'example' => 'Exemplu rezolvat',
            'warning' => 'Greșeala tipică',
            'plot' => 'Grafic',
            'table' => 'Tabel',
            'code' => 'Program',
            'text' => 'Explicație',
            'video' => 'Video',
            null => 'Toată tema',
            default => 'Conținut',
        };
    }
}
