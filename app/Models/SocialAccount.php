<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Legătura dintre un cont eBac și o identitate la un furnizor extern.
 *
 * `provider_id` e identificatorul stabil de la furnizor — la Google, câmpul
 * `sub`. NU emailul: acela se poate schimba, iar o legătură pe el ar muta contul
 * la altcineva în ziua în care adresa e realocată.
 */
#[Fillable(['provider', 'provider_id', 'provider_email', 'user_id', 'linked_at'])]
class SocialAccount extends Model
{
    protected function casts(): array
    {
        return ['linked_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
