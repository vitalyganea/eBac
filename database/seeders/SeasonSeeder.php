<?php

namespace Database\Seeders;

use App\Models\Season;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    /**
     * Sesiunea BAC 2026 s-a desfășurat 2–19 iunie 2026 și s-a încheiat.
     * Cohorta care a început clasa a 12-a în septembrie 2026 dă BAC-ul în 2027.
     *
     * `exam_starts_at` e ancora planului invers: cu 200 de zile rămase platforma
     * predă materie nouă, cu 10 zile rămase refuză și te pune să repari găurile.
     * Data exactă se confirmă din orarul oficial ANCE când apare.
     */
    public function run(): void
    {
        Season::updateOrCreate(['slug' => 'bac-2027-s1'], [
            'name' => 'BAC 2027 · Sezonul 1',
            'exam_target' => 'bac-2027',
            'starts_at' => '2026-09-01',
            'ends_at' => '2027-06-30',
            // Estimare pe baza calendarului obișnuit (începutul lui iunie).
            'exam_starts_at' => '2027-06-01',
            'is_active' => true,
        ]);
    }
}
