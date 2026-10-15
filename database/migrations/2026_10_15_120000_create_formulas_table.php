<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Secțiunea „Formule" — a patra zonă a unei discipline.
     *
     * O formulă e conținut de referință, nu un item: nu se corectează și nu
     * dă XP (§6). Se leagă de competență, ca să ducă spre lecția și spre
     * exercițiile temei.
     *
     * `annex` = testele ANCE în a căror anexă a apărut formula (`r_sb26`,
     * `u_ss24`…). Anexa se schimbă de la test la test, deci eticheta onestă e
     * „a apărut la 11 din 12 teste", nu „o primești la examen".
     */
    public function up(): void
    {
        Schema::create('formulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->nullable()->constrained()->nullOnDelete();

            $table->string('slug', 80);
            $table->string('name');
            $table->text('latex');

            // Ce spune formula, când se folosește — text cu $...$ intercalate.
            $table->text('body');
            // [[simbol, sens], ...]
            $table->jsonb('variables')->nullable();
            // Greșeala tipică, ca blocul `warning` din lecții.
            $table->text('pitfall')->nullable();

            $table->jsonb('annex')->default('[]');

            $table->string('source');
            $table->string('source_ref');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['subject_id', 'slug']);
        });

        /**
         * Ce a bifat elevul în modul „ascunde și verifică".
         *
         * Separat de `mastery`, ca `lesson_progress`: a recunoaște o formulă nu
         * înseamnă a ști s-o aplici. Mastery-ul vine din răspunsuri.
         */
        Schema::create('formula_progress', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('formula_id')->constrained()->cascadeOnDelete();
            // known | review
            $table->string('status', 8);
            $table->timestamps();

            $table->primary(['user_id', 'formula_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formula_progress');
        Schema::dropIfExists('formulas');
    }
};
