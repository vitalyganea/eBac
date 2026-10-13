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
 * DECIZIE DE INTEGRITATE: sursele de mai jos sunt texte NEATRIBUITE, scrise
 * pentru exersarea competenței de lucru cu sursa. Nu am inventat citate pe numele
 * unor istorici reali. Itemii oficiali ANCE folosesc surse reale, atribuite —
 * acelea trebuie adăugate de un editor care are documentele, nu generate.
 *
 * Itemii factuali se limitează la date, tratate, instituții și cronologie —
 * verificabile și necontroversate. Interpretările sunt lăsate Subiectului IV,
 * unde le evaluează un om.
 */
class IstorieContentSeeder extends Seeder
{
    /** Sursă pentru exersarea distincției fapt / opinie. Text neatribuit. */
    private const SURSA_FAPT_OPINIE = <<<'TXT'
        „Prin Tratatul de pace de la București, semnat în anul 1812 între Imperiul Rus și
        Imperiul Otoman, teritoriul dintre Prut și Nistru a intrat în componența Imperiului
        Rus. Noua provincie a primit denumirea de Basarabia. În primii ani, administrația
        rusă a menținut unele particularități locale, iar prin Regulamentul din 1818 a fost
        instituită o autonomie limitată. Considerăm că această autonomie a fost de la început
        una formală, fără intenția reală de a fi păstrată. Treptat, instituțiile locale au fost
        înlocuite cu cele imperiale, iar în anul 1828, prin noul regulament, autonomia a fost
        lichidată."
        TXT;

