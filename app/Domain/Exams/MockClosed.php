<?php

namespace App\Domain\Exams;

use RuntimeException;

/**
 * Lucrarea a fost predată sau timpul a expirat: răspunsurile nu se mai primesc.
 *
 * Timpul e al serverului (§5). Timerul din browser poate fi oprit din DevTools;
 * termenul din `practice_sessions.expires_at`, nu.
 */
final class MockClosed extends RuntimeException {}
