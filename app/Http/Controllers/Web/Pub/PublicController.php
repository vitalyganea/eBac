<?php

namespace App\Http\Controllers\Web\Pub;

use App\Domain\Seo\JsonLd;
use App\Domain\Seo\PageMeta;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Season;
use App\Models\Skill;
use App\Models\Subject;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;

/**
 * Paginile publice, indexabile.
 *
 * Randate cu Blade, fără JavaScript. Nu au nimic în comun cu aplicația de joc
 * în afară de datele din care se construiesc: fiecare item validat în panoul de
 * administrare devine automat conținut public.
 */
class PublicController extends Controller
{
    public function home(): View
    {
        $subjects = $this->subjectsWithContent();
        $season = Season::where('is_active', true)->first();

        $totalItems = Item::practicePool()->count();

        return view('public.home', [
            'meta' => new PageMeta(
                title: 'Pregătire pentru BAC 2027 — Republica Moldova',
                description: "Exerciții rezolvate, indicii pas cu pas și antrenament interactiv pentru bacalaureatul din Republica Moldova. {$totalItems} de exerciții pe programele oficiale ANCE.",
                canonical: route('public.home'),
                breadcrumbs: [['name' => 'eBac', 'url' => route('public.home')]],
            ),
            'subjects' => $subjects,
            'navSubjects' => $subjects->take(3),
            'season' => $season,
            'totalItems' => $totalItems,
        ]);
    }

    public function subject(string $slug): View
    {
        $subject = Subject::where('slug', $slug)->firstOrFail();

        // Arborele de competențe, cu numărul de exerciții publice pe fiecare.
        $chapters = Skill::query()
            ->where('subject_id', $subject->id)
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q
                ->where('is_active', true)
                ->withCount(['items as public_items_count' => fn ($i) => $i->practicePool()])
                ->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        $itemCount = Item::practicePool()->where('subject_id', $subject->id)->count();
        $skillCount = Skill::where('subject_id', $subject->id)->where('is_leaf', true)->count();

        $blueprint = $subject->blueprints()->where('is_active', true)->first();

        abort_if($itemCount === 0 && $chapters->isEmpty(), 404);

        return view('public.subject', [
            'meta' => new PageMeta(
                title: "BAC {$subject->name} — exerciții rezolvate și programa de examen",
                description: "Pregătire pentru proba de {$subject->name} la BAC-ul din Republica Moldova: {$skillCount} competențe din programa ANCE, {$itemCount} de exerciții cu rezolvare și indicii.",
                canonical: route('public.subject', $subject->slug),
                breadcrumbs: [
                    ['name' => 'eBac', 'url' => route('public.home')],
                    ['name' => $subject->name, 'url' => route('public.subject', $subject->slug)],
                ],
                jsonLd: [JsonLd::course($subject, $skillCount, $itemCount)],
            ),
            'subject' => $subject,
            'chapters' => $chapters,
            'itemCount' => $itemCount,
            'skillCount' => $skillCount,
            'blueprint' => $blueprint,
            'navSubjects' => $this->subjectsWithContent()->take(3),
        ]);
    }

    public function skill(string $subjectSlug, string $skillSlug): View
    {
        $subject = Subject::where('slug', $subjectSlug)->firstOrFail();
        $skill = Skill::where('subject_id', $subject->id)
            ->where('slug', $skillSlug)
            ->where('is_active', true)
            ->firstOrFail();

        /**
         * Exercițiile publicate: EXCLUSIV din bazinul de antrenament.
         *
         * `practicePool()` exclude itemii de holdout. Publicarea unui item de
         * holdout l-ar arde definitiv, iar simulările și estimarea de notă și-ar
         * pierde validitatea.
         */
        $items = $skill->items()
            ->practicePool()
            ->orderBy('difficulty_seed')
            ->limit(config('seo.public_items_per_skill'))
            ->get();

        $prerequisites = $skill->prerequisites()->where('is_active', true)->get();
        $unlocks = $skill->unlocks()->where('is_active', true)->get();

        $description = $skill->description
            ?: "Ce trebuie să știi la {$skill->name} pentru BAC-ul din Republica Moldova: exerciții rezolvate, indicii pas cu pas și competențele necesare înainte.";

        return view('public.skill', [
            'meta' => new PageMeta(
                title: "{$skill->name} — BAC {$subject->name}",
                description: $description,
                canonical: route('public.skill', [$subject->slug, $skill->slug]),
                breadcrumbs: [
                    ['name' => 'eBac', 'url' => route('public.home')],
                    ['name' => $subject->name, 'url' => route('public.subject', $subject->slug)],
                    ['name' => $skill->name, 'url' => route('public.skill', [$subject->slug, $skill->slug])],
                ],
                ogType: 'article',
                jsonLd: array_filter([JsonLd::quiz($skill, $items)]),
            ),
            'subject' => $subject,
            'skill' => $skill,
            'items' => $items,
            'prerequisites' => $prerequisites,
            'unlocks' => $unlocks,
            'navSubjects' => $this->subjectsWithContent()->take(3),
        ]);
    }

    /** @return Collection<int, Subject> */
    private function subjectsWithContent(): Collection
    {
        return Subject::query()
            ->withCount(['items as public_items_count' => fn ($q) => $q->practicePool()])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Subject $s) => $s->public_items_count > 0)
            ->values();
    }
}
