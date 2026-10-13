<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Structura testului real, VERSIONATĂ pe sesiune.
     *
     * ANCE ajustează structura între sesiuni — la 2026 numărul de itemi a scăzut
     * (Limba română, profil real: 12 → 10; umanist/arte/sport: 15 → 12; Geografie
     * 15 → 14). De aceea structura examenului stă în date, nu în cod: o simulare
     * din 2025 trebuie să rămână reproductibilă după ce se schimbă cea din 2027.
     */
    public function up(): void
    {
        Schema::create('exam_blueprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('session', 32);              // 2026-baza | 2026-suplimentara | 2027-pretestare
            $table->string('profile', 16);              // real | umanist | arte | sport
            $table->unsignedSmallInteger('total_items');
            $table->unsignedSmallInteger('total_points');
            $table->unsignedSmallInteger('duration_minutes');
            // Secțiunile: [{name, items, points, types:[], skills:[]}]
            $table->jsonb('structure');
            $table->string('source_url')->nullable();   // link către documentul ANCE
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['subject_id', 'session', 'profile']);
            $table->index(['is_active', 'profile']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_blueprints');
    }
};
