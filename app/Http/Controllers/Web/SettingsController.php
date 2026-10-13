<?php

namespace App\Http\Controllers\Web;

use App\Domain\Attempts\PurgeUserData;
use App\Domain\Users\AccountDeletionCode;
use App\Domain\Users\ProcessAvatar;
use App\Enums\ExamProfile;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Season;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Settings/Edit', [
            'settings' => [
                'username' => $user->username,
                'avatar' => $user->avatar,
                // Elevul își vede propria imagine chiar și în așteptare —
                // altfel n-ar ști ce a încărcat.
                'avatar_url' => $user->avatar_path
                    ? Storage::disk('public')->url($user->avatar_path.'-256.webp')
                    : null,
                'public_alias' => $user->public_alias,
                'username_pending' => $user->usernameIsPending(),
                'avatar_pending' => $user->avatarIsPending(),
                'profile' => $user->profile?->value,
                'exam_target' => $user->exam_target,
                'timezone' => $user->timezone,
                'leaderboard_opt_in' => (bool) $user->leaderboard_opt_in,
                'email' => $user->email,

                // Un cont creat prin Google n-are parolă: interfața trebuie să
                // ofere SETAREA ei, nu schimbarea, iar ștergerea se confirmă cu
                // un cod pe email, nu cu o parolă inexistentă.
                'has_password' => $user->hasPassword(),
                'google_email' => $user->socialAccounts()
                    ->where('provider', 'google')
                    ->value('provider_email'),
            ],

            'profiles' => array_map(
                fn (ExamProfile $p) => ['value' => $p->value, 'label' => $p->label()],
                ExamProfile::cases(),
            ),

            'seasons' => Season::orderByDesc('starts_at')->get()
                ->map(fn (Season $s) => ['value' => $s->exam_target, 'label' => $s->name]),

            // Lista scurtă acoperă Moldova plus destinațiile obișnuite ale
            // elevilor plecați, care dau totuși BAC-ul aici.
            'timezones' => [
                'Europe/Chisinau', 'Europe/Bucharest', 'Europe/Moscow',
                'Europe/Rome', 'Europe/Berlin', 'Europe/London', 'UTC',
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        // Dacă un moderator a cerut redenumirea, alegerea unui nume care trece
        // filtrul o încheie. Fără asta, elevul rămâne blocat pentru totdeauna —
        // construisem blocarea fără eliberare.
        if ($data['username'] !== $user->username) {
            $user->forceFill([
                // Numele schimbat e un nume nou: trece din nou prin moderare.
                'username_status' => 'pending',
                'username_reset_required' => false,
                'public_alias' => $user->public_alias ?: User::generatePublicAlias(),
            ])->save();
        }

        $user->fill([
            'username' => $data['username'],
            'avatar' => $data['avatar'] ?? null,
            'profile' => $data['profile'] ?? null,
            'exam_target' => $data['exam_target'] ?? $user->exam_target,
            'timezone' => $data['timezone'],
            'leaderboard_opt_in' => $data['leaderboard_opt_in'],
        ])->save();

        return back(fallback: route('settings.edit'))->with('success', 'Setările au fost salvate.');
    }

    /**
     * Încarcă o imagine de profil.
     *
     * Validarea din request e doar prima barieră — `image` și `mimes` se bazează
     * pe ce spune fișierul despre el însuși. Verificarea reală se face în
     * ProcessAvatar, care încearcă efectiv să decodeze imaginea și scrie una nouă.
     */
    public function updateAvatar(Request $request, ProcessAvatar $process): RedirectResponse
    {
        $request->validate([
            'avatar' => [
                'required', 'file', 'image',
                'mimes:jpeg,jpg,png,webp',
                'max:'.(ProcessAvatar::MAX_BYTES / 1024),
                'dimensions:min_width=64,min_height=64',
            ],
        ], [
            'avatar.max' => 'Imaginea nu poate depăși 2 MB.',
            'avatar.dimensions' => 'Imaginea trebuie să aibă cel puțin 64×64 pixeli.',
        ]);

        try {
            $path = $process($request->user(), $request->file('avatar'));
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['avatar' => $e->getMessage()]);
        }

        $request->user()->forceFill([
            'avatar_path' => $path,
            'avatar_updated_at' => now(),
            // Fiecare imagine nouă trece prin moderare, inclusiv la înlocuire —
            // altfel un avatar aprobat ar fi un permis pentru orice urmează.
            'avatar_status' => 'pending',
            'avatar_hidden_at' => null,
        ])->save();

        return back(fallback: route('settings.edit'))->with('success', 'Imaginea de profil a fost actualizată.');
    }

    public function destroyAvatar(Request $request, ProcessAvatar $process): RedirectResponse
    {
        $process->delete($request->user());

        $request->user()->forceFill([
            'avatar_path' => null,
            'avatar_updated_at' => null,
            'avatar_status' => 'approved',
            'avatar_hidden_at' => null,
        ])->save();

        return back(fallback: route('settings.edit'))->with('success', 'Imaginea de profil a fost ștearsă.');
    }

    /**
     * Schimbă parola — sau o SETEAZĂ, la un cont creat prin Google.
     *
     * Fără ramura a doua, cine s-a înscris cu Google ar rămâne legat de el
     * pentru totdeauna: nicio cale de a-și face o parolă, deci niciun acces dacă
     * pierde contul Google.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        $areParola = $user->hasPassword();

        $data = $request->validate([
            'current_password' => [$areParola ? 'required' : 'nullable', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if ($areParola && ! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Parola actuală nu este corectă.',
            ]);
        }

        $request->user()->forceFill(['password' => Hash::make($data['password'])])->save();

        return back(fallback: route('settings.edit'))->with('success', 'Parola a fost schimbată.');
    }

    /**
     * Trimite codul care confirmă ștergerea, pentru conturile fără parolă.
     */
    public function sendDeletionCode(Request $request, AccountDeletionCode $coduri): RedirectResponse
    {
        // Cine are parolă o folosește pe ea: un email în plus ar fi doar o cale
        // suplimentară de atac asupra aceluiași cont.
        abort_if($request->user()->hasPassword(), 400);

        $coduri->send($request->user());

        return back(fallback: route('settings.edit'))
            ->with('success', 'Ți-am trimis un cod pe email. E valabil 15 minute.');
    }

    /**
     * Ștergerea contului și a datelor.
     *
     * Confirmarea diferă după cum contul are sau nu parolă. La cele create prin
     * Google, `Hash::check` ar întoarce mereu `false` pentru un hash null — adică
     * ștergerea ar eșua TĂCUT, iar obligația din Legea nr. 133/2011 (§9) n-ar
     * putea fi onorată. De aceea acolo confirmă un cod trimis pe email.
     */
    public function destroy(Request $request, PurgeUserData $purge, AccountDeletionCode $coduri): SymfonyResponse
    {
        $user = $request->user();

        if ($user->hasPassword()) {
            $request->validate(['password' => ['required', 'string']]);

            if (! Hash::check($request->input('password'), $user->password)) {
                throw ValidationException::withMessages([
                    'password' => 'Parola nu este corectă.',
                ]);
            }
        } else {
            $request->validate(['code' => ['required', 'string']]);

            if (! $coduri->check($user, $request->input('code'))) {
                throw ValidationException::withMessages([
                    'code' => 'Codul nu este corect sau a expirat. Cere altul.',
                ]);
            }
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $purge($user);

        // Ca la deconectare: ieșirea din zona Inertia spre Blade cere o navigare
        // reală a browserului, altfel XHR-ul primește pagina publică și clientul
        // Inertia deschide modalul de eroare peste ea (§14).
        //
        // `->with()` flash-uiește la apel, deci mesajul ajunge în sesiune
        // indiferent de ramura pe care o ia Inertia::location().
        return Inertia::location(
            to_route('public.home')->with('success', 'Contul și datele tale au fost șterse.')
        );
    }
}
