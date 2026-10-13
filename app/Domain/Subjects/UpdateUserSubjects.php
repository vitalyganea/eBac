<?php

namespace App\Domain\Subjects;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Setează disciplinele elevului.
 *
 * Deselectarea NU șterge progresul: `attempts` e append-only, iar `mastery`
 * rămâne. Un elev care renunță la chimie și se întoarce peste două luni își
 * regăsește exact unde era.
 */
final class UpdateUserSubjects
{
    /**
     * @param  list<int>  $subjectIds  în ordinea dorită de afișare
     */
    public function __invoke(User $user, array $subjectIds, ?int $primaryId = null): void
    {
        // Ignorăm id-uri inexistente în loc să aruncăm: interfața poate fi
        // deschisă în două taburi, iar o disciplină poate dispărea între timp.
        $valid = Subject::whereIn('id', $subjectIds)->pluck('id')->all();
        $ordered = array_values(array_filter($subjectIds, fn ($id) => in_array($id, $valid, true)));

        if ($primaryId !== null && ! in_array($primaryId, $ordered, true)) {
            $primaryId = null;
        }

        $primaryId ??= $ordered[0] ?? null;

        DB::transaction(function () use ($user, $ordered, $primaryId) {
            $user->subjects()->sync(
                collect($ordered)->mapWithKeys(fn (int $id, int $i) => [
                    $id => [
                        'is_primary' => $id === $primaryId,
                        'sort_order' => $i,
                        'added_at' => now(),
                        'updated_at' => now(),
                    ],
                ])->all()
            );

            if ($user->onboarded_at === null) {
                $user->forceFill(['onboarded_at' => now()])->save();
            }
        });

        $user->unsetRelation('subjects');
    }
}
