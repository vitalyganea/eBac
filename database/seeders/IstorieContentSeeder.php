<?php

namespace Database\Seeders;

use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\ItemStat;
use App\Models\Skill;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Conținut de start pentru ISTORIA ROMÂNILOR ȘI UNIVERSALĂ.
 *
 * Construit pe programa oficială ANCE („Istoria românilor și universală —
 * Programă pentru examenul național de bacalaureat", Chișinău 2021, ordinul MEC
 * nr. 1499 din 04.11.2021). Disciplină **obligatorie la profil umanist**, la
 * solicitare pentru celelalte profiluri.
 *
 * Test: 4 subiecte, 24 + 22 + 19 + 35 = 100 de puncte, 180 de minute.
 *
 * CE ARE ISTORIA SPECIFIC, față de matematică sau informatică:
 *
 *  1. Examenul e masiv **pe surse**. Aproape fiecare item pornește de la un text,
 *     o hartă, un tabel sau o diagramă: „Identifică în sursă…", „Compară opiniile
 *     autorilor…", „În baza hărții…". Nu e un examen de memorare a datelor.
 *
 *  2. Subiectul al IV-lea (35 de puncte, cel mai mare) cere **răspuns extins
 *     argumentat** pe 2–3 surse. Nu e auto-corectabil, la fel ca eseul de la
 *     limbi străine.
 *
 *  3. Programa cere explicit „să distingă faptul de opinie din informația oferită
 *     de surse" — o competență care se pretează perfect la itemi automatizabili
 *     și care e, în plus, neutră: nu cere elevului să adopte o interpretare.
 *
 * Itemii vin din testele ANCE 2024–2026 (database/seeders/Istorie): sarcinile
 * „Identifică în sursă", cu sursele reale, atribuite, din test. Restul testului
 * se corectează pe barem, deci proba nu se simulează automat (§8b, §13).
 */
class IstorieContentSeeder extends Seeder
{
    public function run(): void
    {
        $subject = Subject::where('slug', 'istoria')->firstOrFail();

        $skills = $this->seedSkills($subject->id);
        $this->seedPrerequisites($skills);
        $this->seedItems($subject->id, $skills);
        $this->retireUnsourced($subject->id);
    }

    /** @return array<string, Skill> */
    private function seedSkills(int $subjectId): array
    {
        // [slug, nume, cod, pondere, descriere, copii]
        $tree = [
            // Ce se EVALUEAZĂ efectiv: competențe de lucru cu sursa istorică.
            ['competente-istorice', 'Competențe de lucru cu sursa', 'IST.XII.C', 0,
                'Cele patru competențe specifice din programă. Aici se dau punctele, nu pe memorarea datelor.', [
                    ['terminologie', 'Terminologie istorică', 'IST.XII.C1', 10, null],
                    ['cronologie', 'Cronologie și periodizare', 'IST.XII.C2', 12, null],
                    ['analiza-surselor', 'Analiza surselor scrise: fapt și opinie', 'IST.XII.C3', 20,
                        'Programa cere explicit distingerea faptului de opinie.'],
                    ['harti-statistica', 'Hărți, tabele și diagrame', 'IST.XII.C4', 12, null],
                    ['argumentare', 'Argumentare istorică pe surse', 'IST.XII.C5', 35,
                        'Subiectul al IV-lea: răspuns extins pe 2-3 surse. NU e auto-corectabil — cere autoevaluare pe barem.'],
                ]],

            // Conținuturile, pe epocile din programă.
            ['epoci', 'Epoci istorice', null, 0, null, [
                ['antichitate', 'Epoca antică', 'IST.XII.1', 8,
                    'Geto-dacii, civilizațiile antice, etnogeneza.'],
                ['ev-mediu', 'Epoca medievală', 'IST.XII.2', 14,
                    'Țările Române, cruciadele, lupta antiotomană, Ștefan cel Mare, Mihai Viteazul.'],
                ['epoca-moderna', 'Epoca modernă', 'IST.XII.3', 16,
                    '1812, regimul fanariot, Unirea Principatelor, Constituția din 1866, independența.'],
                ['ww1-marea-unire', 'Primul Război Mondial și Marea Unire', 'IST.XII.4', 12,
                    'Basarabia 1917-1918, Sfatul Țării, actele unirii din 1918.'],
                ['interbelic', 'Perioada interbelică', 'IST.XII.5', 10,
                    'Sistemul Versailles-Washington, Titulescu, regimurile politice, Marea Depresiune.'],
                ['ww2', 'Al Doilea Război Mondial', 'IST.XII.6', 12,
                    'Pactul Ribbentrop-Molotov, anul 1940, formarea RSSM, crime împotriva umanității.'],
                ['contemporan', 'Epoca contemporană', 'IST.XII.7', 12,
                    'Războiul Rece, RSSM postbelic, suveranitatea și independența Republicii Moldova.'],
            ]],
        ];

        $all = [];

        foreach ($tree as $i => [$slug, $name, $code, $weight, $desc, $children]) {
            $parent = Skill::updateOrCreate(
                ['subject_id' => $subjectId, 'slug' => $slug],
                [
                    'name' => $name,
                    'curriculum_code' => $code,
                    'description' => $desc,
                    'depth' => 0,
                    'sort_order' => $i,
                    'is_leaf' => false,
                    'exam_weight' => $weight,
                ],
            );
            $all[$slug] = $parent;

            foreach ($children as $j => [$cSlug, $cName, $cCode, $cWeight, $cDesc]) {
                $all[$cSlug] = Skill::updateOrCreate(
                    ['subject_id' => $subjectId, 'slug' => $cSlug],
                    [
                        'parent_id' => $parent->id,
                        'name' => $cName,
                        'curriculum_code' => $cCode,
                        'description' => $cDesc,
                        'depth' => 1,
                        'sort_order' => $j,
                        'is_leaf' => true,
                        'exam_weight' => $cWeight,
                    ],
                );
            }
        }

        return $all;
    }

