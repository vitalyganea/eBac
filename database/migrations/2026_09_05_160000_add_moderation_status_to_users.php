<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Pre-moderare pentru conținutul public, FĂRĂ a bloca utilizatorul.
     *
     * Elevul se înscrie cu numele pe care îl vrea și folosește platforma imediat.
     * Dar până la aprobarea unui om, ceilalți îl văd sub un pseudonim generat.
     * Nimic ales de utilizator nu ajunge în fața altor utilizatori nevăzut de
     * cineva — iar utilizatorii noștri sunt minori.
     *
     * Compromisul opus — a bloca înscrierea până la aprobare — ar omorî
     * conversia și ar transforma coada de moderare într-un blocaj de creștere.
     *
     * `public_alias` e pseudonimul afișat între timp. Se generează o dată și
     * rămâne stabil: dacă s-ar schimba, un elev ar apărea ca persoane diferite
     * în același clasament.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('public_alias', 32)->nullable()->after('username');

            // pending | approved | rejected
            $table->string('username_status', 16)->default('pending')->after('public_alias');

            // pending | approved | rejected — se aplică DOAR imaginilor încărcate.
            // Emoji-urile vin dintr-o listă fixă și nu au ce modera.
            $table->string('avatar_status', 16)->default('approved')->after('avatar_hidden_at');

            $table->timestamp('moderated_at')->nullable()->after('avatar_status');
        });

        // Conturile existente rămân aprobate: au trecut deja prin filtru și sunt
        // vizibile de ceva timp. Nu le retragem retroactiv.
        DB::table('users')->update(['username_status' => 'approved']);

        foreach (DB::table('users')->select('id')->get() as $row) {
            DB::table('users')->where('id', $row->id)->update([
                'public_alias' => 'elev_'.Str::lower(Str::random(6)),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->index(['username_status', 'created_at']);
            $table->index(['avatar_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['username_status', 'created_at']);
            $table->dropIndex(['avatar_status', 'created_at']);
            $table->dropColumn(['public_alias', 'username_status', 'avatar_status', 'moderated_at']);
        });
    }
};
