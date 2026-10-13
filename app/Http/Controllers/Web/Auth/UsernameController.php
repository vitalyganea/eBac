<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Web\Controller;
use App\Rules\AcceptableUsername;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Alegerea numelui public, după prima autentificare cu Google.
 *
 * Există ca ecran separat tocmai pentru că numele NU poate veni de la furnizor.
 * Google întoarce numele real, iar în clasamente apare doar `username` (§9) —
 * dacă am prelua numele automat, l-am face public pe cel real ȘI am ocoli
 * filtrul din `AcceptableUsername`.
 *
 * E al treilea loc din aplicație în care se poate seta un nume, iar §10g cere
 * explicit ca regula să fie prezentă în toate. `UsernameTest` verifică asta.
 */
class UsernameController extends Controller
{
    public function edit(Request $request): Response|RedirectResponse
    {
        // Cine are deja nume n-are ce căuta aici.
        if (filled($request->user()->username)) {
            return to_route('dashboard');
        }

        return Inertia::render('Auth/ChooseUsername', [
            'suggested' => $request->user()->public_alias,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:32', 'alpha_dash',
                'unique:users,username', new AcceptableUsername],
        ]);

        $request->user()->forceFill([
            'username' => $data['username'],
            // Ca la înscrierea obișnuită: numele ales intră în coada de moderare,
            // iar ceilalți văd pseudonimul până la aprobare (§10g).
            'username_status' => 'pending',
        ])->save();

        return to_route('subjects.edit');
    }
}
