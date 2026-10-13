<?php

namespace App\Models;

use App\Enums\ExamProfile;
use App\Enums\SubjectRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['subject_id', 'profile', 'role'])]
class SubjectProfile extends Model
{
    protected $table = 'subject_profile';

    protected function casts(): array
    {
        return [
            'profile' => ExamProfile::class,
            'role' => SubjectRole::class,
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
