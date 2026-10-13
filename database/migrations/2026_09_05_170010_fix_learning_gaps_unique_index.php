<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Unicitatea trebuie să includă competența, iar valorile NULL să conteze.
     *
     * Vechiul index `(user_id, block_id)` nu împiedica nimic pentru marcajele
     * la nivel de temă, unde `block_id` e NULL: în Postgres, NULL-urile sunt
     * implicit distincte într-un index unic, deci se puteau crea oricâte
     * duplicate.
     *
     * `NULLS NOT DISTINCT` (Postgres 15+) tratează două NULL-uri ca fiind egale,
     * exact ce ne trebuie: un elev poate marca o temă o singură dată.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE learning_gaps DROP CONSTRAINT IF EXISTS learning_gaps_unique_block');
        DB::statement('DROP INDEX IF EXISTS learning_gaps_unique_block');

        DB::statement('
            ALTER TABLE learning_gaps
            ADD CONSTRAINT learning_gaps_unique_target
            UNIQUE NULLS NOT DISTINCT (user_id, skill_id, block_id)
        ');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE learning_gaps DROP CONSTRAINT IF EXISTS learning_gaps_unique_target');
        DB::statement('ALTER TABLE learning_gaps ADD CONSTRAINT learning_gaps_unique_block UNIQUE (user_id, block_id)');
    }
};
