<?php

namespace App\Http\Controllers\Web;

use App\Domain\Learning\MarkGap;
use App\Models\LearningGap;
use App\Models\Lesson;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * „Ce nu am înțeles" — istoricul personal al elevului.
 *
 * Lista e privată și nu se afișează nicăieri altundeva. Rostul ei e să fie
 * DUSĂ la cineva: profesor, meditator, coleg.
 */
class GapController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $gaps = LearningGap::where('user_id', $user->id)
            ->with(['skill.subject', 'skill.lesson'])
            ->orderByRaw('resolved_at is not null')
            ->orderByDesc('created_at')
            ->get();

        // Gruparea pe disciplină → competență. Un profesor nu poate lucra cu o
        // listă plată de fragmente; poate lucra cu „la derivate are trei
        // probleme, iată care".
        $grouped = $gaps
            ->groupBy(fn (LearningGap $g) => $g->skill->subject->name)
            ->map(fn ($bySubject, $subjectName) => [
                'subject' => $subjectName,
                'subject_slug' => $bySubject->first()->skill->subject->slug,
                'skills' => $bySubject
                    ->groupBy(fn (LearningGap $g) => $g->skill->name)
                    ->map(fn ($items, $skillName) => [
                        'skill' => $skillName,
                        'skill_slug' => $items->first()->skill->slug,
                        'open_count' => $items->whereNull('resolved_at')->count(),
                        // Dacă tema nu are lecție publicată, nu are unde
                        // trimite — linkul nu se afișează deloc, în loc să ducă
                        // la 404.
                        'has_lesson' => $items->first()->skill->lesson !== null,

                        'items' => $items->map(fn (LearningGap $g) => [
                            'id' => $g->id,
                            'block_id' => $g->block_id,
                            'label' => $g->blockLabel(),
                            'excerpt' => $g->excerpt,
                            'note' => $g->note,
                            'source' => $g->source,
                            'resolved' => $g->isResolved(),
                            'created_at' => $g->created_at->toDateString(),
                        ])->values(),
                    ])->values(),
            ])->values();

        return Inertia::render('Gaps/Index', [
            'groups' => $grouped,
            'open_total' => $gaps->whereNull('resolved_at')->count(),
            'resolved_total' => $gaps->whereNotNull('resolved_at')->count(),
        ]);
    }

    public function store(Request $request, MarkGap $mark): RedirectResponse
    {
        $data = $request->validate([
            'skill_id' => ['required', 'integer', 'exists:skills,id'],
            'lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],

            // Listă, nu un singur bloc: dintr-un exercițiu greșit, elevul poate
            // arăta mai multe părți din lecție care nu i-au fost clare. Goală
            // sau absentă = marchează toată tema.
            'block_ids' => ['nullable', 'array', 'max:20'],
            'block_ids.*' => ['string', 'max:16'],

            'note' => ['nullable', 'string', 'max:1000'],
            'source' => ['nullable', 'string', 'in:lesson,practice'],
        ]);

        $skill = Skill::findOrFail($data['skill_id']);
        $lesson = isset($data['lesson_id']) ? Lesson::find($data['lesson_id']) : null;
        $blockIds = array_filter($data['block_ids'] ?? []);

        // Nota se atașează primului element: e o singură întrebare, chiar dacă
        // trimite spre mai multe locuri din lecție.
        $note = $data['note'] ?? null;

        foreach ($blockIds ?: [null] as $blockId) {
            $mark(
                user: $request->user(),
                skill: $skill,
                lesson: $lesson,
                blockId: $blockId,
                note: $note,
                source: $data['source'] ?? 'lesson',
            );

            $note = null;
        }

        return back(fallback: route('gaps.index'))
            ->with('success', 'Am notat. Îl găsești în „Ce nu am înțeles".');
    }

    public function update(Request $request, MarkGap $mark, LearningGap $gap): RedirectResponse
    {
        abort_unless($gap->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'action' => ['required', 'string', 'in:resolve,reopen'],
        ]);

        $data['action'] === 'resolve'
            ? $mark->resolve($request->user(), $gap->id)
            : $mark->reopen($request->user(), $gap->id);

        return back(fallback: route('gaps.index'));
    }

    public function destroy(Request $request, LearningGap $gap): RedirectResponse
    {
        abort_unless($gap->user_id === $request->user()->id, 403);

        $gap->delete();

        return back(fallback: route('gaps.index'));
    }
}
