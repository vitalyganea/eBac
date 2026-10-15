<?php

namespace App\Http\Controllers\Web;

use App\Enums\ExamProfile;
use App\Models\Formula;
use App\Models\Lesson;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Secțiunea „Formule": a patra zonă a unei discipline.
 *
 * Două moduri, în aceeași pagină: lista de consultat și „ascunde și verifică"
 * — vezi numele, scrii formula pe foaie, o deschizi și spui dacă o știai.
 * Bifele nu dau XP (§6) și nu ating `mastery`: a recunoaște o formulă nu
 * înseamnă a ști s-o aplici.
 */
class FormulaController extends Controller
{
    public function index(Request $request, string $slug): Response
    {
        $user = $request->user();
        $subject = Subject::where('slug', $slug)->firstOrFail();
        abort_unless($user->hasSubject($subject->id), 404);

        $formulas = Formula::query()
            ->where('formulas.subject_id', $subject->id)
            ->leftJoin('skills', 'skills.id', '=', 'formulas.skill_id')
            ->leftJoin('skills as chapters', 'chapters.id', '=', 'skills.parent_id')
            ->orderBy('chapters.sort_order')
            ->orderBy('skills.sort_order')
            ->orderBy('formulas.sort_order')
            ->select('formulas.*')
            ->with('skill')
            ->get();

        $status = DB::table('formula_progress')
            ->where('user_id', $user->id)
            ->whereIn('formula_id', $formulas->pluck('id'))
            ->pluck('status', 'formula_id');

        $withLesson = Lesson::published()
            ->whereIn('skill_id', $formulas->pluck('skill_id')->filter()->unique())
            ->pluck('skill_id')
            ->all();

        // Câte teste are fiecare profil — din datele înseși: orice test ANCE
        // are anexă, deci reuniunea codurilor le cuprinde pe toate.
        $tests = $formulas->flatMap(fn (Formula $f) => $f->annex ?? [])->unique();

        return Inertia::render('Formulas/Index', [
            'subject' => ['slug' => $subject->slug, 'name' => $subject->name, 'icon' => $subject->iconName()],

            // Arte și sport dau testul de la umanist. Fără profil, arătăm ambele.
            'profile' => match ($user->profile) {
                ExamProfile::Real => 'r',
                ExamProfile::Umanist, ExamProfile::Arte, ExamProfile::Sport => 'u',
                default => null,
            },
            'tests' => [
                'r' => $tests->filter(fn (string $c) => str_starts_with($c, 'r_'))->count(),
                'u' => $tests->filter(fn (string $c) => str_starts_with($c, 'u_'))->count(),
            ],

            'formulas' => $formulas->map(fn (Formula $f) => [
                'id' => $f->id,
                'name' => $f->name,
                'latex' => $f->latex,
                'body' => $f->body,
                'variables' => $f->variables,
                'pitfall' => $f->pitfall,
                'annex' => ['r' => $f->annexCount('r'), 'u' => $f->annexCount('u')],
                'skill' => $f->skill ? [
                    'slug' => $f->skill->slug,
                    'name' => $f->skill->name,
                    'has_lesson' => in_array($f->skill_id, $withLesson, true),
                ] : null,
                'status' => $status[$f->id] ?? null,
                'source_ref' => $f->source_ref,
            ])->values(),
        ]);
    }

    public function progress(Request $request, Formula $formula): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasSubject($formula->subject_id), 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(['known', 'review'])],
        ]);

        DB::table('formula_progress')->upsert(
            [[
                'user_id' => $user->id,
                'formula_id' => $formula->id,
                'status' => $data['status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['user_id', 'formula_id'],
            ['status', 'updated_at'],
        );

        return back(fallback: route('formulas.index', $formula->subject->slug));
    }
}
