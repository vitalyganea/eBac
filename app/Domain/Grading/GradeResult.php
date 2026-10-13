<?php

namespace App\Domain\Grading;

/**
 * Rezultatul corectării unui singur item.
 *
 * `score` e 0..1 pentru a putea reprezenta punctaj parțial — baremul de la BAC
 * acordă puncte pentru pași corecți, alegerea metodei și rezultate intermediare,
 * nu doar pentru răspunsul final. `isCorrect` înseamnă strict punctaj complet.
 */
final readonly class GradeResult
{
    public function __construct(
        public bool $isCorrect,
        public float $score,
        /** Feedback per componentă, pentru a arăta elevului CE a greșit. */
        public array $breakdown = [],
    ) {}

    public static function correct(array $breakdown = []): self
    {
        return new self(true, 1.0, $breakdown);
    }

    public static function wrong(array $breakdown = []): self
    {
        return new self(false, 0.0, $breakdown);
    }

    public static function partial(float $score, array $breakdown = []): self
    {
        $score = max(0.0, min(1.0, $score));

        return new self($score >= 0.9999, $score, $breakdown);
    }
}
