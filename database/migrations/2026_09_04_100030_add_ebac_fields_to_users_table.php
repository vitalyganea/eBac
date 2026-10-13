<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Identitate publică: în clasamente apare DOAR username-ul.
            // Elevii sunt minori — numele real nu se expune niciodată public.
            $table->string('username', 32)->nullable()->unique()->after('name');
            $table->string('avatar', 8)->nullable()->after('username');   // emoji
            $table->string('role', 16)->default('student')->after('avatar'); // student|teacher|editor|admin

            $table->string('profile', 16)->nullable();       // real | umanist | arte | sport
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->string('exam_target', 32)->nullable();   // bac-2027
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->string('locale', 8)->default('ro');
            $table->string('timezone', 64)->default('Europe/Chisinau');

            // --- STARE DERIVATĂ ---
            // Toate câmpurile de mai jos sunt denormalizate din `attempts` pentru
            // citire rapidă. Sursa de adevăr rămâne exclusiv `attempts`; ele se pot
            // reconstrui integral cu `php artisan ebac:recompute-user {id}`.
            $table->unsignedBigInteger('xp')->default(0);
            $table->unsignedSmallInteger('level')->default(1);
            $table->unsignedSmallInteger('rating')->default(1000);   // Elo competitiv
            $table->unsignedSmallInteger('rating_peak')->default(1000);
            $table->unsignedSmallInteger('streak_days')->default(0);
            $table->unsignedSmallInteger('longest_streak')->default(0);
            $table->date('last_active_date')->nullable();
            $table->unsignedInteger('xp_today')->default(0);         // pentru capul zilnic
            $table->date('xp_today_date')->nullable();

            $table->boolean('leaderboard_opt_in')->default(false);   // opt-in explicit, nu opt-out
            $table->timestamp('onboarded_at')->nullable();
            $table->timestamp('premium_until')->nullable();

            $table->index(['exam_target', 'rating']);
            $table->index(['school_id', 'xp']);
            $table->index('last_active_date');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
            $table->dropColumn([
                'username', 'avatar', 'role', 'profile', 'exam_target', 'graduation_year',
                'locale', 'timezone', 'xp', 'level', 'rating', 'rating_peak', 'streak_days',
                'longest_streak', 'last_active_date', 'xp_today', 'xp_today_date',
                'leaderboard_opt_in', 'onboarded_at', 'premium_until',
            ]);
        });
    }
};
