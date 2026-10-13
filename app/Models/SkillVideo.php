<?php

namespace App\Models;

use App\Models\Concerns\TracksContentRevision;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['skill_id', 'provider', 'provider_id', 'title', 'channel_name', 'channel_url',
    'duration_seconds', 'language', 'level', 'is_embeddable', 'is_active',
    'has_creator_consent', 'sort_order'])]
class SkillVideo extends Model
{
    use TracksContentRevision;

    protected function casts(): array
    {
        return [
            'is_embeddable' => 'boolean',
            'is_active' => 'boolean',
            'has_creator_consent' => 'boolean',
            'last_checked_at' => 'datetime',
        ];
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    /**
     * URL de embed prin domeniul fără cookie-uri de tracking.
     * Utilizatorii sunt minori — nu le trimitem cookie-uri publicitare.
     */
    public function embedUrl(): ?string
    {
        return $this->provider === 'youtube'
            ? "https://www.youtube-nocookie.com/embed/{$this->provider_id}?rel=0&modestbranding=1"
            : null;
    }

    public function watchUrl(): ?string
    {
        return $this->provider === 'youtube'
            ? "https://www.youtube.com/watch?v={$this->provider_id}"
            : null;
    }
}
