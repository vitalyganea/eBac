<?php

namespace App\Domain\Exams;

use RuntimeException;

/** Simularea nu se poate porni: lipsește structura sau nu ajung itemii rezervați. */
final class MockUnavailable extends RuntimeException {}
