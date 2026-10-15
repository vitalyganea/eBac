<?php

namespace Database\Seeders;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Models\Item;
use App\Models\ItemStat;
use App\Models\Skill;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * GEOGRAFIA: competențele din programa ANCE și primii itemi.
 *
 * Sursa e programa de examen (programa_bac_geografie_romana_1.pdf), care are
 * două părți: „Exemple de itemi" (fără barem) și „Exemplu de test și de barem
 * de corectare". Testele din sesiunile trecute nu le-am găsit încă pe site-ul
 * ANCE; când apar, se adaugă ca la istorie.
 *
 * Proba se sprijină mult pe atlas: coordonate, distanțe pe rețeaua de grade,
 * ora pe Glob, harta-contur. Itemii aceia așteaptă o componentă de hartă și
 * lipsesc deocamdată. Rămân cei care se pot rezolva din ce e pe ecran:
 * climograme și piramide ale populației — desenate din cifre, nu imagini
 * (§10e) —, tabele de date, calcule, cunoștințe.
 *
 * Cheia: la testul cu barem, ce acceptă baremul. La exemplele de itemi
 * programa nu dă barem, deci cheia e calculul pe datele din enunț, iar
 * explicația o spune. Cifrele graficelor sunt citite de pe figurile ANCE;
 * la climograme, suma precipitațiilor lunare reproduce exact totalul anual
 * scris pe figură (2150 mm, 800 mm).
 *
 * Răspunsurile deschise („deduceți o cauză", „argumentați") se corectează pe
 * barem, ca la istorie, și nu se transformă în variante inventate.
 */
class GeografieContentSeeder extends Seeder
{
    private const PDF = 'https://ance.gov.md/sites/default/files/programa_bac_geografie_romana_1.pdf';

    private const SOURCE = 'ance:programa_bac_geografie_romana_1';

    public function run(): void
    {
        $subject = Subject::where('slug', 'geografia')->firstOrFail();

        $skills = $this->seedSkills($subject->id);
        $this->seedPrerequisites($skills);
        $this->seedItems($subject->id, $skills);
    }

    /** @return array<string, Skill> */
    private function seedSkills(int $subjectId): array
    {
        // Modulele programei, grupate ca în atlasele școlare de la examen.
        $tree = [
            ['geografia-fizica', 'Geografia fizică generală', 'GEO.F', [
                ['pamantul-univers', 'Pământul în Univers. Harta și ora pe Glob', 'GEO.F.1', 12],
                ['litosfera', 'Litosfera și relieful', 'GEO.F.2', 8],
                ['atmosfera', 'Atmosfera și clima', 'GEO.F.3', 12],
                ['hidrosfera', 'Hidrosfera', 'GEO.F.4', 8],
                ['biosfera-solul', 'Biosfera și solul', 'GEO.F.5', 6],
            ]],
            ['geografia-umana', 'Geografia umană a lumii', 'GEO.U', [
                ['harta-politica', 'Harta politică a lumii', 'GEO.U.1', 6],
                ['resurse-naturale', 'Condițiile și resursele naturale', 'GEO.U.2', 6],
                ['populatia', 'Populația și așezările umane', 'GEO.U.3', 12],
                ['economia-mondiala', 'Economia mondială', 'GEO.U.4', 12],
                ['state-lumii', 'Caracterizarea economico-geografică a statelor', 'GEO.U.5', 8],
            ]],
            ['mediul-geografic', 'Mediul geografic și problemele globale', 'GEO.M', [
                ['mediul-natural', 'Mediul geografic și protecția lui', 'GEO.M.1', 6],
                ['probleme-globale', 'Problemele globale și dezvoltarea sustenabilă', 'GEO.M.2', 4],
            ]],
        ];

        $all = [];

        foreach ($tree as $i => [$slug, $name, $code, $children]) {
            $parent = Skill::updateOrCreate(
                ['subject_id' => $subjectId, 'slug' => $slug],
                ['name' => $name, 'curriculum_code' => $code, 'depth' => 0, 'sort_order' => $i,
                    'is_leaf' => false, 'exam_weight' => 0],
            );
            $all[$slug] = $parent;

            foreach ($children as $j => [$cSlug, $cName, $cCode, $weight]) {
                $all[$cSlug] = Skill::updateOrCreate(
                    ['subject_id' => $subjectId, 'slug' => $cSlug],
                    ['parent_id' => $parent->id, 'name' => $cName, 'curriculum_code' => $cCode,
                        'depth' => 1, 'sort_order' => $j, 'is_leaf' => true, 'exam_weight' => $weight],
                );
            }
        }

        return $all;
    }

