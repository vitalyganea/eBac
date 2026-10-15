<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * O cerință de producere scrisă dintr-un test ANCE, cu grila baremului ei.
 *
 * `rubric`: {total, criteria: [{key, group, label, options: [[puncte, text]]}],
 * errors: [{key, label, max, per, caps}], volume: {max, over_max, bands}}.
 */
#[Fillable(['subject_id', 'slug', 'kind', 'title', 'task', 'word_min', 'word_max', 'rubric',
    'source', 'source_ref', 'sort_order'])]
class EssayPrompt extends Model
{
    protected function casts(): array
    {
        return ['rubric' => 'array'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return HasMany<EssayAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(EssayAttempt::class);
    }
}
