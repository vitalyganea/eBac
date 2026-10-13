<?php

namespace App\Http\Controllers\Web;

use App\Domain\Moderation\ReportContent;
use App\Domain\Moderation\ReportRejected;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function store(Request $request, ReportContent $report): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'exists:users,username'],
            'subject' => ['required', 'string', 'in:avatar,username'],
            'reason' => ['required', 'string', 'in:ofensator,impersonare,contact,spam,altul'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $target = User::where('username', $data['username'])->firstOrFail();

        try {
            $report($request->user(), $target, $data['subject'], $data['reason'], $data['comment'] ?? null);
        } catch (ReportRejected $e) {
            return back(fallback: route('dashboard'))->with('error', $e->getMessage());
        }

        // Nu confirmăm ce s-a întâmplat cu contul raportat: raportorul nu are de
        // ce să afle dacă ținta a fost sancționată sau nu.
        return back(fallback: route('dashboard'))
            ->with('success', 'Îți mulțumim. Semnalarea a fost trimisă spre verificare.');
    }
}
