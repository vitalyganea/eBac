<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'student',
            'profile' => 'real',
            'exam_target' => 'bac-2027',
            'locale' => 'ro',
            'timezone' => 'Europe/Chisinau',
            'onboarded_at' => now(),
            'leaderboard_opt_in' => true,
            // Implicit un cont deja moderat — cazul normal. Fluxul de așteptare
            // se testează explicit, cu ->pending().
            'public_alias' => 'elev_'.Str::lower(Str::random(6)),
            'username_status' => 'approved',
            'avatar_status' => 'approved',
        ];
    }

    /** Cont proaspăt înscris: numele și imaginea așteaptă moderarea. */
    public function pending(): static
    {
        return $this->state(fn () => [
            'username_status' => 'pending',
            'avatar_status' => 'pending',
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