    /** @param array<string, Skill> $s */
    private function seedPrerequisites(array $s): void
    {
        $edges = [
            // Clima nu se înțelege fără mișcările Pământului și unghiul razelor.
            ['atmosfera', 'pamantul-univers', 0.9],
            ['biosfera-solul', 'atmosfera', 0.8],
            ['hidrosfera', 'atmosfera', 0.6],
            ['economia-mondiala', 'resurse-naturale', 0.8],
            ['state-lumii', 'economia-mondiala', 0.9],
            ['state-lumii', 'populatia', 0.7],
            ['probleme-globale', 'mediul-natural', 0.8],
        ];

        foreach ($edges as [$skill, $prereq, $strength]) {
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
            $skill = $s[$data['skill']];

            $item = Item::updateOrCreate(
                ['source_ref' => $data['ref']],
                [
                    'subject_id' => $subjectId,
                    'type' => $data['type'],
                    'payload' => $data['payload'],
                    'answer' => $data['answer'],
                    'explanation' => $data['explanation'],
                    'hints' => $data['hints'],
                    'difficulty_seed' => $data['difficulty'],
                    'points' => $data['points'],
                    'language' => 'ro',
                    'status' => ItemStatus::Published,
                    'source' => self::SOURCE,
                    'curriculum_code' => $skill->curriculum_code,
                    'is_exam_holdout' => false,
                ],
            );

            $item->skills()->syncWithoutDetaching([$skill->id => ['is_primary' => true, 'weight' => 1.0]]);

            ItemStat::updateOrCreate(['item_id' => $item->id], [
                'rating' => 900 + $data['difficulty'] * 150,
            ]);
        }
    }

    // ==================== constructori ====================

    /**
     * @param  list<string>  $options  prima e cea corectă; se amestecă la afișare
     * @param  list<string>  $hints
     */
    private static function choice(string $ref, string $skill, int $difficulty, string $stem, array $options,
        array $hints, string $explanation, array $extra = []): array
    {
        return [
            'ref' => self::PDF.'#'.$ref,
            'skill' => $skill,
            'type' => ItemType::Mcq,
            'difficulty' => $difficulty,
            'points' => 1,
            'payload' => [
                ...$extra,
                'stem' => $stem,
                'shuffle' => true,
                'choices' => array_map(
                    fn (int $i, string $text) => ['key' => chr(ord('a') + $i), 'text' => $text],
                    array_keys($options), $options,
                ),
            ],
            'answer' => ['correct' => ['a']],
            'hints' => $hints,
            'explanation' => $explanation,
        ];
    }

    /**
     * @param  array<string, array{label: string, values: list<string>, tolerance?: float, options?: list<string>}>  $blanks
     * @param  list<string>  $hints
     */
    private static function blanks(string $ref, string $skill, int $difficulty, string $stem, array $blanks,
        array $hints, string $explanation, bool $numeric, array $extra = []): array
    {
        $tolerance = array_filter(array_map(fn (array $b) => $b['tolerance'] ?? null, $blanks), fn ($t) => $t !== null);

        return [
            'ref' => self::PDF.'#'.$ref,
            'skill' => $skill,
            'type' => ItemType::FillBlanks,
            'difficulty' => $difficulty,
            'points' => count($blanks),
            'payload' => [
                ...$extra,
                'stem' => $stem,
                'blanks' => array_map(
                    fn (string $key, array $b) => array_filter(
                        ['key' => $key, 'label' => $b['label'], 'options' => $b['options'] ?? null],
                        fn ($v) => $v !== null,
                    ),
                    array_keys($blanks), $blanks,
                ),
            ],
            'answer' => array_filter([
                'values' => array_map(fn (array $b) => $b['values'], $blanks),
                'numeric' => $numeric ?: null,
                'tolerance' => $tolerance ?: null,
            ], fn ($v) => $v !== null),
            'hints' => $hints,
            'explanation' => $explanation,
        ];
    }

