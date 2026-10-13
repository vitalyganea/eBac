<?php

namespace App\Models;

use App\Enums\ExamProfile;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['subject_id', 'session', 'profile', 'total_items', 'total_points',
    'duration_minutes', 'structure', 'source_url', 'is_active'])]
class ExamBlueprint extends Model
{
    protected function casts(): array
    {
        return [
            'profile' => ExamProfile::class,
            'structure' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
