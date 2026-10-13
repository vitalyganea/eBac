<?php

namespace App\Models;

use App\Models\Concerns\TracksContentRevision;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * Atenție la cele două structuri distincte:
 *
 *   parent/children       → IERARHIA (arbore): capitol > temă > competență
 *   prerequisites/unlocks → PREREchIZITELE (graf aciclic), care traversează
 *                           ierarhia și pot lega competențe din capitole diferite
 */
#[Fillable(['subject_id', 'parent_id', 'slug', 'name', 'name_ru', 'curriculum_code',
    'description', 'depth', 'sort_order', 'is_leaf', 'is_active', 'exam_weight'])]
class Skill extends Model
{
    use TracksContentRevision;

    protected function casts(): array
    {
        return [
            'is_leaf' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /** Competențele de care ARE NEVOIE aceasta. */
    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'skill_edges', 'skill_id', 'prerequisite_id')
            ->withPivot('strength');
    }

    /** Competențele pe care le DEBLOCHEAZĂ aceasta. */
    public function unlocks(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'skill_edges', 'prerequisite_id', 'skill_id')
            ->withPivot('strength');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'item_skills')->withPivot(['is_primary', 'weight']);
    }

    public function lesson(): HasOne
    {
        return $this->hasOne(Lesson::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(SkillVideo::class)->where('is_active', true)->orderBy('sort_order');
    }

    #[Scope]
    protected function leaves(Builder $query): void
    {
        $query->where('is_leaf', true)->where('is_active', true);
    }

    /**
     * Toate prerechizitele transitive, cu adâncimea lor — o singură interogare
     * recursivă în loc de N cereri în buclă.
     *
     * Ăsta e motorul diagnosticului: elevul pică „integrale", iar noi coborâm în
     * graf ca să găsim cauza reală (de ex. manipulare algebrică), nu simptomul.
     *
     * @return array<int, array{id: int, name: string, depth: int, strength: float}>
     */
    public function transitivePrerequisites(int $maxDepth = 4): array
    {
        return DB::select(<<<'SQL'
            WITH RECURSIVE walk AS (
                -- Termenul ne-recursiv trebuie să aibă EXACT tipul celui recursiv:
                -- `strength` e numeric(3,2) în tabel, dar produsul din pasul
                -- recursiv e `numeric` simplu, iar Postgres refuză nepotrivirea.
                SELECT e.prerequisite_id AS id,
                       e.strength::numeric AS strength,
                       1 AS depth
                FROM skill_edges e
                WHERE e.skill_id = ?
              UNION ALL
                SELECT e.prerequisite_id,
                       (e.strength * w.strength)::numeric,
                       w.depth + 1
                FROM skill_edges e
                JOIN walk w ON e.skill_id = w.id
                WHERE w.depth < ?
            )
            -- O competență poate fi atinsă pe mai multe căi. Păstrăm calea cea
            -- mai scurtă și cea mai puternică, ca diagnosticul să raporteze o
            -- singură dată fiecare cauză posibilă.
            SELECT s.id,
                   s.name,
                   s.curriculum_code,
                   min(w.depth) AS depth,
                   max(w.strength) AS strength
            FROM walk w
            JOIN skills s ON s.id = w.id
            WHERE s.is_active = true
            GROUP BY s.id, s.name, s.curriculum_code
            ORDER BY depth, strength DESC
        SQL, [$this->id, $maxDepth]);
    }
}