    // ==================== graficele ====================

    /** Testul cu barem, itemul 8: climogramă ecuatorială. */
    private static function climogramaEcuatoriala(): array
    {
        return ['chart' => [
            'type' => 'climogram',
            'precip' => [198, 154, 171, 141, 158, 140, 145, 143, 177, 167, 252, 304],
            'temp' => [26, 26, 27, 27, 28, 27, 27, 27, 27, 27, 26, 25],
            'precip_max' => 350,
            'temp_range' => [15, 29],
            'ticks' => 7,
            'annual_precip' => 2150,
        ]];
    }

    /** Exemple de itemi, itemul 12: climogramă temperat-oceanică. */
    private static function climogramaTemperata(): array
    {
        return ['chart' => [
            'type' => 'climogram',
            'precip' => [74, 57, 69, 58, 68, 61, 61, 65, 65, 80, 72, 70],
            'temp' => [5, 4, 7, 9, 13, 16, 18, 17.5, 15, 12, 8, 5],
            'precip_max' => 100,
            'temp_range' => [0, 20],
            'ticks' => 5,
            'annual_precip' => 800,
        ]];
    }

    /** Testul cu barem, itemul 10: piramidele Africa și Europa, 2019 (procente citite de pe figură). */
    private static function piramide(): array
    {
        return ['chart' => [
            'type' => 'pyramids',
            'groups' => ['0-4', '5-9', '10-14', '15-19', '20-24', '25-29', '30-34', '35-39', '40-44',
                '45-49', '50-54', '55-59', '60-64', '65-69', '70-74', '75-79', '80+'],
            'series' => [
                [
                    'title' => 'Africa – 2019',
                    'male' => [7.7, 6.8, 6.0, 5.3, 4.6, 3.9, 3.4, 2.8, 2.3, 2.0, 1.6, 1.3, 1.0, 0.7, 0.5, 0.3, 0.2],
                    'female' => [7.5, 6.6, 5.9, 5.2, 4.5, 3.9, 3.4, 2.9, 2.4, 2.0, 1.6, 1.3, 1.0, 0.8, 0.6, 0.3, 0.3],
                ],
                [
                    'title' => 'Europa – 2019',
                    'male' => [2.7, 2.8, 2.8, 2.7, 2.8, 3.3, 3.7, 3.8, 3.8, 3.8, 3.7, 3.7, 3.2, 2.6, 2.1, 1.2, 1.5],
                    'female' => [2.6, 2.7, 2.6, 2.5, 2.6, 3.1, 3.7, 3.7, 3.7, 3.7, 3.8, 3.7, 3.5, 3.2, 2.6, 1.9, 3.7],
                ],
            ],
            'max' => 10,
        ]];
    }

    // ==================== itemii ====================

