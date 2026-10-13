<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Avatar încărcat, pe lângă cel emoji.
     *
     * Emoji-ul rămâne opțiunea implicită și cea fără risc: nu ocupă spațiu, nu
     * cere moderare și e disponibil instant. Imaginea încărcată e un plus, nu un
     * înlocuitor — dacă lipsește, se cade pe emoji, apoi pe un implicit.
     *
     * Se stochează calea de BAZĂ, fără dimensiune și fără extensie: fișierele
     * reale sunt `{path}-64.webp` și `{path}-256.webp`. Așa putem adăuga o
     * dimensiune nouă fără migrație.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path', 120)->nullable()->after('avatar');
            $table->timestamp('avatar_updated_at')->nullable()->after('avatar_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_path', 'avatar_updated_at']);
        });
    }
};
