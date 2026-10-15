<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un eseu al elevului și autoevaluarea lui. Strict privat (vezi migrația).
 */
#[Fillable(['user_id', 'essay_prompt_id', 'body', 'word_count', 'answers', 'breakdown', 'score', 'max_score'])]
class EssayAttempt extends Model
{
    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'breakdown' => 'array',
        ];
    }

    /** @return BelongsTo<EssayPrompt, $this> */
    public function prompt(): BelongsTo
    {
        return $this->belongsTo(EssayPrompt::class, 'essay_prompt_id');
    }
}
