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
            // Structura testelor ANCE din 2026, sesiunea de bază
            // (12_mat_test_r_ro_sb26.pdf, 12_mat_test_u_ro_sb26.pdf). Fiecare
            // problemă are un răspuns final în barem, deci se poate verifica; la
            // examen se punctează însă și pașii, iar simularea nu-i vede.
            $pasi = 'La examen se punctează și etapele rezolvării; simularea verifică doar răspunsul final.';
            $tipuri = ['numeric', 'mcq', 'fill_blanks'];

            ExamBlueprint::updateOrCreate([
                'subject_id' => $math->id,
                'session' => '2026-baza',
                'profile' => ExamProfile::Real->value,
            ], [
                'total_items' => 14,
                'total_points' => 100,
                'duration_minutes' => 180,
                'structure' => [
                    ['name' => 'Algebră', 'items' => 5, 'points' => 34, 'types' => $tipuri,
                        'skills' => ['algebra', 'trigonometrie'], 'mock_items' => 5, 'note' => $pasi],
                    ['name' => 'Geometrie', 'items' => 3, 'points' => 21, 'types' => $tipuri,
                        'skills' => ['geometrie'], 'mock_items' => 3, 'note' => $pasi],
                    ['name' => 'Analiză matematică', 'items' => 4, 'points' => 29, 'types' => $tipuri,
                        'skills' => ['analiza'], 'mock_items' => 4, 'note' => $pasi],
                    ['name' => 'Combinatorică, binomul lui Newton, probabilități', 'items' => 2, 'points' => 16,
                        'types' => $tipuri, 'skills' => ['combinatorica-probabilitati'], 'mock_items' => 2, 'note' => $pasi],
                ],
                'source_url' => 'https://ance.gov.md/sites/default/files/12_mat_test_r_ro_sb26.pdf',
                'is_active' => true,
            ]);

            ExamBlueprint::updateOrCreate([
                'subject_id' => $math->id,
                'session' => '2026-baza',
                'profile' => ExamProfile::Umanist->value,
            ], [
                'total_items' => 14,
                'total_points' => 100,
                'duration_minutes' => 180,
                'structure' => [
                    ['name' => 'Algebră', 'items' => 5, 'points' => 37, 'types' => $tipuri,
                        'skills' => ['algebra', 'trigonometrie'], 'mock_items' => 5, 'note' => $pasi],
                    ['name' => 'Geometrie', 'items' => 4, 'points' => 26, 'types' => $tipuri,
                        'skills' => ['geometrie'], 'mock_items' => 4, 'note' => $pasi],
                    ['name' => 'Funcții', 'items' => 3, 'points' => 21, 'types' => $tipuri,
                        'skills' => ['functii', 'progresii'], 'mock_items' => 3, 'note' => $pasi],
                    ['name' => 'Combinatorică, statistică, calcul financiar, probabilități', 'items' => 2, 'points' => 16,
                        'types' => $tipuri, 'skills' => ['combinatorica-probabilitati'], 'mock_items' => 2, 'note' => $pasi],
                ],
                'source_url' => 'https://ance.gov.md/sites/default/files/12_mat_test_u_ro_sb26.pdf',
                'is_active' => true,
            ]);
        }

        if ($informatica) {
            // Structura testelor ANCE din 2026 (12_inf_test_r_ro_sb26.pdf):
            // 4 subiecte, 100 de puncte, 180 de minute. Pascal SAU C/C++ (§12).
            // Subiectele II și III amestecă sarcini cu răspuns unic (ce
            // afișează programul, valori, analiza unui program) cu programe
            // scrise de la zero, corectate pe barem; punctele sunt cele din
            // baremul din 2026, împărțite între cele două feluri.
            $tipuri = ['numeric', 'mcq', 'fill_blanks', 'order_steps', 'match'];
            $barem = 'Se corectează pe barem: programul se scrie de la zero și se punctează pe elemente.';

            ExamBlueprint::updateOrCreate([
                'subject_id' => $informatica->id,
                'session' => '2026-baza',
                'profile' => ExamProfile::Real->value,
            ], [
                'total_items' => 4,
                'total_points' => 100,
                'duration_minutes' => 180,
                'structure' => [
                    ['name' => 'Subiectul I: informația, sisteme de numerație, circuite logice', 'items' => 3, 'points' => 25,
                        'types' => $tipuri, 'skills' => ['informatia', 'bazele-aritmetice', 'logica-digitala'], 'mock_items' => 4],
                    ['name' => 'Subiectul II: valori, expresii, ce afișează programul', 'items' => 2, 'points' => 11,
                        'types' => $tipuri, 'skills' => ['tipuri-simple', 'instructiuni', 'tipuri-structurate'], 'mock_items' => 2],
                    ['name' => 'Subiectul II: programe scrise', 'items' => 3, 'points' => 21, 'auto_gradable' => false, 'note' => $barem],
                    ['name' => 'Subiectul III: analiza unui program cu subprograme', 'items' => 2, 'points' => 10,
                        'types' => $tipuri, 'skills' => ['subprograme', 'recursie'], 'mock_items' => 2],
                    ['name' => 'Subiectul III: programe scrise', 'items' => 2, 'points' => 20, 'auto_gradable' => false, 'note' => $barem],
                    ['name' => 'Subiectul IV: baze de date (MS Access)', 'items' => 1, 'points' => 13, 'auto_gradable' => false,
                        'note' => 'Interogarea se construiește în Design View și se corectează pe barem.'],
                ],
                'source_url' => 'https://ance.gov.md/sites/default/files/12_inf_test_r_ro_sb26.pdf',
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
            // Testele ANCE din 2026 (12_ist_test_u_ro_sb26.pdf): 4 subiecte,
            // 24 + 22 + 19 + 35 = 100 de puncte, 180 de minute. Fiecare sarcină
            // — explicarea unui termen, compararea opiniilor, harta, eseul — se
            // corectează pe barem; chiar și „Identifică în sursă" cere două
            // răspunsuri formulate de elev. Proba nu se simulează automat, la fel
            // ca limba română: sarcinile de identificare se exersează în Teste.
            $barem = 'Se corectează pe barem, cu răspuns deschis.';

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
                        ['name' => 'Subiectul I: două surse scrise', 'items' => 5, 'points' => 24, 'auto_gradable' => false, 'note' => $barem],
                        ['name' => 'Subiectul II: o sursă scrisă', 'items' => 5, 'points' => 22, 'auto_gradable' => false, 'note' => $barem],
                        ['name' => 'Subiectul III: harta', 'items' => 3, 'points' => 19, 'auto_gradable' => false, 'note' => $barem],
                        ['name' => 'Subiectul IV: eseu pe o sursă', 'items' => 1, 'points' => 35, 'auto_gradable' => false,
                            'note' => 'Eseu de o pagină, punctat pe criteriile din test: argumente, relații cauză-efect, integrarea sursei, plasarea în timp și spațiu, plan logic, limbaj istoric.'],
                    ],
                    'source_url' => 'https://ance.gov.md/sites/default/files/12_ist_test_u_ro_sb26.pdf',
                    'is_active' => true,
                ]);
            }
        }

        if ($romana) {
            // Structura din testele ANCE, sesiunea de bază 2026:
            //   https://ance.gov.md/sites/default/files/12_llro_test_r_sb26.pdf (+ baremul _barem_r_)
            //   https://ance.gov.md/sites/default/files/12_llro_test_u_sb26.pdf (+ baremul _barem_u_)
            // Un singur text literar, apoi itemi cu răspuns DESCHIS — inclusiv
            // sinonimele, care se cer argumentate. Fiecare item se corectează pe
            // barem, iar „Redactarea" (20 p) se acordă pe toate secvențele scrise.
            // Nimic nu e auto-corectabil, deci proba nu se simulează: Testele
            // antrenează competențele, iar autoevaluarea pe barem vine separat (§13).
            $open = fn (string $name, int $items, int $points, string $note) => [
                'name' => $name, 'items' => $items, 'points' => $points,
                'types' => [], 'auto_gradable' => false, 'note' => $note,
            ];

            ExamBlueprint::updateOrCreate([
                'subject_id' => $romana->id,
                'session' => '2026-baza',
                'profile' => ExamProfile::Real->value,
            ], [
                'total_items' => 10,
                'total_points' => 100,
                'duration_minutes' => 180,
                'structure' => [
                    $open('Vocabular (itemii 1–2)', 2, 9, 'Sinonime contextuale, sensurile unui cuvânt.'),
                    $open('Lectura și interpretarea textului (itemii 3–8)', 6, 38,
                        'Stil funcțional, figuri de stil, motiv literar, titlu, portret moral, apartenența la gen.'),
                    $open('Scriere funcțională (itemul 9)', 1, 9, 'Anunț pe baza unei legende.'),
                    $open('Eseu argumentativ (itemul 10)', 1, 24, '1–1,5 pagini, cu referire la două texte literare.'),
                    $open('Redactare', 0, 20, 'Organizare, analiză, stil, ortografie și punctuație, pe toate secvențele scrise.'),
                ],
                'source_url' => 'https://ance.gov.md/sites/default/files/12_llro_barem_r_sb26.pdf',
                'is_active' => true,
            ]);

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
                        $open('Vocabular (itemii 1–2)', 2, 7, 'Sinonim contextual argumentat, expresii.'),
                        $open('Lectura și interpretarea textului (itemii 3–8)', 6, 34,
                            'Portret moral, figuri de stil, atitudinea personajului, motiv literar, apartenența la gen.'),
                        $open('Opinie și referință literară (itemii 9–10)', 2, 8, 'Punct de vedere, personaj din literatura română.'),
                        $open('Scriere funcțională (itemul 11)', 1, 7, 'Invitație sau anunț pe baza unei legende.'),
                        $open('Eseu structurat (itemul 12)', 1, 24, '1–1,5 pagini, cu referire la două texte literare.'),
                        $open('Redactare', 0, 20, 'Organizare, analiză, stil, ortografie și punctuație, pe toate secvențele scrise.'),
                    ],
                    'source_url' => 'https://ance.gov.md/sites/default/files/12_llro_barem_u_sb26.pdf',
                    'is_active' => true,
                ]);
            }
        }
    }
}
