<?php

namespace App\Http\Controllers\Web;

use App\Domain\Exams\FinishMock;
use App\Domain\Exams\MockUnavailable;
use App\Domain\Exams\StartMock;
use App\Enums\SessionMode;
use App\Models\PracticeSession;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Simularea BAC: pornire și predare.
 *
 * Jocul propriu-zis trece prin aceleași rute ca antrenamentul
 * (`practice.play`, `practice.answer`) — servirea, cronometrajul și corectarea
 * sunt aceleași acțiuni de domeniu. Diferă doar ce vede elevul pe parcurs.
 */
class MockExamController extends Controller
{
    public function start(Request $request, StartMock $startMock): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'exists:subjects,slug'],
        ]);

        $subject = Subject::where('slug', $validated['subject'])->firstOrFail();

        try {
            $session = $startMock($request->user(), $subject->id);
        } catch (MockUnavailable $e) {
            return back(fallback: route('subject.show', $subject->slug))->with('error', $e->getMessage());
        }

        return to_route('practice.play', $session->uuid);
    }

    /** „Predă lucrarea" înainte de termen. Întrebările rămase valorează zero. */
    public function submit(Request $request, PracticeSession $session, FinishMock $finish): RedirectResponse
    {
        abort_unless($session->user_id === $request->user()->id, 403);
        abort_unless($session->mode === SessionMode::Mock, 404);

        $finish($session);

        return to_route('practice.play', $session->uuid);
    }
}
