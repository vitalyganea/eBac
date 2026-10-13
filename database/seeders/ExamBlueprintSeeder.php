<?php

namespace Database\Seeders;

use App\Enums\ExamProfile;
use App\Models\ExamBlueprint;
use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Structura probelor, versionată pe sesiune.
 *
 * ANCE a redus numărul de itemi începând cu sesiunea 2026:
 *   Limba și literatura română — profil real 12 → 10 itemi;
 *                                umanist/arte/sport 15 → 12 itemi
 *   Geografie                  — 15 → 14
 *   Pregătire sportivă         — 15 → 14
 *   Limba rusă, Subiectul A    — 8 → 6
 *
 * De aceea structura stă în date și nu în cod: o simulare din 2026 trebuie să
 * rămână reproductibilă după ce se schimbă structura din 2027.
 *
 * `mock_items` spune câți itemi din bancă reprezintă o secțiune în simulare
 * (vezi App\Domain\Exams\MockPlan). Secțiunile fără `types` sau cu
 * `auto_gradable: false` nu se simulează și se declară ca atare. Valorile de mai
 * jos sunt un punct de plecare — confirmă-le din programele oficiale ANCE
 * înainte de a le folosi în simulări cu estimare de notă.
 */
class ExamBlueprintSeeder extends Seeder
{
    public function run(): void
    {
        $math = Subject::where('slug', 'matematica')->first();
        $romana = Subject::where('slug', 'limba-romana')->first();
        $informatica = Subject::where('slug', 'informatica')->first();
        $engleza = Subject::where('slug', 'limba-engleza')->first();
        $istoria = Subject::where('slug', 'istoria')->first();

        if ($math) {
            ExamBlueprint::updateOrCreate([
                'subject_id' => $math->id,
                'session' => '2026-baza',
                'profile' => ExamProfile::Real->value,
            ], [
                'total_items' => 10,
                'total_points' => 100,
                'duration_minutes' => 180,
                'structure' => [
                    ['name' => 'Itemi cu răspuns scurt', 'items' => 4, 'points' => 20, 'types' => ['numeric', 'mcq'],
                        'mock_items' => 4],
                    // La examen, rezolvări complete corectate pe barem. În bancă le
                    // reprezintă itemii pe pași: pasul următor, ordinea pașilor și
                    // „unde s-a pierdut punctul".
                    ['name' => 'Rezolvare desfășurată', 'items' => 6, 'points' => 80,
                        'types' => ['next_step', 'order_steps', 'find_error'], 'mock_items' => 6],
                ],
                'source_url' => 'https://ance.gov.md/clasa-sesiunea-examen/sesiunea-2026-0',
                'is_active' => true,
            ]);
        }

        if ($informatica) {
            // Structura reală din programa ANCE 2021: 4 subiecte, 100 de puncte,
            // 180 de minute. Subiectele II și III se rezolvă în Pascal SAU C/C++,
            // la alegerea candidatului.
            ExamBlueprint::updateOrCreate([
                'subject_id' => $informatica->id,
                'session' => '2026-baza',
                'profile' => ExamProfile::Real->value,
            ], [
                'total_items' => 4,
                'total_points' => 100,
                'duration_minutes' => 180,
                'structure' => [
                    ['name' => 'Subiectul I', 'items' => 1, 'points' => 25,
                        'types' => ['mcq', 'numeric', 'fill_blanks'], 'mock_items' => 3,
                        'note' => 'Itemi scurți: tipuri de date, informația, sisteme de numerație, algebră booleană.'],
                    ['name' => 'Subiectul II', 'items' => 1, 'points' => 32,
                        'types' => ['code_output', 'find_error'], 'mock_items' => 2,
                        'note' => 'Analiză și elaborare de program. Pascal sau C/C++, la alegerea candidatului.'],
                    ['name' => 'Subiectul III', 'items' => 1, 'points' => 30,
                        'types' => ['order_steps', 'code_output'], 'mock_items' => 2,
                        'note' => 'Problemă de programare cu date structurate sau subprograme.'],
                    ['name' => 'Subiectul IV', 'items' => 1, 'points' => 13,
                        'types' => ['match', 'mcq'], 'mock_items' => 2,
                        'note' => 'Circuite logice / baze de date.'],
                ],
                'source_url' => 'https://ance.gov.md/sites/default/files/programa_bac_informatica_romana.pdf',
                'is_active' => true,
            ]);
        }

        if ($engleza) {
            // Programa ANCE 2021 „Limba străină": nivel B1 (CECRL 2018),
            // 3 subiecte, 180 de minute, 30 + 40 + 30 = 100 de puncte.
            //
            // ATENȚIE la proporție: 70 din 100 de puncte sunt PRODUCERE SCRISĂ,
            // deci nu sunt auto-corectabile. Simulările pot acoperi automat doar
            // Subiectul I; pentru II și III e nevoie de autoevaluare pe barem.
            foreach ([ExamProfile::Real, ExamProfile::Umanist, ExamProfile::Arte, ExamProfile::Sport] as $profile) {
                ExamBlueprint::updateOrCreate([
                    'subject_id' => $engleza->id,
                    'session' => '2026-baza',
                    'profile' => $profile->value,
                ], [
                    'total_items' => 3,
                    'total_points' => 100,
                    'duration_minutes' => 180,
                    'structure' => [
                        ['name' => 'Subiectul I', 'items' => 1, 'points' => 30,
                            'types' => ['mcq', 'fill_blanks', 'match'],
                            'auto_gradable' => true,
                            'mock_items' => 5,
                            'note' => 'Comprehensiunea scrisă a unui text nonliterar/funcțional necunoscut de 400-450 de cuvinte.'],
                        ['name' => 'Subiectul II', 'items' => 1, 'points' => 40,
                            'types' => [],
                            'auto_gradable' => false,
                            'note' => 'Eseu structurat de 180-200 de cuvinte pe o temă actuală. Cere autoevaluare pe barem.'],
                        ['name' => 'Subiectul III', 'items' => 1, 'points' => 30,
                            'types' => [],
                            'auto_gradable' => false,
                            'note' => 'Articol de presă sau scrisoare pe o temă de cultură a țării alofone.'],
                    ],
                    'source_url' => 'https://ance.gov.md/sites/default/files/programa_bac_limba_straina_4.pdf',
                    'is_active' => true,
                ]);
            }
        }

        if ($istoria) {
            // Programa ANCE 2021: 4 subiecte, 24 + 22 + 19 + 35 = 100 de puncte,
            // 180 de minute. Obligatorie la profil umanist.
            //
            // Subiectul al IV-lea (35 de puncte, cel mai mare) cere răspuns extins
            // argumentat pe 2-3 surse — nu e auto-corectabil. Restul sunt itemi
            // pe surse, hărți și tabele, majoritatea automatizabili.
            foreach ([ExamProfile::Umanist, ExamProfile::Real] as $profile) {
                ExamBlueprint::updateOrCreate([
                    'subject_id' => $istoria->id,
                    'session' => '2026-baza',
                    'profile' => $profile->value,
                ], [
                    'total_items' => 4,
                    'total_points' => 100,
                    'duration_minutes' => 180,
                    'structure' => [
                        ['name' => 'Subiectul I', 'items' => 1, 'points' => 24,
                            'types' => ['mcq', 'match', 'fill_blanks'],
                            'auto_gradable' => true,
                            'mock_items' => 2,
                            'note' => 'Terminologie și lucru cu sursa scrisă.'],
                        ['name' => 'Subiectul II', 'items' => 1, 'points' => 22,
                            'types' => ['timeline', 'mcq'],
                            'auto_gradable' => true,
                            'mock_items' => 2,
                            'note' => 'Cronologie, hărți, tabele și diagrame.'],
                        ['name' => 'Subiectul III', 'items' => 1, 'points' => 19,
                            'types' => ['mcq', 'match'],
                            'auto_gradable' => true,
                            'mock_items' => 2,
                            'note' => 'Comparare de surse, cauzalitate.'],
                        ['name' => 'Subiectul IV', 'items' => 1, 'points' => 35,
                            'types' => [],
                            'auto_gradable' => false,
                            'note' => 'Răspuns extins argumentat pe 2-3 surse. Cere autoevaluare pe barem.'],
                    ],
                    'source_url' => 'https://ance.gov.md/sites/default/files/programa_bac_istoria_romanilor_si_universala_romana.pdf',
                    'is_active' => true,
                ]);
            }
        }

        if ($romana) {
            // Profil real: 10 itemi din sesiunea 2026 (de la 12).
            ExamBlueprint::updateOrCreate([
                'subject_id' => $romana->id,
                'session' => '2026-baza',
                'profile' => ExamProfile::Real->value,
            ], [
                'total_items' => 10,
                'total_points' => 100,
                'duration_minutes' => 180,
                'structure' => [
                    ['name' => 'Text nonliterar', 'items' => 4, 'points' => 30],
                    ['name' => 'Text literar', 'items' => 4, 'points' => 30],
                    ['name' => 'Eseu structurat', 'items' => 2, 'points' => 40],
                ],
                'source_url' => 'https://ance.gov.md/clasa-sesiunea-examen/sesiunea-2026-0',
                'is_active' => true,
            ]);

            // Umanist/arte/sport: 12 itemi din sesiunea 2026 (de la 15).
            foreach ([ExamProfile::Umanist, ExamProfile::Arte, ExamProfile::Sport] as $profile) {
                ExamBlueprint::updateOrCreate([
                    'subject_id' => $romana->id,
                    'session' => '2026-baza',
                    'profile' => $profile->value,
                ], [
                    'total_items' => 12,
                    'total_points' => 100,
                    'duration_minutes' => 180,
                    'structure' => [
                        ['name' => 'Text nonliterar', 'items' => 5, 'points' => 30],
                        ['name' => 'Text literar', 'items' => 5, 'points' => 30],
                        ['name' => 'Eseu structurat', 'items' => 2, 'points' => 40],
                    ],
                    'source_url' => 'https://ance.gov.md/clasa-sesiunea-examen/sesiunea-2026-0',
                    'is_active' => true,
                ]);
            }
        }
    }
}