    public function run(): void
    {
        $subject = Subject::where('slug', 'istoria')->firstOrFail();

        $skills = $this->seedSkills($subject->id);
        $this->seedPrerequisites($skills);
        $this->seedItems($subject->id, $skills);
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
        return [
            // ==================== TERMINOLOGIE ====================
            [
                'ref' => 'seed:ist:term:medievali',
                'skill' => 'terminologie',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 4,
                'payload' => [
                    'stem' => 'Potrivește fiecare termen istoric cu definiția lui.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'Descălecat'],
                        ['key' => 'l2', 'text' => 'Capitulații'],
                        ['key' => 'l3', 'text' => 'Cruciadă'],
                        ['key' => 'l4', 'text' => 'Dinastie'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'Expediție militară cu motivație religioasă, organizată de puterile creștine'],
                        ['key' => 'r2', 'text' => 'Tradiția întemeierii statelor medievale românești prin așezarea unui grup condus de un voievod'],
                        ['key' => 'r3', 'text' => 'Succesiune de conducători proveniți din aceeași familie'],
                        ['key' => 'r4', 'text' => 'Acte care reglementau raporturile Țărilor Române cu Poarta Otomană'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r2', 'l2' => 'r4', 'l3' => 'r1', 'l4' => 'r3']],
                'hints' => [
                    'Doi termeni sunt universali (se folosesc pentru orice spațiu istoric), doi sunt specifici spațiului românesc.',
                    '„Cruciadă" și „dinastie" sunt termenii universali. Ceilalți doi apar doar în contextul Țărilor Române.',
                    '„Descălecat" se referă la ÎNTEMEIEREA statelor, iar „capitulații" la RAPORTURILE cu Imperiul Otoman — două momente istorice diferite.',
                ],
                'explanation' => 'Programa cere explicit „selectarea terminologiei istorice specifice" fiecărei epoci. Termenii se cer folosiți corect la Subiectul al IV-lea, unde baremul punctează separat limbajul istoric.',
            ],
            [
                'ref' => 'seed:ist:term:razboiul-rece',
                'skill' => 'terminologie',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Care termen reflectă esența sintagmei „confruntare între democrațiile liberale occidentale și regimurile comuniste postbelice"?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Războiul Rece'],
                        ['key' => 'b', 'text' => 'Marea Depresiune'],
                        ['key' => 'c', 'text' => 'Problema orientală'],
                        ['key' => 'd', 'text' => 'Contrareforma'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Cuvântul-cheie e „postbelice" — deci vorbim de perioada de după 1945.',
                    'Confruntarea a fost politică, ideologică și militară, dar fără conflict armat direct între cele două blocuri.',
                    'Exact această absență a luptei directe a dat numele: Războiul Rece.',
                ],
                'explanation' => 'Războiul Rece (1947–1991) desemnează confruntarea dintre blocul occidental și blocul sovietic, purtată prin cursa înarmării, propagandă și conflicte prin intermediari, fără război direct între superputeri.',
            ],

            // ==================== CRONOLOGIE ====================
            [
                'ref' => 'seed:ist:crono:secolul-xix',
                'skill' => 'cronologie',
                'type' => ItemType::Timeline,
                'difficulty' => 3,
                'points' => 4,
                'payload' => [
                    'stem' => 'Așază evenimentele în ordine cronologică, de la cel mai vechi la cel mai recent.',
                    'steps' => [
                        ['key' => 'e1', 'text' => 'Pacea de la București: teritoriul dintre Prut și Nistru intră în Imperiul Rus'],
                        ['key' => 'e2', 'text' => 'Revoluția condusă de Tudor Vladimirescu'],
                        ['key' => 'e3', 'text' => 'Unirea Principatelor Române sub Alexandru Ioan Cuza'],
                        ['key' => 'e4', 'text' => 'Adoptarea Constituției României'],
                        ['key' => 'e5', 'text' => 'Proclamarea independenței de stat a României'],
                    ],
                ],
                'answer' => ['order' => ['e1', 'e2', 'e3', 'e4', 'e5']],
                'hints' => [
                    'Începe cu ce știi sigur: două evenimente sunt din prima jumătate a secolului, trei din a doua.',
                    'Pacea de la București e din 1812, iar revoluția lui Tudor Vladimirescu din 1821 — foarte apropiate.',
                    'Apoi: Unirea Principatelor (1859), Constituția (1866), independența (1877). Constituția vine DUPĂ unire, pentru că a fost adoptată de statul deja unit.',
                ],
                'explanation' => 'Reperele: 1812 — Pacea de la București; 1821 — revoluția lui Tudor Vladimirescu; 1859 — Unirea Principatelor; 1866 — Constituția; 1877 — independența. Ordinea „unire → constituție → independență" e logica construcției statului român modern.',
            ],
            [
                'ref' => 'seed:ist:crono:1939-1944',
                'skill' => 'cronologie',
                'type' => ItemType::Timeline,
                'difficulty' => 3,
                'points' => 4,
                'payload' => [
                    'stem' => 'Așază evenimentele în ordine cronologică.',
                    'steps' => [
                        ['key' => 'e1', 'text' => 'Semnarea Pactului Ribbentrop-Molotov'],
                        ['key' => 'e2', 'text' => 'Ultimatumurile sovietice și ocuparea Basarabiei și a nordului Bucovinei'],
                        ['key' => 'e3', 'text' => 'Formarea RSS Moldovenești'],
                        ['key' => 'e4', 'text' => 'Intrarea României în război împotriva URSS'],
                    ],
                ],
                'answer' => ['order' => ['e1', 'e2', 'e3', 'e4']],
                'hints' => [
                    'Pactul a fost semnat înainte de începerea celui de-Al Doilea Război Mondial în Europa.',
                    'Pactul e din august 1939; ultimatumurile sovietice din iunie 1940, deci aproape un an mai târziu.',
                    'RSSM s-a format în august 1940, la puțin timp după ocupare, iar România a intrat în război împotriva URSS în iunie 1941.',
                ],
                'explanation' => 'Reperele: 23 august 1939 — Pactul Ribbentrop-Molotov, cu protocolul adițional secret; 28 iunie 1940 — ocuparea Basarabiei și a nordului Bucovinei; 2 august 1940 — formarea RSSM; 22 iunie 1941 — intrarea României în război. Succesiunea arată legătura directă dintre pact și schimbările teritoriale.',
            ],
            [
                'ref' => 'seed:ist:crono:independenta-rm',
                'skill' => 'cronologie',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'În ce an a fost adoptată Declarația de Independență a Republicii Moldova?',
                    'unit' => 'anul',
                ],
                'answer' => ['value' => 1991, 'tolerance' => 0],
                'hints' => [
                    'Evenimentul se leagă de destrămarea Uniunii Sovietice.',
                    'Suveranitatea fusese proclamată cu un an mai devreme, în 1990.',
                    'Declarația de Independență a fost adoptată pe 27 august 1991.',
                ],
                'explanation' => 'Etapele: 23 iunie 1990 — Declarația de suveranitate; 27 august 1991 — Declarația de Independență. Distincția între suveranitate și independență e cerută explicit de programă.',
            ],

            // ==================== ANALIZA SURSELOR: FAPT ȘI OPINIE ====================
            [
                'ref' => 'seed:ist:sursa:fapt-vs-opinie',
                'skill' => 'analiza-surselor',
                'type' => ItemType::Mcq,
                'difficulty' => 4,
                'points' => 3,
                'payload' => [
                    'stem' => 'Care dintre afirmațiile de mai jos reprezintă o OPINIE a autorului, nu un fapt istoric?',
                    'passage_title' => 'Sursă (text neatribuit, pentru exersare)',
                    'passage' => self::SURSA_FAPT_OPINIE,
                    'choices' => [
                        ['key' => 'a', 'text' => '„Considerăm că această autonomie a fost de la început una formală, fără intenția reală de a fi păstrată."'],
                        ['key' => 'b', 'text' => '„Tratatul de pace de la București [a fost] semnat în anul 1812 între Imperiul Rus și Imperiul Otoman."'],
                        ['key' => 'c', 'text' => '„Noua provincie a primit denumirea de Basarabia."'],
                        ['key' => 'd', 'text' => '„În anul 1828, prin noul regulament, autonomia a fost lichidată."'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Un fapt se poate verifica în documente. O opinie exprimă evaluarea autorului și poate fi contestată.',
                    'Caută formulările care semnalează poziția autorului: „considerăm", „credem", „de fapt", „din păcate".',
                    'Varianta cu „Considerăm că…" plus judecata „fără intenția reală" e evaluare, nu constatare. Celelalte trei conțin date, denumiri și acte verificabile.',
                ],
                'explanation' => 'Programa cere explicit „să distingă faptul de opinie din informația oferită de surse". Semnalele de opinie: verbe de atitudine („considerăm", „apreciem"), adjective evaluative („formală", „reală") și afirmații despre intenții, care nu se pot verifica direct în documente.',
            ],
            [
                'ref' => 'seed:ist:sursa:cauza-in-sursa',
                'skill' => 'analiza-surselor',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'points' => 2,
                'payload' => [
                    'stem' => 'Conform sursei, prin ce act a fost lichidată autonomia Basarabiei?',
                    'passage_title' => 'Sursă (text neatribuit, pentru exersare)',
                    'passage' => self::SURSA_FAPT_OPINIE,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Prin regulamentul din 1828'],
                        ['key' => 'b', 'text' => 'Prin Tratatul de la București din 1812'],
                        ['key' => 'c', 'text' => 'Prin Regulamentul din 1818'],
                        ['key' => 'd', 'text' => 'Sursa nu precizează'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Răspunsul e în sursă — nu adăuga informație din memorie. Citește ultima frază.',
                    'Atenție la cele două regulamente menționate: unul instituie autonomia, celălalt o lichidează.',
                    'Regulamentul din 1818 a INSTITUIT autonomia limitată; cel din 1828 a lichidat-o. Tratatul din 1812 doar a schimbat stăpânirea teritoriului.',
                ],
                'explanation' => 'La itemii de tip „identifică în sursă", răspunsul trebuie să vină EXCLUSIV din text. Capcana e aici confuzia între cele două regulamente, ambele prezente în sursă cu roluri opuse.',
            ],

            // ==================== EPOCA ANTICĂ ====================
            [
                'ref' => 'seed:ist:antic:dacia-provincie',
                'skill' => 'antichitate',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'În urma războaielor daco-romane încheiate în anul 106, o parte a Daciei a devenit:',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Provincie a Imperiului Roman'],
                        ['key' => 'b', 'text' => 'Regat aliat al Romei, cu conducere proprie'],
                        ['key' => 'c', 'text' => 'Teritoriu al Imperiului Bizantin'],
                        ['key' => 'd', 'text' => 'Colonie greacă'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Războaiele s-au purtat între Decebal și împăratul Traian.',
                    'Rezultatul a fost înfrângerea statului dac, nu un compromis diplomatic.',
                    'În 106 Dacia a fost transformată în provincie romană — moment esențial pentru procesul de romanizare.',
                ],
                'explanation' => 'Războaiele daco-romane (101–102 și 105–106) s-au încheiat cu transformarea unei părți a Daciei în provincie romană. Imperiul Bizantin nici nu exista încă la acea dată, iar coloniile grecești sunt cu secole mai vechi și situate pe litoralul Mării Negre.',
            ],
            [
                'ref' => 'seed:ist:antic:personalitati',
                'skill' => 'antichitate',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 4,
                'payload' => [
                    'stem' => 'Potrivește fiecare personalitate cu ceea ce o definește.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'Burebista'],
                        ['key' => 'l2', 'text' => 'Decebal'],
                        ['key' => 'l3', 'text' => 'Traian'],
                        ['key' => 'l4', 'text' => 'Alexandru Macedon'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'Împărat roman în timpul căruia Dacia a devenit provincie'],
                        ['key' => 'r2', 'text' => 'Rege care a unificat triburile geto-dacice în secolul I î.Hr.'],
                        ['key' => 'r3', 'text' => 'Cuceritor a cărui expansiune a deschis epoca elenistică'],
                        ['key' => 'r4', 'text' => 'Ultimul rege al statului dac, învins în războaiele cu Roma'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r2', 'l2' => 'r4', 'l3' => 'r1', 'l4' => 'r3']],
                'hints' => [
                    'Doi sunt regi daci, unul e împărat roman, unul e din spațiul grecesc.',
                    'Burebista e mai vechi (secolul I î.Hr.), Decebal e contemporanul lui Traian (secolul I–II d.Hr.).',
                    'Alexandru Macedon e singurul care nu are legătură cu spațiul dac — el a deschis epoca elenistică.',
                ],
                'explanation' => 'Toate patru apar în lista oficială de personalități din programă, la epoca antică. Ordinea cronologică ajută: Alexandru Macedon (sec. IV î.Hr.) → Burebista (sec. I î.Hr.) → Decebal și Traian (sec. I–II d.Hr.).',
            ],

            // ==================== EPOCA MEDIEVALĂ ====================
            [
                'ref' => 'seed:ist:medieval:stefan-cel-mare',
                'skill' => 'ev-mediu',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Care a fost principala direcție a politicii externe a lui Ștefan cel Mare, domn al Moldovei?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Apărarea independenței Moldovei, în special împotriva expansiunii otomane'],
                        ['key' => 'b', 'text' => 'Cucerirea Constantinopolului'],
                        ['key' => 'c', 'text' => 'Unirea celor trei Țări Române sub o singură autoritate'],
                        ['key' => 'd', 'text' => 'Alianța permanentă cu Imperiul Otoman împotriva Poloniei'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Ștefan cel Mare a domnit în a doua jumătate a secolului al XV-lea, după căderea Constantinopolului.',
                    'Constantinopolul căzuse deja în 1453, deci varianta cu cucerirea lui e imposibilă cronologic.',
                    'Unirea celor trei Țări Române e realizarea lui Mihai Viteazul, în 1600 — cu peste un secol mai târziu.',
                ],
                'explanation' => 'Ștefan cel Mare (1457–1504) a purtat o politică de apărare a independenței Moldovei, cu războaie împotriva Imperiului Otoman, dar și confruntări cu Ungaria și Polonia. Capcanele itemului sunt anacronismele: Constantinopolul căzuse în 1453, iar unirea din 1600 aparține lui Mihai Viteazul.',
            ],
            [
                'ref' => 'seed:ist:medieval:mihai-viteazul',
                'skill' => 'ev-mediu',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Ce realizare politică este asociată cu domnia lui Mihai Viteazul, în anul 1600?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Reunirea, pentru scurt timp, a Țării Românești, Transilvaniei și Moldovei sub autoritatea sa'],
                        ['key' => 'b', 'text' => 'Adoptarea primei constituții a Țării Românești'],
                        ['key' => 'c', 'text' => 'Obținerea independenței față de Imperiul Otoman prin tratat'],
                        ['key' => 'd', 'text' => 'Întemeierea statului medieval Moldova'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Anul 1600 e reperul: ce s-a întâmplat atunci și a durat foarte puțin?',
                    'Realizarea a fost teritorială și politică, nu juridică — constituțiile apar abia în epoca modernă.',
                    'Mihai Viteazul a reunit cele trei țări sub autoritatea sa în 1600, dar unirea a durat câteva luni.',
                ],
                'explanation' => 'Unirea din 1600 a fost personală și de scurtă durată, dar a rămas un reper politic important. Constituțiile aparțin epocii moderne, iar întemeierea Moldovei e cu peste două secole mai veche.',
            ],

            // ==================== EPOCA MODERNĂ ====================
            [
                'ref' => 'seed:ist:modern:1812',
                'skill' => 'epoca-moderna',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Prin Pacea de la București din 1812, teritoriul dintre Prut și Nistru a intrat în componența:',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Imperiului Rus'],
                        ['key' => 'b', 'text' => 'Imperiului Otoman'],
                        ['key' => 'c', 'text' => 'Imperiului Habsburgic'],
                        ['key' => 'd', 'text' => 'Regatului Poloniei'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Pacea a încheiat un război ruso-turc. Cine câștigă un război obține de obicei teritoriu.',
                    'Teritoriul aparținea Țării Moldovei, aflată sub suzeranitate otomană, și a fost cedat celeilalte părți.',
                    'A intrat în Imperiul Rus și a primit denumirea de Basarabia. Habsburgii anexaseră Bucovina mai devreme, în 1775.',
                ],
                'explanation' => 'Pacea de la București (1812) a încheiat războiul ruso-turc din 1806–1812. Teritoriul dintre Prut și Nistru a fost anexat de Imperiul Rus și denumit Basarabia. A nu se confunda cu anexarea Bucovinei de către Imperiul Habsburgic, în 1775.',
            ],
            [
                'ref' => 'seed:ist:modern:unirea-1859',
                'skill' => 'epoca-moderna',
                'type' => ItemType::FillBlanks,
                'difficulty' => 3,
                'points' => 2,
                'payload' => [
                    'stem' => 'Completează afirmația despre Unirea Principatelor Române.',
                    'stem_template' => 'În anul ___ , ___ a fost ales domn în ambele Principate, realizând unirea acestora.',
                    'blanks' => [
                        ['key' => 'anul', 'options' => ['1859', '1848', '1866', '1877']],
                        ['key' => 'domnul', 'options' => ['Alexandru Ioan Cuza', 'Carol I', 'Mihail Kogălniceanu', 'Tudor Vladimirescu']],
                    ],
                ],
                'answer' => ['values' => [
                    'anul' => ['1859'],
                    'domnul' => ['Alexandru Ioan Cuza'],
                ]],
                'hints' => [
                    'Unirea s-a realizat prin dubla alegere a aceleiași persoane ca domn în Moldova și în Țara Românească.',
                    'Anul e la mijlocul secolului al XIX-lea, după revoluțiile de la 1848 și înainte de Constituția din 1866.',
                    'Alexandru Ioan Cuza a fost alesul, în 1859. Carol I a urcat pe tron în 1866, după abdicarea lui Cuza.',
                ],
                'explanation' => 'Unirea din 1859 s-a realizat prin dubla alegere a lui Alexandru Ioan Cuza — o soluție care ocolea interdicția Marilor Puteri privind unirea propriu-zisă. Kogălniceanu a fost colaboratorul său apropiat, nu domn.',
            ],
            [
                'ref' => 'seed:ist:modern:anacronism',
                'skill' => 'epoca-moderna',
                'type' => ItemType::FindError,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'Următoarea afirmație conține o eroare. Care este?',
                    'passage' => '„În anul 1859, Alexandru Ioan Cuza a fost ales domn în ambele Principate, iar noul stat a adoptat imediat Constituția din 1866, prin care se instituia monarhia constituțională."',
                    'choices' => [
                        ['key' => 'a', 'text' => 'Constituția din 1866 nu putea fi adoptată „imediat" după 1859 — sunt șapte ani între evenimente, iar ea a fost adoptată după înlăturarea lui Cuza'],
                        ['key' => 'b', 'text' => 'Cuza nu a fost ales în ambele Principate'],
                        ['key' => 'c', 'text' => 'Constituția din 1866 nu instituia monarhia constituțională'],
                        ['key' => 'd', 'text' => 'Unirea Principatelor a avut loc în 1848, nu în 1859'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Verifică relația de timp dintre cele două date menționate în aceeași frază.',
                    'Cuvântul „imediat" leagă anul 1859 de anul 1866 — sunt compatibile?',
                    'Între ele sunt șapte ani, iar Constituția din 1866 a fost adoptată sub domnitorul Carol I, după abdicarea lui Cuza în februarie 1866.',
                ],
                'explanation' => 'Eroarea e de tip anacronism relațional: datele sunt corecte individual, dar legătura dintre ele e falsă. Constituția din 1866 chiar a instituit monarhia constituțională, iar Cuza chiar a fost ales în ambele Principate — de aceea celelalte variante sunt capcane.',
            ],

            // ==================== PRIMUL RĂZBOI ȘI MAREA UNIRE ====================
            [
                'ref' => 'seed:ist:unire:1918-ordine',
                'skill' => 'ww1-marea-unire',
                'type' => ItemType::Timeline,
                'difficulty' => 4,
                'points' => 4,
                'payload' => [
                    'stem' => 'Așază în ordine cronologică actele de unire din anul 1918.',
                    'steps' => [
                        ['key' => 'e1', 'text' => 'Sfatul Țării votează unirea Basarabiei cu România'],
                        ['key' => 'e2', 'text' => 'Congresul general al Bucovinei votează unirea cu România'],
                        ['key' => 'e3', 'text' => 'Marea Adunare Națională de la Alba Iulia hotărăște unirea Transilvaniei cu România'],
                    ],
                ],
                'answer' => ['order' => ['e1', 'e2', 'e3']],
                'hints' => [
                    'Toate trei au avut loc în 1918, dar în luni diferite: primăvară, toamnă și început de iarnă.',
                    'Basarabia a fost prima, în martie 1918 — cu mult înainte de sfârșitul războiului.',
                    'Bucovina a urmat în noiembrie, iar Transilvania pe 1 decembrie 1918, la Alba Iulia.',
                ],
                'explanation' => 'Reperele: 27 martie 1918 — Sfatul Țării, unirea Basarabiei; 28 noiembrie 1918 — Congresul Bucovinei; 1 decembrie 1918 — Alba Iulia. Ordinea nu e întâmplătoare: Basarabia s-a unit în timpul războiului, celelalte două după prăbușirea Austro-Ungariei.',
            ],
            [
                'ref' => 'seed:ist:unire:sfatul-tarii',
                'skill' => 'ww1-marea-unire',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Ce a fost Sfatul Țării, în contextul anilor 1917–1918?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Organul reprezentativ al Basarabiei, care a proclamat Republica Democratică Moldovenească și a votat unirea cu România'],
                        ['key' => 'b', 'text' => 'Guvernul Regatului României în perioada refugiului la Iași'],
                        ['key' => 'c', 'text' => 'Adunarea care a votat unirea Transilvaniei cu România'],
                        ['key' => 'd', 'text' => 'Consiliul militar al armatei ruse pe frontul român'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Denumirea se leagă de un teritoriu anume — care?',
                    'A fost un organ reprezentativ, format în condițiile prăbușirii Imperiului Rus.',
                    'Sfatul Țării a fost organul reprezentativ al Basarabiei. Adunarea Transilvaniei a fost Marea Adunare Națională de la Alba Iulia.',
                ],
                'explanation' => 'Sfatul Țării, constituit în 1917, a proclamat Republica Democratică Moldovenească (decembrie 1917), a declarat independența (ianuarie 1918) și a votat unirea cu România (27 martie 1918). Cele trei etape sunt cerute distinct de programă.',
            ],

            // ==================== INTERBELIC ȘI AL DOILEA RĂZBOI ====================
            [
                'ref' => 'seed:ist:ww2:pactul',
                'skill' => 'ww2',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Ce a prevăzut protocolul adițional secret al Pactului Ribbentrop-Molotov din 1939?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Delimitarea sferelor de influență ale Germaniei și URSS în Europa de Est'],
                        ['key' => 'b', 'text' => 'O alianță militară deschisă între Germania și URSS împotriva Franței'],
                        ['key' => 'c', 'text' => 'Garantarea frontierelor României de către ambele state'],
                        ['key' => 'd', 'text' => 'Crearea unei uniuni economice între cele două state'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Pactul propriu-zis era un tratat de neagresiune. Partea secretă privea altceva.',
                    'Consecințele s-au văzut în lunile următoare, prin schimbări teritoriale în Europa de Est.',
                    'Protocolul delimita sferele de influență, iar Basarabia era menționată ca zonă de interes sovietic — de unde ultimatumurile din iunie 1940.',
                ],
                'explanation' => 'Pactul din 23 august 1939 era formal un tratat de neagresiune, dar protocolul adițional secret împărțea Europa de Est în sfere de influență. El explică direct evenimentele din 1940 pentru Basarabia și nordul Bucovinei — legătura cauzală cerută de programă.',
            ],
            [
                'ref' => 'seed:ist:ww2:crime',
                'skill' => 'ww2',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Potrivește fiecare termen cu descrierea corespunzătoare.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'Holocaust'],
                        ['key' => 'l2', 'text' => 'GULAG'],
                        ['key' => 'l3', 'text' => 'Katyn'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'Sistemul sovietic de lagăre de muncă forțată'],
                        ['key' => 'r2', 'text' => 'Genocidul evreilor din Europa, comis de regimul nazist'],
                        ['key' => 'r3', 'text' => 'Executarea în masă a ofițerilor polonezi de către autoritățile sovietice, în 1940'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r2', 'l2' => 'r1', 'l3' => 'r3']],
                'hints' => [
                    'Unul dintre termeni e un nume de loc, ceilalți doi denumesc fenomene.',
                    'Katyn e o localitate, legată de un eveniment precis din 1940.',
                    'Holocaustul e asociat regimului nazist, iar GULAG-ul sistemului sovietic de lagăre.',
                ],
                'explanation' => 'Programa tratează explicit unitatea „Crime împotriva umanității: GULAG. Holocaust. Katyn". Toate trei sunt fapte documentate, dar aparțin unor regimuri și contexte diferite — distincția e cerută la examen.',
            ],

            // ==================== CONTEMPORAN (HOLDOUT) ====================
            [
                'ref' => 'seed:ist:mock:razboiul-rece-inceput',
                'skill' => 'contemporan',
                'type' => ItemType::Mcq,
                'difficulty' => 4,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Care afirmație descrie corect Lumea bipolară instaurată după 1945?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Organizarea relațiilor internaționale în jurul a două blocuri politico-militare rivale, conduse de SUA și URSS'],
                        ['key' => 'b', 'text' => 'Împărțirea lumii între cele două imperii coloniale rămase, britanic și francez'],
                        ['key' => 'c', 'text' => 'Confruntarea directă, cu arme, dintre SUA și URSS pe teritoriul european'],
                        ['key' => 'd', 'text' => 'Existența a două organizații internaționale cu atribuții egale, ONU și Liga Națiunilor'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    '„Bipolar" indică două centre de putere. Care erau ele după 1945?',
                    'Confruntarea a fost politică, ideologică și militară, dar fără război direct între cele două superputeri.',
                    'Cele două blocuri s-au instituționalizat prin alianțe militare rivale — NATO și, ulterior, Tratatul de la Varșovia.',
                ],
                'explanation' => 'Lumea bipolară desemnează structurarea relațiilor internaționale în jurul SUA și URSS, cu blocuri militare rivale. Varianta cu confruntarea directă e falsă — tocmai absența ei definește Războiul Rece. Liga Națiunilor își încetase activitatea în 1946.',
            ],
        ];
    }
}
