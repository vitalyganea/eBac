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
 * Matematica, construită din documente oficiale (§11b):
 *
 *   - programa ANCE de bacalaureat (programa_bac_matematica_romana_0.pdf) —
 *     capitolele, adică arborele de competențe;
 *   - testele și baremele ANCE 2021–2026, profil real și umanist, sesiunea de
 *     bază și cea suplimentară — itemii (database/seeders/Matematica).
 *
 * Problemele se servesc ambelor profiluri: profilul e ghidaj, nu poartă (§2a).
 */
class MathContentSeeder extends Seeder
{
    public function run(): void
    {
        $subject = Subject::where('slug', 'matematica')->firstOrFail();

        $skills = $this->seedSkills($subject->id);
        $this->seedPrerequisites($skills);
        $this->seedItems($subject->id, $skills);
        $this->retireUnsourced($subject->id);
    }

    /** @return array<string, Skill> */
    private function seedSkills(int $subjectId): array
    {
        // [slug, nume, cod curricular, pondere la examen, copii]
        $tree = [
            ['algebra', 'Algebră', null, 0, [
                ['numere-reale', 'Numere reale și calcule', 'MAT.XII.1.1', 8],
                ['ecuatii-inecuatii', 'Ecuații și inecuații', 'MAT.XII.1.2', 12],
                ['logaritmi', 'Puteri, radicali și logaritmi', 'MAT.XII.1.3', 8],
                ['numere-complexe', 'Numere complexe', 'MAT.XII.1.4', 8],
                ['matrici', 'Matrici și determinanți', 'MAT.XII.1.5', 10],
                ['polinoame', 'Polinoame', 'MAT.XII.1.6', 5],
            ]],
            ['trigonometrie', 'Trigonometrie', null, 0, [
                ['valori-trigonometrice', 'Valori trigonometrice', 'MAT.XII.2.1', 6],
                ['identitati-trigonometrice', 'Identități trigonometrice', 'MAT.XII.2.2', 8],
            ]],
            // Testele ANCE pun progresiile la „Analiză matematică".
            ['analiza', 'Analiză matematică', null, 0, [
                ['progresii', 'Șiruri și progresii', 'MAT.XII.3.0', 5],
                ['functii', 'Funcții', 'MAT.XII.3.1', 8],
                ['limite', 'Limite de funcții', 'MAT.XII.3.2', 6],
                ['derivate', 'Derivate', 'MAT.XII.3.3', 14],
                ['integrale', 'Integrale', 'MAT.XII.3.4', 12],
            ]],
            ['geometrie', 'Geometrie', null, 0, [
                ['triunghiuri', 'Triunghiuri', 'MAT.XII.4.1', 8],
                ['patrulatere', 'Patrulatere', 'MAT.XII.4.4', 8],
                ['cerc', 'Cercul', 'MAT.XII.4.2', 6],
                ['corpuri', 'Corpuri geometrice', 'MAT.XII.4.3', 10],
            ]],
            // Capitolul cu același nume din test: „Elemente de combinatorică.
            // Binomul lui Newton. Elemente de teoria probabilităților și
            // statistică matematică".
            ['combinatorica-probabilitati', 'Combinatorică și probabilități', null, 0, [
                ['combinatorica', 'Combinatorică și binomul lui Newton', 'MAT.XII.5.1', 8],
                ['probabilitati', 'Probabilități, statistică și calcul financiar', 'MAT.XII.5.2', 8],
            ]],
        ];

        $all = [];

        foreach ($tree as $i => [$slug, $name, $code, $weight, $children]) {
            $parent = Skill::updateOrCreate(
                ['subject_id' => $subjectId, 'slug' => $slug],
                [
                    'name' => $name,
                    'curriculum_code' => $code,
                    'depth' => 0,
                    'sort_order' => $i,
                    'is_leaf' => false,
                    'exam_weight' => $weight,
                ],
            );
            $all[$slug] = $parent;

            foreach ($children as $j => [$childSlug, $childName, $childCode, $childWeight]) {
                $all[$childSlug] = Skill::updateOrCreate(
                    ['subject_id' => $subjectId, 'slug' => $childSlug],
                    [
                        'parent_id' => $parent->id,
                        'name' => $childName,
                        'curriculum_code' => $childCode,
                        'depth' => 1,
                        'sort_order' => $j,
                        'is_leaf' => true,
                        'exam_weight' => $childWeight,
                    ],
                );
            }
        }

        return $all;
    }

