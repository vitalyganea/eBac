<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');                          // ro
            $table->string('name_ru')->nullable();           // segmentul alolingv
            $table->string('icon', 16)->nullable();
            $table->string('color', 16)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // Aceeași disciplină are rol diferit după profil: matematica e probă de
        // profil la real, dar disciplină la alegere la umanist.
        Schema::create('subject_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('profile', 16);      // real | umanist | arte | sport
            $table->string('role', 16);         // obligatoriu | profil | la_alegere
            $table->timestamps();

            $table->unique(['subject_id', 'profile']);
            $table->index('profile');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_profile');
        Schema::dropIfExists('subjects');
    }
};
