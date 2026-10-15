<?php

namespace App\Http\Controllers\Web;

use App\Domain\Essays\InvalidSelfAssessment;
use App\Domain\Essays\ScoreEssay;
use App\Models\EssayAttempt;
use App\Models\EssayPrompt;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Autoevaluarea producerii scrise: scrii, apoi te notezi singur pe grila
 * baremului ANCE al aceluiași test.
 *
 * Nu dă XP (§6): punctajul îl alege elevul, deci orice recompensă l-ar
 * împinge să-și umfle nota. Eseurile sunt strict private — doar autorul le
 * vede, ca lista „Ce nu am înțeles" (§10h).
 */
class EssayController extends Controller
{
    public function index(Request $request, string $slug): Response
    {
        $user = $request->user();
        $subject = Subject::where('slug', $slug)->firstOrFail();
        abort_unless($user->hasSubject($subject->id), 404);

        $prompts = EssayPrompt::where('subject_id', $subject->id)->orderBy('sort_order')->get();

        $attempts = EssayAttempt::where('user_id', $user->id)
            ->whereIn('essay_prompt_id', $prompts->pluck('id'))
            ->latest()
            ->get();

        return Inertia::render('Essays/Index', [
            'subject' => ['slug' => $subject->slug, 'name' => $subject->name],
            'prompts' => $prompts->map(fn (EssayPrompt $p) => [
                'slug' => $p->slug,
                'kind' => $p->kind,
                'title' => $p->title,
                'session' => self::sessionLabel($p->source),
                'total' => $p->rubric['total'],
                'best' => $attempts->where('essay_prompt_id', $p->id)->max('score'),
            ])->values(),
            'history' => $attempts->take(10)->map(fn (EssayAttempt $a) => [
                'id' => $a->id,
                'title' => $prompts->firstWhere('id', $a->essay_prompt_id)?->title,
                'score' => $a->score,
                'max' => $a->max_score,
                'date' => $a->created_at->toIso8601String(),
            ])->values(),
        ]);
    }

    public function show(Request $request, EssayPrompt $essayPrompt): Response
    {
        abort_unless($request->user()->hasSubject($essayPrompt->subject_id), 404);

        $subject = $essayPrompt->subject;

        return Inertia::render('Essays/Write', [
            'subject' => ['slug' => $subject->slug, 'name' => $subject->name],
            'prompt' => [
                'slug' => $essayPrompt->slug,
                'kind' => $essayPrompt->kind,
                'title' => $essayPrompt->title,
                'session' => self::sessionLabel($essayPrompt->source),
                'task' => $essayPrompt->task,
                'words' => $essayPrompt->word_min ? [$essayPrompt->word_min, $essayPrompt->word_max] : null,
                'rubric' => $essayPrompt->rubric,
                'source_ref' => $essayPrompt->source_ref,
                // Engleza e în engleză: corectorul ortografic al browserului
                // trebuie să știe limba, altfel subliniază totul.
                'lang' => $subject->slug === 'limba-engleza' ? 'en' : 'ro',
            ],
        ]);
    }

    public function store(Request $request, EssayPrompt $essayPrompt, ScoreEssay $score): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasSubject($essayPrompt->subject_id), 404);

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:20000'],
            'word_count' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'criteria' => ['array'],
            'criteria.*' => ['integer', 'min:0', 'max:20'],
            'errors' => ['array'],
            'errors.*' => ['integer', 'min:0', 'max:500'],
        ]);

        $body = trim((string) ($data['body'] ?? '')) ?: null;

        // Textul scris aici se numără pe server; cel scris pe hârtie, după
        // cifra introdusă de elev.
        $words = $body !== null ? ScoreEssay::countWords($body) : ($data['word_count'] ?? null);

        try {
            $result = $score($essayPrompt->rubric, $data['criteria'] ?? [], $data['errors'] ?? [], $words);
        } catch (InvalidSelfAssessment $e) {
            throw ValidationException::withMessages([$e->field => $e->getMessage()]);
        }

        $attempt = EssayAttempt::create([
            'user_id' => $user->id,
            'essay_prompt_id' => $essayPrompt->id,
            'body' => $body,
            'word_count' => $words,
            'answers' => ['criteria' => $data['criteria'] ?? [], 'errors' => $data['errors'] ?? []],
            'breakdown' => $result['breakdown'],
            'score' => $result['score'],
            'max_score' => $result['max'],
        ]);

        return redirect()->route('essays.attempt', $attempt);
    }

    public function attempt(Request $request, EssayAttempt $essayAttempt): Response
    {
        // Doar autorul. 404, nu 403: nici existența eseului altcuiva nu se divulgă.
        abort_unless($essayAttempt->user_id === $request->user()->id, 404);

        $prompt = $essayAttempt->prompt;

        return Inertia::render('Essays/Result', [
            'subject' => ['slug' => $prompt->subject->slug, 'name' => $prompt->subject->name],
            'prompt' => ['slug' => $prompt->slug, 'title' => $prompt->title, 'task' => $prompt->task, 'kind' => $prompt->kind],
            'attempt' => [
                'body' => $essayAttempt->body,
                'word_count' => $essayAttempt->word_count,
                'breakdown' => $essayAttempt->breakdown,
                'score' => $essayAttempt->score,
                'max' => $essayAttempt->max_score,
                'date' => $essayAttempt->created_at->toIso8601String(),
            ],
        ]);
    }

    /** `ance:12_len_barem_sb26` → „BAC 2026, sesiunea de bază". */
    private static function sessionLabel(string $source): string
    {
        preg_match('/(?:([ru])_)?(s[bs])(\d\d)$/', $source, $m);

        $label = 'BAC 20'.($m[3] ?? '').', '.(($m[2] ?? '') === 'sb' ? 'sesiunea de bază' : 'sesiunea suplimentară');

        return match ($m[1] ?? '') {
            'r' => "{$label}, profil real",
            'u' => "{$label}, profil umanist",
            default => $label,
        };
    }
}
