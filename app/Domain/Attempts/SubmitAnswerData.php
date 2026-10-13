<?php

namespace App\Domain\Attempts;

use App\Enums\ClientSource;
use Illuminate\Support\Carbon;

/**
 * Datele unui răspuns, independente de transport.
 *
 * Aceeași structură se construiește din:
 *   - un request Inertia (web),
 *   - un element din lotul POST /api/v1/attempts/batch (mobil, posibil offline).
 */
final readonly class SubmitAnswerData
{
    public function __construct(
        /** Generat de CLIENT. Face submit-ul idempotent. */
        public string $clientUuid,
        public int $itemId,
        public array $answer,
        public ?string $sessionUuid = null,
        public ?int $clientMs = null,
        public ?Carbon $answeredAt = null,
        public ClientSource $source = ClientSource::Web,
        public ?string $appVersion = null,
    ) {}

    public function answeredAt(): Carbon
    {
        return $this->answeredAt ?? now();
    }
}
