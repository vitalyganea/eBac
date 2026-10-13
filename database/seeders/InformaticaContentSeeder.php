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
 * INFORMATICA. Itemii vin din testele și baremele ANCE 2024–2026
 * (database/seeders/Informatica), §11b.
 *
 * Construit pe programa oficială de examen a ANCE („Informatica — Programă
 * pentru examenul național de bacalaureat", Chișinău 2021, aprobată prin
 * ordinul MEC nr. 1499 din 04.11.2021), nu pe un curriculum generic.
 *
 * Trei decizii care vin direct din programă:
 *
 *  1. Examenul acceptă **Pascal sau C/C++, la alegerea candidatului**, iar
 *     itemii oficiali afișează codul în AMBELE limbaje, în coloane paralele.
 *     De aceea itemii de aici folosesc `code_variants`, iar interfața are un
 *     comutator de limbaj. Un elev care învață Pascal nu trebuie să citească C++.
 *
 *  2. Unitățile de conținut marcate cu `*` în programă se aplică **doar
 *     profilului real**. Le păstrez marcate în `description`, ca simulările să
 *     poată filtra pe profil mai târziu.
 *
 *  3. Informatica e disciplină **la alegere** („la solicitare"), nu obligatorie.
 *
 * Test: 4 subiecte, 100 de puncte, 180 de minute.
 */
class InformaticaContentSeeder extends Seeder
{
    public function run(): void
    {
        $subject = Subject::where('slug', 'informatica')->firstOrFail();

        $skills = $this->seedSkills($subject->id);
        $this->seedPrerequisites($skills);
        $this->seedItems($subject->id, $skills);
        $this->retireUnsourced($subject->id);
    }

    /**
     * Arborele de competențe, pe unitățile de conținut din programa ANCE.
     *
     * @return array<string, Skill>
     */
    private function seedSkills(int $subjectId): array
    {
        // [slug, nume, cod, pondere, doar_profil_real, copii]
        $tree = [
            ['programare', 'Programare', null, 0, false, [
                ['tipuri-simple', 'Tipuri de date simple', 'INF.XII.1', 8, false],
                ['instructiuni', 'Instrucțiuni și expresii', 'INF.XII.2', 14, false],
                ['tipuri-structurate', 'Tipuri de date structurate', 'INF.XII.3', 14, false],
                ['subprograme', 'Subprograme', 'INF.XII.8', 12, false],
                ['recursie', 'Recursia', 'INF.XII.8.R', 8, true],
            ]],
            ['informatia', 'Informația', null, 0, false, [
                ['cantitatea-informatie', 'Cantitatea de informație', 'INF.XII.4.1', 6, false],
                ['codificare', 'Codificarea și decodificarea mesajelor', 'INF.XII.4.2', 6, false],
            ]],
            ['bazele-aritmetice', 'Bazele aritmetice ale tehnicii de calcul', null, 0, true, [
                ['sisteme-numeratie', 'Sisteme de numerație', 'INF.XII.5.1', 8, true],
                ['conversii-baze', 'Conversia numerelor dintr-o bază în alta', 'INF.XII.5.2', 8, true],
            ]],
            ['logica-digitala', 'Algebră booleană și circuite logice', null, 0, true, [
                ['algebra-booleana', 'Algebra booleană', 'INF.XII.6', 8, true],
                ['circuite-logice', 'Circuite logice', 'INF.XII.7', 6, true],
            ]],
            ['calcul-numeric', 'Modelare și calcul numeric', null, 0, true, [
                ['modelare-erori', 'Modelare matematică și erori de calcul', 'INF.XII.9.1', 5, true],
                ['metode-iterative', 'Metoda bisecției și a coardelor', 'INF.XII.9.2', 6, true],
                ['integrare-numerica', 'Calculul numeric al integralelor', 'INF.XII.9.3', 5, true],
            ]],
            ['baze-de-date', 'Baze de date', null, 0, false, [
                ['tabele', 'Tabele, chei și corelații', 'INF.XII.10.1', 6, false],
                ['interogari', 'Interogări', 'INF.XII.10.2', 6, false],
            ]],
        ];

        $all = [];

        foreach ($tree as $i => [$slug, $name, $code, $weight, $realOnly, $children]) {
            $parent = Skill::updateOrCreate(
                ['subject_id' => $subjectId, 'slug' => $slug],
                [
                    'name' => $name,
                    'curriculum_code' => $code,
                    'description' => $realOnly ? 'Doar profilul real (marcat cu * în programa ANCE).' : null,
                    'depth' => 0,
                    'sort_order' => $i,
                    'is_leaf' => false,
                    'exam_weight' => $weight,
                ],
            );
            $all[$slug] = $parent;

            foreach ($children as $j => [$cSlug, $cName, $cCode, $cWeight, $cRealOnly]) {
                $all[$cSlug] = Skill::updateOrCreate(
                    ['subject_id' => $subjectId, 'slug' => $cSlug],
                    [
                        'parent_id' => $parent->id,
                        'name' => $cName,
                        'curriculum_code' => $cCode,
                        'description' => $cRealOnly ? 'Doar profilul real (marcat cu * în programa ANCE).' : null,
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
     * Muchiile care contează pentru diagnostic sunt cele care traversează
     * capitolele: „recursia" cere subprograme, dar „algebra booleană" cere
     * sisteme de numerație (fără binar, tabelele de adevăr n-au sens), iar
     * „cantitatea de informație" tot de acolo pornește. Așa putem spune
     * elevului „nu ai o problemă cu circuitele logice, ai o problemă cu binarul".
     *
     * @param  array<string, Skill>  $s
     */
    private function seedPrerequisites(array $s): void
    {
        $edges = [
            ['instructiuni', 'tipuri-simple', 1.0],
            ['tipuri-structurate', 'instructiuni', 1.0],
            ['tipuri-structurate', 'tipuri-simple', 0.7],
            ['subprograme', 'instructiuni', 1.0],
            ['recursie', 'subprograme', 1.0],
            ['recursie', 'instructiuni', 0.6],

            ['conversii-baze', 'sisteme-numeratie', 1.0],
            ['cantitatea-informatie', 'sisteme-numeratie', 0.7],
            ['codificare', 'cantitatea-informatie', 0.9],

            ['algebra-booleana', 'sisteme-numeratie', 0.6],
            ['circuite-logice', 'algebra-booleana', 1.0],

            ['modelare-erori', 'tipuri-simple', 0.6],
            ['metode-iterative', 'instructiuni', 0.9],
            ['metode-iterative', 'modelare-erori', 0.7],
            ['integrare-numerica', 'instructiuni', 0.9],
            ['integrare-numerica', 'modelare-erori', 0.6],

            ['interogari', 'tabele', 1.0],
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
     * Sarcinile din testele ANCE 2024–2026, cu răspunsurile din barem (§11b),
     * plus variantele rezervate simulării.
     *
     * @return list<array<string, mixed>>
     */
    private function items(): array
    {
        return [
            ...Informatica\Ance2026::items(),
            ...Informatica\Ance2025::items(),
            ...Informatica\Ance2024::items(),
            ...Informatica\Simulare::items(),
        ];
    }

    /**
     * Itemii scriși înainte de regula surselor oficiale (§11b) se retrag, nu
     * se șterg: `attempts` îi referă (§1).
     */
    private function retireUnsourced(int $subjectId): void
    {
        Item::where('subject_id', $subjectId)
            ->where('source', 'not like', 'ance:%')
            ->where('status', '!=', ItemStatus::Retired)
            ->each(fn (Item $item) => $item->update(['status' => ItemStatus::Retired]));
    }
}