    /**
     * Graful de prerechizite — traversează ierarhia.
     *
     * Muchia care contează cel mai mult e `integrale ← ecuatii-inecuatii`:
     * ea permite diagnosticul „nu ai o problemă cu integralele, ai o problemă cu
     * manipularea algebrică". Fără muchii între capitole diferite, sistemul ar
     * putea raporta doar simptome.
     *
     * @param  array<string, Skill>  $s
     */
    private function seedPrerequisites(array $s): void
    {
        $edges = [
            // [competență, prerechizit, cât de esențial]
            ['limite', 'functii', 1.0],
            ['derivate', 'limite', 1.0],
            ['derivate', 'functii', 0.8],
            ['derivate', 'ecuatii-inecuatii', 0.7],
            ['integrale', 'derivate', 1.0],
            ['integrale', 'ecuatii-inecuatii', 0.6],
            ['integrale', 'numere-reale', 0.5],
            ['logaritmi', 'ecuatii-inecuatii', 0.8],
            ['logaritmi', 'numere-reale', 0.6],
            ['numere-complexe', 'numere-reale', 0.9],
            ['numere-complexe', 'valori-trigonometrice', 0.5],
            ['identitati-trigonometrice', 'valori-trigonometrice', 1.0],
            ['matrici', 'numere-reale', 0.5],
            ['polinoame', 'ecuatii-inecuatii', 0.7],
            ['corpuri', 'triunghiuri', 0.8],
            ['corpuri', 'cerc', 0.6],
            ['corpuri', 'patrulatere', 0.6],
            ['patrulatere', 'triunghiuri', 0.9],
            ['probabilitati', 'combinatorica', 1.0],
            ['progresii', 'ecuatii-inecuatii', 0.6],
            ['ecuatii-inecuatii', 'numere-reale', 0.9],
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
                    // Fiecare item are indicii în trepte: primul împinge spre
                    // metodă, ultimul e aproape rezolvarea. Elevul le cere pe
                    // rând, iar fiecare costă XP.
                    'hints' => $data['hints'] ?? [],
                    'difficulty_seed' => $data['difficulty'] ?? 3,
                    'points' => $data['points'] ?? 1,
                    'language' => 'ro',
                    'status' => ItemStatus::Published,
                    'source' => $data['source'] ?? 'seed',
                    'curriculum_code' => $skill->curriculum_code,
                    'is_exam_holdout' => $data['holdout'] ?? false,
                ],
            );

            $item->skills()->syncWithoutDetaching([$skill->id => ['is_primary' => true, 'weight' => 1.0]]);

            // Rating inițial derivat din estimarea autorului. Se recalibrează
            // automat din răspunsurile reale (RecalibrateItem).
            ItemStat::updateOrCreate(['item_id' => $item->id], [
                'rating' => 900 + ($data['difficulty'] ?? 3) * 150,
            ]);
        }
    }

    /**
     * Banca de itemi: problemele din testele ANCE, cu cheia din barem (§11b).
     *
     * @return list<array<string, mixed>>
     */
    private function items(): array
    {
        return [
            ...Matematica\Ance2026::items(),
            ...Matematica\Ance2025::items(),
            ...Matematica\Ance2024::items(),
            ...Matematica\Simulare2026::items(),
        ];
    }

    /**
     * Itemii scriși înainte de regula surselor oficiale (§11b) nu trimiteau la
     * niciun document — erau originali, „aliniați la programă". Se retrag, nu
     * se șterg: `attempts` îi referă (§1), iar istoricul elevilor rămâne.
     */
    private function retireUnsourced(int $subjectId): void
    {
        Item::where('subject_id', $subjectId)
            ->where('source', 'not like', 'ance:%')
            ->where('status', '!=', ItemStatus::Retired)
            ->each(fn (Item $item) => $item->update(['status' => ItemStatus::Retired]));
    }
}
