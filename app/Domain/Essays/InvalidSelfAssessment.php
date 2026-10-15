<?php

namespace App\Domain\Essays;

use DomainException;

/**
 * O autoevaluare care nu se potrivește cu grila: un nivel inexistent în barem,
 * un criteriu nebifat, numărul de cuvinte lipsă.
 *
 * Excepție de domeniu, nu `ValidationException`: calculul rămâne pur și se
 * testează fără aplicația Laravel (tests/Unit). Controllerul o traduce.
 */
final class InvalidSelfAssessment extends DomainException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }
}
