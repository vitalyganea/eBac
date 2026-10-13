<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- 1. Renunțăm la licee ----------------------------------------
        //
        // Platforma nu presupune că utilizatorul e înscris undeva: pot fi elevi
        // care repetă examenul, candidați care învață pe cont propriu sau oameni
        // care se pregătesc din timp. Un clasament pe licee cere o apartenență
        // pe care nu toți o au, iar cererea datelor de școală de la minori fără
        // o utilizare clară e un cost fără beneficiu.
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
        });

        Schema::dropIfExists('schools');

        // ---- 2. Clasamentele devin implicite ----------------------------
        //
        // Participarea nu mai e opt-in: un clasament la care nu participă nimeni
        // nu e un clasament. Protecția care conta se păstrează intactă — în
        // clasamente apare EXCLUSIV `username`, niciodată numele real — iar
        // utilizatorul se poate retrage oricând din setări.
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('leaderboard_opt_in')->default(true)->change();
        });

        DB::table('users')->update(['leaderboard_opt_in' => true]);

        // ---- 3. Clasament PE DISCIPLINĂ ----------------------------------
        //
        // „La matematică sunt al 34-lea din 938" cere XP contorizat per
        // disciplină, nu doar global. `attempts.subject_id` e denormalizat din
        // item ca recalcularea și interogările per disciplină să nu facă join
        // pe un tabel de zeci de milioane de rânduri.
        Schema::table('attempts', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable()->after('skill_id')
                ->constrained()->nullOnDelete();
            $table->index(['user_id', 'subject_id', 'answered_at']);
        });

        DB::statement('
            UPDATE attempts SET subject_id = items.subject_id
            FROM items WHERE items.id = attempts.item_id
        ');

        /**
         * Stare DERIVATĂ per utilizator × disciplină. Reconstruibilă integral din
         * `attempts`, ca și `mastery`.
         */
        Schema::create('subject_stats', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();

            $table->unsignedBigInteger('xp')->default(0);
            $table->unsignedSmallInteger('rating')->default(1000);
            $table->unsignedInteger('attempts_count')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->primary(['user_id', 'subject_id']);
            // Clasamentul se poate reconstrui din DB dacă Redis se golește.
            $table->index(['subject_id', 'xp']);
        });

        // Popularea inițială din istoricul existent.
        DB::statement('
            INSERT INTO subject_stats
                (user_id, subject_id, xp, attempts_count, correct_count, last_activity_at, created_at, updated_at)
            SELECT user_id, subject_id,
                   coalesce(sum(xp_awarded), 0),
                   count(*),
                   sum(case when is_correct then 1 else 0 end),
                   max(answered_at), now(), now()
            FROM attempts
            WHERE subject_id IS NOT NULL
            GROUP BY user_id, subject_id
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_stats');

        Schema::table('attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subject_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('leaderboard_opt_in')->default(false)->change();
        });

        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('locality')->nullable();
            $table->string('district')->nullable();
            $table->string('type', 32)->nullable();
            $table->string('language', 8)->default('ro');
            $table->boolean('is_verified')->default(false);
            $table->unsignedInteger('students_count')->default(0);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
        });
    }
};
