<?php

namespace App\Http\Controllers\Web\Pub;

use App\Domain\Seo\PageMeta;
use App\Http\Controllers\Controller;
use App\Models\ExamBlueprint;
use App\Models\Season;
use App\Models\Subject;
use Illuminate\Contracts\View\View;

/**
 * Pagini despre EXAMENUL în sine: structura probelor și calendarul.
 *
 * Sunt cele mai sezoniere pagini din tot proiectul — „când e BAC-ul" și „câți
 * itemi are testul la X" se caută masiv în primăvară. Se generează integral din
 * `exam_blueprints` și `seasons`, deci se actualizează odată cu datele, nu prin
 * rescriere manuală în fiecare an.
 */
class ExamController extends Controller
{
    public function index(string $year): View
    {
        $season = Season::where('exam_target', "bac-{$year}")->first()
            ?? Season::where('is_active', true)->firstOrFail();

        $blueprints = ExamBlueprint::query()
            ->where('is_active', true)
            ->with('subject')
            ->get()
            ->groupBy('subject.slug');

        $daysLeft = $season->daysUntilExam();

        $description = $daysLeft !== null
            ? "Structura probelor, punctajul și calendarul examenului național de bacalaureat {$year} din Republica Moldova. Au mai rămas {$daysLeft} de zile."
            : "Structura probelor și punctajul la examenul național de bacalaureat {$year} din Republica Moldova, pe programele oficiale ANCE.";

        return view('public.exam', [
            'meta' => new PageMeta(
                title: "BAC {$year} Moldova — structura probelor și calendar",
                description: $description,
                canonical: route('public.exam', $year),
                breadcrumbs: [
                    ['name' => 'eBac', 'url' => route('public.home')],
                    ['name' => "BAC {$year}", 'url' => route('public.exam', $year)],
                ],
                ogType: 'article',
            ),
            'year' => $year,
            'season' => $season,
            'blueprints' => $blueprints,
            'navSubjects' => Subject::query()
                ->withCount(['items as public_items_count' => fn ($q) => $q->practicePool()])
                ->orderBy('sort_order')->get()
                ->filter(fn ($s) => $s->public_items_count > 0)->take(3),
        ]);
    }
}
