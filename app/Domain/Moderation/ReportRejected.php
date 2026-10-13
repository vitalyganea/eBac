<?php

namespace App\Domain\Moderation;

use RuntimeException;

/**
 * Raportarea a fost refuzată dintr-un motiv de DOMENIU — nu din cauza unei
 * erori de programare.
 *
 * Există ca tip distinct pentru că `catch (\RuntimeException)` prinde și
 * MassAssignmentException, și alte defecte interne, transformându-le în mesaje
 * liniștitoare pentru utilizator. Un bug ascuns așa poate trăi luni întregi.
 */
class ReportRejected extends RuntimeException {}
