<?php

namespace App\Domain\Rating;

final readonly class EloResult
{
    public function __construct(
        public int $userRatingBefore,
        public int $userRatingAfter,
        public float $itemRatingBefore,
        public float $itemRatingAfter,
        public float $expectedScore,
    ) {}

    public function userDelta(): int
    {
        return $this->userRatingAfter - $this->userRatingBefore;
    }
}
