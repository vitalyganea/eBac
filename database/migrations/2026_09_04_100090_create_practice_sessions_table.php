<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mode', 24);            // practice | duel | daily | mock | boss | flashcards
            $table->jsonb('config')->nullable();   // skill-uri țintă, dificultate, limită de timp

            // Duelul-fantomă: jucăm împotriva unei sesiuni ÎNREGISTRATE a altui elev.
            // Așa duelul funcționează cu un singur utilizator online — nu avem nevoie
            // de matchmaking în timp real ca să livrăm senzația de 1v1.
            $table->foreignId('ghost_session_id')->nullable()->constrained('practice_sessions')->nullOnDelete();

            $table->foreignId('blueprint_id')->nullable()->constrained('exam_blueprints')->nullOnDelete();
            $table->unsignedSmallInteger('total_items')->default(0);
            $table->unsignedSmallInteger('answered_items')->default(0);
            $table->unsignedSmallInteger('correct_items')->default(0);
            $table->decimal('score', 6, 2)->default(0);
            $table->unsignedInteger('xp_awarded')->default(0);
            $table->smallInteger('rating_delta')->nullable();
            $table->boolean('is_ranked')->default(false);
            $table->string('source', 16)->default('web');   // web | ios | android
            $table->string('app_version', 24)->nullable();
            $table->timestamp('expires_at')->nullable();     // sesiunile abandonate expiră
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'mode', 'started_at']);
            $table->index(['mode', 'completed_at']);
            $table->index('expires_at');
        });

        /**
         * Ce item a fost servit, în ce poziție și CÂND.
         *
         * `served_at` e măsurat pe server și e singura bază legitimă pentru
         * cronometraj. Timpul raportat de client e decor: pe mobil clientul e
         * complet în mâna atacatorului.
         */
        Schema::create('session_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('practice_sessions')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->timestamp('served_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->unsignedTinyInteger('hints_used')->default(0);
            $table->timestamps();

            $table->unique(['session_id', 'position']);
            $table->index(['session_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_items');
        Schema::dropIfExists('practice_sessions');
    }
};
