<?php

namespace App\Http\Controllers\Web\Auth;

use App\Enums\ExamProfile;
use App\Http\Controllers\Web\Controller;
use App\Models\User;
use App\Rules\AcceptableUsername;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuthController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function showRegister(): Response
    {
        return Inertia::render('Auth/Register', [
            'profiles' => array_map(
                fn (ExamProfile $p) => ['value' => $p->value, 'label' => $p->label()],
                ExamProfile::cases(),
            ),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            // Username-ul e identitatea PUBLICĂ. Elevii sunt minori: în clasamente
            // și în dueluri nu apare niciodată numele real.
            'username' => ['required', 'string', 'min:3', 'max:32', 'alpha_dash',
                'unique:users,username', new AcceptableUsername],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            // Opțional, și doar informativ: sugerează ce discipline sunt
            // obligatorii pentru el. NU restrânge la ce are acces.
            'profile' => ['nullable', 'string', 'in:real,umanist,arte,sport'],
        ]);

        // Intrarea utilizatorului trece prin fill() (deci prin #[Fillable]).
        // Starea controlată de aplicație se setează DIRECT, nu prin mass-assignment:
        // `onboarded_at` și `exam_target` nu vin niciodată din formular, iar
        // `role` e intenționat absent din Fillable ca escaladarea de privilegii
        // să fie imposibilă printr-un câmp injectat în payload.
        $user = new User;
        $user->fill([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'profile' => $data['profile'] ?? null,
        ]);
        $user->password = Hash::make($data['password']);

        // Numele ales intră în coada de moderare. Elevul îl vede pe al lui și
        // folosește platforma normal; ceilalți văd pseudonimul până la aprobare.
        $user->public_alias = User::generatePublicAlias();
        $user->username_status = 'pending';
        $user->exam_target = config('ebac.default_exam_target');
        $user->save();

        event(new Registered($user));
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        // Pasul următor e alegerea disciplinelor, nu panoul: fără ele nu are ce
        // exersa. `onboarded_at` se setează abia când chiar a ales
        // (UpdateUserSubjects).
        return to_route('subjects.edit');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        if (! Auth::attempt(
            ['email' => $credentials['email'], 'password' => $credentials['password']],
            (bool) ($credentials['remember'] ?? true),
        )) {
            throw ValidationException::withMessages([
                'email' => 'Datele de autentificare nu sunt corecte.',
            ]);
        }

        $request->session()->regenerate();

        return to_route('dashboard');
    }

    public function logout(Request $request): SymfonyResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // `public.home`, nu `home`: pagina publică e în grupul de rute prefixate
        // (§14). Numele greșit nu dă eroare la înregistrarea rutei — abia la
        // apăsarea butonului, după ce sesiunea a fost deja invalidată.
        //
        // `Inertia::location()`, nu `to_route()`: butonul e apăsat dintr-o pagină
        // Inertia, deci cererea pleacă prin XHR. Un 302 ar fi urmat tot de XHR,
        // care ar primi pagina publică Blade — fără anteturi Inertia, deci
        // clientul deschide modalul de eroare în loc să navigheze. 409 cu
        // X-Inertia-Location îi spune să facă o navigare reală a browserului,
        // singura ieșire din SPA spre zona publică (§14).
        return Inertia::location(route('public.home'));
    }
}
