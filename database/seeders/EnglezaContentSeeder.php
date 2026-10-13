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
 * Conținut de start pentru LIMBA ENGLEZĂ.
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
 * deci majoritatea punctajului NU e auto-corectabil. Itemii de aici antrenează
 * competențele pe care se sprijină scrisul — gramatică B1, vocabular tematic,
 * conectori, registru, structura eseului — plus comprehensiunea de la Subiectul I.
 * Eseul propriu-zis are nevoie de autoevaluare pe barem și de feedback, adăugate
 * separat.
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
    /** Text-suport folosit de itemii de comprehensiune (tema: cultura digitală). */
    /**
     * Text pentru simulare (holdout), scris pentru platformă. Ca și celelalte,
     * e original și neatribuit: nu prezentăm ficțiune ca știre reală despre o
     * persoană existentă.
     */
    private const PASSAGE_REPAIR = <<<'TXT'
        Three years ago, a retired electrician named Tom Wilkins noticed something that
        annoyed him. Every Monday, his neighbours put perfectly good things out with the
        rubbish: lamps with a broken switch, chairs with a loose leg, radios that only needed
        a new wire. "Most of these things could be fixed in ten minutes," he says. "People
        just didn't know how, or they thought it wasn't worth the trouble."

        So Tom decided to do something about it. He asked the local library if he could use
        one of its rooms on Saturday mornings, and he put up a few posters in shops. On the
        first Saturday, only four people came. Two of them brought broken toasters, one
        brought a bicycle with a flat tyre, and the fourth simply wanted to see what was
        going on.

        Today the Repair Café is one of the busiest places in town. Around twenty volunteers
        – electricians, tailors, carpenters and a few computer specialists – work there every
        week, and together they fix about sixty objects each Saturday. The repairs are free,
        although visitors can leave a small donation for tools and spare parts.

        But Tom insists that repairing things is only half of the idea. Visitors are not
        allowed to leave their objects and come back later. Instead, they have to sit next to
        the volunteer and watch, and they are encouraged to help. "If I just fix your lamp,
        you will need me again next time," Tom explains. "If you fix it with me, you won't."

        Many of the regular visitors are teenagers. Sixteen-year-old Maya first came with a
        broken pair of headphones. Now she volunteers herself and helps older visitors with
        their phones and laptops. "At school we learn a lot of theory," she says. "Here I can
        see straight away whether something works or not. It's the most satisfying feeling."

        The café has also changed the way people in the town think about their things. Last
        year, the town council reported that the amount of electrical rubbish collected from
        homes had fallen for the first time in a decade. Tom is careful not to take all the
        credit, but he is clearly proud. "We didn't invent anything new," he says. "Our
        grandparents repaired everything. We have simply remembered how to do it."
        TXT;

    private const PASSAGE_DIGITAL = <<<'TXT'
        For most teenagers today, being online is not an activity but a condition. A recent
        study of secondary school students found that the average participant checked their
        phone 78 times a day, usually without any particular reason. What surprised the
        researchers, however, was not the number itself but what the students said about it.
        Nearly two thirds described the habit as "tiring" rather than enjoyable.

        The study suggests that the problem is not technology as such, but the absence of
        boundaries around it. Students who kept their phones in another room while studying
        reported both better concentration and, unexpectedly, better moods. Those who simply
        promised themselves to use their phones "less" saw almost no change at all.

        The researchers therefore argue that schools should teach digital habits in the same
        way they teach reading or writing. Telling young people to spend less time online,
        they conclude, is about as useful as telling them to be happier.
        TXT;

    public function run(): void
    {
        $subject = Subject::where('slug', 'limba-engleza')->firstOrFail();

        $skills = $this->seedSkills($subject->id);
        $this->seedPrerequisites($skills);
        $this->seedItems($subject->id, $skills);
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
        return array_merge(
            $this->itemsDeReferinta(),
            $this->itemsGramatica(),
            $this->itemsLexicSiRegistru(),
            $this->itemsComprehensiuneSiEseu(),
            $this->itemsCultura(),
        );
    }

    /**
     * Itemii de referință: câte unul pentru fiecare mecanică de corectare.
     *
     * @return list<array<string, mixed>>
     */
    private function itemsDeReferinta(): array
    {
        return [
            // ==================== TIMPURI VERBALE ====================
            [
                'ref' => 'seed:eng:tense:present-perfect-since',
                'skill' => 'timpuri-verbale',
                'type' => ItemType::FillBlanks,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Choose the correct form of the verb.',
                    'stem_template' => 'My family ___ in Chișinău since 2019.',
                    'blanks' => [
                        ['key' => 'verb', 'options' => ['has lived', 'lives', 'lived', 'is living']],
                    ],
                ],
                'answer' => ['values' => ['verb' => ['has lived']]],
                'hints' => [
                    'Uită-te la cuvântul „since": el arată că acțiunea a început în trecut și continuă acum.',
                    '„Since" + un moment din trecut cere Present Perfect, nu Past Simple.',
                    'Subiectul „my family" e tratat ca singular în engleza standard, deci „has lived", nu „have lived".',
                ],
                'explanation' => 'Present Perfect („has lived") leagă trecutul de prezent — acțiunea continuă. „Lived" ar închide acțiunea în trecut și ar intra în conflict cu „since". Atenție și la acord: „family" e substantiv colectiv, tratat ca singular.',
            ],
            [
                'ref' => 'seed:eng:tense:past-continuous-interrupt',
                'skill' => 'timpuri-verbale',
                'type' => ItemType::FillBlanks,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Complete the sentence with the correct tenses.',
                    'stem_template' => 'While I ___ for the exam, the electricity ___ .',
                    'blanks' => [
                        ['key' => 'action_1', 'options' => ['was studying', 'studied', 'have studied', 'study']],
                        ['key' => 'action_2', 'options' => ['went off', 'was going off', 'has gone off', 'goes off']],
                    ],
                ],
                'answer' => ['values' => [
                    'action_1' => ['was studying'],
                    'action_2' => ['went off'],
                ]],
                'hints' => [
                    'Una din acțiuni durează, iar cealaltă o întrerupe brusc. Care e care?',
                    '„While" introduce acțiunea de fundal, cea care durează — Past Continuous.',
                    'Acțiunea scurtă care întrerupe merge la Past Simple: „was studying" + „went off".',
                ],
                'explanation' => 'Combinația Past Continuous + Past Simple e tiparul standard pentru „acțiune întreruptă": fundalul durează („was studying"), evenimentul e punctual („went off").',
            ],

            // ==================== CONDIȚIONALE ȘI PASIV ====================
            [
                'ref' => 'seed:eng:cond:type-2',
                'skill' => 'conditionale-pasiv',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Choose the correct option: "If I ___ more free time, I would do voluntary work."',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'had'],
                        ['key' => 'b', 'text' => 'have'],
                        ['key' => 'c', 'text' => 'would have'],
                        ['key' => 'd', 'text' => 'will have'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Uită-te în a doua parte a propoziției: ce formă apare acolo?',
                    '„Would do" indică o condițională de tipul 2 — situație ipotetică, contrară realității prezente.',
                    'Tipul 2 are structura: If + Past Simple, would + infinitiv. Deci „If I had…, I would do…". „Would" nu se pune în ambele părți.',
                ],
                'explanation' => 'Condiționala de tipul 2 (ipotetic prezent): `If + Past Simple, would + infinitiv`. Greșeala frecventă e „If I would have" — „would" apare doar în propoziția principală, niciodată după „if".',
            ],
            [
                'ref' => 'seed:eng:passive:past-simple',
                'skill' => 'conditionale-pasiv',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Choose the correct passive form: "The recycling project ___ by our class last spring."',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'was organised'],
                        ['key' => 'b', 'text' => 'organised'],
                        ['key' => 'c', 'text' => 'has organised'],
                        ['key' => 'd', 'text' => 'is organised'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    '„By our class" arată că subiectul nu face acțiunea, ci o suferă — deci ai nevoie de diateza pasivă.',
                    'Pasivul se construiește cu verbul „to be" + participiul trecut.',
                    '„Last spring" cere trecut, deci „was" + „organised". „Is organised" ar fi prezent, iar „has organised" e activ.',
                ],
                'explanation' => 'Pasivul = `to be` la timpul cerut + participiul trecut. Aici „last spring" fixează trecutul: „was organised". Marca sigură a pasivului e agentul introdus cu „by".',
            ],

            // ==================== VORBIRE INDIRECTĂ ȘI MODALE ====================
            [
                'ref' => 'seed:eng:reported:statement',
                'skill' => 'vorbire-indirecta',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Report the sentence correctly: Ana said, "I am preparing for my exams."',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Ana said that she was preparing for her exams.'],
                        ['key' => 'b', 'text' => 'Ana said that she is preparing for her exams.'],
                        ['key' => 'c', 'text' => 'Ana said that I was preparing for my exams.'],
                        ['key' => 'd', 'text' => 'Ana said she will prepare for her exams.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'În vorbirea indirectă se schimbă două lucruri: timpul verbal și pronumele.',
                    'După „said" (trecut), timpul se dă cu un pas în urmă: Present Continuous → Past Continuous.',
                    'Și pronumele se mută: „I" devine „she", „my" devine „her". Varianta cu „I was" păstrează greșit pronumele vorbitorului.',
                ],
                'explanation' => 'Trecerea la vorbirea indirectă cere „backshift" (am → was) plus schimbarea pronumelor și a adjectivelor posesive. Cele două se uită frecvent împreună — verifică-le mereu pe amândouă.',
            ],
            [
                'ref' => 'seed:eng:modal:deduction',
                'skill' => 'vorbire-indirecta',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Choose the best modal verb: "He ___ be at home — his car is in front of the house."',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'must'],
                        ['key' => 'b', 'text' => "can't"],
                        ['key' => 'c', 'text' => 'should'],
                        ['key' => 'd', 'text' => 'needn\'t'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Propoziția a doua e o dovadă. Vorbitorul trage o concluzie logică pe baza ei.',
                    'Pentru o deducție aproape sigură, engleza folosește „must". Pentru o imposibilitate logică folosește „can\'t".',
                    'Dovada („car is in front of the house") susține concluzia, nu o contrazice — deci „must be", nu „can\'t be".',
                ],
                'explanation' => 'Modalele de deducție: `must` = „sunt aproape sigur că da", `can\'t` = „e imposibil". `Should` exprimă așteptare sau sfat, nu deducție din dovezi.',
            ],

            // ==================== ARTICOLE ȘI PREPOZIȚII ====================
            [
                'ref' => 'seed:eng:prep:interested-in',
                'skill' => 'articole-prepozitii',
                'type' => ItemType::FindError,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Find the mistake in this sentence: "Many young people are interested about learning foreign languages."',
                    'choices' => [
                        ['key' => 'a', 'text' => '"about" should be "in"'],
                        ['key' => 'b', 'text' => '"learning" should be "to learn"'],
                        ['key' => 'c', 'text' => '"are" should be "is"'],
                        ['key' => 'd', 'text' => 'There is no mistake'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Greșeala e la o prepoziție fixată de adjectiv. Adjectivele își „aleg" prepoziția, nu se traduce liber din română.',
                    'În română spui „interesat DE", iar traducerea directă duce la greșeală în engleză.',
                    'Forma corectă e „interested IN". După ea, verbul stă la gerunziu: „interested in learning".',
                ],
                'explanation' => 'Adjectivele au prepoziții fixe, care nu se pot deduce din română: `interested in`, `good at`, `afraid of`, `responsible for`. După prepoziție verbul ia forma în -ing, nu infinitivul.',
            ],
            [
                'ref' => 'seed:eng:article:superlative',
                'skill' => 'articole-prepozitii',
                'type' => ItemType::FindError,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Find the mistake in this sentence: "She is best student in our class."',
                    'choices' => [
                        ['key' => 'a', 'text' => 'The article "the" is missing before "best"'],
                        ['key' => 'b', 'text' => '"best" should be "better"'],
                        ['key' => 'c', 'text' => '"in" should be "from"'],
                        ['key' => 'd', 'text' => 'There is no mistake'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Lipsește un cuvânt scurt. Gândește-te la ce cere gradul superlativ.',
                    'Superlativul desemnează un lucru unic în grupul lui, deci cere articolul hotărât.',
                    'Corect: „She is THE best student in our class". „Better" ar fi comparativ și ar cere „than".',
                ],
                'explanation' => 'Superlativul cere obligatoriu „the": `the best`, `the most interesting`. Româna nu are un echivalent direct, de aceea omisiunea e frecventă la vorbitorii nativi de română.',
            ],

            // ==================== VOCABULAR TEMATIC ====================
            [
                'ref' => 'seed:eng:vocab:collocation-decision',
                'skill' => 'vocabular-tematic',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Choose the correct verb: "Before choosing a profession, you should ___ a careful decision."',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'make'],
                        ['key' => 'b', 'text' => 'do'],
                        ['key' => 'c', 'text' => 'take'],
                        ['key' => 'd', 'text' => 'give'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'E o colocație fixă: anumite verbe merg cu anumite substantive, fără regulă logică.',
                    'În engleză „do" se folosește pentru activități și sarcini (do homework, do the dishes), iar „make" pentru lucruri care se creează sau se stabilesc.',
                    'Corect e „make a decision". „Take a decision" apare în engleza britanică formală, dar „make" e forma standard cerută la B1.',
                ],
                'explanation' => 'Colocațiile make/do se memorează, nu se deduc: `make a decision`, `make progress`, `make a mistake` versus `do homework`, `do research`, `do your best`.',
            ],
            [
                'ref' => 'seed:eng:vocab:environment-match',
                'skill' => 'vocabular-tematic',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 4,
                'payload' => [
                    'stem' => 'Match each word with its definition. (Topic: the environment)',
                    'left' => [
                        ['key' => 'l1', 'text' => 'renewable'],
                        ['key' => 'l2', 'text' => 'landfill'],
                        ['key' => 'l3', 'text' => 'emissions'],
                        ['key' => 'l4', 'text' => 'sustainable'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'gases released into the air by vehicles or factories'],
                        ['key' => 'r2', 'text' => 'able to be used without being permanently used up'],
                        ['key' => 'r3', 'text' => 'able to continue for a long time without harming the environment'],
                        ['key' => 'r4', 'text' => 'a place where rubbish is buried in the ground'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r2', 'l2' => 'r4', 'l3' => 'r1', 'l4' => 'r3']],
                'hints' => [
                    'Începe cu cele mai concrete: „landfill" descrie un loc fizic.',
                    '„Emissions" vine de la „to emit" — a elibera. Caută definiția care menționează aer.',
                    'Rămân „renewable" și „sustainable": primul se referă la resursa în sine (se reînnoiește), al doilea la practică (poate continua fără să dăuneze).',
                ],
                'explanation' => 'Confuzia „renewable" / „sustainable" e frecventă: prima descrie o RESURSĂ care se regenerează, a doua o PRACTICĂ ce poate continua în timp. Sunt cuvinte-cheie pentru tema „mediul natural" din programă.',
            ],
            [
                'ref' => 'seed:eng:vocab:phrasal-cut-down',
                'skill' => 'vocabular-tematic',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'What does the phrasal verb mean? "We should all cut down on single-use plastic."',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'reduce the amount of something'],
                        ['key' => 'b', 'text' => 'stop something completely'],
                        ['key' => 'c', 'text' => 'divide something into pieces'],
                        ['key' => 'd', 'text' => 'pay less for something'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Nu traduce cuvânt cu cuvânt — „cut" aici nu are sensul de „a tăia".',
                    'Prepoziția „on" arată că urmează cantitatea a ceva pe care o reducem.',
                    '„Cut down on" = a reduce, nu a elimina complet. Pentru eliminare totală s-ar folosi „cut out" sau „give up".',
                ],
                'explanation' => 'Diferența dintre `cut down on` (reduce) și `cut out` / `give up` (elimină complet) contează la eseu: prima e o propunere realistă, a doua una radicală.',
            ],

            // ==================== COEZIUNE ȘI CONECTORI ====================
            [
                'ref' => 'seed:eng:cohesion:connectors-match',
                'skill' => 'coeziune-conectori',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 4,
                'payload' => [
                    'stem' => 'Match each linking word with its function.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'However'],
                        ['key' => 'l2', 'text' => 'Therefore'],
                        ['key' => 'l3', 'text' => 'Moreover'],
                        ['key' => 'l4', 'text' => 'For instance'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'adds another point in the same direction'],
                        ['key' => 'r2', 'text' => 'introduces a contrast with what was said before'],
                        ['key' => 'r3', 'text' => 'gives an example'],
                        ['key' => 'r4', 'text' => 'introduces a result or conclusion'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r2', 'l2' => 'r4', 'l3' => 'r1', 'l4' => 'r3']],
                'hints' => [
                    'Grupează-le după ce fac: unul contrazice, unul concluzionează, unul adaugă, unul exemplifică.',
                    '„However" e cel mai apropiat de „dar/totuși", iar „Therefore" de „prin urmare".',
                    '„Moreover" adaugă un argument în aceeași direcție, iar „For instance" e sinonim cu „for example".',
                ],
                'explanation' => 'Conectorii sunt cea mai ieftină sursă de puncte la eseul structurat: baremul evaluează coeziunea. Patru funcții acoperă aproape tot: contrast, rezultat, adăugare, exemplificare.',
            ],
            [
                'ref' => 'seed:eng:cohesion:connector-in-context',
                'skill' => 'coeziune-conectori',
                'type' => ItemType::FillBlanks,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Choose the linking word that fits the logic of the sentence.',
                    'stem_template' => 'Online lessons save time. ___ , they make it harder to stay focused.',
                    'blanks' => [
                        ['key' => 'linker', 'options' => ['However', 'Therefore', 'Moreover', 'For instance']],
                    ],
                ],
                'answer' => ['values' => ['linker' => ['However']]],
                'hints' => [
                    'Compară cele două propoziții: a doua continuă ideea primei sau o contrazice?',
                    'Prima spune un avantaj, a doua un dezavantaj — deci e o schimbare de direcție.',
                    'Contrastul cere „However". „Moreover" ar fi corect doar dacă a doua propoziție ar adăuga un alt avantaj.',
                ],
                'explanation' => 'Alegerea conectorului nu e o chestiune de stil, ci de logică: „Moreover" într-un loc unde ideea se schimbă face textul incoerent și se penalizează la barem.',
            ],

            // ==================== REGISTRU ====================
            [
                'ref' => 'seed:eng:register:formal-essay',
                'skill' => 'registru',
                'type' => ItemType::FindError,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'This sentence comes from a formal essay. What is wrong with it? "In my opinion, social media has a lot of bad stuff and kids are gonna suffer."',
                    'choices' => [
                        ['key' => 'a', 'text' => 'The register is too informal for an essay ("stuff", "kids", "gonna")'],
                        ['key' => 'b', 'text' => 'The sentence is grammatically incorrect'],
                        ['key' => 'c', 'text' => '"In my opinion" cannot be used in an essay'],
                        ['key' => 'd', 'text' => 'There is nothing wrong with it'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Gramatica e în regulă. Problema e la felul cum sună, nu la ce e greșit.',
                    'Uită-te la trei cuvinte: unul e vag, unul e colocvial, unul e o formă scurtă din vorbire.',
                    '„Stuff" → „aspects/effects", „kids" → „children/young people", „gonna" → „will". Formele contrase și colocviale se penalizează la eseu.',
                ],
                'explanation' => 'Registrul e criteriu de barem la Subiectul II. Eseul cere limbaj formal: fără forme contrase din vorbire („gonna", „wanna"), fără cuvinte vagi („stuff", „things") și fără colocvialisme („kids"). „In my opinion" e perfect acceptabil.',
            ],

            // ==================== EESEUL STRUCTURAT ====================
            [
                'ref' => 'seed:eng:essay:structure-order',
                'skill' => 'eseu-structurat',
                'type' => ItemType::OrderSteps,
                'difficulty' => 3,
                'points' => 4,
                'payload' => [
                    'stem' => 'Put the parts of a structured essay in the correct order. (Subiectul II: 180–200 words)',
                    'steps' => [
                        ['key' => 's1', 'text' => 'Introduction: present the topic and state your position'],
                        ['key' => 's2', 'text' => 'First body paragraph: your strongest argument, with an example'],
                        ['key' => 's3', 'text' => 'Second body paragraph: another argument or the opposite view'],
                        ['key' => 's4', 'text' => 'Response to the opposite view, or a proposed solution'],
                        ['key' => 's5', 'text' => 'Conclusion: restate your position in different words'],
                    ],
                ],
                'answer' => ['order' => ['s1', 's2', 's3', 's4', 's5']],
                'hints' => [
                    'Structura e mereu aceeași: spui ce crezi, argumentezi, răspunzi obiecțiilor, închei.',
                    'Argumentul cel mai puternic se pune primul, nu la final — cititorul îl reține mai bine.',
                    'Concluzia reformulează poziția cu alte cuvinte; repetarea identică a introducerii se penalizează.',
                ],
                'explanation' => 'Baremul de la Subiectul II punctează separat structura. Cu 180–200 de cuvinte ai loc pentru exact 5 părți — de aceea planul se face înainte de a scrie, nu în timpul scrierii.',
            ],
            [
                'ref' => 'seed:eng:essay:thesis-statement',
                'skill' => 'eseu-structurat',
                'type' => ItemType::Mcq,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'Essay topic: "Should schools teach students how to use social media?" Which sentence is the best thesis statement for the introduction?',
                    'choices' => [
                        ['key' => 'a', 'text' => 'Schools should teach digital habits, because young people learn boundaries more effectively than they learn rules.'],
                        ['key' => 'b', 'text' => 'Social media is a very important topic in the modern world today.'],
                        ['key' => 'c', 'text' => 'In this essay I am going to write about social media in schools.'],
                        ['key' => 'd', 'text' => 'Everybody knows that social media is bad for students.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'O teză bună răspunde la întrebare ȘI spune de ce. Verifică fiecare variantă: care face ambele?',
                    'Variantele care doar anunță subiectul („I am going to write about") nu conțin nicio poziție.',
                    'Prima variantă ia o poziție clară („should teach") și o justifică („because…"). „Everybody knows" e o generalizare nesusținută, penalizată la barem.',
                ],
                'explanation' => 'Teza trebuie să conțină poziția plus un motiv. Cele trei capcane tipice: anunțarea subiectului fără poziție, generalizarea („everybody knows"), și afirmația vagă („is very important").',
            ],

            // ==================== COMPREHENSIUNE SCRISĂ ====================
            [
                'ref' => 'seed:eng:reading:digital-main-idea',
                'skill' => 'comprehensiune-scrisa',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'points' => 2,
                'payload' => [
                    'stem' => 'What is the main idea of the text?',
                    'passage_title' => 'Always On',
                    'passage' => self::PASSAGE_DIGITAL,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Teenagers need to be taught digital habits, not simply told to use their phones less.'],
                        ['key' => 'b', 'text' => 'Teenagers check their phones 78 times a day on average.'],
                        ['key' => 'c', 'text' => 'Technology is harmful to young people and should be limited at school.'],
                        ['key' => 'd', 'text' => 'Students who study without their phones get better marks.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Ideea principală nu e un detaliu din text, ci concluzia pe care textul o susține în întregime.',
                    'Citește ultimul paragraf: acolo autorii își formulează argumentul.',
                    'Cifra 78 e un detaliu, iar „technology is harmful" contrazice textul („the problem is not technology as such"). Rămâne concluzia despre predarea obiceiurilor digitale.',
                ],
                'explanation' => 'La întrebările de idee principală, capcanele standard sunt: un DETALIU adevărat dar prea îngust (78 de ori), și o afirmație care CONTRAZICE textul. Verifică mereu ultimul paragraf — acolo stă de obicei teza.',
            ],
            [
                'ref' => 'seed:eng:reading:digital-detail',
                'skill' => 'comprehensiune-scrisa',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'points' => 2,
                'payload' => [
                    'stem' => 'According to the text, what surprised the researchers?',
                    'passage_title' => 'Always On',
                    'passage' => self::PASSAGE_DIGITAL,
                    'choices' => [
                        ['key' => 'a', 'text' => 'That most students described the habit as tiring rather than enjoyable.'],
                        ['key' => 'b', 'text' => 'How often the students checked their phones.'],
                        ['key' => 'c', 'text' => 'That students kept their phones in another room.'],
                        ['key' => 'd', 'text' => 'That schools do not teach reading and writing properly.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Caută în text cuvântul „surprised" și citește propoziția în care apare.',
                    'Textul spune explicit ce NU i-a surprins: „not the number itself".',
                    'Deci surprinderea vine din ce au declarat elevii — că obiceiul e „tiring", nu plăcut.',
                ],
                'explanation' => 'Textul distinge explicit între ce nu a surprins („the number itself") și ce a surprins („what the students said about it"). Structurile „not X but Y" sunt aproape întotdeauna locul de unde se construiește un item de detaliu.',
            ],

            // ==================== HOLDOUT: simularea Subiectului I ====================
            // Un text nou, necunoscut elevului, cu întrebări de idee principală,
            // detaliu și inferență — exact tipologia Subiectului I.
            [
                'ref' => 'seed:eng:mock:repair-main-idea',
                'skill' => 'comprehensiune-scrisa',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'points' => 2,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'What is the main idea of the text?',
                    'passage_title' => 'The Repair Café',
                    'passage' => self::PASSAGE_REPAIR,
                    'choices' => [
                        ['key' => 'a', 'text' => 'A volunteer project repairs broken objects, teaches people to fix things themselves and has changed local habits.'],
                        ['key' => 'b', 'text' => 'Tom Wilkins is a retired electrician who repairs lamps and radios for his neighbours.'],
                        ['key' => 'c', 'text' => 'The town council has made it illegal to throw away electrical objects.'],
                        ['key' => 'd', 'text' => 'Teenagers prefer repairing things to learning theory at school.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Ideea principală trebuie să cuprindă tot textul, nu doar un paragraf.',
                    'Varianta despre Tom e adevărată, dar e doar începutul poveștii. Cea despre consiliul local nu apare deloc în text.',
                    'Textul vorbește despre trei lucruri: reparațiile, faptul că vizitatorii învață să repare singuri și efectul asupra orașului.',
                ],
                'explanation' => 'Varianta corectă rezumă cele trei idei ale textului: reparațiile, învățarea și schimbarea obiceiurilor din oraș. Celelalte sunt capcanele obișnuite: un detaliu prea îngust (Tom), o informație care nu apare în text (legea) și o generalizare pornind de la o singură persoană (Maya).',
            ],
            [
                'ref' => 'seed:eng:mock:repair-detail',
                'skill' => 'comprehensiune-scrisa',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'points' => 2,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Why do visitors have to stay while their object is being repaired?',
                    'passage_title' => 'The Repair Café',
                    'passage' => self::PASSAGE_REPAIR,
                    'choices' => [
                        ['key' => 'a', 'text' => 'So that they learn how to repair it themselves next time.'],
                        ['key' => 'b', 'text' => 'Because they have to pay the volunteers before they leave.'],
                        ['key' => 'c', 'text' => 'Because the café is only open for a short time.'],
                        ['key' => 'd', 'text' => 'Because objects left in the library sometimes get lost.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Caută paragraful care începe cu „But Tom insists…".',
                    'Citește explicația lui Tom: „If I just fix your lamp, you will need me again next time".',
                    'Plata nu e obligatorie („the repairs are free"), iar celelalte două motive nu apar în text.',
                ],
                'explanation' => 'Tom explică direct: dacă repară el obiectul, vizitatorul va avea nevoie de el și data viitoare; dacă îl repară împreună, nu. Varianta cu plata contrazice textul, pentru că reparațiile sunt gratuite, iar donația e opțională.',
            ],
            [
                'ref' => 'seed:eng:mock:repair-first-day',
                'skill' => 'comprehensiune-scrisa',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'points' => 2,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Which statement about the first Saturday is true according to the text?',
                    'passage_title' => 'The Repair Café',
                    'passage' => self::PASSAGE_REPAIR,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Four people came, but not all of them brought something to repair.'],
                        ['key' => 'b', 'text' => 'Twenty volunteers repaired about sixty objects.'],
                        ['key' => 'c', 'text' => 'Nobody came, because there were no posters.'],
                        ['key' => 'd', 'text' => 'All the visitors brought broken toasters.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Atenție la timp: textul descrie separat prima sâmbătă și situația de „today".',
                    'Cifrele 20 și 60 se referă la „today", nu la prima zi.',
                    'Al patrulea vizitator „simply wanted to see what was going on" — deci nu adusese nimic de reparat.',
                ],
                'explanation' => 'În prima sâmbătă au venit patru oameni: doi cu prăjitoare, unul cu bicicleta, iar al patrulea doar din curiozitate. Varianta cu 20 de voluntari amestecă trecutul cu prezentul — o capcană frecventă la itemii de detaliu.',
            ],
            [
                'ref' => 'seed:eng:mock:repair-inference',
                'skill' => 'comprehensiune-scrisa',
                'type' => ItemType::Mcq,
                'difficulty' => 4,
                'points' => 2,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'What does Tom mean when he says "We have simply remembered how to do it"?',
                    'passage_title' => 'The Repair Café',
                    'passage' => self::PASSAGE_REPAIR,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Repairing things is an old habit that people had forgotten, not a new idea.'],
                        ['key' => 'b', 'text' => 'He learned to repair things from an old book.'],
                        ['key' => 'c', 'text' => 'Older people are better at repairs than young people.'],
                        ['key' => 'd', 'text' => 'The café copies a project from another town.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Citește cele două propoziții dinaintea citatului: „We didn\'t invent anything new… Our grandparents repaired everything."',
                    'Tom compară prezentul cu generația bunicilor. Ce făceau ei și ce s-a pierdut între timp?',
                    'Ideea lui e că reparatul nu e o invenție, ci un obicei vechi care fusese uitat.',
                ],
                'explanation' => 'Tom spune că nu au inventat nimic: bunicii reparau totul, iar ei doar și-au „amintit" cum se face. E o întrebare de inferență: răspunsul nu e scris direct, ci se deduce din propozițiile din jurul citatului. Varianta c generalizează o idee pe care textul nu o susține — Maya, o adolescentă, e unul dintre voluntari.',
            ],
            [
                'ref' => 'seed:eng:mock:connector-therefore',
                'skill' => 'coeziune-conectori',
                'type' => ItemType::FillBlanks,
                'difficulty' => 2,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Choose the linking word that fits the meaning of the sentence.',
                    'stem_template' => 'Tom was tired of seeing good things thrown away; ___, he opened the Repair Café.',
                    'blanks' => [
                        ['key' => 'connector', 'options' => ['therefore', 'however', 'although', 'unless']],
                    ],
                ],
                'answer' => ['values' => ['connector' => ['therefore']]],
                'hints' => [
                    'Ce relație e între cele două părți ale propoziției: contrast, condiție sau cauză și efect?',
                    'Prima parte e motivul, a doua e consecința.',
                    '„Therefore" introduce o consecință. „However" ar introduce un contrast, iar „although" și „unless" nu pot sta după punct și virgulă în această structură.',
                ],
                'explanation' => 'Relația e de cauză și efect: pentru că era deranjat de risipă, a deschis cafeneaua. „Therefore" marchează consecința. Conectorii de cauză și efect sunt punctați separat la eseul de la Subiectul II.',
            ],

            // ==================== CULTURĂ ====================
            [
                'ref' => 'seed:eng:culture:a-levels',
                'skill' => 'spatiul-anglofon',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'In the United Kingdom, what are "A-levels"?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Subject-based qualifications taken at the end of secondary school, used for university entry'],
                        ['key' => 'b', 'text' => 'The highest marks a student can receive in any exam'],
                        ['key' => 'c', 'text' => 'The first two years of a university degree'],
                        ['key' => 'd', 'text' => 'Compulsory tests taken at the age of eleven'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    '„A-levels" corespund, funcțional, examenului pe care îl dai tu la finalul liceului.',
                    'Litera „A" vine de la „Advanced", nu de la o notă.',
                    'Sunt calificări pe discipline, susținute la 17–18 ani, folosite pentru admiterea la universitate — echivalentul britanic al bacalaureatului.',
                ],
                'explanation' => 'A-levels („Advanced Level") sunt calificări pe discipline alese, susținute la finalul învățământului secundar și folosite la admiterea universitară — cel mai apropiat echivalent britanic al bacalaureatului. Tema „sistemul educațional din țara alofonă" apare explicit în programă, la Subiectul III.',
            ],
            [
                'ref' => 'seed:eng:culture:personalities',
                'skill' => 'patrimoniu-personalitati',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 4,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Match each figure with the field they are known for.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'William Shakespeare'],
                        ['key' => 'l2', 'text' => 'Alan Turing'],
                        ['key' => 'l3', 'text' => 'Emmeline Pankhurst'],
                        ['key' => 'l4', 'text' => 'David Attenborough'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'computer science and code-breaking'],
                        ['key' => 'r2', 'text' => 'drama and poetry'],
                        ['key' => 'r3', 'text' => 'nature documentaries and broadcasting'],
                        ['key' => 'r4', 'text' => 'the campaign for women\'s right to vote'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r2', 'l2' => 'r1', 'l3' => 'r4', 'l4' => 'r3']],
                'hints' => [
                    'Doi sunt din secolul XX, unul din perioada elisabetană, iar unul e contemporan.',
                    'Shakespeare a scris piese de teatru și sonete; Turing a lucrat la descifrarea codurilor în timpul celui de-al Doilea Război Mondial.',
                    'Pankhurst a condus mișcarea sufragetelor, iar Attenborough e cunoscut pentru documentarele despre natură.',
                ],
                'explanation' => 'Tema „personalitățile ilustre" din Domeniul Cultură apare la Subiectul III, unde se cere un articol de presă sau o scrisoare pe o temă de cultură a țării alofone. Cunoașterea câtorva figuri-reper îți dă material concret de scris.',
            ],
        ];
    }

    /**
     * Gramatică B1: timpuri, condiționale și pasiv, vorbire indirectă și modale,
     * articole, prepoziții și acord.
     *
     * Enunțul și variantele sunt în engleză; indiciile și explicațiile, în
     * română (§13). Elevul e testat la engleză, dar învață în limba lui.
     *
     * @return list<array<string, mixed>>
     */
    private function itemsGramatica(): array
    {
        return [
            // ---------- Timpuri verbale ----------
            [
                'ref' => 'seed:en:timpuri:present-perfect-since',
                'hints' => [
                    'Acțiunea a început în trecut și continuă până acum. Present Simple nu poate exprima asta.',
                    '„Since" și „for" cer aproape întotdeauna Present Perfect.',
                    'Corect: „have lived". „I live here since 2020" e un calc direct din română.',
                ],
                'skill' => 'timpuri-verbale',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Choose the correct form: "I ___ in this town since 2020."',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'have lived'],
                        ['key' => 'b', 'text' => 'live'],
                        ['key' => 'c', 'text' => 'lived'],
                        ['key' => 'd', 'text' => 'am living'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '„Since 2020" arată o acțiune începută în trecut și încă valabilă, deci Present Perfect. În română spunem „locuiesc din 2020" la prezent — de aici vine greșeala.',
            ],
            [
                'ref' => 'seed:en:timpuri:past-simple-marker',
                'hints' => [
                    'Caută marcajul de timp din propoziție: el decide timpul verbal.',
                    '„Last year", „yesterday", „in 2019" cer o acțiune încheiată, cu moment precizat.',
                    'Un moment trecut precizat exclude Present Perfect: se folosește Past Simple, „visited".',
                ],
                'skill' => 'timpuri-verbale',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Choose the correct form: "We ___ London last summer."',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'visited'],
                        ['key' => 'b', 'text' => 'have visited'],
                        ['key' => 'c', 'text' => 'were visiting'],
                        ['key' => 'd', 'text' => 'have been visiting'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Un moment trecut PRECIZAT („last summer") cere Past Simple. Present Perfect se folosește tocmai când momentul nu e precizat: „We have visited London twice."',
            ],
            [
                'ref' => 'seed:en:timpuri:future-plan',
                'hints' => [
                    'Există o diferență între o decizie luată pe loc și un plan făcut dinainte.',
                    'Pentru aranjamente deja stabilite, engleza folosește Present Continuous.',
                    '„I am meeting her tomorrow" = e programat. „I will meet her" ar suna ca o decizie de moment.',
                ],
                'skill' => 'timpuri-verbale',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Which form best expresses a fixed arrangement? "I ___ the dentist at 4 p.m. tomorrow."',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'am seeing'],
                        ['key' => 'b', 'text' => 'will see'],
                        ['key' => 'c', 'text' => 'see'],
                        ['key' => 'd', 'text' => 'would see'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Present Continuous exprimă aranjamente viitoare deja stabilite, cu oră și loc. „Will" se folosește pentru decizii spontane sau predicții.',
            ],
            [
                'ref' => 'seed:en:timpuri:past-perfect-ordine',
                'hints' => [
                    'Două acțiuni trecute, dar una s-a petrecut înaintea celeilalte.',
                    'Acțiunea MAI VECHE merge la Past Perfect, cea mai recentă la Past Simple.',
                    'Trenul plecase înainte ca el să ajungă: „the train had left".',
                ],
                'skill' => 'timpuri-verbale',
                'type' => ItemType::FillBlanks,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Complete with the correct tenses.',
                    'stem_template' => 'When we ___ at the station, the train ___ .',
                    'blanks' => [
                        ['key' => 'sosire', 'options' => ['arrived', 'had arrived', 'have arrived', 'arrive']],
                        ['key' => 'plecare', 'options' => ['had already left', 'already left', 'has already left', 'was leaving']],
                    ],
                ],
                'answer' => ['values' => ['sosire' => ['arrived'], 'plecare' => ['had already left']]],
                'explanation' => 'Past Perfect marchează anterioritatea: plecarea trenului e mai veche decât sosirea noastră. Fără el, ordinea evenimentelor se pierde.',
            ],
            [
                'ref' => 'seed:en:timpuri:stative-eroare',
                'hints' => [
                    'Unele verbe nu descriu acțiuni, ci stări, și nu se folosesc la forma continuă.',
                    '„Know", „believe", „understand", „belong", „want" sunt verbe de stare.',
                    'Corect e „I know", nu „I am knowing".',
                ],
                'skill' => 'timpuri-verbale',
                'type' => ItemType::FindError,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'A student wrote the sentence below. Where is the mistake?',
                    'problem' => 'I am knowing the answer to this question.',
                    'choices' => [
                        ['key' => 'a', 'text' => '„Know" e verb de stare și nu se folosește la forma continuă: „I know"'],
                        ['key' => 'b', 'text' => 'Trebuia „I am knowing of"'],
                        ['key' => 'c', 'text' => 'Trebuia Past Simple: „I knew"'],
                        ['key' => 'd', 'text' => 'Propoziția e corectă'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Verbele de stare descriu situații, nu acțiuni în desfășurare, deci nu acceptă forma „-ing". Excepție cunoscută: „I am thinking" când înseamnă „mă gândesc", nu „cred".',
            ],
            [
                'ref' => 'seed:en:timpuri:potrivire-markeri',
                'hints' => [
                    'Fiecare timp verbal are marcajele lui de timp. Ele sunt indiciul din enunț.',
                    '„Ago" trimite mereu la un moment trecut încheiat.',
                    '„Already/yet/just" sunt semnalele Present Perfect; „while" cere de obicei o acțiune în desfășurare.',
                ],
                'skill' => 'timpuri-verbale',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Match the time marker with the tense it signals.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'two days ago'],
                        ['key' => 'l2', 'text' => 'already, yet, just'],
                        ['key' => 'l3', 'text' => 'while, as'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'Present Perfect'],
                        ['key' => 'r2', 'text' => 'Past Continuous'],
                        ['key' => 'r3', 'text' => 'Past Simple'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r3', 'l2' => 'r1', 'l3' => 'r2']],
                'explanation' => 'Marcajele de timp sunt cel mai rapid indiciu la Subiectul I: identifici marcajul, apoi alegi forma — nu invers.',
            ],

            // ---------- Condiționale și pasiv ----------
            [
                'ref' => 'seed:en:conditionale:tip1-if-will',
                'hints' => [
                    'După „if" nu se pune niciodată „will" în engleza standard.',
                    'Condiționalul de tip 1 are prezent după „if" și „will" în propoziția principală.',
                    'Corect: „If it rains, we will stay home."',
                ],
                'skill' => 'conditionale-pasiv',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Choose the correct sentence.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'If it rains, we will stay home.'],
                        ['key' => 'b', 'text' => 'If it will rain, we will stay home.'],
                        ['key' => 'c', 'text' => 'If it rains, we stay home tomorrow.'],
                        ['key' => 'd', 'text' => 'If it would rain, we will stay home.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Regula se extinde și la „when", „as soon as", „until", „before": după ele se folosește prezentul, chiar dacă sensul e de viitor.',
            ],
            [
                'ref' => 'seed:en:conditionale:tip3-regret',
                'hints' => [
                    'Propoziția exprimă un regret despre trecut: lucrurile nu se mai pot schimba.',
                    'Asta cere condiționalul de tip 3: Past Perfect după „if", „would have" în principală.',
                    'Corect: „If I had studied, I would have passed."',
                ],
                'skill' => 'conditionale-pasiv',
                'type' => ItemType::Mcq,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'Complete: "___ harder, I would have passed the exam."',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'If I had studied'],
                        ['key' => 'b', 'text' => 'If I studied'],
                        ['key' => 'c', 'text' => 'If I would study'],
                        ['key' => 'd', 'text' => 'If I have studied'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Tipul 3 se referă la un trecut imposibil de schimbat. Tipul 2 („If I studied") ar vorbi despre prezent, iar „would" după „if" e greșit în ambele cazuri.',
            ],
            [
                'ref' => 'seed:en:conditionale:unless',
                'hints' => [
                    '„Unless" înseamnă „if not" — negația e deja conținută în el.',
                    'Nu se adaugă încă o negație după el.',
                    '„Unless you hurry" = „If you do not hurry". Ambele forme sunt corecte; combinarea lor nu.',
                ],
                'skill' => 'conditionale-pasiv',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Which sentence means the same as "If you do not hurry, you will miss the bus"?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Unless you hurry, you will miss the bus.'],
                        ['key' => 'b', 'text' => 'Unless you do not hurry, you will miss the bus.'],
                        ['key' => 'c', 'text' => 'If you hurry, you will miss the bus.'],
                        ['key' => 'd', 'text' => 'Unless you will hurry, you will miss the bus.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '„Unless" conține deja negația. Dublarea ei inversează sensul, iar „will" după „unless" încalcă aceeași regulă ca după „if".',
            ],
            [
                'ref' => 'seed:en:pasiv:present-perfect',
                'hints' => [
                    'Pasivul se formează cu forma potrivită a lui „be" plus participiul trecut.',
                    'La Present Perfect, „be" devine „been": have/has + been + participiu.',
                    'Corect: „The contract has been signed."',
                ],
                'skill' => 'conditionale-pasiv',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Put into the passive: "They have signed the contract."',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'The contract has been signed.'],
                        ['key' => 'b', 'text' => 'The contract has signed.'],
                        ['key' => 'c', 'text' => 'The contract was been signed.'],
                        ['key' => 'd', 'text' => 'The contract is signed by they.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Timpul îl poartă verbul „be", iar participiul rămâne neschimbat. Varianta „by they" e dublu greșită: după prepoziție se folosește forma „them".',
            ],
            [
                'ref' => 'seed:en:pasiv:cand-se-foloseste',
                'hints' => [
                    'Pasivul nu e „mai elegant" în sine — are situații în care chiar e alegerea corectă.',
                    'Se folosește când autorul e necunoscut, neimportant, sau când accentul cade pe rezultat.',
                    'Într-un eseu, pasivul folosit peste tot devine greoi și se penalizează la coerență.',
                ],
                'skill' => 'conditionale-pasiv',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'In which situation is the passive voice the better choice?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'When the person who did the action is unknown or unimportant.'],
                        ['key' => 'b', 'text' => 'Whenever you want the text to sound more formal.'],
                        ['key' => 'c', 'text' => 'In every sentence of an academic essay.'],
                        ['key' => 'd', 'text' => 'When the sentence has no object.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '„My bike was stolen" — nu știm cine. „The road is being repaired" — nu contează cine. Folosit mecanic, pasivul face textul impersonal și greu de urmărit.',
            ],
            [
                'ref' => 'seed:en:pasiv:ordonare-transformare',
                'hints' => [
                    'Începe prin a identifica obiectul propoziției active: el devine subiect la pasiv.',
                    'Abia apoi alegi timpul verbului „be", care trebuie să fie același cu al verbului activ.',
                    'Autorul, dacă merită menționat, se adaugă la final cu „by".',
                ],
                'skill' => 'conditionale-pasiv',
                'type' => ItemType::OrderSteps,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Order the steps for turning an active sentence into the passive.',
                    'problem' => 'Shakespeare wrote Hamlet in 1601.',
                    'steps' => [
                        ['key' => 's1', 'text' => 'Identific obiectul: „Hamlet"'],
                        ['key' => 's2', 'text' => 'Îl mut pe poziția de subiect: „Hamlet ..."'],
                        ['key' => 's3', 'text' => 'Păstrez timpul: Past Simple → „was"'],
                        ['key' => 's4', 'text' => 'Adaug participiul trecut: „was written"'],
                        ['key' => 's5', 'text' => 'Adaug autorul cu „by": „by Shakespeare in 1601"'],
                    ],
                ],
                'answer' => ['order' => ['s1', 's2', 's3', 's4', 's5']],
                'explanation' => 'Păstrarea timpului e pasul care se sare cel mai des: „Hamlet is written by Shakespeare" schimbă sensul din trecut în prezent.',
            ],
            // ---------- Vorbire indirectă și modale ----------
            [
                'ref' => 'seed:en:indirecta:decalaj-prezent',
                'hints' => [
                    'În vorbirea indirectă verbul face un pas înapoi în timp.',
                    'Present Simple („I am tired") coboară la Past Simple.',
                    'Se schimbă și pronumele: „I" devine „she". Rezultatul: „she was tired".',
                ],
                'skill' => 'vorbire-indirecta',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Report the sentence: Maria said, "I am tired."',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Maria said that she was tired.'],
                        ['key' => 'b', 'text' => 'Maria said that she is tired.'],
                        ['key' => 'c', 'text' => 'Maria said that I was tired.'],
                        ['key' => 'd', 'text' => 'Maria told that she was tired.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Se schimbă două lucruri odată: timpul („am" → „was") și pronumele („I" → „she"). Varianta „told that" e greșită: „tell" cere obiect — „told me that".',
            ],
            [
                'ref' => 'seed:en:indirecta:say-tell',
                'hints' => [
                    '„Say" și „tell" nu sunt interschimbabile, deși se traduc la fel.',
                    '„Tell" cere obligatoriu un obiect: cui i s-a spus.',
                    '„He told me that..." e corect; „He said me that..." nu.',
                ],
                'skill' => 'vorbire-indirecta',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Choose the correct sentence.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'He told me that he would come later.'],
                        ['key' => 'b', 'text' => 'He said me that he would come later.'],
                        ['key' => 'c', 'text' => 'He told that he would come later.'],
                        ['key' => 'd', 'text' => 'He said me he will come later.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '„Tell" cere obiect direct („tell me"), „say" nu îl acceptă direct („say to me"). Regula practică: „tell somebody", „say something".',
            ],
            [
                'ref' => 'seed:en:indirecta:repere-timp',
                'hints' => [
                    'Nu doar verbul se schimbă, ci și reperele de timp și de loc.',
                    'Relatarea se face în alt moment, deci „tomorrow" nu mai înseamnă același lucru.',
                    '„Tomorrow" devine „the next day" sau „the following day".',
                ],
                'skill' => 'vorbire-indirecta',
                'type' => ItemType::FillBlanks,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Report the sentence: He said, "I will call you tomorrow."',
                    'stem_template' => 'He said he ___ call me ___ .',
                    'blanks' => [
                        ['key' => 'verb', 'options' => ['would', 'will', 'would have', 'had']],
                        ['key' => 'timp', 'options' => ['the next day', 'tomorrow', 'yesterday', 'that day']],
                    ],
                ],
                'answer' => ['values' => ['verb' => ['would'], 'timp' => ['the next day']]],
                'explanation' => '„Will" devine „would", iar „tomorrow" devine „the next day". Păstrarea lui „tomorrow" e a doua cea mai frecventă greșeală, după cea de timp verbal.',
            ],
            [
                'ref' => 'seed:en:modale:mustnt-dont-have-to',
                'hints' => [
                    'Cele două forme par apropiate, dar exprimă lucruri opuse.',
                    '„Mustn\'t" e interdicție; „don\'t have to" înseamnă că nu ești obligat.',
                    'Într-o bibliotecă nu ai voie să vorbești tare: „You mustn\'t talk loudly."',
                ],
                'skill' => 'vorbire-indirecta',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Which sentence expresses a prohibition?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'You mustn\'t use your phone during the exam.'],
                        ['key' => 'b', 'text' => 'You don\'t have to use your phone during the exam.'],
                        ['key' => 'c', 'text' => 'You shouldn\'t use your phone during the exam.'],
                        ['key' => 'd', 'text' => 'You needn\'t use your phone during the exam.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '„Mustn\'t" = interzis. „Don\'t have to" și „needn\'t" = nu e obligatoriu. „Shouldn\'t" e doar un sfat. Confuzia schimbă complet sensul.',
            ],
            [
                'ref' => 'seed:en:modale:deductie',
                'hints' => [
                    'Modalele exprimă și grade de CERTITUDINE, nu doar obligație.',
                    'Pentru o deducție aproape sigură se folosește „must".',
                    'Lumina e aprinsă, deci „She must be at home" — deducție logică, nu obligație.',
                ],
                'skill' => 'vorbire-indirecta',
                'type' => ItemType::Mcq,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'The lights are on. Choose the sentence expressing a confident deduction.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'She must be at home.'],
                        ['key' => 'b', 'text' => 'She might be at home.'],
                        ['key' => 'c', 'text' => 'She can be at home.'],
                        ['key' => 'd', 'text' => 'She should be at home.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '„Must" exprimă o deducție aproape certă; „might/may" o posibilitate incertă. Negativul deducției nu e „mustn\'t", ci „can\'t be": „She can\'t be at home."',
            ],
            [
                'ref' => 'seed:en:modale:potrivire-sens',
                'hints' => [
                    'Fiecare modal acoperă o zonă de sens: obligație, sfat, posibilitate, abilitate.',
                    '„Should" e recomandare, nu obligație.',
                    '„Have to" indică o obligație venită din exterior, nu o decizie proprie.',
                ],
                'skill' => 'vorbire-indirecta',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Match each modal verb with the meaning it expresses.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'should'],
                        ['key' => 'l2', 'text' => 'have to'],
                        ['key' => 'l3', 'text' => 'might'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'Possibility that is uncertain'],
                        ['key' => 'r2', 'text' => 'Advice or recommendation'],
                        ['key' => 'r3', 'text' => 'Obligation imposed from outside'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r2', 'l2' => 'r3', 'l3' => 'r1']],
                'explanation' => 'Alegerea modalului schimbă tonul unui text: „You should apply" e o sugestie, „You have to apply" e o constrângere.',
            ],

            // ---------- Articole, prepoziții, acord ----------
            [
                'ref' => 'seed:en:articole:generalizare',
                'hints' => [
                    'În română articolul se lipește de cuvânt, deci pare mereu prezent. În engleză, nu.',
                    'La generalizări cu substantive necontabile sau la plural nu se pune articol.',
                    'Corect: „Life is beautiful", nu „The life is beautiful".',
                ],
                'skill' => 'articole-prepozitii',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Choose the correct sentence.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Music helps me concentrate.'],
                        ['key' => 'b', 'text' => 'The music helps me concentrate.'],
                        ['key' => 'c', 'text' => 'A music helps me concentrate.'],
                        ['key' => 'd', 'text' => 'Musics help me concentrate.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'La generalizare, substantivele necontabile merg fără articol. „The music" ar trimite la o piesă anume, despre care s-a vorbit deja.',
            ],
            [
                'ref' => 'seed:en:articole:a-an-sunet',
                'hints' => [
                    'Alegerea între „a" și „an" se face după SUNET, nu după literă.',
                    '„Hour" începe cu literă consoană, dar se citește cu vocală: „aur".',
                    'Deci „an hour", dar „a university", pentru că „u" se citește aici „iu".',
                ],
                'skill' => 'articole-prepozitii',
                'type' => ItemType::FillBlanks,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Complete with the correct articles.',
                    'stem_template' => 'I waited for ___ hour before ___ university opened.',
                    'blanks' => [
                        ['key' => 'ora', 'options' => ['an', 'a', 'the', '—']],
                        ['key' => 'univ', 'options' => ['the', 'a', 'an', '—']],
                    ],
                ],
                'answer' => ['values' => ['ora' => ['an'], 'univ' => ['the']]],
                'explanation' => '„An hour" pentru că „h" e mut. Al doilea spațiu cere „the": e vorba de o universitate anume, identificabilă din context.',
            ],
            [
                'ref' => 'seed:en:prepozitii:timp',
                'hints' => [
                    'Prepozițiile de timp merg ca o pâlnie, de la perioade mari la momente precise.',
                    '„In" pentru luni și ani, „on" pentru zile și date, „at" pentru ore.',
                    'Deci: „in July", „on Monday", „at 7 o\'clock".',
                ],
                'skill' => 'articole-prepozitii',
                'type' => ItemType::FillBlanks,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Complete with the correct prepositions of time.',
                    'stem_template' => 'The exam takes place ___ June, ___ Monday, ___ 9 a.m.',
                    'blanks' => [
                        ['key' => 'luna', 'options' => ['in', 'on', 'at', 'to']],
                        ['key' => 'zi', 'options' => ['on', 'in', 'at', 'by']],
                        ['key' => 'ora', 'options' => ['at', 'in', 'on', 'from']],
                    ],
                ],
                'answer' => ['values' => ['luna' => ['in'], 'zi' => ['on'], 'ora' => ['at']]],
                'explanation' => 'Regula pâlniei: in (perioade lungi) → on (zile) → at (momente). Excepții de reținut: „at night", „at the weekend".',
            ],
            [
                'ref' => 'seed:en:prepozitii:verb-fix',
                'hints' => [
                    'Prepoziția face parte din verb și se învață împreună cu el.',
                    'În română spunem „depinde DE", dar engleza cere altă prepoziție.',
                    'Corect e „depend on". La fel: „listen to", „wait for", „arrive at".',
                ],
                'skill' => 'articole-prepozitii',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Complete: "The result depends ___ how much you practise."',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'on'],
                        ['key' => 'b', 'text' => 'of'],
                        ['key' => 'c', 'text' => 'from'],
                        ['key' => 'd', 'text' => 'by'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '„Depend on" e fix. „Depend of" e un calc direct din română și e una dintre cele mai frecvente greșeli la vorbitorii de limbi romanice.',
            ],
            [
                'ref' => 'seed:en:acord:news-singular',
                'hints' => [
                    'Unele substantive se termină în „-s", dar nu sunt plural.',
                    '„News", „information" și „advice" cer verb la singular.',
                    'Corect: „The news is good", nu „The news are good".',
                ],
                'skill' => 'articole-prepozitii',
                'type' => ItemType::FindError,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'A student wrote the sentence below. Where is the mistake?',
                    'problem' => 'The news about the exam results are very good.',
                    'choices' => [
                        ['key' => 'a', 'text' => '„News" cere verb la singular: „The news ... is very good"'],
                        ['key' => 'b', 'text' => 'Trebuia „The news were"'],
                        ['key' => 'c', 'text' => 'Trebuia „A news is"'],
                        ['key' => 'd', 'text' => 'Propoziția e corectă'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '„News" e necontabil și singular, în ciuda terminației. Nu are nici formă de plural, nici articol nehotărât: se spune „a piece of news".',
            ],
            [
                'ref' => 'seed:en:acord:varsta',
                'hints' => [
                    'Vârsta se exprimă altfel decât în română.',
                    'În engleză se folosește verbul „be", nu „have".',
                    'Corect: „I am eighteen years old", nu „I have eighteen years".',
                ],
                'skill' => 'articole-prepozitii',
                'type' => ItemType::Mcq,
                'difficulty' => 1,
                'payload' => [
                    'stem' => 'Choose the correct sentence.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'My sister is eighteen years old.'],
                        ['key' => 'b', 'text' => 'My sister has eighteen years.'],
                        ['key' => 'c', 'text' => 'My sister has eighteen years old.'],
                        ['key' => 'd', 'text' => 'My sister is eighteen years.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Engleza folosește „be" pentru vârstă, foame, sete și frică: „I am hungry", „I am cold" — acolo unde româna folosește „a avea" sau „a-i fi".',
            ],
        ];
    }

    /**
     * Lexic și registru: vocabular tematic, colocații, formarea cuvintelor,
     * prieteni falși și alegerea tonului.
     *
     * @return list<array<string, mixed>>
     */
    private function itemsLexicSiRegistru(): array
    {
        return [
            // ---------- Vocabular tematic ----------
            [
                'ref' => 'seed:en:vocabular:colocatie-take',
                'hints' => [
                    'E o colocație fixă: combinația se memorează, nu se deduce.',
                    'Engleza spune „a lua parte" cu verbul „take".',
                    'Corect: „take part in". „Make part" sau „do part" nu există.',
                ],
                'skill' => 'vocabular-tematic',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Complete: "Over fifty students will ___ part in the competition."',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'take'],
                        ['key' => 'b', 'text' => 'make'],
                        ['key' => 'c', 'text' => 'do'],
                        ['key' => 'd', 'text' => 'have'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '„Take part in" e colocație fixă. Din aceeași familie: „take place", „take responsibility", „take a break".',
            ],
            [
                'ref' => 'seed:en:vocabular:formare-cuvinte',
                'hints' => [
                    'Spațiul cere un SUBSTANTIV, nu verbul din care provine.',
                    'De la „decide" se formează substantivul cu sufixul „-sion".',
                    'Corect: „decision". „Decidement" nu există în engleză.',
                ],
                'skill' => 'vocabular-tematic',
                'type' => ItemType::FillBlanks,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Complete with the correct word formed from DECIDE and SUCCEED.',
                    'stem_template' => 'It was a difficult ___ , but the project was a great ___ .',
                    'blanks' => [
                        ['key' => 'subst1', 'options' => ['decision', 'decide', 'deciding', 'decisive']],
                        ['key' => 'subst2', 'options' => ['success', 'succeed', 'successful', 'successfully']],
                    ],
                ],
                'answer' => ['values' => ['subst1' => ['decision'], 'subst2' => ['success']]],
                'explanation' => 'Ambele spații cer substantive, cerute de articolele „a" dinaintea lor. „Successful" e adjectiv, iar „successfully" adverb — niciunul nu poate urma după „a great".',
            ],
            [
                'ref' => 'seed:en:vocabular:prieten-fals-actually',
                'hints' => [
                    'Cuvântul seamănă cu unul din română, dar nu înseamnă același lucru.',
                    '„Actually" nu înseamnă „actualmente" / „în prezent".',
                    'Înseamnă „de fapt", „în realitate". Pentru „în prezent" se folosește „currently" sau „at the moment".',
                ],
                'skill' => 'vocabular-tematic',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'What does "actually" mean in "I thought he was Italian, but actually he is Spanish"?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'in fact, in reality'],
                        ['key' => 'b', 'text' => 'at the present time'],
                        ['key' => 'c', 'text' => 'finally'],
                        ['key' => 'd', 'text' => 'almost'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '„Actually" = de fapt. Alt prieten fals din aceeași familie: „eventually" nu înseamnă „eventual", ci „în cele din urmă".',
            ],
            [
                'ref' => 'seed:en:vocabular:prefix-negativ',
                'hints' => [
                    'Prefixul negativ se alege după sunetul cu care începe cuvântul.',
                    'Înainte de „p" și „m" se folosește „im-", nu „in-".',
                    'Corect: „impossible", „impatient". Dar „incorrect", „irregular", „illegal".',
                ],
                'skill' => 'vocabular-tematic',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Match each adjective with its negative prefix.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'possible'],
                        ['key' => 'l2', 'text' => 'regular'],
                        ['key' => 'l3', 'text' => 'legal'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'ir-'],
                        ['key' => 'r2', 'text' => 'il-'],
                        ['key' => 'r3', 'text' => 'im-'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r3', 'l2' => 'r1', 'l3' => 'r2']],
                'explanation' => 'Prefixul se asimilează la sunetul următor: im- înainte de m/p, ir- înainte de r, il- înainte de l, in- în rest.',
            ],
            [
                'ref' => 'seed:en:vocabular:mediu-tematic',
                'hints' => [
                    'Tema „mediu" are un vocabular propriu, care apare des la Subiectul I și II.',
                    'Caută cuvântul care înseamnă „care se poate reînnoi", despre surse de energie.',
                    '„Renewable energy" — energie regenerabilă. „Renewed" ar însemna „reînnoit", altceva.',
                ],
                'skill' => 'vocabular-tematic',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Complete: "Solar and wind power are examples of ___ energy."',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'renewable'],
                        ['key' => 'b', 'text' => 'renewed'],
                        ['key' => 'c', 'text' => 'renewing'],
                        ['key' => 'd', 'text' => 'renovated'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Sufixul „-able" formează adjective cu sensul „care poate fi". „Renovated" înseamnă „renovat" și se referă la clădiri.',
            ],

            // ---------- Registru și ton ----------
            [
                'ref' => 'seed:en:registru:contrase',
                'hints' => [
                    'Textul formal evită formele contrase.',
                    '„Don\'t", „can\'t", „it\'s" aparțin registrului informal.',
                    'Într-un eseu se scrie „do not", „cannot", „it is".',
                ],
                'skill' => 'registru',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Which sentence is appropriate for a formal essay?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'It is often argued that technology does not improve communication.'],
                        ['key' => 'b', 'text' => 'It\'s often said that tech doesn\'t really help us talk.'],
                        ['key' => 'c', 'text' => 'Loads of people think tech is rubbish for talking.'],
                        ['key' => 'd', 'text' => 'Tech? Doesn\'t help at all, believe me.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Registrul formal cere forme necontrase, construcții impersonale și vocabular neutru. „Loads of" și „rubbish" sunt colocviale.',
            ],
            [
                'ref' => 'seed:en:registru:incheiere-scrisoare',
                'hints' => [
                    'Formula de încheiere se alege în funcție de formula de deschidere.',
                    'Dacă nu știi numele destinatarului, ai deschis cu „Dear Sir or Madam".',
                    'Perechea ei e „Yours faithfully". „Yours sincerely" merge cu un nume cunoscut.',
                ],
                'skill' => 'registru',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'A formal letter begins with "Dear Sir or Madam". How should it end?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Yours faithfully,'],
                        ['key' => 'b', 'text' => 'Yours sincerely,'],
                        ['key' => 'c', 'text' => 'Best wishes,'],
                        ['key' => 'd', 'text' => 'See you soon,'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Perechile sunt fixe: „Dear Sir or Madam" → „Yours faithfully"; „Dear Mr Smith" → „Yours sincerely". Amestecul lor se penalizează la Subiectul III.',
            ],
            [
                'ref' => 'seed:en:registru:verbe-frazale',
                'hints' => [
                    'Verbele frazale sunt informale; echivalentele lor de origine latină sunt formale.',
                    '„Put off" e informal. Care e varianta neutră, potrivită într-un e-mail oficial?',
                    '„Postpone" e forma formală pentru „a amâna".',
                ],
                'skill' => 'registru',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Match each informal phrasal verb with its formal equivalent.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'put off'],
                        ['key' => 'l2', 'text' => 'find out'],
                        ['key' => 'l3', 'text' => 'get in touch with'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'contact'],
                        ['key' => 'r2', 'text' => 'postpone'],
                        ['key' => 'r3', 'text' => 'discover'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r2', 'l2' => 'r3', 'l3' => 'r1']],
                'explanation' => 'Verbele frazale fac textul natural, dar informal. Într-un eseu sau într-o scrisoare oficială se preferă echivalentele de origine latină.',
            ],
            [
                'ref' => 'seed:en:registru:amestec-eroare',
                'hints' => [
                    'Problema nu e gramaticală, ci de consecvență.',
                    'Textul începe formal și se încheie colocvial.',
                    'Registrul se alege din prima frază și se păstrează până la ultima.',
                ],
                'skill' => 'registru',
                'type' => ItemType::FindError,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'An essay ends as shown below. What is wrong with it?',
                    'problem' => 'It is widely believed that social media affects young people. Anyway, that\'s just my opinion, thanks for reading!',
                    'choices' => [
                        ['key' => 'a', 'text' => 'Registrul se schimbă brusc din formal în colocvial'],
                        ['key' => 'b', 'text' => 'Prima propoziție e greșită gramatical'],
                        ['key' => 'c', 'text' => 'Lipsește un conector între propoziții'],
                        ['key' => 'd', 'text' => 'Textul e corect'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Gramatical nu e nimic greșit, dar baremul punctează coerența registrului. Încheierea potrivită ar fi: „In conclusion, social media has a significant impact on young people."',
            ],
        ];
    }

    /**
     * Comprehensiune scrisă, coeziune și eseu.
     *
     * Eseul propriu-zis NU e auto-corectabil (§13): itemii de aici testează ce
     * SE POATE testa — structura, planificarea, conectorii, teza — nu producerea
     * de text. Blueprintul marchează onest Subiectele II și III drept
     * `auto_gradable: false`.
     *
     * @return list<array<string, mixed>>
     */
    private function itemsComprehensiuneSiEseu(): array
    {
        return [
            // ---------- Comprehensiunea scrisă ----------
            [
                'ref' => 'seed:en:comprehensiune:idee-principala',
                'hints' => [
                    'Ideea principală a unui paragraf stă de obicei în prima sau în ultima frază.',
                    'Detaliile și exemplele susțin ideea, dar nu SUNT ideea.',
                    'Întreabă-te: dacă aș păstra o singură frază, care le explică pe celelalte?',
                ],
                'skill' => 'comprehensiune-scrisa',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Read the paragraph: "Many cities are redesigning their centres. Cars are being replaced by bicycle lanes, pavements are widened, and new parks appear where parking lots used to be. The aim is to make urban life healthier." What is the main idea?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Cities are being redesigned to improve urban health.'],
                        ['key' => 'b', 'text' => 'Bicycle lanes are cheaper than roads.'],
                        ['key' => 'c', 'text' => 'Parking lots are disappearing everywhere.'],
                        ['key' => 'd', 'text' => 'People prefer parks to cars.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Variantele b, c și d sunt detalii sau deducții care depășesc textul. Ideea principală acoperă tot paragraful, nu o singură propoziție din el.',
            ],
            [
                'ref' => 'seed:en:comprehensiune:inferenta',
                'hints' => [
                    'Întrebarea cere ce se DEDUCE, nu ce scrie explicit.',
                    'Textul nu spune direct că e nemulțumit, dar sugerează asta.',
                    'Alege varianta susținută de text, fără să adaugi informații din afara lui.',
                ],
                'skill' => 'comprehensiune-scrisa',
                'type' => ItemType::Mcq,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'Read: "I had been waiting for the delivery for three weeks. When the box finally arrived, half the items were missing and no one answered the customer service line." What can be inferred about the writer?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'The writer is dissatisfied with the service.'],
                        ['key' => 'b', 'text' => 'The writer never received the box.'],
                        ['key' => 'c', 'text' => 'The writer works in customer service.'],
                        ['key' => 'd', 'text' => 'The writer ordered the wrong items.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Inferența se sprijină pe text, dar nu îl repetă. Varianta b contrazice textul („the box finally arrived"), iar c și d adaugă informații inexistente.',
            ],
            [
                'ref' => 'seed:en:comprehensiune:true-false-not-given',
                'hints' => [
                    'Diferența e între „textul spune contrariul" și „textul nu spune nimic".',
                    'Dacă informația nu apare deloc, răspunsul nu e „False", ci „Not given".',
                    '„False" înseamnă că textul afirmă explicit altceva.',
                ],
                'skill' => 'comprehensiune-scrisa',
                'type' => ItemType::Mcq,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'Text: "The museum opened in 1998 and welcomes about 200,000 visitors a year." Statement: "The museum is the most popular in the country." The correct answer is:',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Not given'],
                        ['key' => 'b', 'text' => 'True'],
                        ['key' => 'c', 'text' => 'False'],
                        ['key' => 'd', 'text' => 'Partly true'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Textul nu compară muzeul cu altele, deci nu putem ști. „False" ar cere ca textul să afirme explicit că nu e cel mai popular. Confuzia False / Not given e cea mai costisitoare la Subiectul I.',
            ],
            [
                'ref' => 'seed:en:comprehensiune:cuvant-din-context',
                'hints' => [
                    'Nu ai nevoie de dicționar: sensul se deduce din restul frazei.',
                    'Ce urmează după virgulă explică termenul necunoscut.',
                    '„Reluctant" înseamnă „ezitant", „fără tragere de inimă".',
                ],
                'skill' => 'comprehensiune-scrisa',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Guess the meaning from context: "She was reluctant to speak in public; she always found excuses to avoid presentations."',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'unwilling, hesitant'],
                        ['key' => 'b', 'text' => 'eager, enthusiastic'],
                        ['key' => 'c', 'text' => 'skilled, experienced'],
                        ['key' => 'd', 'text' => 'nervous but prepared'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'A doua parte a frazei („found excuses to avoid") arată direcția sensului. La Subiectul I nu ai dicționar — deducerea din context e competența testată.',
            ],
            [
                'ref' => 'seed:en:comprehensiune:strategie-ordonare',
                'hints' => [
                    'Nu începe prin a citi textul cuvânt cu cuvânt: pierzi timp.',
                    'Citește întâi întrebările, ca să știi ce cauți.',
                    'Ordinea eficientă: întrebări → citire rapidă → citire țintită → verificare.',
                ],
                'skill' => 'comprehensiune-scrisa',
                'type' => ItemType::OrderSteps,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Order the steps of an efficient reading strategy for Subiectul I.',
                    'problem' => 'A 400-450 word text with comprehension questions',
                    'steps' => [
                        ['key' => 's1', 'text' => 'Citesc întrebările, ca să știu ce caut'],
                        ['key' => 's2', 'text' => 'Parcurg rapid textul, pentru ideea generală'],
                        ['key' => 's3', 'text' => 'Recitesc doar paragrafele care conțin răspunsurile'],
                        ['key' => 's4', 'text' => 'Notez răspunsurile cu cuvintele mele'],
                        ['key' => 's5', 'text' => 'Verific fiecare răspuns în text, nu din memorie'],
                    ],
                ],
                'answer' => ['order' => ['s1', 's2', 's3', 's4', 's5']],
                'explanation' => 'Citirea întrebărilor înainte de text schimbă complet eficiența: citești căutând, nu memorând. Ultimul pas prinde răspunsurile „din amintire", care sună plauzibil dar nu sunt în text.',
            ],
            [
                'ref' => 'seed:en:comprehensiune:copiere-eroare',
                'hints' => [
                    'La întrebările cu răspuns deschis nu se copiază fraze întregi din text.',
                    'Baremul cere reformulare, ca dovadă că ai înțeles, nu că ai găsit.',
                    'Răspunsul se scrie cu propriile cuvinte, scurt și la obiect.',
                ],
                'skill' => 'comprehensiune-scrisa',
                'type' => ItemType::FindError,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'A student answers an open question by copying three full sentences from the text. Why does this lose points?',
                    'problem' => 'Question: Why did the author change his mind?',
                    'choices' => [
                        ['key' => 'a', 'text' => 'Baremul cere reformulare: copierea nu dovedește înțelegerea'],
                        ['key' => 'b', 'text' => 'Răspunsul e prea scurt'],
                        ['key' => 'c', 'text' => 'Nu are voie să răspundă la întrebările deschise'],
                        ['key' => 'd', 'text' => 'Nu e nicio problemă'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Copierea arată că ai localizat informația, nu că ai înțeles-o. Un răspuns reformulat, de una-două propoziții, ia punctajul întreg.',
            ],

            // ---------- Coeziune și conectori ----------
            [
                'ref' => 'seed:en:conectori:contrast',
                'hints' => [
                    'Cele două părți ale frazei spun lucruri opuse. Ai nevoie de un conector de contrast.',
                    '„However" introduce o idee opusă și se desparte prin virgulă.',
                    '„Therefore" ar arăta consecință, „moreover" ar adăuga — ambele contrazic sensul.',
                ],
                'skill' => 'coeziune-conectori',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Complete: "The project was expensive. ___, the results were worth every penny."',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'However'],
                        ['key' => 'b', 'text' => 'Therefore'],
                        ['key' => 'c', 'text' => 'Moreover'],
                        ['key' => 'd', 'text' => 'For example'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Conectorul trebuie să reflecte relația dintre idei. Un conector greșit nu e doar o scăpare de stil: face textul incoerent, iar coerența e criteriu separat în barem.',
            ],
            [
                'ref' => 'seed:en:conectori:although-despite',
                'hints' => [
                    '„Although" și „despite" exprimă același contrast, dar cer construcții diferite.',
                    'După „although" urmează o propoziție cu subiect și predicat.',
                    'După „despite" urmează un substantiv sau un verb la „-ing", nu o propoziție.',
                ],
                'skill' => 'coeziune-conectori',
                'type' => ItemType::Mcq,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'Choose the correct sentence.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Despite the rain, we went for a walk.'],
                        ['key' => 'b', 'text' => 'Despite it was raining, we went for a walk.'],
                        ['key' => 'c', 'text' => 'Although the rain, we went for a walk.'],
                        ['key' => 'd', 'text' => 'Despite of the rain, we went for a walk.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '„Despite" + substantiv sau „-ing"; „although" + propoziție. Iar „despite of" nu există — „in spite of" da, dar cu „in".',
            ],
            [
                'ref' => 'seed:en:conectori:consecinta',
                'hints' => [
                    'A doua propoziție e rezultatul primei.',
                    'Ai nevoie de un conector de consecință.',
                    '„Therefore" sau „as a result" exprimă urmarea logică.',
                ],
                'skill' => 'coeziune-conectori',
                'type' => ItemType::FillBlanks,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Complete with the appropriate connector.',
                    'stem_template' => 'The train was cancelled. ___, we had to take a taxi.',
                    'blanks' => [['key' => 'conector', 'options' => ['Therefore', 'However', 'Nevertheless', 'In contrast']]],
                ],
                'answer' => ['values' => ['conector' => ['Therefore']]],
                'explanation' => 'Relația e de cauză-efect. „However" și „nevertheless" ar semnala contrast, ceea ce ar face fraza ilogică.',
            ],
            [
                'ref' => 'seed:en:conectori:potrivire-functii',
                'hints' => [
                    'Grupează conectorii după FUNCȚIE, nu după cât de sofisticat sună.',
                    'Adăugare, contrast, consecință, exemplificare — patru funcții de bază.',
                    '„Furthermore" adaugă, „nevertheless" contrastează, „for instance" exemplifică.',
                ],
                'skill' => 'coeziune-conectori',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Match each connector with its function.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'Furthermore'],
                        ['key' => 'l2', 'text' => 'Nevertheless'],
                        ['key' => 'l3', 'text' => 'For instance'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'Giving an example'],
                        ['key' => 'r2', 'text' => 'Adding information'],
                        ['key' => 'r3', 'text' => 'Expressing contrast'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r2', 'l2' => 'r3', 'l3' => 'r1']],
                'explanation' => 'Un conector folosit pe funcția greșită derutează cititorul mai tare decât lipsa lui. Învață-i pe grupe de funcție.',
            ],
            [
                'ref' => 'seed:en:conectori:supraincarcare',
                'hints' => [
                    'Problema nu e că textul n-are conectori, ci că are prea mulți.',
                    'Trei conectori în două fraze fac textul artificial și greu de urmărit.',
                    'Conectorii marchează relațiile importante, nu fiecare propoziție.',
                ],
                'skill' => 'coeziune-conectori',
                'type' => ItemType::FindError,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'What is the problem with this essay extract?',
                    'problem' => 'Moreover, technology is useful. Furthermore, it saves time. In addition, it helps us communicate. Additionally, it is fun.',
                    'choices' => [
                        ['key' => 'a', 'text' => 'Conectorii sunt supraîncărcați: toți adaugă, niciunul nu structurează'],
                        ['key' => 'b', 'text' => 'Conectorii sunt greșiți gramatical'],
                        ['key' => 'c', 'text' => 'Lipsește un conector de contrast la început'],
                        ['key' => 'd', 'text' => 'Textul e corect'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Patru conectori de adăugare la rând sună mecanic. Un paragraf bun are una-două legături marcate explicit; restul coerenței vine din ordinea ideilor.',
            ],

            // ---------- Eseul structurat ----------
            [
                'ref' => 'seed:en:eseu:teza',
                'hints' => [
                    'Teza nu e o observație generală, ci o poziție pe care o vei susține.',
                    'Ea trebuie să anunțe și direcția argumentării.',
                    'O teză bună se poate contrazice — dacă nimeni nu poate fi în dezacord, nu e teză.',
                ],
                'skill' => 'eseu-structurat',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Which sentence works best as a thesis statement for an essay about social media?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Although social media connects people, it harms the quality of real friendships.'],
                        ['key' => 'b', 'text' => 'Social media is very popular nowadays.'],
                        ['key' => 'c', 'text' => 'In this essay I will talk about social media.'],
                        ['key' => 'd', 'text' => 'Many people use social media every day.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Teza ia poziție și anunță structura. Variantele b și d sunt constatări cu care nimeni nu poate fi în dezacord, iar c anunță intenția fără să spună nimic.',
            ],
            [
                'ref' => 'seed:en:eseu:structura-paragraf',
                'hints' => [
                    'Un paragraf de eseu urmează o schemă fixă, nu se scrie liber.',
                    'Începe cu ideea, apoi o susține, apoi explică de ce contează.',
                    'Schema e: idee → exemplu → explicație.',
                ],
                'skill' => 'eseu-structurat',
                'type' => ItemType::OrderSteps,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Order the parts of a well-built body paragraph.',
                    'problem' => 'Topic: the benefits of volunteering',
                    'steps' => [
                        ['key' => 's1', 'text' => 'Topic sentence: anunț ideea paragrafului'],
                        ['key' => 's2', 'text' => 'Supporting detail: aduc un exemplu concret'],
                        ['key' => 's3', 'text' => 'Explanation: arăt de ce exemplul susține ideea'],
                        ['key' => 's4', 'text' => 'Link: fac trecerea spre paragraful următor'],
                    ],
                ],
                'answer' => ['order' => ['s1', 's2', 's3', 's4']],
                'explanation' => 'Exemplul fără explicație e cea mai frecventă lipsă: elevul dă un caz concret, dar nu spune ce demonstrează el. Baremul punctează tocmai legătura.',
            ],
            [
                'ref' => 'seed:en:eseu:numar-cuvinte',
                'hints' => [
                    'Enunțul Subiectului II cere un număr precis de cuvinte.',
                    'Programa ANCE prevede 180–200 de cuvinte.',
                    'Abaterea semnificativă de la limită se penalizează, în ambele sensuri.',
                ],
                'skill' => 'eseu-structurat',
                'type' => ItemType::FillBlanks,
                'difficulty' => 1,
                'payload' => [
                    'stem' => 'Complete the required length for Subiectul II (structured essay).',
                    'stem_template' => 'The essay must have between 180 and ___ words.',
                    'blanks' => [['key' => 'limita', 'options' => ['200', '250', '150', '300']]],
                ],
                'answer' => ['values' => ['limita' => ['200']]],
                'explanation' => 'Intervalul 180–200 de cuvinte e o cerință a baremului, nu o sugestie. Un eseu de 300 de cuvinte pierde puncte chiar dacă e bine scris — și îți consumă timpul de la Subiectul III.',
            ],
            [
                'ref' => 'seed:en:eseu:introducere',
                'hints' => [
                    'Introducerea are două sarcini: să prezinte tema și să anunțe poziția.',
                    'Nu e locul pentru argumente detaliate — acelea vin în cuprins.',
                    'Nici pentru concluzie: dacă începi cu ea, cuprinsul rămâne fără rost.',
                ],
                'skill' => 'eseu-structurat',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'What should the introduction of a structured essay contain?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'A presentation of the topic and a clear thesis statement.'],
                        ['key' => 'b', 'text' => 'All the arguments, in detail.'],
                        ['key' => 'c', 'text' => 'The conclusion, so the reader knows the result.'],
                        ['key' => 'd', 'text' => 'A long personal story.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Introducerea ocupă 25–30 de cuvinte din cele 200: context scurt plus teză. Argumentele detaliate ar lăsa cuprinsul fără conținut.',
            ],
            [
                'ref' => 'seed:en:eseu:planificare',
                'hints' => [
                    'Cu 180–200 de cuvinte nu ai loc de improvizație.',
                    'Planul de trei minute economisește timp, nu îl consumă.',
                    'Se scrie întâi planul, apoi textul — nu invers.',
                ],
                'skill' => 'eseu-structurat',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'What is the most effective first step when writing the essay?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Spend two or three minutes planning the structure.'],
                        ['key' => 'b', 'text' => 'Start writing immediately to save time.'],
                        ['key' => 'c', 'text' => 'Write the conclusion first.'],
                        ['key' => 'd', 'text' => 'Count the words after every sentence.'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Planificarea previne exact ce se penalizează cel mai des: paragrafe dezechilibrate, idei repetate și lipsa concluziei pentru că s-a terminat spațiul.',
            ],
            [
                'ref' => 'seed:en:eseu:opinie-personala-eroare',
                'hints' => [
                    'Problema nu e conținutul opiniei, ci forma în care e exprimată.',
                    'Registrul formal evită adresarea directă și exprimarea prea personală.',
                    'În loc de „I think that guys should...", se scrie „It could be argued that young people should...".',
                ],
                'skill' => 'eseu-structurat',
                'type' => ItemType::FindError,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'What is wrong with this sentence in a formal essay?',
                    'problem' => 'I think that guys should stop wasting time online, you know what I mean?',
                    'choices' => [
                        ['key' => 'a', 'text' => 'Registrul e prea colocvial pentru un eseu formal'],
                        ['key' => 'b', 'text' => 'Propoziția are o greșeală de timp verbal'],
                        ['key' => 'c', 'text' => 'Lipsește un conector la început'],
                        ['key' => 'd', 'text' => 'Propoziția e potrivită'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '„Guys" și „you know what I mean" aparțin vorbirii colocviale. O variantă formală: „It could be argued that young people spend too much time online."',
            ],
        ];
    }

    /**
     * Domeniul Cultură: spațiul anglofon și patrimoniul.
     *
     * Materia Subiectului III, unde se cere un articol sau o scrisoare pe o temă
     * de cultură a țării alofone. Textul nu se poate corecta automat, dar
     * CUNOAȘTEREA faptelor din el, da — iar o eroare factuală se penalizează
     * chiar dacă engleza e impecabilă.
     *
     * @return list<array<string, mixed>>
     */
    private function itemsCultura(): array
    {
        return [
            // ---------- Țările și instituțiile ----------
            [
                'ref' => 'seed:en:anglofon:uk-componenta',
                'hints' => [
                    'Regatul Unit nu e același lucru cu Marea Britanie.',
                    'Marea Britanie e insula: England, Scotland, Wales.',
                    'Regatul Unit adaugă și Irlanda de Nord, care nu se află pe acea insulă.',
                ],
                'skill' => 'spatiul-anglofon',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Which nations form the United Kingdom?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'England, Scotland, Wales and Northern Ireland'],
                        ['key' => 'b', 'text' => 'England, Scotland and Wales'],
                        ['key' => 'c', 'text' => 'England, Scotland, Wales and the Republic of Ireland'],
                        ['key' => 'd', 'text' => 'England and Scotland only'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Varianta b descrie Marea Britanie (insula), nu Regatul Unit. Republica Irlanda e stat independent și nu face parte din UK.',
            ],
            [
                'ref' => 'seed:en:anglofon:capitala-australia',
                'hints' => [
                    'Capitala Australiei nu e orașul cel mai mare și nici cel mai cunoscut.',
                    'A fost construită special ca oraș-capitală, ca un compromis între Sydney și Melbourne.',
                    'Capitala este Canberra.',
                ],
                'skill' => 'spatiul-anglofon',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'What is the capital of Australia?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Canberra'],
                        ['key' => 'b', 'text' => 'Sydney'],
                        ['key' => 'c', 'text' => 'Melbourne'],
                        ['key' => 'd', 'text' => 'Brisbane'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Sydney e cel mai mare oraș, dar capitala e Canberra. Aceeași capcană la Canada (Ottawa, nu Toronto) și la Noua Zeelandă (Wellington, nu Auckland).',
            ],
            [
                'ref' => 'seed:en:anglofon:parlament-britanic',
                'hints' => [
                    'Parlamentul britanic are două camere, cu roluri diferite.',
                    'Una e aleasă prin vot, cealaltă nu.',
                    'Camera aleasă se numește House of Commons; membrii ei sunt MPs.',
                ],
                'skill' => 'spatiul-anglofon',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Which chamber of the UK Parliament is elected by the people?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'The House of Commons'],
                        ['key' => 'b', 'text' => 'The House of Lords'],
                        ['key' => 'c', 'text' => 'The Senate'],
                        ['key' => 'd', 'text' => 'The Cabinet'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'House of Commons e aleasă; House of Lords, nu — are rol de revizuire. „Senate" aparține sistemului american, nu celui britanic.',
            ],
            [
                'ref' => 'seed:en:anglofon:congres-american',
                'hints' => [
                    'Congresul american are două camere, cu principii de reprezentare diferite.',
                    'Într-una, fiecare stat are exact același număr de reprezentanți.',
                    'Senatul: doi senatori de stat, indiferent de populație — deci 100 în total.',
                ],
                'skill' => 'spatiul-anglofon',
                'type' => ItemType::FillBlanks,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Complete the composition of the US Senate.',
                    'stem_template' => 'The Senate has ___ senators, ___ from each state.',
                    'blanks' => [
                        ['key' => 'total', 'options' => ['100', '50', '435', '200']],
                        ['key' => 'per_stat', 'options' => ['two', 'one', 'three', 'five']],
                    ],
                ],
                'answer' => ['values' => ['total' => ['100'], 'per_stat' => ['two']]],
                'explanation' => 'Cele 50 de state trimit câte doi senatori, deci 100. Camera Reprezentanților are 435 de membri, repartizați proporțional cu populația.',
            ],
            [
                'ref' => 'seed:en:anglofon:forma-guvernare',
                'hints' => [
                    'Regatul Unit are monarh, dar puterea reală aparține altcuiva.',
                    'SUA n-are monarh și are o constituție scrisă, din 1787.',
                    'UK: monarhie constituțională. SUA: republică federală.',
                ],
                'skill' => 'spatiul-anglofon',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Match each country with its capital city.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'Canada'],
                        ['key' => 'l2', 'text' => 'New Zealand'],
                        ['key' => 'l3', 'text' => 'Ireland'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'Dublin'],
                        ['key' => 'r2', 'text' => 'Ottawa'],
                        ['key' => 'r3', 'text' => 'Wellington'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r2', 'l2' => 'r3', 'l3' => 'r1']],
                'explanation' => 'Toronto (Canada) și Auckland (Noua Zeelandă) sunt cele mai mari orașe, dar nu capitale. Dublinul e și capitală, și cel mai mare oraș al Irlandei.',
            ],
            [
                'ref' => 'seed:en:anglofon:commonwealth-eroare',
                'hints' => [
                    'Commonwealth nu e o extindere a Regatului Unit.',
                    'E o asociere VOLUNTARĂ de state, majoritatea foste colonii britanice.',
                    'Regatul Unit nu legiferează pentru membrii ei, care sunt state independente.',
                ],
                'skill' => 'spatiul-anglofon',
                'type' => ItemType::FindError,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'A student wrote the statement below in an article. What is wrong?',
                    'problem' => 'The Commonwealth is a group of countries governed by the United Kingdom.',
                    'choices' => [
                        ['key' => 'a', 'text' => 'E o asociere voluntară de state independente, nu guvernate de UK'],
                        ['key' => 'b', 'text' => 'Commonwealth cuprinde doar patru state'],
                        ['key' => 'c', 'text' => 'Commonwealth e numele vechi al Regatului Unit'],
                        ['key' => 'd', 'text' => 'Afirmația e corectă'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Commonwealth of Nations reunește peste 50 de state suverane, cooperând voluntar. Regatul Unit nu are autoritate legislativă asupra lor.',
            ],

            // ---------- Patrimoniu și personalități ----------
            [
                'ref' => 'seed:en:patrimoniu:shakespeare',
                'hints' => [
                    'Este cel mai cunoscut dramaturg englez, născut în secolul al XVI-lea.',
                    'Orașul lui natal poartă numele unui râu: Avon.',
                    'S-a născut la Stratford-upon-Avon, iar piesele se jucau la teatrul The Globe.',
                ],
                'skill' => 'patrimoniu-personalitati',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Where was William Shakespeare born?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Stratford-upon-Avon'],
                        ['key' => 'b', 'text' => 'London'],
                        ['key' => 'c', 'text' => 'Oxford'],
                        ['key' => 'd', 'text' => 'Canterbury'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'S-a născut la Stratford-upon-Avon în 1564 și a lucrat la Londra, unde piesele se jucau la teatrul The Globe.',
            ],
            [
                'ref' => 'seed:en:patrimoniu:big-ben',
                'hints' => [
                    'Numele „Big Ben" nu se referă la clădire.',
                    'E numele unui obiect din interiorul turnului.',
                    '„Big Ben" e clopotul cel mare; turnul se numește Elizabeth Tower.',
                ],
                'skill' => 'patrimoniu-personalitati',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'What does the name "Big Ben" actually refer to?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'The great bell inside the tower'],
                        ['key' => 'b', 'text' => 'The tower itself'],
                        ['key' => 'c', 'text' => 'The clock face'],
                        ['key' => 'd', 'text' => 'The Houses of Parliament'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '„Big Ben" e clopotul. Turnul a fost redenumit Elizabeth Tower în 2012. E genul de detaliu care arată la Subiectul III că știi despre ce scrii.',
            ],
            [
                'ref' => 'seed:en:patrimoniu:penicilina',
                'hints' => [
                    'Descoperirea a schimbat medicina secolului XX și a fost, în parte, accidentală.',
                    'Autorul e un bacteriolog scoțian.',
                    'Alexander Fleming a descoperit penicilina în 1928.',
                ],
                'skill' => 'patrimoniu-personalitati',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Who discovered penicillin?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Alexander Fleming'],
                        ['key' => 'b', 'text' => 'Charles Darwin'],
                        ['key' => 'c', 'text' => 'Isaac Newton'],
                        ['key' => 'd', 'text' => 'Alan Turing'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Fleming a descoperit penicilina în 1928, deschizând era antibioticelor. Darwin — evoluția, Newton — gravitația, Turing — bazele informaticii.',
            ],
            [
                'ref' => 'seed:en:patrimoniu:potrivire-domenii',
                'hints' => [
                    'Fiecare personalitate e legată de un domeniu precis.',
                    'Turing a lucrat la decriptare și la bazele calculatorului.',
                    'Darwin — biologie; Austen — literatură; Turing — informatică.',
                ],
                'skill' => 'patrimoniu-personalitati',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Match each person with their field.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'Charles Darwin'],
                        ['key' => 'l2', 'text' => 'Jane Austen'],
                        ['key' => 'l3', 'text' => 'Alan Turing'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'Computer science'],
                        ['key' => 'r2', 'text' => 'Biology'],
                        ['key' => 'r3', 'text' => 'Literature'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r2', 'l2' => 'r3', 'l3' => 'r1']],
                'explanation' => 'Darwin a formulat teoria evoluției prin selecție naturală, Austen a scris romane despre societatea engleză, iar Turing a pus bazele teoretice ale calculatorului.',
            ],
            [
                'ref' => 'seed:en:patrimoniu:statuia-libertatii',
                'hints' => [
                    'Statuia nu a fost construită de americani.',
                    'A fost un dar din partea unei țări europene, la o aniversare a independenței SUA.',
                    'A fost dăruită de Franța și inaugurată în 1886.',
                ],
                'skill' => 'patrimoniu-personalitati',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Which country gave the Statue of Liberty to the United States?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'France'],
                        ['key' => 'b', 'text' => 'The United Kingdom'],
                        ['key' => 'c', 'text' => 'Italy'],
                        ['key' => 'd', 'text' => 'Spain'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'A fost un dar al Franței, inaugurat în 1886, ca simbol al prieteniei dintre cele două republici.',
            ],
            [
                'ref' => 'seed:en:patrimoniu:mlk',
                'hints' => [
                    'Discursul a fost rostit în timpul unui marș pentru drepturi civile.',
                    'A avut loc la Washington, în 1963.',
                    'Discursul „I Have a Dream" îi aparține lui Martin Luther King Jr.',
                ],
                'skill' => 'patrimoniu-personalitati',
                'type' => ItemType::FillBlanks,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Complete the sentence about the American civil rights movement.',
                    'stem_template' => 'The "I Have a Dream" speech was delivered by ___ in ___ .',
                    'blanks' => [
                        ['key' => 'autor', 'options' => ['Martin Luther King Jr.', 'Rosa Parks', 'Abraham Lincoln', 'John F. Kennedy']],
                        ['key' => 'an', 'options' => ['1963', '1955', '1929', '1975']],
                    ],
                ],
                'answer' => ['values' => ['autor' => ['Martin Luther King Jr.'], 'an' => ['1963']]],
                'explanation' => 'Discursul a fost rostit la marșul de la Washington, în august 1963. Rosa Parks a declanșat în 1955 boicotul autobuzelor din Montgomery.',
            ],
            [
                'ref' => 'seed:en:patrimoniu:enumerare-eroare',
                'hints' => [
                    'Problema nu ține de gramatică, ci de ce punctează baremul Subiectului III.',
                    'O listă de nume fără nicio informație nu arată cunoaștere.',
                    'Un paragraf despre o singură personalitate, cu detalii, valorează mai mult.',
                ],
                'skill' => 'patrimoniu-personalitati',
                'type' => ItemType::FindError,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Why does this opening paragraph of an article score poorly?',
                    'problem' => 'Britain has many famous people: Shakespeare, Newton, Darwin, Austen, Dickens, Turing, Fleming and many others. They are all very important.',
                    'choices' => [
                        ['key' => 'a', 'text' => 'E o enumerare fără conținut: niciun detaliu, nicio idee dezvoltată'],
                        ['key' => 'b', 'text' => 'Numele sunt scrise greșit'],
                        ['key' => 'c', 'text' => 'Paragraful e prea lung'],
                        ['key' => 'd', 'text' => 'Paragraful e potrivit'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Baremul punctează relevanța și dezvoltarea conținutului. Șapte nume fără informație valorează mai puțin decât patru fraze despre unul singur, cu un detaliu verificabil.',
            ],
        ];
    }
}
