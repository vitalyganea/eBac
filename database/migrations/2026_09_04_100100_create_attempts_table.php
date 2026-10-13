<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ==========================================================================
     *  `attempts` ESTE SINGURA SURSĂ DE ADEVĂR DIN TOATĂ APLICAȚIA.
     * ==========================================================================
     *
     * Un attempt e un FAPT imuabil: „userul 42 a răspuns B la itemul 991 la
     * 14:03:22, în 6.4 secunde". Tabelul e strict append-only — fără UPDATE,
     * fără DELETE, fără `updated_at`.
     *
     * De aici derivă TOT: xp, level, rating, streak, mastery, achievements,
     * clasamente, estimarea de notă. Nimic din ele nu e sursă de adevăr; toate
     * se pot reconstrui prin recalculare.
     *
     * Două consecințe practice:
     *
     *  1. Când schimbi formula de XP (și o vei schimba de câteva ori), rulezi o
     *     recalculare, nu o migrație distructivă.
     *
     *  2. Sincronizarea cu viitoarea aplicație mobilă devine trivială: faptele
     *     imuabile nu intră niciodată în conflict între ele. Nu există merge de
     *     rezolvat — clientul trimite evenimente, serverul deduplică pe
     *     `client_uuid` și recalculează. Starea derivată nu se sincronizează
     *     niciodată în ambele sensuri; se descarcă de la server.
     */
    public function up(): void
    {
        Schema::create('attempts', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Generat de CLIENT. Face endpointul de submit idempotent: rețelele
            // mobile duplică request-uri, iar fără asta un elev care intră în
            // tunel primește XP dublu.
            $table->uuid('client_uuid')->unique();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('practice_sessions')->nullOnDelete();

            // Competența principală, denormalizată din item_skills, pentru ca
            // interogările de mastery să nu facă join la fiecare citire.
            $table->foreignId('skill_id')->nullable()->constrained('skills')->nullOnDelete();

            $table->jsonb('answer');                      // ce a trimis elevul
            $table->boolean('is_correct');
            // Punctaj parțial 0..1 — itemii de tip „next_step" / „order_steps" se
            // corectează pe pași, ca baremul analitic real de la BAC.
            $table->decimal('score', 4, 3)->default(0);

            $table->unsignedInteger('client_ms')->nullable();  // raportat de client (informativ)
            $table->unsignedInteger('server_ms')->nullable();  // served_at → submit (autoritar)
            $table->unsignedTinyInteger('hints_used')->default(0);

            // answered_at = când a răspuns efectiv elevul (poate fi offline)
            // synced_at   = când a ajuns faptul la server
            // Streak-ul se calculează pe answered_at, altfel elevul care a învățat
            // în autobuz și a sincronizat dimineața își pierde streak-ul pe nedrept.
            $table->timestamp('answered_at')->index();
            $table->timestamp('synced_at')->nullable();

            $table->string('context', 24);                 // practice|duel|daily|mock|boss|flashcards
            $table->string('source', 16)->default('web');  // web|ios|android
            $table->string('app_version', 24)->nullable();

            // Răspunsurile date offline dau XP, streak și mastery, dar NU intră în
            // rating-ul competitiv: timpul nu a putut fi măsurat pe server.
            $table->boolean('is_ranked')->default(true);

            $table->unsignedSmallInteger('xp_awarded')->default(0);
            $table->smallInteger('rating_delta')->nullable();
            $table->decimal('item_rating_before', 8, 2)->nullable(); // pentru recalibrare
            $table->decimal('user_rating_before', 8, 2)->nullable();

            // Fără updated_at: tabelul e imuabil prin construcție.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'answered_at']);
            $table->index(['user_id', 'skill_id', 'answered_at']);
            $table->index(['item_id', 'is_ranked']);
            $table->index(['session_id', 'answered_at']);
            // La ~50M rânduri/an tabelul se partiționează pe answered_at.
            // Nu acum — dar coloana de partiționare există deja.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
