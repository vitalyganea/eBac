<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['slug', 'name', 'exam_target', 'starts_at', 'ends_at', 'exam_starts_at', 'is_active'])]
class Season extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'exam_starts_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /** Zile rămase până la examen — ancora planului de învățare invers. */
    public function daysUntilExam(): ?int
    {
        return $this->exam_starts_at
            ? max(0, now()->startOfDay()->diffInDays($this->exam_starts_at, false))
            : null;
    }
}
