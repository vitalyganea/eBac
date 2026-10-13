<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Itemii sunt independenți de disciplină: `type` + `payload` (JSONB).
     * Un tip nou de mini-joc = un `type` nou + un component Vue. Zero migrații.
     *
     * DECIZIA DE SECURITATE CENTRALĂ:
     *   `payload` = ce se trimite elevului (enunț, variante, imagini)
     *   `answer`  = cheia de corectare — NU PĂRĂSEȘTE NICIODATĂ SERVERUL
     *               înainte ca elevul să trimită răspunsul
     *
     * Sunt coloane separate exact ca să fie imposibil să scapi cheia din greșeală
     * la serializare. `answer` e în $hidden pe model, iar resursele publice nu îl
     * ating deloc.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();          // identificator public, ne-secvențial
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);              // mcq | match | order_steps | next_step | numeric | ...
            $table->jsonb('payload');                // SIGUR de trimis clientului
            $table->jsonb('answer');                 // NICIODATĂ trimis înainte de submit
            $table->text('explanation')->nullable(); // se trimite doar după răspuns
            $table->text('hint')->nullable();
            $table->text('hint_2')->nullable();

            $table->unsignedSmallInteger('difficulty_seed')->default(3); // estimarea autorului 1-5
            $table->unsignedSmallInteger('points')->default(1);          // punctaj în barem
            $table->string('language', 8)->default('ro');
            $table->string('status', 16)->default('draft');   // draft|review|published|retired
            $table->string('source', 64)->nullable();         // ance-2026-real-baza | generated | community
            $table->string('source_ref')->nullable();         // link/pagina exactă
            $table->string('curriculum_code', 64)->nullable();

            // Itemii de holdout se folosesc DOAR în simulări cronometrate, ca să nu
            // se ardă în practice și să rămână valide estimările de notă.
            $table->boolean('is_exam_holdout')->default(false);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->bigInteger('revision')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subject_id', 'status', 'language']);
            $table->index(['status', 'is_exam_holdout']);
            $table->index('type');
            $table->index('revision');
            $table->index('source');
        });

        DB::statement('CREATE INDEX items_payload_gin ON items USING gin (payload jsonb_path_ops)');

        Schema::create('item_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(true);
            $table->decimal('weight', 3, 2)->default(1.00);
            $table->timestamps();

            $table->unique(['item_id', 'skill_id']);
            $table->index(['skill_id', 'is_primary']);
        });

        /**
         * Statistici per item, în tabel separat de `items`.
         *
         * Motiv: `items` e „rece" (se citește mult, se scrie rar, se poate cachea
         * agresiv), iar statisticile sunt „fierbinți" (se scriu la fiecare răspuns).
         * Separarea le lasă pe fiecare cu profilul ei de acces.
         *
         * `rating` e Elo pe ITEM, nu pe user — el face posibile dificultatea
         * adaptivă, matchmaking-ul corect și detectarea automată de itemi stricați.
         */
        Schema::create('item_stats', function (Blueprint $table) {
            $table->foreignId('item_id')->primary()->constrained()->cascadeOnDelete();
            $table->decimal('rating', 8, 2)->default(1200);       // Elo item
            $table->decimal('rating_deviation', 8, 2)->default(350);
            $table->unsignedInteger('attempts_count')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->decimal('p_correct', 5, 4)->nullable();       // rata observată de succes
            $table->unsignedInteger('avg_ms')->nullable();
            $table->decimal('discrimination', 5, 4)->nullable();  // cât de bine separă elevii buni
            $table->unsignedInteger('flag_count')->default(0);
            $table->boolean('needs_review')->default(false);      // ridicat automat de calibrare
            $table->timestamp('last_calibrated_at')->nullable();
            $table->timestamps();

            $table->index('rating');
            $table->index('needs_review');
        });

        // Semnalările elevilor: al doilea mecanism (după Elo) de depistare a
        // itemilor greșiți sau ambigui.
        Schema::create('item_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 32);      // raspuns_gresit | ambiguu | typo | offtopic | altul
            $table->text('comment')->nullable();
            $table->string('status', 16)->default('open'); // open|accepted|rejected
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_reports');
        Schema::dropIfExists('item_stats');
        Schema::dropIfExists('item_skills');
        Schema::dropIfExists('items');
    }
};
