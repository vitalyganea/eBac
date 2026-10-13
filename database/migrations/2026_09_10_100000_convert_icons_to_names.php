<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `subjects.icon` și `achievements.icon` trec de la emoji la nume de iconițe.
 *
 * De ce: emoji-urile de steag (🇷🇴, 🇬🇧, 🇲🇩) NU se randează ca steaguri pe
 * Windows — perechea de indicatori regionali apare ca două litere („RO"). Restul
 * emoji-urilor variază în desen și în lățime de la un Android la altul, nu se
 * pot colora în tema aplicației și nu pot avea grosime de linie uniformă cu
 * cromatica din jur.
 *
 * Maparea se face pe SLUG, nu pe emoji: emoji-urile stocate au selectori de
 * variație invizibili (⚛️ = U+269B U+FE0F), iar o comparație pe conținut ar rata
 * tăcut rândurile care au un octet în plus.
 *
 * Coloana crește de la 16 la 40 de caractere: „graduation-cap" nu încăpea.
 * Orice valoare rămasă nemapată devine null, iar `Subject::iconName()` /
 * `Achievement::iconName()` întorc iconița implicită.
 */
return new class extends Migration
{
    private const SUBJECTS = [
        'matematica' => 'radical',
        'limba-romana' => 'book-open-text',
        'istoria' => 'globe',
        'limba-engleza' => 'languages',
        'fizica' => 'atom',
        'chimia' => 'flask-conical',
        'biologia' => 'dna',
        'informatica' => 'laptop',
        'geografia' => 'map',
        'limba-romana-alolingvi' => 'messages-square',
    ];

    private const ACHIEVEMENTS = [
        'prima-victorie' => 'medal',
        'streak-7' => 'flame',
        'streak-30' => 'flame',
        'formule-100' => 'brain',
        'retentie-50' => 'target',
        'duelist-50' => 'swords',
        'boss-slayer' => 'skull',
        'top-100' => 'map-pin',
        'bac-ready' => 'graduation-cap',
    ];

    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('icon', 40)->nullable()->change();
        });

        Schema::table('achievements', function (Blueprint $table) {
            $table->string('icon', 40)->nullable()->change();
        });

        foreach (self::SUBJECTS as $slug => $icon) {
            DB::table('subjects')->where('slug', $slug)->update(['icon' => $icon]);
        }

        foreach (self::ACHIEVEMENTS as $slug => $icon) {
            DB::table('achievements')->where('slug', $slug)->update(['icon' => $icon]);
        }

        // Rândurile adăugate manual, în afara seederelor, ar rămâne cu un emoji
        // pe care setul de iconițe nu-l cunoaște.
        DB::table('subjects')->whereNotIn('slug', array_keys(self::SUBJECTS))->update(['icon' => null]);
        DB::table('achievements')->whereNotIn('slug', array_keys(self::ACHIEVEMENTS))->update(['icon' => null]);
    }

    public function down(): void
    {
        $subjects = [
            'matematica' => '📐',
            'limba-romana' => '🇷🇴',
            'istoria' => '🌍',
            'limba-engleza' => '🇬🇧',
            'fizica' => '⚛️',
            'chimia' => '🧪',
            'biologia' => '🧬',
            'informatica' => '💻',
            'geografia' => '🗺️',
            'limba-romana-alolingvi' => '🗣️',
        ];

        $achievements = [
            'prima-victorie' => '🏅',
            'streak-7' => '🔥',
            'streak-30' => '🔥',
            'formule-100' => '🧠',
            'retentie-50' => '🎯',
            'duelist-50' => '⚔️',
            'boss-slayer' => '👹',
            'top-100' => '🇲🇩',
            'bac-ready' => '🎓',
        ];

        foreach ($subjects as $slug => $icon) {
            DB::table('subjects')->where('slug', $slug)->update(['icon' => $icon]);
        }

        foreach ($achievements as $slug => $icon) {
            DB::table('achievements')->where('slug', $slug)->update(['icon' => $icon]);
        }

        Schema::table('subjects', function (Blueprint $table) {
            $table->string('icon', 16)->nullable()->change();
        });

        Schema::table('achievements', function (Blueprint $table) {
            $table->string('icon', 16)->nullable()->change();
        });
    }
};
