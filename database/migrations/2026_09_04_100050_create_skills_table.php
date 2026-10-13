<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Competențele au DOUĂ structuri distincte, care nu trebuie confundate:
     *
     *  1. IERARHIE (`parent_id`) — arbore: capitol > temă > competență.
     *     Serveste navigarea și afișarea progresului.
     *
     *  2. PREREchIZITE (`skill_edges`) — graf orientat aciclic, care traversează
     *     ierarhia. Serveste diagnosticul: dacă elevul pică „integrale", cobor în
     *     graf și testez părinții ca să găsesc cauza reală, nu simptomul.
     */
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('skills')->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->string('name_ru')->nullable();
            $table->string('curriculum_code', 64)->nullable(); // codul din curriculumul oficial
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('depth')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_leaf')->default(true);         // doar frunzele primesc itemi
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('exam_weight')->default(0); // ponderea la examenul real
            $table->bigInteger('revision')->default(0);        // delta sync
            $table->timestamps();

            $table->unique(['subject_id', 'slug']);
            $table->index(['subject_id', 'parent_id', 'sort_order']);
            $table->index('revision');
            $table->index('curriculum_code');
        });

        Schema::create('skill_edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->foreignId('prerequisite_id')->constrained('skills')->cascadeOnDelete();
            // Cât de esențial e prerechizitul (0..1). Diagnosticul coboară mai
            // întâi pe muchiile cu strength mare.
            $table->decimal('strength', 3, 2)->default(1.00);
            $table->timestamps();

            $table->unique(['skill_id', 'prerequisite_id']);
            $table->index('prerequisite_id');
        });

        // O competență nu poate fi propriul prerechizit. Ciclurile mai lungi sunt
        // blocate în App\Domain\Skills\AddPrerequisite (verificare de traversare).
        DB::statement('ALTER TABLE skill_edges ADD CONSTRAINT skill_edges_no_self_loop CHECK (skill_id <> prerequisite_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_edges');
        Schema::dropIfExists('skills');
    }
};
