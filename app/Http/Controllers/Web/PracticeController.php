<?php

namespace App\Http\Controllers\Web;

use App\Domain\Attempts\SubmitAnswer;
use App\Domain\Exams\FinishMock;
use App\Domain\Exams\MockClosed;
use App\Domain\Exams\MockReport;
use App\Domain\Sessions\RevealHint;
use App\Domain\Sessions\ServeNextItem;
use App\Domain\Sessions\StartSession;
use App\Enums\SessionMode;
use App\Http\Requests\SubmitAnswerRequest;
use App\Http\Resources\AttemptFeedbackResource;
use App\Http\Resources\ItemForStudentResource;
use App\Models\Item;
use App\Models\Lesson;
use App\Models\PracticeSession;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controllerul NU conține logică de business.
 *
 * Traduce HTTP → domeniu → răspuns Inertia. Exact aceleași trei acțiuni de
 * domeniu (StartSession, ServeNextItem, SubmitAnswer) vor fi apelate de
 * Api\V1\AttemptController pentru aplicația mobilă. De asta nu vom rescrie
 * niciodată corectarea sau XP-ul a doua oară.
 */
class PracticeController extends Controller
{
    public function start(Request $request, StartSession $startSession): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'exists:subjects,slug'],
            // Simularea pornește din MockExamController: itemii ei vin din
            // structura examenului, nu din selecția adaptivă.
            'mode' => ['nullable', 'string', 'in:practice,daily,boss'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['integer', 'exists:skills,id'],
        ]);

        $subject = Subject::where('slug', $validated['subject'])->firstOrFail();
        $mode = SessionMode::from($validated['mode'] ?? 'practice');

        $session = $startSession(
            user: $request->user(),
            subjectId: $subject->id,
            mode: $mode,
            count: config("ebac.session_sizes.{$mode->value}", 10),
            skillIds: $validated['skills'] ?? [],
        );

        return to_route('practice.play', $session->uuid);
    }

    public function play(Request $request, PracticeSession $session, ServeNextItem $server): Response
    {
        abort_unless($session->user_id === $request->user()->id, 403);

        $isMock = $session->mode === SessionMode::Mock;

        // Simularea se încheie când e predată, când expiră termenul (pe server)
        // sau când nu mai are întrebări. Rezultatul se arată abia atunci.
        if ($isMock && ($session->isComplete() || $session->isExpired())) {
            return $this->mockResult($request, $session);
        }

        $sessionItem = $server->next($session);

        if ($sessionItem === null && $isMock) {
            return $this->mockResult($request, $session);
        }

        if ($sessionItem === null) {
            return Inertia::render('Practice/Complete', [
                'session' => [
                    'uuid' => $session->uuid,
                    'mode' => $session->mode->value,
                    'mode_label' => $session->mode->label(),
                    'progress' => $server->progress($session),
                ],
            ]);
        }

        return Inertia::render('Practice/Play', [
            'session' => [
                'uuid' => $session->uuid,
                'mode' => $session->mode->value,
                'mode_label' => $session->mode->label(),
                'progress' => $server->progress($session),
            ],
            // Itemul pleacă spre client DOAR prin resursa cu listă albă de câmpuri.
            // `answer` și `explanation` rămân pe server până după submit.
            'item' => new ItemForStudentResource(
                $sessionItem->item,
                hintsAllowed: $session->mode->allowsHints(),
                sessionItem: $sessionItem,
            ),
            'feedback' => null,
            'submitted' => null,
            'lesson' => null,
            'has_next' => true,
            'exam' => $isMock ? $this->examProps($session, $sessionItem->position) : null,
        ]);
    }

    /**
     * Ce vede elevul în timpul simulării: termenul (al serverului) și secțiunea.
     * `server_now` permite clientului să-și corecteze ceasul: un telefon cu ora
     * greșită ar număra altfel, chiar dacă termenul real rămâne cel de aici.
     */
    private function examProps(PracticeSession $session, int $position): array
    {
        $section = collect($session->config['sections'] ?? [])
            ->first(fn (array $s) => in_array($position, $s['positions'], true));

        return [
            'deadline_at' => $session->expires_at?->toIso8601String(),
            'server_now' => now()->toIso8601String(),
            'section' => $section['name'] ?? null,
        ];
    }

    private function mockResult(Request $request, PracticeSession $session): Response
    {
        app(FinishMock::class)($session);

        return Inertia::render('Exam/Result', [
            'subject' => $session->subject?->only(['slug', 'name']),
            'result' => app(MockReport::class)->build($session->refresh(), $request),
        ]);
    }

    /**
     * Randează ACELAȘI item, plus feedback-ul.
     *
     * Nu redirecționăm către practice.play, pentru că acolo ServeNextItem ar
     * livra deja itemul următor, iar elevul nu ar mai vedea de ce a greșit.
     * Trecerea la itemul următor e o acțiune explicită a elevului.
     */
    /**
     * Dezvăluie indiciul următor.
     *
     * Trece prin server anume ca penalizarea de XP să fie reală: dacă textele ar
     * veni odată cu itemul, elevul le-ar citi din props și nimic nu l-ar
     * împiedica să pretindă că n-a folosit niciunul.
     */
    public function hint(Request $request, PracticeSession $session, RevealHint $reveal): RedirectResponse
    {
        abort_unless($session->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'item_uuid' => ['required', 'uuid', 'exists:items,uuid'],
        ]);

        try {
            $hint = $reveal->next($session, $validated['item_uuid']);
        } catch (\RuntimeException $e) {
            return back(fallback: route('practice.play', $session->uuid))->with('error', $e->getMessage());
        }

        return back(fallback: route('practice.play', $session->uuid))->with('hint', $hint);
    }

    /**
     * Rezumatul blocurilor din lecția competenței, pentru selecția „ce nu înțeleg".
     *
     * Doar id, tip și un fragment scurt — nu tot conținutul lecției. Se trimite
     * exclusiv după un răspuns, nu odată cu fiecare item servit.
     *
     * @return array<string, mixed>|null
     */
    private function lessonSummary(Item $item): ?array
    {
        $skill = $item->skills->first();

        if ($skill === null) {
            return null;
        }

        $lesson = Lesson::published()->where('skill_id', $skill->id)->first();

        if ($lesson === null) {
            return null;
        }

        $markable = ['text', 'formula', 'plot', 'example', 'warning', 'table', 'code', 'video'];

        return [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'skill_slug' => $skill->slug,
            'blocks' => collect($lesson->blocks)
                ->filter(fn (array $b) => isset($b['id']) && in_array($b['type'] ?? '', $markable, true))
                ->map(fn (array $b) => [
                    'id' => $b['id'],
                    'type' => $b['type'],
                    'label' => Str::limit(
                        strip_tags((string) ($b['text'] ?? $b['latex'] ?? $b['problem'] ?? $b['caption'] ?? $b['title'] ?? '')),
                        90,
                    ),
                ])
                ->values(),
        ];
    }

    public function answer(
        SubmitAnswerRequest $request,
        PracticeSession $session,
        SubmitAnswer $submit,
        ServeNextItem $server,
    ): Response|RedirectResponse {
        abort_unless($session->user_id === $request->user()->id, 403);

        // În simulare nu există feedback pe parcurs: răspunsul se înregistrează
        // și elevul trece la întrebarea următoare. Corectarea o vede la final.
        if ($session->mode === SessionMode::Mock) {
            try {
                $submit($request->user(), $request->toData());
            } catch (MockClosed $e) {
                return to_route('practice.play', $session->uuid)->with('error', $e->getMessage());
            }

            return to_route('practice.play', $session->uuid);
        }

        $result = $submit($request->user(), $request->toData());

        $item = $result->attempt->item->load('skills');
        $sessionItem = $session->sessionItems->firstWhere('item_id', $item->id);

        return Inertia::render('Practice/Play', [
            'session' => [
                'uuid' => $session->uuid,
                'mode' => $session->mode->value,
                'mode_label' => $session->mode->label(),
                'progress' => $server->progress($session->refresh()),
            ],
            'item' => new ItemForStudentResource(
                $item,
                hintsAllowed: $session->mode->allowsHints(),
                sessionItem: $sessionItem,
            ),
            // Abia ACUM ajung la client cheia de corectare și explicația.
            'feedback' => (new AttemptFeedbackResource($result))->toArray($request),

            // Blocurile lecției temei, ca elevul să poată arăta EXACT ce parte
            // din teorie nu i-e clară — nu doar „nu înțeleg tema".
            'lesson' => $this->lessonSummary($item),
            'submitted' => $request->input('answer'),
            'has_next' => $session->answered_items < $session->total_items,
            'exam' => null,
        ]);
    }
}
