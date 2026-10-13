<?php

namespace App\Domain\Auth;

use App\Models\User;

/** Rezultatul autentificării cu Google: cine e și dacă tocmai s-a creat contul. */
final readonly class LinkResult
{
    public function __construct(
        public User $user,
        public bool $isNew,
    ) {}
}
