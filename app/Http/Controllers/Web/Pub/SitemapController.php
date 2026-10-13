<?php

namespace App\Http\Controllers\Web\Pub;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Season;
use App\Models\Skill;
use App\Models\Subject;
use Illuminate\Http\Response;

/**
 * Sitemap generat din baza de date.
 *
 * Nu conține pagini de competență fără exerciții publice: o pagină goală
 * trimisă la indexare e conținut subțire și trage în jos întregul domeniu.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            ['loc' => route('public.home'), 'priority' => '1.0', 'changefreq' => 'weekly'],
        ];

        foreach (Season::where('is_active', true)->get() as $season) {
            $year = str_replace('bac-', '', $season->exam_target);
            $urls[] = [
                'loc' => route('public.exam', $year),
                'priority' => '0.9',
                'changefreq' => 'weekly',
            ];
        }

        $subjects = Subject::query()
            ->withCount(['items as public_items_count' => fn ($q) => $q->practicePool()])
            ->get()
            ->filter(fn (Subject $s) => $s->public_items_count > 0);

        foreach ($subjects as $subject) {
            $urls[] = [
                'loc' => route('public.subject', $subject->slug),
                'priority' => '0.8',
                'changefreq' => 'weekly',
            ];

            $skills = Skill::where('subject_id', $subject->id)
                ->where('is_active', true)
                ->withCount(['items as public_items_count' => fn ($q) => $q->practicePool()])
                ->get()
                ->filter(fn (Skill $s) => $s->public_items_count > 0);

            foreach ($skills as $skill) {
                $urls[] = [
                    'loc' => route('public.skill', [$subject->slug, $skill->slug]),
                    'priority' => '0.7',
                    'changefreq' => 'monthly',
                    'lastmod' => Item::practicePool()
                        ->whereHas('skills', fn ($q) => $q->whereKey($skill->id))
                        ->max('updated_at'),
                ];
            }
        }

        $xml = view('public.sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}
