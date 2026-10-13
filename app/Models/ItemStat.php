<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Elo pe ITEM, nu pe utilizator.
 *
 * Face posibile trei lucruri pe care un simplu „difficulty: 1-5" nu le poate:
 *   - dificultate adaptivă reală (servim itemi la ~70% șansă de succes)
 *   - matchmaking corect în dueluri
 *   - detectarea automată a itemilor stricați: dacă elevii puternici pică un item
 *     „ușor", itemul e ambiguu sau are cheia greșită → needs_review
 */
#[Fillable(['item_id', 'rating', 'rating_deviation', 'attempts_count', 'correct_count',
    'p_correct', 'avg_ms', 'discrimination', 'flag_count', 'needs_review', 'last_calibrated_at'])]
class ItemStat extends Model
{
    protected $primaryKey = 'item_id';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'rating' => 'float',
            'rating_deviation' => 'float',
            'p_correct' => 'float',
            'discrimination' => 'float',
            'needs_review' => 'boolean',
            'last_calibrated_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
