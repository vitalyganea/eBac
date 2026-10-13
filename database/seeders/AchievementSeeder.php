<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

/** `icon` e un nume din setul din `resources/icons`, verificat de `IconTest`. */
class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            ['prima-victorie', 'Primul pas', 'Primul răspuns corect.', 'medal', 'volume', ['type' => 'correct_answers', 'threshold' => 1], 50],
            ['streak-7', 'O săptămână', '7 zile consecutive de învățare.', 'flame', 'streak', ['type' => 'streak_days', 'threshold' => 7], 300],
            ['streak-30', 'O lună', '30 de zile consecutive.', 'flame', 'streak', ['type' => 'streak_days', 'threshold' => 30], 1500],
            ['formule-100', 'Maestrul formulelor', '100 de formule învățate.', 'brain', 'mastery', ['type' => 'items_mastered', 'threshold' => 100], 800],
            ['retentie-50', 'Nu mai uit', '50 de greșeli reparate după o săptămână.', 'target', 'mastery', ['type' => 'retention_recoveries', 'threshold' => 50], 700],
            ['duelist-50', 'Duelist', '50 de victorii în arenă.', 'swords', 'duel', ['type' => 'duel_wins', 'threshold' => 50], 1000],
            ['boss-slayer', 'Boss Slayer', 'Ai învins primul boss de temă.', 'skull', 'special', ['type' => 'bosses_defeated', 'threshold' => 1], 400],
            ['top-100', 'Top 100 Moldova', 'Ai intrat în primii 100 din țară.', 'map-pin', 'special', ['type' => 'national_rank', 'threshold' => 100], 2000],
            ['bac-ready', 'BAC Ready', 'Mastery peste 80% pe toate competențele examinabile.', 'graduation-cap', 'mastery', ['type' => 'subject_mastery', 'threshold' => 80], 5000],
        ];

        foreach ($achievements as $i => [$slug, $name, $desc, $icon, $category, $criteria, $xp]) {
            Achievement::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $desc,
                'icon' => $icon,
                'category' => $category,
                'criteria' => $criteria,
                'xp_reward' => $xp,
                'sort_order' => $i,
            ]);
        }
    }
}
