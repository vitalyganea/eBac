<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Autoevaluarea eseului — producerea scrisă, partea de punctaj pe care
     * itemii nu o pot acoperi (§13: 70 din 100 de puncte la limbi străine).
     *
     * Elevul scrie, apoi se notează singur pe grila baremului ANCE al aceluiași
     * test. Nu e corectare automată și nu se pretinde a fi: volumul se
     * calculează, restul îl bifează elevul.
     */
    public function up(): void
    {
        Schema::create('essay_prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 80)->unique();

            // eseu | articol
            $table->string('kind', 16);
            $table->string('title');
            $table->text('task');

            // Limita de cuvinte, doar unde baremul o punctează (engleză).
            $table->unsignedSmallInteger('word_min')->nullable();
            $table->unsignedSmallInteger('word_max')->nullable();

            // {total, criteria[], errors[], volume}: grila din baremul testului.
            $table->jsonb('rubric');

            $table->string('source');
            $table->string('source_ref');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        /**
         * Eseurile elevului și autoevaluarea lor.
         *
         * Strict private, ca „Ce nu am înțeles" (§10h): un elev care ar bănui
         * că profesorul sau administratorul îi citește ciornele ar scrie
         * pentru ei, nu pentru el. Se șterg odată cu contul.
         */
        Schema::create('essay_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('essay_prompt_id')->constrained()->cascadeOnDelete();

            // Lipsește când elevul a scris pe hârtie și a introdus doar numărul de cuvinte.
            $table->text('body')->nullable();
            $table->unsignedSmallInteger('word_count')->nullable();

            // {criteria: {cheie: puncte}, errors: {cheie: număr}} — ce a bifat elevul.
            $table->jsonb('answers');
            // Punctajul pe fiecare criteriu, calculat pe server.
            $table->jsonb('breakdown');
            $table->unsignedSmallInteger('score');
            $table->unsignedSmallInteger('max_score');

            $table->timestamps();

            $table->index(['user_id', 'essay_prompt_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('essay_attempts');
        Schema::dropIfExists('essay_prompts');
    }
};
