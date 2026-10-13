<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Teoria unei competențe — secțiunea „Curs".
     *
     * Conținutul e o listă ORDONATĂ de blocuri în JSONB, nu HTML liber:
     *
     *   heading   titlu de secțiune
     *   text      paragraf, cu formule $...$ intercalate
     *   formula   formulă centrată, evidențiată
     *   plot      grafic de funcție, desenat ca SVG din puncte precalculate
     *   figure    figură geometrică, tot SVG
     *   example   exemplu rezolvat, pas cu pas
     *   warning   greșeala tipică — ce pierde puncte la examen
     *   item      trimitere la un item existent, ca exercițiu în text
     *   video     videoclip explicativ (prin skill_videos)
     *
     * De ce blocuri și nu HTML: se pot randa diferit pe mobil, se pot traduce
     * bloc cu bloc pentru segmentul alolingv, se pot valida automat, și un
     * editor nu poate injecta markup arbitrar.
     */
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->string('summary', 400)->nullable();

            // „Ce vei ști după lecția asta" — 2-4 puncte, afișate la început.
            $table->jsonb('objectives')->nullable();

            $table->jsonb('blocks');

            $table->unsignedSmallInteger('reading_minutes')->nullable();
            $table->string('status', 16)->default('draft'); // draft | review | published
            $table->string('language', 8)->default('ro');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            // Aceeași secvență globală ca la itemi: delta sync pentru offline.
            $table->bigInteger('revision')->default(0);
            $table->timestamps();

            $table->index(['status', 'language']);
            $table->index('revision');
        });

        DB::statement('CREATE INDEX lessons_blocks_gin ON lessons USING gin (blocks jsonb_path_ops)');

        /**
         * Progresul elevului prin lecție.
         *
         * Separat de `mastery`: a citi teoria nu înseamnă a ști. Mastery-ul vine
         * din răspunsuri; asta arată doar ce a parcurs, ca să știm de unde reia.
         */
        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('last_block')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->primary(['user_id', 'lesson_id']);
            $table->index(['user_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('lessons');
    }
};
