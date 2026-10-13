<?php

namespace App\Http\Controllers\Web;

use App\Models\LearningGap;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Skill;
use App\Models\SkillVideo;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Secțiunea „Curs": teoria pe competențe.
 *
 * Lecția vine ÎNAINTE de test în ordinea firească — dar nu e obligatorie.
 * Un elev care știe materia poate merge direct la exerciții.
 */
class CourseController extends Controller
{
    public function index(Request $request, string $slug): Response
    {
        $user = $request->user();
        $subject = Subject::where('slug', $slug)->firstOrFail();
        abort_unless($user->hasSubject($subject->id), 404);

        $completed = LessonProgress::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->pluck('lesson_id')
            ->all();

        $chapters = Skill::query()
            ->where('subject_id', $subject->id)
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->where('is_active', true)->with('lesson')->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Skill $chapter) => [
                'name' => $chapter->name,
                'skills' => $chapter->children->map(fn (Skill $s) => [
                    'slug' => $s->slug,
                    'name' => $s->name,
                    'has_lesson' => $s->lesson !== null && $s->lesson->status === 'published',
                    'reading_minutes' => $s->lesson?->readingMinutes(),
                    'is_completed' => $s->lesson && in_array($s->lesson->id, $completed, true),
                ])->all(),
            ])
            ->filter(fn (array $c) => $c['skills'] !== [])
            ->values();

        return Inertia::render('Course/Index', [
            'subject' => ['slug' => $subject->slug, 'name' => $subject->name, 'icon' => $subject->iconName()],
            'chapters' => $chapters,
        ]);
    }

    public function show(Request $request, string $slug, string $skillSlug): Response
    {
        $user = $request->user();
        $subject = Subject::where('slug', $slug)->firstOrFail();
        abort_unless($user->hasSubject($subject->id), 404);

        $skill = Skill::where('subject_id', $subject->id)->where('slug', $skillSlug)->firstOrFail();
        $lesson = Lesson::published()->where('skill_id', $skill->id)->firstOrFail();

        // Blocul spre care s-a venit din lista „Ce nu am înțeles".
        $highlight = $request->string('bloc')->toString() ?: null;

        // Doar dacă blocul chiar există în lecția CURENTĂ: între timp lecția
        // s-a putut edita, iar o evidențiere spre nimic ar deruta.
        if ($highlight !== null && $lesson->findBlock($highlight) === null) {
            $highlight = null;
        }

        return Inertia::render('Course/Show', [
            'subject' => ['slug' => $subject->slug, 'name' => $subject->name, 'icon' => $subject->iconName()],
            'skill' => ['id' => $skill->id, 'slug' => $skill->slug, 'name' => $skill->name, 'curriculum_code' => $skill->curriculum_code],

            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'summary' => $lesson->summary,
                'objectives' => $lesson->objectives ?? [],
                'blocks' => $lesson->blocks,
                'reading_minutes' => $lesson->readingMinutes(),
            ],

            // Videoclipurile atașate competenței. Sunt separate de blocuri: se
            // adaugă și se retrag independent de textul lecției, iar un clip
            // care dispare de pe YouTube nu trebuie să lase o gaură în teorie.
            'videos' => $skill->videos()->get()->map(fn (SkillVideo $v) => [
                'provider_id' => $v->provider_id,
                'title' => $v->title,
                'channel_name' => $v->channel_name,
                'channel_url' => $v->channel_url,
                'duration_seconds' => $v->duration_seconds,
                'is_embeddable' => $v->is_embeddable,
            ]),

            // Ce trebuie știut înainte — din graful de prerechizite, filtrat la
            // competențele care chiar au lecție scrisă.
            'prerequisites' => $skill->prerequisites()
                ->where('is_active', true)
                ->with('lesson')
                ->get()
                ->map(fn (Skill $p) => [
                    'slug' => $p->slug,
                    'name' => $p->name,
                    'has_lesson' => $p->lesson !== null && $p->lesson->status === 'published',
                ]),

            // Blocurile deja marcate — ca butonul să arate starea, nu să
            // permită marcarea de două ori.
            'marked_blocks' => LearningGap::where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->whereNotNull('block_id')
                ->pluck('block_id'),

            'highlight' => $highlight,

            // Nelămurirea corespunzătoare, ca elevul s-o poată închide direct de
            // aici — fără să se întoarcă în listă.
            'highlighted_gap' => $highlight === null ? null : LearningGap::query()
                ->where('user_id', $user->id)
                ->where('block_id', $highlight)
                ->whereNull('resolved_at')
                ->value('id'),

            'is_completed' => LessonProgress::where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->whereNotNull('completed_at')
                ->exists(),
        ]);
    }

    /**
     * Marchează lecția ca parcursă.
     *
     * Deliberat separat de `mastery`: a citi teoria nu înseamnă a ști. Mastery-ul
     * se câștigă din răspunsuri, nu din derulare — de aceea aici nu se acordă XP.
     */
    public function complete(Request $request, string $slug, string $skillSlug): RedirectResponse
    {
        $user = $request->user();
        $subject = Subject::where('slug', $slug)->firstOrFail();
        abort_unless($user->hasSubject($subject->id), 404);

        $skill = Skill::where('subject_id', $subject->id)->where('slug', $skillSlug)->firstOrFail();
        $lesson = Lesson::published()->where('skill_id', $skill->id)->firstOrFail();

        LessonProgress::query()->upsert([[
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'last_block' => count($lesson->blocks ?? []),
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]], ['user_id', 'lesson_id'], ['last_block', 'completed_at', 'updated_at']);

        return back(fallback: route('course.show', [$slug, $skillSlug]))
            ->with('success', 'Lecție marcată ca parcursă.');
    }
}
