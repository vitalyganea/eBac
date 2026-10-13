<?php

namespace App\Models;

use App\Support\Icons;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'name_ru', 'icon', 'color', 'description', 'is_active', 'sort_order'])]
class Subject extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(SubjectProfile::class);
    }

    public function blueprints(): HasMany
    {
        return $this->hasMany(ExamBlueprint::class);
    }

    /** Competențele de nivel superior (capitolele). */
    public function rootSkills(): HasMany
    {
        return $this->hasMany(Skill::class)->whereNull('parent_id')->orderBy('sort_order');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_subjects');
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * O disciplină e „jucabilă" doar dacă are itemi publicați de antrenament.
     *
     * Elevul poate ALEGE orice disciplină, dar interfața trebuie să spună
     * cinstit care are conținut și care e încă în pregătire — altfel apasă
     * „Antrenament" și primește o eroare.
     */
    public function isPlayable(): bool
    {
        return ($this->practice_items_count ?? $this->items()->practicePool()->count()) > 0;
    }

    /**
     * Numele iconiței de afișat, garantat existent în setul din `resources/icons`.
     *
     * `icon` vine din baza de date, deci poate fi null sau poate rămâne în urmă
     * după o redenumire în set. Fără plasa asta, un nume necunoscut ar randa
     * nimic — o casetă goală în lista de discipline, care arată a defecțiune, nu
     * a dată lipsă. Numele valide se verifică în `IconTest`.
     */
    public function iconName(): string
    {
        return $this->icon && Icons::has($this->icon) ? $this->icon : 'book';
    }

    public function localizedName(string $locale = 'ro'): string
    {
        return $locale === 'ru' ? ($this->name_ru ?? $this->name) : $this->name;
    }
}
