<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Contor global de revizie pentru conținut.
     *
     * Fiecare scriere pe `items` / `skills` / `skill_videos` primește numărul
     * următor. Clientul (web offline sau, mai târziu, aplicația mobilă) ține
     * minte ultima revizie văzută și cere doar diferența:
     *   GET /api/v1/content/manifest?since=1043
     */
    public function up(): void
    {
        DB::statement('CREATE SEQUENCE IF NOT EXISTS content_revision AS bigint START WITH 1');
    }

    public function down(): void
    {
        DB::statement('DROP SEQUENCE IF EXISTS content_revision');
    }
};
