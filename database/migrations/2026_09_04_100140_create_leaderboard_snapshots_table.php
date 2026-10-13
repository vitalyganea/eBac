<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clasamentele LIVE stau în Redis (sorted sets): ZINCRBY la scriere,
     * ZREVRANGE / ZREVRANK la citire — O(log n), nu `ORDER BY xp DESC` pe zeci
     * de mii de rânduri la fiecare afișare.
     *
     * Acest tabel păstrează doar INSTANTANEE la închiderea perioadei, ca istoricul
     * de sezoane să supraviețuiască unui flush de Redis.
     */
    public function up(): void
    {
        Schema::create('leaderboard_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 24);              // moldova | subject | daily | weekly
            $table->string('scope_key', 64)->nullable(); // id-ul școlii / disciplinei
            $table->string('metric', 16);             // xp | rating
            $table->string('period', 16);             // daily | weekly | season
            $table->string('period_key', 32);         // 2026-09-04 | 2026-W36 | bac-2027-s1
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('rank');
            $table->unsignedBigInteger('score');
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->unique(['scope', 'scope_key', 'metric', 'period', 'period_key', 'user_id'], 'lb_snapshot_unique');
            $table->index(['scope', 'period', 'period_key', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_snapshots');
    }
};
