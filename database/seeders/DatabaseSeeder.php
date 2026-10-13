<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SubjectSeeder::class,
            SeasonSeeder::class,
            AchievementSeeder::class,
            ExamBlueprintSeeder::class,
            MathContentSeeder::class,
            MathLessonSeeder::class,
            InformaticaContentSeeder::class,
            InformaticaLessonSeeder::class,
            EnglezaContentSeeder::class,
            EnglezaLessonSeeder::class,
            IstorieContentSeeder::class,
            IstorieLessonSeeder::class,
            RomanaContentSeeder::class,
            RomanaLessonSeeder::class,
        ]);
    }
}
