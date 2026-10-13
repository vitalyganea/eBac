<?php

namespace App\Domain\Subjects;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Catalogul complet de discipline de bacalaureat, adnotat pentru un elev anume.
 *
 * Principiul: elevul vede TOATE disciplinele și poate alege oricare, oricând.
 * `users.profile` nu filtrează nimic — adaugă doar o etichetă („obligatoriu",
 * „disciplină de profil", „la alegere") ca ghidaj, pentru că bacalaureatul e din
 * mai multe probe și un elev de la real are tot dreptul să exerseze istorie.
 */
final class SubjectCatalog
{
    /** @return Collection<int, array<string, mixed>> */
    public function for(User $user): Collection
    {
        $selected = $user->subjects()->pluck('subjects.id', 'subjects.id');
        $primary = $user->subjects->firstWhere('pivot.is_primary', true)?->id;

        return Subject::query()
            ->with(['profiles' => fn ($q) => $q->where('profile', $user->profile?->value)])
            ->withCount(['items as practice_items_count' => fn ($q) => $q->practicePool()])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Subject $subject) => [
                'id' => $subject->id,
                'slug' => $subject->slug,
                'name' => $subject->localizedName($user->locale ?? 'ro'),
                'icon' => $subject->iconName(),
                'color' => $subject->color,

                // Etichetă informativă, nu restricție.
                'role' => $subject->profiles->first()?->role?->value,
                'role_label' => $subject->profiles->first()?->role?->label(),

                'is_selected' => $selected->has($subject->id),
                'is_primary' => $primary === $subject->id,

                // Cinstit: câte întrebări există efectiv. O disciplină fără itemi
                // se poate alege, dar nu se poate juca încă.
                'items_count' => $subject->practice_items_count,
                'is_playable' => $subject->practice_items_count > 0,
            ]);
    }

    /**
     * Disciplinele sugerate la înscriere, pe baza profilului.
     *
     * E o SUGESTIE preselectată, nu o constrângere: elevul le poate deselecta
     * pe toate și alege altele.
     *
     * @return list<int>
     */
    public function suggestFor(User $user): array
    {
        if ($user->profile === null) {
            return Subject::active()->pluck('id')->all();
        }

        return Subject::query()
            ->whereHas('profiles', fn ($q) => $q
                ->where('profile', $user->profile->value)
                ->whereIn('role', ['obligatoriu', 'profil']))
            ->pluck('id')
            ->all();
    }
}
