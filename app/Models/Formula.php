<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * O formulă din secțiunea „Formule" a unei discipline.
 *
 * Conținut de referință, nu item: nu se corectează și nu dă XP (§6).
 * `annex` ține testele ANCE în a căror anexă a apărut — coduri ca `r_sb26`
 * (profil real, sesiunea de bază 2026) sau `u_ss24`.
 */
#[Fillable(['subject_id', 'skill_id', 'slug', 'name', 'latex', 'body', 'variables', 'pitfall',
    'annex', 'source', 'source_ref', 'sort_order'])]
class Formula extends Model
{
    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'annex' => 'array',
        ];
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return BelongsTo<Skill, $this> */
    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    /**
     * În câte teste ale profilului a apărut formula în anexă.
     *
     * Arte și sport dau testul de la umanist, deci numără ca el.
     */
    public function annexCount(string $prefix): int
    {
        return count(array_filter($this->annex ?? [], fn (string $code) => str_starts_with($code, $prefix.'_')));
    }
}
