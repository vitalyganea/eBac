<?php

namespace App\Models;

use App\Enums\ExamProfile;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

/**
 * Câmpurile `xp`, `level`, `rating`, `streak_days` sunt DERIVATE din `attempts`
 * și denormalizate aici doar pentru citire rapidă. Nu le scrie direct din
 * controllere — treci prin App\Domain\Attempts\SubmitAnswer, ca să rămână
 * reconstruibile.
 */
#[Fillable(['name', 'email', 'password', 'username', 'avatar', 'profile',
    'exam_target', 'graduation_year', 'locale', 'timezone', 'leaderboard_opt_in'])]
#[Hidden(['password', 'remember_token', 'email'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Valori implicite prezente în instanța din memorie, nu doar ca DEFAULT în
     * baza de date.
     *
     * Fără ele, un utilizator abia creat (`User::create([...])` fără `role`) are
     * atributul null până la un `refresh()` — iar props-urile partajate Inertia
     * citesc `$user->role->value` la primul request de după înregistrare.
     * Diferența dintre „default în schemă" și „default în model" e exact ce a
     * spart fluxul de register.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'student',
        'locale' => 'ro',
        'timezone' => 'Europe/Chisinau',
        'xp' => 0,
        'level' => 1,
        'rating' => 1000,
        'rating_peak' => 1000,
        'streak_days' => 0,
        'longest_streak' => 0,
        'xp_today' => 0,
        'leaderboard_opt_in' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'profile' => ExamProfile::class,
            'last_active_date' => 'date',
            'xp_today_date' => 'date',
            'onboarded_at' => 'datetime',
            'premium_until' => 'datetime',
            'leaderboard_opt_in' => 'boolean',
        ];
    }

    /**
     * Disciplinele pe care elevul se pregătește.
     *
     * Alese explicit de el și schimbabile oricând. `profile` NU filtrează
     * nimic — spune doar ce e obligatoriu / la alegere pentru profilul lui, ca
     * ghidaj în ecranul de selecție.
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'user_subjects')
            ->withPivot(['is_primary', 'sort_order', 'added_at'])
            ->orderByPivot('sort_order');
    }

    public function primarySubject(): ?Subject
    {
        return $this->subjects->firstWhere('pivot.is_primary', true)
            ?? $this->subjects->first();
    }

    public function hasSubject(int $subjectId): bool
    {
        return $this->subjects()->whereKey($subjectId)->exists();
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    /** XP și rating per disciplină — baza clasamentelor pe disciplină. */
    public function subjectStats(): HasMany
    {
        return $this->hasMany(SubjectStat::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PracticeSession::class);
    }

    public function mastery(): HasMany
    {
        return $this->hasMany(Mastery::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /** Identitățile externe legate de cont (deocamdată doar Google). */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * Are contul o parolă?
     *
     * Conturile create prin Google n-au. Diferența contează în două locuri:
     * schimbarea parolei devine SETAREA ei, iar ștergerea contului nu se poate
     * confirma cu o parolă inexistentă — acolo se trimite un cod pe email.
     */
    public function hasPassword(): bool
    {
        return filled($this->password);
    }

    /**
     * Panoul de administrare e pentru validarea itemilor. Elevii nu au ce căuta
     * acolo: ar vedea cheile de corectare.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role?->canAccessAdmin() ?? false;
    }

    /**
     * URL-ul avatarului încărcat, la dimensiunea cerută.
     *
     * `avatar_path` NU e în #[Fillable]: se scrie doar de
     * App\Domain\Users\ProcessAvatar, după ce imaginea a fost decodată și
     * reencodată. Un câmp trimis din formular ar însemna cale controlată de
     * utilizator.
     */
    public function avatarUrl(int $size = 64): ?string
    {
        // Imaginea încărcată devine publică abia după aprobare. Emoji-ul, în
        // schimb, vine dintr-o listă fixă și se afișează imediat — majoritatea
        // utilizatorilor nu observă nicio întârziere.
        if (blank($this->avatar_path) || $this->avatar_status !== 'approved') {
            return null;
        }

        // Numele fișierului e aleator și se schimbă la fiecare încărcare, deci
        // nu e nevoie de cache busting.
        return Storage::disk('public')
            ->url("{$this->avatar_path}-{$size}.webp");
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(ContentReport::class, 'reportable');
    }

    public function isPremium(): bool
    {
        return $this->premium_until !== null && $this->premium_until->isFuture();
    }

    /**
     * Numele pe care îl văd CEILALȚI utilizatori.
     *
     * Până la aprobarea unui moderator, elevul apare sub pseudonimul generat.
     * Nimic ales de utilizator nu ajunge în fața altora nevăzut de cineva —
     * publicul e format în mare parte din minori.
     *
     * Pentru ce vede elevul despre SINE, folosește `username` direct.
     */
    public function publicName(): string
    {
        if ($this->username_status === 'approved' && filled($this->username)) {
            return $this->username;
        }

        return $this->public_alias ?? 'elev';
    }

    public function usernameIsPending(): bool
    {
        return $this->username_status === 'pending';
    }

    public function avatarIsPending(): bool
    {
        return filled($this->avatar_path) && $this->avatar_status === 'pending';
    }

    /**
     * Generează pseudonimul public. Se apelează O DATĂ, la înscriere.
     *
     * Nu conține id-ul: un „elev_142" ar spune tuturor câți utilizatori are
     * platforma și în ce ordine s-au înscris.
     */
    public static function generatePublicAlias(): string
    {
        do {
            $alias = 'elev_'.Str::lower(Str::random(6));
        } while (self::where('public_alias', $alias)->exists());

        return $alias;
    }
}
