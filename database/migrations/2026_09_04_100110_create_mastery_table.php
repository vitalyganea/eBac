<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stare DERIVATĂ din `attempts` — se poate reconstrui integral.
     *
     * Ține două roluri într-un singur rând, pentru că folosesc aceleași date:
     *   - mastery (cât de bine stăpânește elevul competența)
     *   - programarea repetiției spațiate (`next_review_at`, `ease_factor`)
     */
    public function up(): void
    {
        Schema::create('mastery', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();

            $table->decimal('mastery', 5, 4)->default(0);       // 0..1
            $table->decimal('confidence', 5, 4)->default(0);    // scade cu timpul, crește cu volumul
            $table->unsignedInteger('attempts_count')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedSmallInteger('correct_streak')->default(0);

            // Repetiție spațiată (SM-2 simplificat)
            $table->decimal('ease_factor', 4, 2)->default(2.50);
            $table->unsignedSmallInteger('interval_days')->default(0);
            $table->timestamp('next_review_at')->nullable();
            $table->timestamp('last_practiced_at')->nullable();

            $table->timestamps();

            $table->primary(['user_id', 'skill_id']);
            $table->index(['user_id', 'next_review_at']);   // „ce trebuie repetat azi"
            $table->index(['user_id', 'mastery']);          // „cele mai slabe competențe"
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mastery');
    }
};
