<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ascunderea automată a avatarului, la mai multe raportări independente.
     *
     * Denormalizat pe `users` intenționat: altfel fiecare afișare de clasament ar
     * număra raportările pentru fiecare rând. E stare derivată — se poate
     * reconstrui din `content_reports`.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('avatar_hidden_at')->nullable()->after('avatar_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_hidden_at');
        });
    }
};
