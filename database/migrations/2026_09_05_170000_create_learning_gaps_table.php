<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * „Nu înțeleg asta" — istoricul personal al dificultăților.
     *
     * Scopul nu e statistic, ci practic: elevul ajunge la profesor sau la
     * meditator cu o listă concretă de întrebări, în loc de „nu prea înțeleg
     * derivatele".
     *
     * De aceea captura e la nivel de BLOC (precisă), dar organizarea e pe
     * COMPETENȚĂ (așa gândește și predă un profesor). `skill_id` e denormalizat
     * exact pentru gruparea asta.
     *
     * `excerpt` e o fotografie a textului la momentul marcării: dacă lecția se
     * editează sau se șterge, elevul trebuie să înțeleagă în continuare ce
     * anume marcase.
     *
     * PRIVAT. Nimeni altcineva nu vede lista — nici alți elevi, nici
     * administratorii. Dacă elevul ar bănui că e urmărit, ar înceta să
     * recunoască ce nu înțelege, iar funcția și-ar pierde tot rostul.
     */
    public function up(): void
    {
        Schema::create('learning_gaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();

            // Null = elevul a marcat toată tema, nu un bloc anume. Se întâmplă
            // când vine dinspre un exercițiu greșit, nu dinspre lecție.
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->string('block_id', 16)->nullable();
            $table->string('block_type', 24)->nullable();

            $table->text('excerpt')->nullable();

            // Întrebarea pe care elevul vrea s-o pună. E câmpul care transformă
            // lista într-un instrument de discuție.
            $table->text('note')->nullable();

            $table->string('source', 24)->default('lesson'); // lesson | practice

            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            // Același bloc nu se marchează de două ori.
            $table->unique(['user_id', 'block_id'], 'learning_gaps_unique_block');
            $table->index(['user_id', 'resolved_at']);
            $table->index(['user_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_gaps');
    }
};
