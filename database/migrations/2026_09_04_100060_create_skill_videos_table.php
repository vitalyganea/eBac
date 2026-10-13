<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Videoclipuri explicative atașate competențelor.
     *
     * Stocăm DOAR identificatorul la furnizor, niciodată fișierul video —
     * redarea se face prin playerul oficial (youtube-nocookie.com pentru minori).
     * `is_embeddable` + `last_checked_at` există pentru că o parte din clipuri au
     * embed-ul dezactivat sau dispar; un job periodic le revalidează.
     */
    public function up(): void
    {
        Schema::create('skill_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 16)->default('youtube');
            $table->string('provider_id', 64);
            $table->string('title');
            $table->string('channel_name')->nullable();
            $table->string('channel_url')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('language', 8)->default('ro');
            $table->string('level', 16)->default('normal');   // scurt | normal | detaliat
            $table->boolean('is_embeddable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('has_creator_consent')->default(false); // parteneriat explicit
            $table->timestamp('last_checked_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->bigInteger('revision')->default(0);
            $table->timestamps();

            $table->unique(['provider', 'provider_id', 'skill_id']);
            $table->index(['skill_id', 'is_active', 'sort_order']);
            $table->index('revision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_videos');
    }
};
