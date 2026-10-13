<?php

namespace App\Domain\Exams;

use App\Enums\SessionMode;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Alege itemii unei simulări, secțiune cu secțiune, EXCLUSIV din holdout (§8).
 *
 * Spre deosebire de antrenament, aici nu căutăm zona de ~70% succes: o
 * simulare măsoară, nu antrenează. Itemii nu se aleg după rating-ul elevului —
 * altfel un elev slab ar primi un test ușor și o estimare umflată.
 *
 * La final elevul vede rezolvările, deci un item deja văzut într-o simulare
 * anterioară nu mai măsoară nimic PENTRU EL. Preferăm itemii nevăzuți; dacă nu
 * ajung, completăm cu cei văzuți cel mai demult și o spunem în rezultat
 * (`reused`) — o estimare mai puțin precisă, declarată ca atare, e mai utilă
 * decât nicio simulare.
 */
final class MockItemPicker
{
    /**
     * @return array{sections: list<array{section: array, items: Collection<int, Item>}>, reused: int}
     *
     * @throws MockUnavailable
     */
    public function pick(User $user, int $subjectId, MockPlan $plan): array
    {
        try {
            return $this->attempt($user, $subjectId, $plan, preferFresh: true);
        } catch (MockUnavailable) {
            // Preferința pentru itemi nevăzuți poate lăsa o secțiune ulterioară
            // fără tipul de care are nevoie. A doua încercare pune pe primul loc
            // ca simularea să se poată completa; prospețimea vine pe locul doi.
            return $this->attempt($user, $subjectId, $plan, preferFresh: false);
        }
    }

    /** @throws MockUnavailable */
    private function attempt(User $user, int $subjectId, MockPlan $plan, bool $preferFresh): array
    {
        if (! $plan->isSimulable()) {
            // Fie toate subiectele cer corectare umană, fie structura nu are încă
            // tipuri de întrebări asociate (o disciplină fără conținut).
            throw new MockUnavailable('Simularea la această disciplină e în pregătire: încă nu avem întrebări pentru subiectele ei.');
        }

        $lastSeen = $this->lastSeenInMocks($user);
        $taken = [];
        $reused = 0;
        $sections = [];

        foreach ($plan->autoSections as $index => $section) {
            $candidates = $this->candidates($user, $subjectId, $section['types'], $taken);

            [$fresh, $seen] = $candidates->partition(fn (Item $i) => ! $lastSeen->has($i->id));

            // Tipurile cerute și de secțiunile următoare se folosesc ultimele.
            // Altfel „Subiectul I" (mcq, numeric) poate lua toate grilele, iar
            // „Subiectul IV" (match, mcq) rămâne fără itemi, deși banca le-ar
            // ajunge tuturor. Regula se aplică și itemilor deja văzuți: prima
            // versiune o aplica doar celor nevăzuți, iar a doua simulare a
            // aceluiași elev nu mai pornea.
            $laterTypes = collect(array_slice($plan->autoSections, $index + 1))->pluck('types')->flatten()->all();
            $isShared = fn (Item $i) => in_array($i->type->value, $laterTypes, true);

            [$freshShared, $freshExclusive] = $fresh->partition($isShared);
            $oldestFirst = fn (Collection $items) => $items->sortBy(fn (Item $i) => $lastSeen->get($i->id))->values();
            [$seenShared, $seenExclusive] = $seen->partition($isShared);

            $need = $section['items'];
            $chosen = $this->spreadAcrossSkills($freshExclusive->shuffle(), $need);

            if ($preferFresh) {
                $chosen = $chosen->concat($this->spreadAcrossSkills($freshShared->shuffle(), $need - $chosen->count()));
                $fill = $oldestFirst($seenExclusive)->concat($oldestFirst($seenShared));
            } else {
                $fill = $oldestFirst($seenExclusive)->concat($freshShared->shuffle())->concat($oldestFirst($seenShared));
            }

            $fill = $fill->take($need - $chosen->count());
            $reused += $fill->filter(fn (Item $i) => $lastSeen->has($i->id))->count();
            $chosen = $chosen->concat($fill);

            if ($chosen->count() < $section['items']) {
                throw new MockUnavailable(sprintf(
                    'Secțiunea „%s" are nevoie de %d întrebări rezervate simulărilor; deocamdată sunt %d.',
                    $section['name'], $section['items'], $candidates->count(),
                ));
            }

            $taken = [...$taken, ...$chosen->pluck('id')->all()];
            $sections[] = ['section' => $section, 'items' => $chosen->values()];
        }

        return ['sections' => $sections, 'reused' => $reused];
    }

    /** Se poate porni o simulare? Același calcul, fără excepție. */
    public function check(User $user, int $subjectId, MockPlan $plan): ?string
    {
        try {
            $this->pick($user, $subjectId, $plan);

            return null;
        } catch (MockUnavailable $e) {
            return $e->getMessage();
        }
    }

    /** @param list<string> $types */
    private function candidates(User $user, int $subjectId, array $types, array $exclude): Collection
    {
        return Item::query()
            ->examPool()
            ->where('subject_id', $subjectId)
            ->where('language', $user->locale ?? 'ro')
            ->whereIn('type', $types)
            ->whereNotIn('items.id', $exclude)
            ->leftJoin('item_stats', 'item_stats.item_id', '=', 'items.id')
            // Un item semnalat automat ca posibil stricat ar strica nota.
            ->where(fn ($q) => $q->where('item_stats.needs_review', false)->orWhereNull('item_stats.needs_review'))
            ->select('items.*')
            // Ordinea fizică a rândurilor nu are voie să decidă selecția: un
            // bug de selecție s-a ascuns în teste tocmai pentru că baza
            // proaspătă le returna într-o ordine norocoasă.
            ->inRandomOrder()
            ->with('skills')
            ->get();
    }

    /**
     * Câte un item pe competență, cât se poate. Cinci itemi pe aceeași temă ar
     * măsura o temă, nu secțiunea.
     */
    private function spreadAcrossSkills(Collection $items, int $count): Collection
    {
        $bySkill = $items->groupBy(fn (Item $i) => $i->primarySkillId() ?? 0);
        $chosen = collect();

        while ($count > 0 && $chosen->count() < $count && $bySkill->isNotEmpty()) {
            foreach ($bySkill->keys() as $skill) {
                $chosen->push($bySkill[$skill]->shift());

                if ($bySkill[$skill]->isEmpty()) {
                    $bySkill->forget($skill);
                }
                if ($chosen->count() === $count) {
                    break;
                }
            }
        }

        return $chosen;
    }

    /** @return Collection<int, string> item_id → ultima dată când a apărut într-o simulare */
    private function lastSeenInMocks(User $user): Collection
    {
        return DB::table('session_items')
            ->join('practice_sessions', 'practice_sessions.id', '=', 'session_items.session_id')
            ->where('practice_sessions.user_id', $user->id)
            ->where('practice_sessions.mode', SessionMode::Mock->value)
            ->groupBy('session_items.item_id')
            ->selectRaw('session_items.item_id, max(session_items.created_at) as last_seen')
            ->pluck('last_seen', 'item_id');
    }
}
