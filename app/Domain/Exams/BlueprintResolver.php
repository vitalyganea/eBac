<?php

namespace App\Domain\Exams;

use App\Models\ExamBlueprint;
use App\Models\User;

/**
 * Alege structura de examen pentru simularea unui elev.
 *
 * Profilul e ghidaj, nu poartă (§2a): un elev de la real care exersează istoria
 * primește simularea de istorie, cu structura profilului lui dacă există și cu
 * cea mai apropiată disponibilă altfel. Nu refuzăm niciodată pe motiv de profil.
 */
final class BlueprintResolver
{
    public function for(User $user, int $subjectId): ?ExamBlueprint
    {
        $candidates = ExamBlueprint::query()
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        $profile = $user->profile?->value;

        return $candidates
            // Profilul elevului întâi, apoi sesiunea cea mai recentă. Numele
            // sesiunilor încep cu anul („2026-baza"), deci ordinea lexicală e
            // și cea cronologică.
            ->sortBy([
                fn ($a, $b) => ($b->profile->value === $profile) <=> ($a->profile->value === $profile),
                fn ($a, $b) => strcmp($b->session, $a->session),
                fn ($a, $b) => $a->id <=> $b->id,
            ])
            ->first();
    }

    public function planFor(User $user, int $subjectId): ?MockPlan
    {
        $blueprint = $this->for($user, $subjectId);

        return $blueprint ? MockPlan::fromBlueprint($blueprint) : null;
    }
}
