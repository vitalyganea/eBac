<?php

namespace App\Http\Middleware;

use App\Domain\Xp\XpCalculator;
use App\Models\LearningGap;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props partajate pe toate paginile.
     *
     * Atenție: orice pui aici ajunge în HTML-ul fiecărei pagini. Nimic sensibil,
     * niciodată date de corectare, niciodată numele real al altui elev.
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    // Ce vede elevul despre SINE: numele ales, nu pseudonimul.
                    'username' => $user->username,
                    'public_alias' => $user->public_alias,
                    'username_pending' => $user->usernameIsPending(),
                    'avatar_pending' => $user->avatarIsPending(),
                    'avatar' => $user->avatar,
                    'avatar_url' => $user->avatarUrl(64),
                    'role' => $user->role?->value ?? 'student',
                    'locale' => $user->locale,
                    'profile' => $user->profile?->value,
                    'exam_target' => $user->exam_target,
                    'is_premium' => $user->isPremium(),
                ] : null,
            ],

            // Bara de gamificare, prezentă pe fiecare ecran.
            'gamification' => $user ? fn () => [
                'xp' => $user->xp,
                'level' => $user->level,
                'rating' => $user->rating,
                'streak_days' => $user->streak_days,
                'xp_today' => $user->xp_today,
                'daily_cap' => XpCalculator::DAILY_CAP,
            ] : null,

            // Câte lucruri nemarcate ca lămurite are elevul. E un COUNT pe
            // index, deci ieftin — dar apare în antet pe fiecare pagină, așa că
            // nu are voie să fie altceva decât ieftin.
            'open_gaps' => $user
                ? LearningGap::where('user_id', $user->id)->whereNull('resolved_at')->count()
                : 0,

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                // Indiciul dezvăluit acum: circulă prin flash, deci nu rămâne în
                // HTML-ul paginii următoare.
                'hint' => fn () => $request->session()->get('hint'),
            ],
        ];
    }
}
