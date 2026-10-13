<?php

namespace App\Http\Controllers\Web;

use App\Domain\Attempts\SubmitAnswer;
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
            'mode' => ['nullable', 'string', 'in:practice,daily,mock,boss'],
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

        $sessionItem = $server->next($session);

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
    ): Response {
        abort_unless($session->user_id === $request->user()->id, 403);

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
        ]);
    }
}
