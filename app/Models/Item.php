<?php

namespace App\Models;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Models\Concerns\TracksContentRevision;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * `answer`, `explanation`, `hint` și `hint_2` sunt marcate ca ascunse: nu apar
 * niciodată într-un `toArray()` / `toJson()` accidental, deci nu pot scăpa în
 * props-urile Inertia sau într-un răspuns de API.
 *
 * Cheia de corectare se citește DOAR explicit, prin gradingKey(), din
 * App\Domain\Grading — și numai pe server, după ce elevul a trimis răspunsul.
 */
#[Fillable(['uuid', 'subject_id', 'type', 'payload', 'answer', 'explanation', 'hints',
    'difficulty_seed', 'points', 'language', 'status', 'source', 'source_ref',
    'curriculum_code', 'is_exam_holdout', 'created_by'])]
#[Hidden(['answer', 'explanation', 'hints'])]
class Item extends Model
{
    use HasUuids, SoftDeletes, TracksContentRevision;

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected function casts(): array
    {
        return [
            'type' => ItemType::class,
            'status' => ItemStatus::class,
            'payload' => 'array',
            'answer' => 'array',
            'hints' => 'array',
            'is_exam_holdout' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'item_skills')->withPivot(['is_primary', 'weight']);
    }

    public function stats(): HasOne
    {
        return $this->hasOne(ItemStat::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ItemReport::class);
    }

    public function primarySkillId(): ?int
    {
        return $this->skills->firstWhere('pivot.is_primary', true)?->id
            ?? $this->skills->first()?->id;
    }

    /** Cheia de corectare. Se apelează exclusiv din stratul de grading, pe server. */
    public function gradingKey(): array
    {
        return $this->answer ?? [];
    }

    /**
     * Indiciile, în ordine crescătoare de „cât dezvăluie".
     *
     * Textul unui indiciu NU pleacă spre client odată cu itemul: se cere unul
     * câte unul, iar serverul contorizează. Altfel elevul ar putea citi toate
     * indiciile din props și raporta `hints_used = 0`, păstrând tot XP-ul.
     *
     * @return list<string>
     */
    public function hintList(): array
    {
        return array_values(array_filter(
            $this->hints ?? [],
            static fn ($h) => filled($h),
        ));
    }

    public function hintCount(): int
    {
        return count($this->hintList());
    }

    public function hintAt(int $index): ?string
    {
        return $this->hintList()[$index] ?? null;
    }

    #[Scope]
    protected function servable(Builder $query): void
    {
        $query->where('status', ItemStatus::Published);
    }

    /** Itemii de antrenament: tot ce NU e ținut deoparte pentru simulări. */
    #[Scope]
    protected function practicePool(Builder $query): void
    {
        $query->servable()->where('is_exam_holdout', false);
    }

    /** Itemii rezervați simulărilor cronometrate, ca să nu se ardă în antrenament. */
    #[Scope]
    protected function examPool(Builder $query): void
    {
        $query->servable()->where('is_exam_holdout', true);
    }
}