    /** @return list<array<string, mixed>> */
    private function items(): array
    {
        $barem = 'Răspunsul e cel din baremul ANCE (testul model din programa de examen).';
        $calcul = 'Programa ANCE dă acest item fără barem; răspunsul e calculul pe datele din enunț.';

        $af = 'Enunțul este adevărat sau fals? Dacă e fals, alege și cuvintele care îl fac adevărat — la examen le scrii în locul celor subliniate.';

        return [
            // ---------- Testul cu barem ----------
            self::choice('test-item1.1', 'atmosfera', 2,
                "{$af}\n\n„În mediul **ecuatorial** vara este caldă și umedă, pe când iarna este caldă și uscată.”",
                ['Fals — corect: în mediul subecuatorial', 'Adevărat', 'Fals — corect: în mediul tropical deșertic', 'Fals — corect: în mediul temperat'],
                [
                    'Câte anotimpuri are mediul ecuatorial?',
                    'La Ecuator e cald și umed tot anul; alternanța unui sezon umed cu unul uscat e semnul altui mediu.',
                ],
                "Fals. În mediul ecuatorial nu există anotimpuri: e cald și umed tot anul. Vara umedă și iarna uscată descriu mediul subecuatorial. {$barem}"),

            self::choice('test-item1.2', 'populatia', 2,
                "{$af}\n\n„Diferența dintre rata natalității și rata mortalității constituie **bilanțul migratoriu** al populației.”",
                ['Fals — corect: bilanțul natural', 'Adevărat', 'Fals — corect: densitatea populației', 'Fals — corect: sporul mecanic'],
                [
                    'Natalitatea și mortalitatea sunt procese naturale sau migrații?',
                    'Bilanțul migratoriu compară imigranții cu emigranții.',
                ],
                "Fals. Natalitatea minus mortalitatea este bilanțul natural. Bilanțul migratoriu e diferența dintre imigranți și emigranți. {$barem}"),

            self::choice('test-item1.3', 'mediul-natural', 2,
                "{$af}\n\n„Componentele primare ale mediului geografic, care includ apa, aerul și rocile, sunt numite **abiotice**.”",
                ['Adevărat', 'Fals — corect: biotice', 'Fals — corect: antropice', 'Fals — corect: secundare'],
                [
                    '„Bio-" înseamnă viață. Apa, aerul și rocile sunt vii?',
                    '„A-" la început neagă: abiotic = fără viață.',
                ],
                "Adevărat. Apa, aerul și rocile sunt componente fără viață — abiotice. Plantele și animalele sunt biotice. {$barem}"),

            self::blanks('test-item2', 'pamantul-univers', 3,
                'Completează informațiile lipsă despre solstiții și echinocții.',
                [
                    'vara' => ['label' => 'Data solstițiului de vară', 'values' => ['22 iunie', '21 iunie', '22.06', '21.06']],
                    'tropic' => ['label' => 'Data când razele Soarelui cad perpendicular la Tropicul de Sud', 'values' => ['22 decembrie', '21 decembrie', '22.12', '21.12']],
                    'iarna' => ['label' => 'Denumirea astronomică a acestei zile', 'values' => ['solstițiul de iarnă', 'solstitiul de iarna', 'solstițiu de iarnă', 'solstitiu de iarna']],
                    'toamna' => ['label' => 'Denumirea astronomică a zilei de 23 septembrie', 'values' => ['echinocțiul de toamnă', 'echinoctiul de toamna', 'echinocțiu de toamnă', 'echinoctiu de toamna']],
                ],
                [
                    'Solstițiile sunt zilele cu ziua cea mai lungă sau cea mai scurtă; echinocțiile, cele cu ziua egală cu noaptea.',
                    'Razele cad perpendicular la Tropicul de Nord în iunie, la Ecuator în martie și septembrie, la Tropicul de Sud în decembrie.',
                ],
                "Solstițiul de vară: 22 (sau 21) iunie — razele cad perpendicular la Tropicul de Nord, în emisfera nordică e cea mai lungă zi. La Tropicul de Sud razele cad perpendicular pe 22 (sau 21) decembrie, la solstițiul de iarnă. Pe 23 septembrie e echinocțiul de toamnă: razele cad perpendicular la Ecuator, ziua e egală cu noaptea. {$barem}",
                false),

            self::blanks('test-item3', 'economia-mondiala', 2,
                'Asociază fiecare materie primă cu ramura industriei care o utilizează.',
                [
                    'bauxita' => ['label' => 'Bauxită', 'values' => ['Industria metalurgică'], 'options' => ['Industria alimentară', 'Industria metalurgică', 'Industria ușoară']],
                    'bumbac' => ['label' => 'Bumbac', 'values' => ['Industria ușoară'], 'options' => ['Industria alimentară', 'Industria metalurgică', 'Industria ușoară']],
                    'fier' => ['label' => 'Minereu de fier', 'values' => ['Industria metalurgică'], 'options' => ['Industria alimentară', 'Industria metalurgică', 'Industria ușoară']],
                    'fructe' => ['label' => 'Fructe', 'values' => ['Industria alimentară'], 'options' => ['Industria alimentară', 'Industria metalurgică', 'Industria ușoară']],
                    'lana' => ['label' => 'Lână', 'values' => ['Industria ușoară'], 'options' => ['Industria alimentară', 'Industria metalurgică', 'Industria ușoară']],
                    'peste' => ['label' => 'Pește', 'values' => ['Industria alimentară'], 'options' => ['Industria alimentară', 'Industria metalurgică', 'Industria ușoară']],
                ],
                [
                    'Industria ușoară produce țesături, haine, încălțăminte.',
                    'Bauxita dă aluminiu, minereul de fier dă oțel — amândouă intră în metalurgie.',
                ],
                "Alimentară: fructe, pește. Metalurgică: bauxită (aluminiu), minereu de fier. Ușoară: bumbac, lână (țesături). La examen, fiecare cifră se scrie o singură dată. {$barem}",
                false),

            self::choice('test-item7', 'pamantul-univers', 2,
                'Orașul Chișinău (R. Moldova) este situat în fusul orar 2 (emisfera de Est), iar orașul Paris (Franța) — în fusul orar 1 (emisfera de Est). Locuitorii cărui oraș întâlnesc Anul Nou mai devreme și cu câte ore?',
                ['Chișinău, cu o oră mai devreme', 'Paris, cu o oră mai devreme', 'Chișinău, cu două ore mai devreme', 'În același moment'],
                [
                    'Pământul se rotește de la vest spre est: ziua nouă începe întâi în est.',
                    'Diferența de fuse orare: 2 − 1.',
                ],
                "Chișinău, cu o oră. Orașul e mai la est, iar Pământul se rotește de la vest spre est, deci ora oficială la Chișinău e mai devreme. Diferența de fuse orare: 2 − 1 = 1 oră. {$barem}"),

            self::blanks('test-item8.1', 'atmosfera', 2,
                'Analizează climograma. Indică temperatura aerului în luna cea mai caldă și în luna cea mai rece (eroare admisibilă: ±1°) și calculează amplitudinea anuală a temperaturii.',
                [
                    'cald' => ['label' => 'Luna cea mai caldă, °C', 'values' => ['28'], 'tolerance' => 1],
                    'rece' => ['label' => 'Luna cea mai rece, °C', 'values' => ['25'], 'tolerance' => 1],
                    'amplitudine' => ['label' => 'Amplitudinea anuală, °C', 'values' => ['3'], 'tolerance' => 2],
                ],
                [
                    'Temperatura e linia roșie și se citește pe axa din DREAPTA.',
                    'Amplitudinea anuală = temperatura lunii celei mai calde − temperatura lunii celei mai reci.',
                ],
                "Luna cea mai caldă (mai): 28 °C; cea mai rece (decembrie): 25 °C; amplitudinea: 28° − 25° = 3 °C. Baremul acceptă pentru amplitudine valori de la 1° la 5°, ținând cont de eroarea de citire. {$barem}",
                true, self::climogramaEcuatoriala()),

            self::choice('test-item8.3', 'atmosfera', 2,
                'Analizează climograma. Identifică tipul de mase de aer care predomină pe parcursul anului.',
                ['Mase de aer ecuatorial', 'Mase de aer tropical', 'Mase de aer temperat', 'Mase de aer arctic'],
                [
                    'Temperatura stă între 25 și 28 °C tot anul, iar precipitațiile depășesc 2000 mm.',
                    'Aerul cald și umed tot anul se formează deasupra Ecuatorului.',
                ],
                "Aer ecuatorial: cald și umed tot anul — temperaturi de 25–28 °C și 2150 mm de precipitații. {$barem}",
                self::climogramaEcuatoriala()),

            self::choice('test-item8.5', 'atmosfera', 3,
                'Analizează climograma. Deduce tipul de climă.',
                ['Ecuatorial', 'Subecuatorial', 'Tropical deșertic', 'Musonic'],
                [
                    'Are climograma un sezon uscat?',
                    'Fără sezon uscat și cu amplitudine termică de doar câteva grade: clima de la Ecuator.',
                ],
                "Climă ecuatorială: cald tot anul (amplitudine de 3°), ploios în fiecare lună. Clima subecuatorială și cea musonică au un sezon uscat bine marcat. {$barem}",
                self::climogramaEcuatoriala()),

            self::choice('test-item10.1', 'populatia', 2,
                'Analizează piramidele populației pentru Africa și Europa. Identifică tipul de reproducere a populației specific fiecărei regiuni.',
                ['Africa — tradițional; Europa — modern', 'Africa — modern; Europa — tradițional', 'Ambele — tradițional', 'Ambele — modern'],
                [
                    'O bază largă înseamnă mulți copii; un vârf larg, mulți vârstnici.',
                    'Tipul tradițional: natalitate și mortalitate ridicate. Tipul modern: natalitate scăzută, speranță de viață mare.',
                ],
                "Africa: tip tradițional — baza largă (natalitate ridicată), vârf îngust (speranță de viață mai mică). Europa: tip modern — bază îngustă, multe persoane vârstnice. {$barem}",
                self::piramide()),

            self::choice('test-item10.4', 'populatia', 2,
                'Analizează piramidele populației pentru Africa și Europa. Indică tipul de politică demografică promovată în statele din Europa.',
                ['Pronatalistă (de stimulare a natalității)', 'Antinatalistă (de limitare a natalității)', 'De încurajare a emigrației'],
                [
                    'Baza piramidei Europei e mai îngustă decât mijlocul ei: se nasc puțini copii.',
                    'Un stat cu populație îmbătrânită încearcă să crească numărul nașterilor.',
                ],
                "Politică pronatalistă: natalitatea scăzută și îmbătrânirea demografică îi fac pe statele europene să stimuleze nașterile — alocații pentru copii, păstrarea locului de muncă al mamei, înlesniri pentru familiile numeroase. {$barem}",
                self::piramide()),

            self::choice('test-item13.1', 'atmosfera', 1,
                'Aerul atmosferic este un amestec de gaze, particule solide și lichide. Care este gazul cu cea mai mare pondere în compoziția aerului atmosferic?',
                ['Azotul', 'Oxigenul', 'Dioxidul de carbon', 'Argonul'],
                [
                    'Nu e gazul pe care îl respirăm ca să trăim.',
                    'Ocupă cam 78% din volumul aerului.',
                ],
                "Azotul — cam 78% din aer. Oxigenul are aproximativ 21%, iar restul gazelor sub 1%. {$barem}"),

            // ---------- Exemple de itemi (fără barem) ----------
            self::blanks('exemplu-item12.1', 'atmosfera', 2,
                'Analizează climograma. Indică temperatura aerului în luna cea mai caldă și în luna cea mai rece (eroare admisibilă: ±1°) și calculează amplitudinea anuală a temperaturii.',
                [
                    'cald' => ['label' => 'Luna cea mai caldă, °C', 'values' => ['18'], 'tolerance' => 1],
                    'rece' => ['label' => 'Luna cea mai rece, °C', 'values' => ['4'], 'tolerance' => 1],
                    'amplitudine' => ['label' => 'Amplitudinea anuală, °C', 'values' => ['14'], 'tolerance' => 2],
                ],
                [
                    'Temperatura e linia roșie și se citește pe axa din DREAPTA.',
                    'Amplitudinea anuală = temperatura lunii celei mai calde − temperatura lunii celei mai reci.',
                ],
                "Luna cea mai caldă (iulie): 18 °C; cea mai rece (februarie): 4 °C; amplitudinea: 18° − 4° = 14 °C. {$calcul}",
                true, self::climogramaTemperata()),

            self::choice('exemplu-item12.3', 'atmosfera', 2,
                'Analizează climograma. În ce emisferă este situată localitatea?',
                ['În emisfera nordică', 'În emisfera sudică'],
                [
                    'În ce lună e cel mai cald?',
                    'Iulie e lună de vară în emisfera nordică și de iarnă în cea sudică.',
                ],
                "În emisfera nordică: luna cea mai caldă e iulie, iar cea mai rece — februarie. În emisfera sudică ar fi invers. {$calcul}",
                self::climogramaTemperata()),

            self::choice('exemplu-item12.4', 'atmosfera', 3,
                'Analizează climograma. Deduce tipul de climă.',
                ['Temperat oceanic', 'Temperat continental', 'Subtropical mediteranean', 'Subarctic'],
                [
                    'Iarna rămâne peste 0 °C, iar precipitațiile sunt repartizate uniform, cam 60–80 mm pe lună.',
                    'Iernile blânde și ploile tot anul vin de la ocean.',
                ],
                "Climă temperat oceanică: ierni blânde (4 °C), veri răcoroase (18 °C), amplitudine moderată și precipitații repartizate uniform pe tot anul (800 mm). Clima continentală ar avea ierni sub 0 °C, iar cea mediteraneană — veri secetoase. {$calcul}",
                self::climogramaTemperata()),

            self::choice('exemplu-item14.1', 'populatia', 1,
                'Analizează datele din tabel. Identifică tendința dinamicii numărului de locuitori ai Ucrainei.',
                ['Descreștere', 'Creștere', 'Stagnare'],
                [
                    'Compară numărul populației din 1990 cu cel din 2019.',
                    '51,9 milioane → 44,3 milioane.',
                ],
                "Descreștere: de la 51,9 milioane în 1990 la 44,3 milioane în 2019. {$calcul}",
                [
                    'passage_title' => 'Unii indicatori demografici ai populației Ucrainei',
                    'passage' => "Numărul populației, milioane persoane: 51,9 (1990); 44,3 (2019)\nPonderea persoanelor de peste 65 de ani, %: 11,9 (1990); 16,9 (2019)\nRata natalității, la 1000 de locuitori: 12,6 (1990); 8,1 (2019)\nRata mortalității, la 1000 de locuitori: 12,1 (1990); 14,7 (2019)\n\nSursa: www.data.worldbank.org, citată în programa ANCE",
                ]),

            self::blanks('exemplu-item14.2', 'populatia', 2,
                'Analizează datele din tabel. Calculează bilanțul natural al populației Ucrainei pentru anul 2019.',
                [
                    'bilant' => ['label' => 'Bilanțul natural, ‰', 'values' => ['-6.6']],
                ],
                [
                    'Bilanțul natural = rata natalității − rata mortalității.',
                    '8,1 − 14,7.',
                ],
                "Bilanțul natural = 8,1 − 14,7 = −6,6 ‰. Valoarea negativă înseamnă că mor mai mulți oameni decât se nasc: populația scade natural. {$calcul}",
                true,
                [
                    'passage_title' => 'Unii indicatori demografici ai populației Ucrainei',
                    'passage' => "Rata natalității, la 1000 de locuitori: 12,6 (1990); 8,1 (2019)\nRata mortalității, la 1000 de locuitori: 12,1 (1990); 14,7 (2019)\n\nSursa: www.data.worldbank.org, citată în programa ANCE",
                ]),

            self::blanks('exemplu-item20', 'atmosfera', 3,
                'Determină temperatura medie a aerului la altitudinea unui munte de 4500 m, dacă la poalele lui, la altitudinea de 400 m, temperatura medie a aerului este de 8 °C.',
                [
                    'altitudine' => ['label' => 'Diferența de altitudine, m', 'values' => ['4100']],
                    'gradient' => ['label' => 'Gradientul termic vertical, °C la 100 m', 'values' => ['0.6']],
                    'diferenta' => ['label' => 'Diferența de temperatură, °C', 'values' => ['24.6']],
                    'temperatura' => ['label' => 'Temperatura la 4500 m, °C', 'values' => ['-16.6']],
                ],
                [
                    'Diferența de altitudine: 4500 − 400.',
                    'Temperatura scade cu 0,6 °C la fiecare 100 m: 41 × 0,6.',
                ],
                "4500 − 400 = 4100 m; gradientul termic vertical: 0,6 °C la 100 m; 4100 : 100 × 0,6 = 24,6 °C; 8 − 24,6 = −16,6 °C. {$calcul}",
                true),
        ];
    }
}