    /**
     * Graful de prerechizite.
     *
     * Cele mai utile muchii nu sunt cronologice, ci de competență:
     * `argumentare ← analiza-surselor ← terminologie`. Un elev care pierde puncte
     * la Subiectul al IV-lea (35 de puncte!) aproape niciodată nu are o problemă
     * „cu argumentarea" — de obicei nu distinge faptul de opinie în sursă, sau nu
     * stăpânește termenii cu care ar trebui să scrie.
     *
     * @param  array<string, Skill>  $s
     */
    private function seedPrerequisites(array $s): void
    {
        $edges = [
            // Competențe
            ['analiza-surselor', 'terminologie', 0.9],
            ['harti-statistica', 'cronologie', 0.7],
            ['argumentare', 'analiza-surselor', 1.0],
            ['argumentare', 'cronologie', 0.8],
            ['argumentare', 'terminologie', 0.7],

            // Epoci: dependență cronologică reală (evenimentele ulterioare nu se
            // înțeleg fără cele anterioare).
            ['epoca-moderna', 'ev-mediu', 0.5],
            ['ww1-marea-unire', 'epoca-moderna', 0.7],
            ['interbelic', 'ww1-marea-unire', 0.9],
            ['ww2', 'interbelic', 0.9],
            ['contemporan', 'ww2', 0.9],

            // Orice epocă cere terminologia epocii respective.
            ['ev-mediu', 'terminologie', 0.6],
            ['epoca-moderna', 'terminologie', 0.6],
        ];

        foreach ($edges as [$skill, $prereq, $strength]) {
            if (! isset($s[$skill], $s[$prereq])) {
                continue;
            }

            DB::table('skill_edges')->updateOrInsert(
                ['skill_id' => $s[$skill]->id, 'prerequisite_id' => $s[$prereq]->id],
                ['strength' => $strength, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    /** @param array<string, Skill> $s */
    private function seedItems(int $subjectId, array $s): void
    {
        foreach ($this->items() as $data) {
            $skill = $s[$data['skill']] ?? null;

            if ($skill === null) {
                continue;
            }

            $item = Item::updateOrCreate(
                ['source_ref' => $data['ref']],
                [
                    'subject_id' => $subjectId,
                    'type' => $data['type'],
                    'payload' => $data['payload'],
                    'answer' => $data['answer'],
                    'explanation' => $data['explanation'] ?? null,
                    'hints' => $data['hints'] ?? [],
                    'difficulty_seed' => $data['difficulty'] ?? 3,
                    'points' => $data['points'] ?? 1,
                    'language' => 'ro',
                    'status' => ItemStatus::Published,
                    'source' => $data['source'],
                    'curriculum_code' => $skill->curriculum_code,
                    'is_exam_holdout' => $data['holdout'] ?? false,
                ],
            );

            $item->skills()->syncWithoutDetaching([$skill->id => ['is_primary' => true, 'weight' => 1.0]]);

            ItemStat::updateOrCreate(['item_id' => $item->id], [
                'rating' => 900 + ($data['difficulty'] ?? 3) * 150,
            ]);
        }
    }

    /**
     * Sarcinile „Identifică în sursă" din testele ANCE 2024–2026 (§11b).
     *
     * @return list<array<string, mixed>>
     */
    private function items(): array
    {
        return Istorie\Ance::items();
    }

    /**
     * Itemii scriși înainte de regula surselor oficiale — inclusiv cei pe surse
     * neatribuite, „scrise pentru platformă" — se retrag, nu se șterg (§1).
     */
    private function retireUnsourced(int $subjectId): void
    {
        Item::where('subject_id', $subjectId)
            ->where('source', 'not like', 'ance:%')
            ->where('status', '!=', ItemStatus::Retired)
            ->each(fn (Item $item) => $item->update(['status' => ItemStatus::Retired]));
    }
}
