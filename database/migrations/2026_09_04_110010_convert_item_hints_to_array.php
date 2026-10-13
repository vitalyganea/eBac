<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `hint` + `hint_2` → `hints` (JSONB, listă ordonată).
     *
     * Două coloane fixe limitau artificial câte indicii poate avea un item.
     * Obiectivul e ca fiecare întrebare să aibă indicii, cât mai multe, în
     * trepte: primul te împinge spre metodă, ultimul e aproape rezolvarea.
     * O listă nu impune un plafon.
     *
     * `hints_used` crește de la TinyInteger la SmallInteger pentru același motiv.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->jsonb('hints')->nullable()->after('explanation');
        });

        // Migrăm ce există deja, în ordine.
        foreach (DB::table('items')->select('id', 'hint', 'hint_2')->get() as $item) {
            $hints = array_values(array_filter(
                [$item->hint, $item->hint_2],
                fn ($h) => filled($h),
            ));

            if ($hints !== []) {
                DB::table('items')->where('id', $item->id)->update(['hints' => json_encode($hints)]);
            }
        }

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['hint', 'hint_2']);
        });

        Schema::table('attempts', function (Blueprint $table) {
            $table->unsignedSmallInteger('hints_used')->default(0)->change();
        });

        Schema::table('session_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('hints_used')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->text('hint')->nullable();
            $table->text('hint_2')->nullable();
        });

        foreach (DB::table('items')->select('id', 'hints')->whereNotNull('hints')->get() as $item) {
            $hints = json_decode($item->hints, true) ?: [];
            DB::table('items')->where('id', $item->id)->update([
                'hint' => $hints[0] ?? null,
                'hint_2' => $hints[1] ?? null,
            ]);
        }

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('hints');
        });
    }
};
