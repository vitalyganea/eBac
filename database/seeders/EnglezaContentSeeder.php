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
 * LIMBA ENGLEZĂ: competențele din programă și itemii din testele ANCE.
 *
 * Construit pe programa oficială ANCE („Limba străină — Programă pentru
 * examenul național de bacalaureat", Chișinău 2021, ordinul MEC nr. 1499 din
 * 04.11.2021). Programa e unificată pentru toate limbile străine.
 *
 * Ce spune programa și ce înseamnă pentru platformă:
 *
 *  • **Nivelul cerut este B1** (CECRL 2018), nu B2. Toate itemii de aici țintesc
 *    B1 — un item de C1 ar fi la fel de inutil ca unul de A1.
 *
 *  • Testul are 180 de minute și **3 subiecte**:
 *      I.   Înțelegerea scrisă a unui text necunoscut (nonliterar/funcțional),
 *           400–450 de cuvinte — 30 de puncte
 *      II.  Producerea scrisă a unui eseu structurat, 180–200 de cuvinte
 *      III. Producerea scrisă pe o temă de cultură (articol de presă / scrisoare)
 *
 *  • Două domenii: **Comunicare** (Subiectele I–II) și **Cultură** (Subiectul III).
 *
 * LIMITAREA DE FOND, spusă direct: două din trei subiecte sunt producere scrisă,
 * deci 70 din 100 de puncte NU sunt auto-corectabile. Itemii (`Engleza\Ance`)
 * sunt sarcinile Subiectului I din testele ANCE 2022–2026; eseul și articolul
 * cer autoevaluare pe barem, adăugată separat. Gramatica, conectorii și
 * registrul stau în lecții (`EnglezaLessonSeeder`): testul ANCE nu le
 * evaluează prin itemi separați.
 *
 * Convenție de limbă: enunțurile și variantele sunt în ENGLEZĂ (e un examen de
 * engleză), iar indiciile și explicațiile în ROMÂNĂ — așa explică un profesor
 * din Moldova. Coloana `items.language` rămâne 'ro' pentru că se referă la
 * varianta lingvistică a platformei (ro / ru pentru segmentul alolingv), nu la
 * limba textului din item.
 *
 * Temele urmează conținuturile tematice oficiale: mediul personal, familial,
 * școlar, natural, social-informațional și cultural.
 */
class EnglezaContentSeeder extends Seeder
{
    public function run(): void
    {
        $subject = Subject::where('slug', 'limba-engleza')->firstOrFail();

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
            ['comunicare', 'Domeniul Comunicare', 'LS.XII.II.1', 0,
                'Subiectele I și II ale examenului.', [
                    ['timpuri-verbale', 'Timpuri verbale', 'LS.XII.G.1', 12, null],
                    ['conditionale-pasiv', 'Condiționale și diateza pasivă', 'LS.XII.G.2', 10, null],
                    ['vorbire-indirecta', 'Vorbire indirectă și verbe modale', 'LS.XII.G.3', 8, null],
                    ['articole-prepozitii', 'Articole, prepoziții și acord', 'LS.XII.G.4', 8, null],
                    ['vocabular-tematic', 'Vocabular tematic', 'LS.XII.V.1', 12,
                        'Pe conținuturile tematice oficiale: mediul personal, familial, școlar, natural, social-informațional.'],
                    ['coeziune-conectori', 'Coeziune și conectori', 'LS.XII.V.2', 10, null],
                    ['registru', 'Registru și ton', 'LS.XII.V.3', 6, null],
                    ['comprehensiune-scrisa', 'Comprehensiunea scrisă', 'LS.XII.S1', 30,
                        'Subiectul I: text nonliterar/funcțional necunoscut de 400–450 de cuvinte.'],
                    ['eseu-structurat', 'Eseul structurat', 'LS.XII.S2', 35,
                        'Subiectul II: 180–200 de cuvinte pe o temă actuală. Doar structura e auto-corectabilă; eseul cere autoevaluare pe barem.'],
                ]],
            ['cultura', 'Domeniul Cultură', 'LS.XII.II.2', 0,
                'Subiectul III: temă de cultură a țării alofone.', [
                    ['spatiul-anglofon', 'Țările și instituțiile spațiului anglofon', 'LS.XII.C.1', 10, null],
                    ['patrimoniu-personalitati', 'Patrimoniu și personalități ilustre', 'LS.XII.C.2', 10, null],
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
     * Muchia cea mai utilă pentru diagnostic e `eseu-structurat ← timpuri-verbale`:
     * un elev care pierde puncte la eseu rareori are o problemă „cu eseul" — de
     * obicei are o problemă cu timpurile sau cu conectorii, iar asta se poate
     * antrena separat.
     *
     * @param  array<string, Skill>  $s
     */
    private function seedPrerequisites(array $s): void
    {
        $edges = [
            ['conditionale-pasiv', 'timpuri-verbale', 1.0],
            ['vorbire-indirecta', 'timpuri-verbale', 1.0],
            ['coeziune-conectori', 'vocabular-tematic', 0.8],
            ['registru', 'vocabular-tematic', 0.7],

            ['comprehensiune-scrisa', 'vocabular-tematic', 0.9],
            ['comprehensiune-scrisa', 'timpuri-verbale', 0.6],

            ['eseu-structurat', 'coeziune-conectori', 1.0],
            ['eseu-structurat', 'registru', 0.9],
            ['eseu-structurat', 'timpuri-verbale', 0.8],
            ['eseu-structurat', 'articole-prepozitii', 0.6],

            ['patrimoniu-personalitati', 'spatiul-anglofon', 0.8],
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
                    'source' => $data['source'] ?? 'seed:programa-ance-2021',
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

    /** @return list<array<string, mixed>> */
    private function items(): array
    {
        return Engleza\Ance::items();
    }

    /**
     * Itemii de dinainte de testele ANCE erau scriși original, „aliniați la
     * programă", fără un document anume (§11b). Se retrag, nu se șterg:
     * `attempts` îi referă (§1), iar istoricul elevilor rămâne.
     */
    private function retireUnsourced(int $subjectId): void
    {
        Item::where('subject_id', $subjectId)
            ->where('source', 'not like', 'ance:%')
            ->where('status', '!=', ItemStatus::Retired)
            ->each(fn (Item $item) => $item->update(['status' => ItemStatus::Retired]));
    }
}
