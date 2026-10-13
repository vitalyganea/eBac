<?php

namespace App\Models;

use App\Support\Icons;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['slug', 'name', 'name_ru', 'description', 'icon', 'category', 'criteria',
    'xp_reward', 'is_secret', 'sort_order'])]
class Achievement extends Model
{
    protected function casts(): array
    {
        return [
            'criteria' => 'array',
            'is_secret' => 'boolean',
        ];
    }

    /** Vezi `Subject::iconName()` — aceeași plasă pentru un nume rămas în urmă. */
    public function iconName(): string
    {
        return $this->icon && Icons::has($this->icon) ? $this->icon : 'medal';
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot(['progress', 'unlocked_at']);
    }
}
