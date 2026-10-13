<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['reportable_type', 'reportable_id', 'subject_type', 'reason', 'comment', 'reported_by'])]
class ContentReport extends Model
{
    /**
     * `status` NU e în #[Fillable]: e stare de moderare, schimbată doar de
     * moderatori. O valoare implicită pe model o face prezentă în instanța din
     * memorie, nu doar ca DEFAULT în schemă.
     *
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => 'open'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    #[Scope]
    protected function open(Builder $query): void
    {
        $query->where('status', 'open');
    }

    public function reasonLabel(): string
    {
        return match ($this->reason) {
            'ofensator' => 'Conținut ofensator',
            'impersonare' => 'Impersonare',
            'contact' => 'Date de contact',
            'spam' => 'Spam sau reclamă',
            default => 'Altul',
        };
    }
}
