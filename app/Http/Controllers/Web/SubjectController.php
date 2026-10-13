<?php

namespace App\Http\Controllers\Web;

use App\Domain\Subjects\SubjectCatalog;
use App\Domain\Subjects\UpdateUserSubjects;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function edit(Request $request, SubjectCatalog $catalog): Response
    {
        $user = $request->user();

        return Inertia::render('Subjects/Edit', [
            'subjects' => $catalog->for($user),
            'profile' => $user->profile?->value,
            'profile_label' => $user->profile?->label(),
            'suggested' => $catalog->suggestFor($user),
            'is_onboarding' => $user->subjects()->doesntExist(),
        ]);
    }

    public function update(Request $request, UpdateUserSubjects $update): RedirectResponse
    {
        $data = $request->validate([
            'subject_ids' => ['present', 'array', 'max:12'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'primary_id' => ['nullable', 'integer', 'exists:subjects,id'],
        ]);

        $update($request->user(), $data['subject_ids'], $data['primary_id'] ?? null);

        return to_route('dashboard')->with('success', 'Disciplinele au fost actualizate.');
    }
}
