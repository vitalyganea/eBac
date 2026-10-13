<?php

namespace App\Domain\Xp;

final readonly class XpAward
{
    public function __construct(
        public int $amount,
        public array $reasons = [],
        public bool $cappedByDailyLimit = false,
    ) {}
}
