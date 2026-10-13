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
 * Conținut de start pentru matematică, profil real.
 *
 * Temele urmăresc ce a apărut efectiv la sesiunea 2026 (calcule, inecuații,
 * matrici, numere complexe, trigonometrie, geometrie) plus analiza matematică.
 *
 * Nu e o bancă de itemi — sunt ~20 de itemi de referință, câte unul pentru
 * fiecare mecanică, care servesc ca șablon pentru generare. Banca reală se
 * construiește cu pipeline-ul de generare + validare.
 */
class MathContentSeeder extends Seeder
{
    public function run(): void
    {
        $subject = Subject::where('slug', 'matematica')->firstOrFail();

        $skills = $this->seedSkills($subject->id);
        $this->seedPrerequisites($skills);
        $this->seedItems($subject->id, $skills);
    }

    /** @return array<string, Skill> */
    private function seedSkills(int $subjectId): array
    {
        // [slug, nume, cod curricular, pondere la examen, copii]
        $tree = [
            ['algebra', 'Algebră', null, 0, [
                ['numere-reale', 'Numere reale și calcule', 'MAT.XII.1.1', 8],
                ['ecuatii-inecuatii', 'Ecuații și inecuații', 'MAT.XII.1.2', 12],
                ['logaritmi', 'Logaritmi', 'MAT.XII.1.3', 8],
                ['numere-complexe', 'Numere complexe', 'MAT.XII.1.4', 8],
                ['matrici', 'Matrici și determinanți', 'MAT.XII.1.5', 10],
            ]],
            ['trigonometrie', 'Trigonometrie', null, 0, [
                ['valori-trigonometrice', 'Valori trigonometrice', 'MAT.XII.2.1', 6],
                ['identitati-trigonometrice', 'Identități trigonometrice', 'MAT.XII.2.2', 8],
            ]],
            ['analiza', 'Analiză matematică', null, 0, [
                ['functii', 'Funcții', 'MAT.XII.3.1', 8],
                ['limite', 'Limite de funcții', 'MAT.XII.3.2', 6],
                ['derivate', 'Derivate', 'MAT.XII.3.3', 14],
                ['integrale', 'Integrale', 'MAT.XII.3.4', 12],
            ]],
            ['geometrie', 'Geometrie', null, 0, [
                ['triunghiuri', 'Triunghiuri', 'MAT.XII.4.1', 8],
                ['cerc', 'Cercul', 'MAT.XII.4.2', 6],
                ['corpuri', 'Corpuri geometrice', 'MAT.XII.4.3', 10],
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
            ['corpuri', 'triunghiuri', 0.8],
            ['corpuri', 'cerc', 0.6],
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
     * Banca de itemi, pe capitole.
     *
     * Împărțită pe metode pentru că un singur array de câteva sute de itemi
     * devine imposibil de navigat. Ordinea urmează capitolele din programă.
     *
     * @return list<array<string, mixed>>
     */
    private function items(): array
    {
        return array_merge(
            $this->itemsDeReferinta(),
            $this->itemsAlgebra(),
            $this->itemsTrigonometrie(),
            $this->itemsAnaliza(),
            $this->itemsGeometrie(),
        );
    }

    /**
     * Itemii de referință: câte unul pentru fiecare mecanică de corectare.
     *
     * Ei sunt șablonul după care se scriu ceilalți — dacă adaugi un tip nou de
     * item, exemplul canonic stă aici.
     *
     * @return list<array<string, mixed>>
     */
    private function itemsDeReferinta(): array
    {
        return [
            // ============ MCQ: formulă ↔ definiție ============
            [
                'ref' => 'seed:mat:derivate:produs',
                'hints' => [
                    'Nu derivezi factorii separat și apoi îi înmulțești — regula produsului are doi termeni.',
                    'Derivează primul factor păstrând al doilea neschimbat, apoi invers, și adună rezultatele.',
                    'Forma e $f\'g + fg\'$. Varianta cu numitor $g^2$ e regula câtului, nu a produsului.',
                ],
                'skill' => 'derivate',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Care este formula derivatei unui produs de două funcții?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => "f'g + fg'"],
                        ['key' => 'b', 'latex' => "f'g'"],
                        ['key' => 'c', 'latex' => "f'g - fg'"],
                        ['key' => 'd', 'latex' => '\frac{f\'g - fg\'}{g^2}'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Regula produsului: $(fg)\' = f\'g + fg\'$. Varianta cu numitor $g^2$ este regula câtului.',
            ],
            [
                'ref' => 'seed:mat:trig:tangenta-definitie',
                'hints' => [
                    'Sinusul și cosinusul se raportează la ipotenuză. Aici ipotenuza nu apare deloc.',
                    'Raportul e între cele două catete. Rămân doar tangenta și cotangenta.',
                    'Cateta OPUSĂ la numărător înseamnă tangentă; dacă ar fi alăturată la numărător, ar fi cotangentă.',
                ],
                'skill' => 'valori-trigonometrice',
                'type' => ItemType::Mcq,
                'difficulty' => 1,
                'payload' => [
                    'stem' => 'Într-un triunghi dreptunghic, raportul dintre cateta opusă și cateta alăturată definește:',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '\sin \alpha'],
                        ['key' => 'b', 'latex' => '\cos \alpha'],
                        ['key' => 'c', 'latex' => '\operatorname{tg} \alpha'],
                        ['key' => 'd', 'latex' => '\operatorname{ctg} \alpha'],
                    ],
                ],
                'answer' => ['correct' => ['c']],
                'explanation' => '$\operatorname{tg} \alpha = \frac{\text{cateta opusă}}{\text{cateta alăturată}}$.',
            ],

            // ============ NEXT_STEP: antrenament pe baremul analitic ============
            // Tipul central al platformei: examenul se corectează pe pași, deci
            // elevul trebuie antrenat să aleagă pasul, nu doar răspunsul final.
            [
                'ref' => 'seed:mat:log:ecuatie-pas3',
                'hints' => [
                    'Ai adus deja suma la un singur logaritm. Ce spune definiția logaritmului despre $\\log_2 A = 3$?',
                    '$\\log_a A = b$ este echivalent cu $A = a^b$. Aici baza e 2, iar rezultatul 3.',
                    'Deci $(x-1)(x+1) = 2^3 = 8$. Atenție: NU $= 3$ — asta ar însemna $\\log_2 A = \\log_2 3$.',
                ],
                'skill' => 'logaritmi',
                'type' => ItemType::NextStep,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Rezolvă ecuația. Care este pasul următor?',
                    'problem' => '\log_2(x-1) + \log_2(x+1) = 3',
                    'solved_steps' => [
                        'Condiții de existență: $x > 1$',
                        '$\log_2\big((x-1)(x+1)\big) = 3$',
                    ],
                    'choices' => [
                        ['key' => 'a', 'latex' => '(x-1)(x+1) = 8'],
                        ['key' => 'b', 'latex' => '(x-1)(x+1) = 6'],
                        ['key' => 'c', 'latex' => 'x^2 - 1 = 3'],
                        ['key' => 'd', 'latex' => 'x = 3'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Din definiția logaritmului, $\log_2 A = 3 \Rightarrow A = 2^3 = 8$. Varianta $x^2-1=3$ ar însemna $\log_2 A = \log_2 3$, ceea ce nu e cazul.',
            ],
            [
                'ref' => 'seed:mat:integrale:parti-pas1',
                'hints' => [
                    'Formula e $\\int u\\,dv = uv - \\int v\\,du$. Vrei ca integrala rămasă să fie mai simplă decât cea inițială.',
                    'Alege ca $u$ funcția care se SIMPLIFICĂ prin derivare. Care dintre $x$ și $e^x$ devine mai simplă?',
                    '$x$ derivat devine 1, deci $\\int e^x dx$ rămâne — imediată. Cu $u = e^x$ ai obținut o integrală mai grea.',
                ],
                'skill' => 'integrale',
                'type' => ItemType::NextStep,
                'difficulty' => 4,
                'points' => 3,
                'payload' => [
                    'stem' => 'Pentru integrarea prin părți, ce alegere de $u$ și $dv$ duce la soluție?',
                    'problem' => '\int x \cdot e^x \, dx',
                    'choices' => [
                        ['key' => 'a', 'latex' => 'u = x,\ dv = e^x dx'],
                        ['key' => 'b', 'latex' => 'u = e^x,\ dv = x\,dx'],
                        ['key' => 'c', 'latex' => 'u = x e^x,\ dv = dx'],
                        ['key' => 'd', 'latex' => 'u = 1,\ dv = x e^x dx'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Cu $u = x$ avem $du = dx$, iar integrala rămasă $\int e^x dx$ e imediată. Cu $u = e^x$ integrala rezultată e mai complicată decât cea inițială.',
            ],

            // ============ FIND_ERROR: „unde s-a pierdut punctul?" ============
            // Îl învață pe elev să gândească precum evaluatorul.
            [
                'ref' => 'seed:mat:alg:patrat-binom-eroare',
                'hints' => [
                    'Verifică rezultatul cu un număr concret: pune $x = 1$ în ambele părți. Ies la fel?',
                    'Cu $x = 1$: stânga dă $(1+3)^2 = 16$, dreapta dă $1 + 9 = 10$. Lipsește ceva.',
                    '$(a+b)^2 = a^2 + 2ab + b^2$. Termenul $2ab = 6x$ a fost omis — greșeala cea mai frecventă la examen.',
                ],
                'skill' => 'numere-reale',
                'type' => ItemType::FindError,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Un elev a scris rezolvarea de mai jos și a primit 0 puncte. Unde este greșeala?',
                    'problem' => '(x + 3)^2 = x^2 + 9',
                    'choices' => [
                        ['key' => 'a', 'text' => 'A omis dublul produsului: lipsește termenul $6x$'],
                        ['key' => 'b', 'text' => 'A greșit semnul: rezultatul corect e $x^2 - 9$'],
                        ['key' => 'c', 'text' => 'Nu a pus condiții de existență'],
                        ['key' => 'd', 'text' => 'Nu există nicio greșeală'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '$(a+b)^2 = a^2 + 2ab + b^2$, deci $(x+3)^2 = x^2 + 6x + 9$. Omiterea dublului produs e una dintre cele mai frecvente greșeli de la examen.',
            ],
            [
                'ref' => 'seed:mat:log:conditii-eroare',
                'hints' => [
                    'Calculele sunt corecte, deci problema nu e la rezolvare. Uită-te la ce se cere ÎNAINTE de a rezolva.',
                    'Logaritmul cere argument strict pozitiv. Verifică ambele soluții în $\\log_2(x-1)$ și $\\log_2(x+1)$.',
                    'Cu $x = -3$, argumentul $x-1 = -4 < 0$: soluția e străină și trebuie respinsă EXPLICIT. Baremul dă puncte separate pentru asta.',
                ],
                'skill' => 'logaritmi',
                'type' => ItemType::FindError,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Elevul a rezolvat corect calculele și a obținut $x_1 = 3$, $x_2 = -3$, dar a primit doar jumătate din punctaj. De ce?',
                    'problem' => '\log_2(x-1) + \log_2(x+1) = 3',
                    'choices' => [
                        ['key' => 'a', 'text' => 'Nu a eliminat $x = -3$, care nu respectă condiția $x > 1$'],
                        ['key' => 'b', 'text' => 'A greșit baza logaritmului'],
                        ['key' => 'c', 'text' => 'Nu a verificat prin înlocuire'],
                        ['key' => 'd', 'text' => 'Trebuia să scrie rezultatul ca fracție'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Domeniul cere $x-1>0$ și $x+1>0$, deci $x>1$. Soluția $x=-3$ e străină și trebuie respinsă explicit. Baremul acordă puncte separate pentru verificarea condițiilor.',
            ],

            // ============ ORDER_STEPS: structura rezolvării ============
            [
                'ref' => 'seed:mat:ecuatii:ordonare-patratica',
                'hints' => [
                    'Într-o ecuație irațională, condițiile de existență se pun ÎNAINTE de a ridica la pătrat, nu după.',
                    'Ridicarea la pătrat poate introduce soluții false, deci la final trebuie o verificare a condiției.',
                    'Ordinea din barem: condiții → ridicare la pătrat → forma canonică → rezolvare → verificarea condițiilor.',
                ],
                'skill' => 'ecuatii-inecuatii',
                'type' => ItemType::OrderSteps,
                'difficulty' => 2,
                'points' => 4,
                'payload' => [
                    'stem' => 'Pune pașii în ordinea corectă pentru rezolvarea ecuației iraționale.',
                    'problem' => '\sqrt{x + 3} = x - 3',
                    'steps' => [
                        ['key' => 's1', 'text' => 'Condiții: $x + 3 \ge 0$ și $x - 3 \ge 0$, deci $x \ge 3$'],
                        ['key' => 's2', 'text' => 'Ridic la pătrat: $x + 3 = (x-3)^2$'],
                        ['key' => 's3', 'text' => 'Aduc la forma $x^2 - 7x + 6 = 0$'],
                        ['key' => 's4', 'text' => 'Rezolv: $x_1 = 1$, $x_2 = 6$'],
                        ['key' => 's5', 'text' => 'Verific condiția $x \ge 3$: rămâne doar $x = 6$'],
                    ],
                ],
                'answer' => ['order' => ['s1', 's2', 's3', 's4', 's5']],
                'explanation' => 'Condițiile se pun ÎNAINTE de ridicarea la pătrat, iar verificarea lor la final elimină soluțiile străine. Ambele etape au punctaj în barem.',
            ],
            [
                'ref' => 'seed:mat:derivate:ordonare-tangenta',
                'explanation' => 'Ecuația tangentei are nevoie de două lucruri: un punct pe grafic, $f(x_0)$, și panta, $f\'(x_0)$. Ordinea din barem urmează exact această logică — nu poți scrie ecuația înainte de a avea ambele. Pentru $f(x) = x^3 - 2x$ în $x_0 = 1$: $f(1) = -1$, $f\'(1) = 1$, deci $y = x - 2$.',
                'hints' => [
                    'Ecuația tangentei are nevoie de două lucruri: un PUNCT pe grafic și o PANTĂ.',
                    'Punctul vine din $f(x_0)$, panta din $f\'(x_0)$ — deci trebuie mai întâi derivata, apoi valoarea ei în $x_0$.',
                    'Abia cu ambele poți scrie $y - f(x_0) = f\'(x_0)(x - x_0)$ și apoi simplifica.',
                ],
                'skill' => 'derivate',
                'type' => ItemType::OrderSteps,
                'difficulty' => 3,
                'points' => 4,
                'payload' => [
                    'stem' => 'Ordonează pașii pentru ecuația tangentei la grafic în punctul $x_0 = 1$.',
                    'problem' => 'f(x) = x^3 - 2x',
                    'steps' => [
                        ['key' => 's1', 'text' => 'Calculez $f(1) = 1 - 2 = -1$'],
                        ['key' => 's2', 'text' => 'Calculez $f\'(x) = 3x^2 - 2$'],
                        ['key' => 's3', 'text' => 'Calculez panta $m = f\'(1) = 1$'],
                        ['key' => 's4', 'text' => 'Scriu $y - f(1) = m(x - 1)$'],
                        ['key' => 's5', 'text' => 'Obțin $y = x - 2$'],
                    ],
                ],
                'answer' => ['order' => ['s1', 's2', 's3', 's4', 's5']],
            ],

            // ============ MATCH: formulă ↔ nume ============
            [
                'ref' => 'seed:mat:derivate:tabel',
                'explanation' => 'Tabelul derivatelor se învață, dar două elemente ajută la verificare: $e^x$ e singura funcție egală cu derivata sa, iar $(\\operatorname{tg} x)\' = 1/\\cos^2 x$ se obține aplicând regula câtului la $\\sin x / \\cos x$.',
                'hints' => [
                    'Începe cu cele pe care le știi sigur: $e^x$ are o proprietate unică între toate funcțiile.',
                    '$(e^x)\' = e^x$ — singura funcție egală cu derivata ei. Iar $(\\ln x)\' = 1/x$.',
                    'Rămân sinusul și tangenta: $(\\sin x)\' = \\cos x$, iar $(\\operatorname{tg} x)\' = 1/\\cos^2 x$.',
                ],
                'skill' => 'derivate',
                'type' => ItemType::Match,
                'difficulty' => 2,
                'points' => 4,
                'payload' => [
                    'stem' => 'Potrivește fiecare funcție cu derivata ei.',
                    'left' => [
                        ['key' => 'l1', 'latex' => '\sin x'],
                        ['key' => 'l2', 'latex' => '\ln x'],
                        ['key' => 'l3', 'latex' => 'e^x'],
                        ['key' => 'l4', 'latex' => '\operatorname{tg} x'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'latex' => '\frac{1}{\cos^2 x}'],
                        ['key' => 'r2', 'latex' => '\cos x'],
                        ['key' => 'r3', 'latex' => 'e^x'],
                        ['key' => 'r4', 'latex' => '\frac{1}{x}'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r2', 'l2' => 'r4', 'l3' => 'r3', 'l4' => 'r1']],
            ],
            [
                'ref' => 'seed:mat:geometrie:formule-arii',
                'explanation' => 'Sfera e singurul corp fără înălțime în formulă. Conul și cilindrul au aceeași bază $\\pi r^2$, dar conul are exact o treime din volumul cilindrului cu aceeași bază și înălțime — factorul $1/3$ apare la toate corpurile ascuțite (con, piramidă).',
                'hints' => [
                    'Sfera e singura fără înălțime — formula ei depinde doar de rază.',
                    'Conul și cilindrul au aceeași bază circulară $\\pi r^2$; diferă printr-un factor de $1/3$.',
                    'Corpul ascuțit (conul) are o treime din volumul cilindrului cu aceeași bază și înălțime.',
                ],
                'skill' => 'corpuri',
                'type' => ItemType::Match,
                'difficulty' => 2,
                'points' => 3,
                'payload' => [
                    'stem' => 'Potrivește corpul cu formula volumului.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'Sferă'],
                        ['key' => 'l2', 'text' => 'Con'],
                        ['key' => 'l3', 'text' => 'Cilindru'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'latex' => '\pi r^2 h'],
                        ['key' => 'r2', 'latex' => '\frac{4}{3}\pi r^3'],
                        ['key' => 'r3', 'latex' => '\frac{1}{3}\pi r^2 h'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r2', 'l2' => 'r3', 'l3' => 'r1']],
            ],

            // ============ NUMERIC ============
            [
                'ref' => 'seed:mat:trig:sin30',
                'hints' => [
                    '$30^\\circ$ și $60^\\circ$ sunt unghiuri din tabelul valorilor remarcabile — nu ai nevoie de calculator.',
                    '$\\sin 30^\\circ = \\frac{1}{2}$. Iar $\\cos 60^\\circ$ e egal cu $\\sin 30^\\circ$, pentru că unghiurile sunt complementare.',
                    'Deci $2 \\cdot \\frac{1}{2} + \\frac{1}{2}$.',
                ],
                'skill' => 'valori-trigonometrice',
                'type' => ItemType::Numeric,
                'difficulty' => 1,
                'payload' => [
                    'stem' => 'Calculează valoarea expresiei.',
                    'stem_latex' => '2\sin 30^\circ + \cos 60^\circ',
                ],
                'answer' => ['value' => 1.5, 'tolerance' => 0.001, 'accept' => [1.5]],
                'explanation' => '$\sin 30^\circ = \frac{1}{2}$ și $\cos 60^\circ = \frac{1}{2}$, deci $2 \cdot \frac{1}{2} + \frac{1}{2} = 1{,}5$.',
            ],
            [
                'ref' => 'seed:mat:matrici:determinant2x2',
                'hints' => [
                    'Pentru o matrice $2 \\times 2$ se înmulțesc elementele pe diagonale.',
                    'Formula e $ad - bc$: produsul diagonalei principale minus produsul celei secundare.',
                    'Diagonala principală: $3 \\cdot 4$. Cea secundară: $5 \\cdot 2$. Atenție la ordinea scăderii.',
                ],
                'skill' => 'matrici',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Calculează determinantul matricei.',
                    'stem_latex' => '\begin{vmatrix} 3 & 5 \\\\ 2 & 4 \end{vmatrix}',
                ],
                'answer' => ['value' => 2, 'tolerance' => 0],
                'explanation' => '$3 \cdot 4 - 5 \cdot 2 = 12 - 10 = 2$.',
            ],
            [
                'ref' => 'seed:mat:complexe:modul',
                'hints' => [
                    'Modulul e distanța de la origine până la punctul $(a, b)$ din plan.',
                    'Deci se aplică teorema lui Pitagora: $|z| = \\sqrt{a^2 + b^2}$.',
                    '$\\sqrt{3^2 + 4^2} = \\sqrt{25}$ — un triunghi 3-4-5.',
                ],
                'skill' => 'numere-complexe',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Calculează modulul numărului complex.',
                    'stem_latex' => 'z = 3 + 4i',
                ],
                'answer' => ['value' => 5, 'tolerance' => 0],
                'explanation' => '$|z| = \sqrt{a^2 + b^2} = \sqrt{9 + 16} = 5$.',
            ],

            // ============ FILL_BLANKS ============
            [
                'ref' => 'seed:mat:geometrie:aria-triunghi',
                'hints' => [
                    'Compară triunghiul cu un dreptunghi care are aceeași bază și aceeași înălțime.',
                    'Triunghiul ocupă exact jumătate din acel dreptunghi.',
                    'Deci coeficientul e $1/2$: $S = \\frac{1}{2} b h$.',
                ],
                'skill' => 'triunghiuri',
                'type' => ItemType::FillBlanks,
                'difficulty' => 1,
                'points' => 2,
                'payload' => [
                    'stem' => 'Completează formula ariei triunghiului.',
                    'stem_template' => 'S = ___ · b · h',
                    'blanks' => [
                        ['key' => 'coef', 'options' => ['1/2', '1', '2', '1/3']],
                    ],
                ],
                'answer' => ['values' => ['coef' => ['1/2', '0.5', '0,5']]],
                'explanation' => 'Aria triunghiului este jumătate din produsul bazei cu înălțimea: $S = \frac{1}{2} b h$.',
            ],
            [
                'ref' => 'seed:mat:trig:identitate-fundamentala',
                'explanation' => '$\\sin^2 \\alpha + \\cos^2 \\alpha = 1$ pentru ORICE unghi — de aici numele de identitate fundamentală. E consecința directă a teoremei lui Pitagora în cercul trigonometric de rază 1, iar din ea se deduc majoritatea celorlalte identități.',
                'hints' => [
                    'Ia un unghi concret pe care îl știi, de exemplu $30^\\circ$, și calculează suma.',
                    '$\\left(\\frac{1}{2}\\right)^2 + \\left(\\frac{\\sqrt{3}}{2}\\right)^2 = \\frac{1}{4} + \\frac{3}{4}$.',
                    'Rezultatul e 1, pentru orice unghi — de aici și numele de identitate fundamentală.',
                ],
                'skill' => 'identitati-trigonometrice',
                'type' => ItemType::FillBlanks,
                'difficulty' => 2,
                'points' => 2,
                'payload' => [
                    'stem' => 'Completează identitatea fundamentală a trigonometriei.',
                    'stem_template' => 'sin²α + cos²α = ___',
                    'blanks' => [
                        ['key' => 'rezultat', 'options' => ['1', '0', '2', 'α']],
                    ],
                ],
                'answer' => ['values' => ['rezultat' => ['1']]],
            ],

            // ============ HOLDOUT: rezervat simulărilor ============
            // Nu se servesc niciodată în antrenament, ca estimarea de notă să
            // rămână validă (vezi Item::examPool()).
            [
                'ref' => 'seed:mat:mock:inecuatie-logaritmica',
                'hints' => [
                    'O inecuație logaritmică are DOUĂ condiții: domeniul de existență și inecuația propriu-zisă. Răspunsul e intersecția lor.',
                    'Domeniul: $x - 2 > 0$. Inecuația: $x - 2 < 3^2$, pentru că baza 3 e mai mare decât 1, deci sensul se păstrează.',
                    'Intersectează $x > 2$ cu $x < 11$. Variantele care ignoră domeniul sunt capcana itemului.',
                ],
                'skill' => 'logaritmi',
                'type' => ItemType::Mcq,
                'difficulty' => 4,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Mulțimea soluțiilor inecuației $\log_3(x-2) < 2$ este:',
                    'choices' => [
                        ['key' => 'a', 'latex' => '(2, 11)'],
                        ['key' => 'b', 'latex' => '(-\infty, 11)'],
                        ['key' => 'c', 'latex' => '(2, 9)'],
                        ['key' => 'd', 'latex' => '(0, 11)'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Condiția $x-2>0$ dă $x>2$; din $\log_3(x-2)<2$ rezultă $x-2<9$, deci $x<11$. Intersecția este $(2, 11)$.',
            ],
            [
                'ref' => 'seed:mat:mock:derivata-compusa',
                'hints' => [
                    'Ai un produs de două funcții, deci pornești de la regula produsului.',
                    '$f\'(x) = (e^{2x})\' \\sin x + e^{2x} (\\sin x)\'$, iar $(e^{2x})\' = 2e^{2x}$ prin regula lanțului.',
                    'În $x = 0$: $\\sin 0 = 0$ anulează primul termen, iar $e^0 \\cos 0 = 1$.',
                ],
                'skill' => 'derivate',
                'type' => ItemType::Numeric,
                'difficulty' => 4,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Calculează $f\'(0)$ pentru funcția dată.',
                    'stem_latex' => 'f(x) = e^{2x}\sin x',
                ],
                'answer' => ['value' => 1, 'tolerance' => 0.001],
                'explanation' => '$f\'(x) = 2e^{2x}\sin x + e^{2x}\cos x$, deci $f\'(0) = 0 + 1 = 1$.',
            ],

            // Banca de simulare, partea a doua: câte o mecanică pe secțiune, pe
            // competențe diferite, ca o simulare să nu măsoare o singură temă.
            [
                'ref' => 'seed:mat:mock:determinant-2x2',
                'skill' => 'matrici',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Calculează determinantul matricei $A$.',
                    'stem_latex' => 'A = \begin{pmatrix} 3 & -1 \\\\ 2 & 4 \end{pmatrix}',
                ],
                'answer' => ['value' => 14, 'tolerance' => 0],
                'hints' => [
                    'Determinantul unei matrice $2 \times 2$ e produsul diagonalei principale minus produsul diagonalei secundare.',
                    'Diagonala principală: $3 \cdot 4 = 12$. Diagonala secundară: $(-1) \cdot 2 = -2$.',
                    '$12 - (-2) = 14$. Greșeala tipică e să scazi $2$ în loc de $-2$ și să obții $10$.',
                ],
                'explanation' => '$\det A = 3 \cdot 4 - (-1) \cdot 2 = 12 + 2 = 14$. Semnul elementului $-1$ e capcana itemului: scăderea unui produs negativ devine adunare.',
            ],
            [
                'ref' => 'seed:mat:mock:integrala-polinom',
                'skill' => 'integrale',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Calculează integrala definită.',
                    'stem_latex' => '\int_0^1 (2x + 1)\,dx',
                ],
                'answer' => ['value' => 2, 'tolerance' => 0.001],
                'hints' => [
                    'Găsește întâi o primitivă a funcției $2x + 1$, apoi aplică formula Leibniz–Newton.',
                    'O primitivă este $F(x) = x^2 + x$.',
                    '$F(1) - F(0) = (1 + 1) - 0 = 2$.',
                ],
                'explanation' => 'O primitivă este $F(x) = x^2 + x$, deci $\int_0^1 (2x+1)\,dx = F(1) - F(0) = 2 - 0 = 2$.',
            ],
            [
                'ref' => 'seed:mat:mock:sin-5pi-6',
                'skill' => 'valori-trigonometrice',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Valoarea expresiei $\sin\dfrac{5\pi}{6}$ este:',
                    'choices' => [
                        ['key' => 'a', 'latex' => '\dfrac{1}{2}'],
                        ['key' => 'b', 'latex' => '-\dfrac{1}{2}'],
                        ['key' => 'c', 'latex' => '\dfrac{\sqrt{3}}{2}'],
                        ['key' => 'd', 'latex' => '-\dfrac{\sqrt{3}}{2}'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Unghiul $\frac{5\pi}{6}$ e în cadranul al II-lea. Ce semn are sinusul acolo?',
                    'Sinusul e pozitiv în cadranul al II-lea, iar $\sin(\pi - x) = \sin x$.',
                    '$\sin\frac{5\pi}{6} = \sin\left(\pi - \frac{\pi}{6}\right) = \sin\frac{\pi}{6} = \frac{1}{2}$.',
                ],
                'explanation' => 'Prin formula de reducere, $\sin\frac{5\pi}{6} = \sin\left(\pi - \frac{\pi}{6}\right) = \sin\frac{\pi}{6} = \frac{1}{2}$. Sinusul e pozitiv în cadranul al II-lea, deci variantele negative cad din start.',
            ],
            [
                'ref' => 'seed:mat:mock:volum-con',
                'skill' => 'corpuri',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Un con circular drept are raza bazei $r = 3$ cm și înălțimea $h = 4$ cm. Volumul conului este:',
                    'choices' => [
                        ['key' => 'a', 'latex' => '12\pi \text{ cm}^3'],
                        ['key' => 'b', 'latex' => '36\pi \text{ cm}^3'],
                        ['key' => 'c', 'latex' => '15\pi \text{ cm}^3'],
                        ['key' => 'd', 'latex' => '4\pi \text{ cm}^3'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Volumul conului e o treime din volumul cilindrului cu aceeași bază și aceeași înălțime.',
                    '$V = \frac{1}{3}\pi r^2 h$.',
                    '$\frac{1}{3} \cdot \pi \cdot 9 \cdot 4 = 12\pi$. Varianta $36\pi$ e volumul cilindrului — ai uitat factorul $\frac{1}{3}$.',
                ],
                'explanation' => '$V = \frac{1}{3}\pi r^2 h = \frac{1}{3}\pi \cdot 9 \cdot 4 = 12\pi$ cm³. Varianta $36\pi$ omite factorul $\frac{1}{3}$, iar $15\pi$ e aria laterală $\pi r g$ cu generatoarea $g = 5$.',
            ],
            [
                'ref' => 'seed:mat:mock:exponentiala-substitutie',
                'skill' => 'ecuatii-inecuatii',
                'type' => ItemType::NextStep,
                'difficulty' => 3,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Rezolvă ecuația. Care este pasul următor?',
                    'problem' => '4^x - 3 \cdot 2^x + 2 = 0',
                    'solved_steps' => [
                        'Notăm $t = 2^x$, cu $t > 0$; atunci $4^x = t^2$',
                        '$t^2 - 3t + 2 = 0$',
                    ],
                    'choices' => [
                        ['key' => 'a', 'latex' => 't_1 = 1,\ t_2 = 2'],
                        ['key' => 'b', 'latex' => 't_1 = -1,\ t_2 = -2'],
                        ['key' => 'c', 'latex' => 'x_1 = 1,\ x_2 = 2'],
                        ['key' => 'd', 'latex' => 't_1 = 1,\ t_2 = -2'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Ai acum o ecuație de gradul al II-lea în $t$. Rezolv-o, abia apoi te întorci la $x$.',
                    'Suma rădăcinilor e $3$, produsul e $2$.',
                    'Rădăcinile sunt $t = 1$ și $t = 2$, ambele pozitive, deci ambele acceptate. Varianta cu $x$ sare peste pasul întoarcerii la necunoscuta inițială.',
                ],
                'explanation' => 'Din $t^2 - 3t + 2 = 0$ rezultă $t_1 = 1$, $t_2 = 2$, ambele pozitive. Pasul următor e revenirea: $2^x = 1 \Rightarrow x = 0$ și $2^x = 2 \Rightarrow x = 1$. A scrie direct $x = 1, 2$ confundă valorile lui $t$ cu cele ale lui $x$.',
            ],
            [
                'ref' => 'seed:mat:mock:limita-nedeterminare',
                'skill' => 'limite',
                'type' => ItemType::NextStep,
                'difficulty' => 2,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Calculează limita. Care este pasul următor?',
                    'problem' => '\lim_{x \to 2} \frac{x^2 - 4}{x - 2}',
                    'solved_steps' => [
                        'Înlocuirea directă dă $\frac{0}{0}$ — caz de nedeterminare',
                    ],
                    'choices' => [
                        ['key' => 'a', 'text' => 'Descompun numărătorul: $\frac{(x-2)(x+2)}{x-2} = x + 2$, pentru $x \neq 2$'],
                        ['key' => 'b', 'text' => 'Limita este $0$, pentru că numărătorul se anulează'],
                        ['key' => 'c', 'text' => 'Limita nu există, pentru că numitorul se anulează'],
                        ['key' => 'd', 'text' => 'Limita este $+\infty$, pentru că se împarte la zero'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    '$\frac{0}{0}$ nu e un rezultat, ci un semn că expresia trebuie transformată.',
                    'Numărătorul e o diferență de pătrate: $x^2 - 4 = (x-2)(x+2)$.',
                    'După simplificarea cu $x - 2$ (permisă, pentru că $x \to 2$, dar $x \neq 2$) rămâne $\lim_{x\to 2}(x+2) = 4$.',
                ],
                'explanation' => 'Nedeterminarea $\frac{0}{0}$ se ridică prin descompunere: $\frac{x^2-4}{x-2} = x + 2$ pentru $x \neq 2$, deci limita e $4$. Variantele b, c și d tratează $\frac{0}{0}$ ca pe un rezultat — exact greșeala pe care o penalizează baremul.',
            ],
            [
                'ref' => 'seed:mat:mock:puncte-critice',
                'skill' => 'derivate',
                'type' => ItemType::NextStep,
                'difficulty' => 3,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Studiezi monotonia funcției. Care este pasul următor?',
                    'problem' => 'f(x) = x^3 - 3x,\ x \in \mathbb{R}',
                    'solved_steps' => [
                        '$f\'(x) = 3x^2 - 3$',
                    ],
                    'choices' => [
                        ['key' => 'a', 'latex' => "f'(x) = 0 \\Leftrightarrow x = -1 \\text{ sau } x = 1"],
                        ['key' => 'b', 'latex' => "f'(x) = 0 \\Leftrightarrow x = 0"],
                        ['key' => 'c', 'latex' => "f'(x) = 0 \\Leftrightarrow x = \\pm\\sqrt{3}"],
                        ['key' => 'd', 'latex' => "f'(x) = 0 \\Leftrightarrow x = 1"],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Monotonia se citește din semnul derivatei, iar semnul se schimbă doar în zerourile ei.',
                    '$3x^2 - 3 = 0 \Leftrightarrow x^2 = 1$.',
                    '$x^2 = 1$ are DOUĂ soluții, $x = \pm 1$. A păstra doar $x = 1$ pierde un interval de monotonie.',
                ],
                'explanation' => '$f\'(x) = 3(x^2 - 1) = 0 \Leftrightarrow x = \pm 1$. Tabelul de semn: $f\' > 0$ pe $(-\infty, -1)$ și $(1, +\infty)$, $f\' < 0$ pe $(-1, 1)$. Varianta $x = \pm\sqrt{3}$ rezolvă $f(x) = 0$, nu $f\'(x) = 0$.',
            ],
            [
                'ref' => 'seed:mat:mock:primitiva-sin-eroare',
                'skill' => 'integrale',
                'type' => ItemType::FindError,
                'difficulty' => 3,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Un elev a obținut rezultatul de mai jos și a primit 0 puncte. Unde este greșeala?',
                    'problem' => '\int_0^{\pi} \sin x\,dx = \big[\cos x\big]_0^{\pi} = \cos\pi - \cos 0 = -2',
                    'choices' => [
                        ['key' => 'a', 'text' => 'Primitiva lui $\sin x$ este $-\cos x$, nu $\cos x$; rezultatul corect e $2$'],
                        ['key' => 'b', 'text' => 'A calculat greșit $\cos\pi$, care este $1$'],
                        ['key' => 'c', 'text' => 'Trebuia scăzută valoarea din limita superioară din cea inferioară'],
                        ['key' => 'd', 'text' => 'Nu există nicio greșeală'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Un indiciu de plauzibilitate: $\sin x \ge 0$ pe $[0, \pi]$. Poate integrala să fie negativă?',
                    'Verifică primitiva derivând-o: $(\cos x)\' = -\sin x$, nu $\sin x$.',
                    'Cu $F(x) = -\cos x$: $F(\pi) - F(0) = 1 - (-1) = 2$.',
                ],
                'explanation' => 'Primitiva lui $\sin x$ este $-\cos x$, deci $\int_0^\pi \sin x\,dx = -\cos\pi + \cos 0 = 1 + 1 = 2$. Semnul negativ al rezultatului era deja un semnal: funcția e nenegativă pe interval, deci aria nu poate fi negativă.',
            ],
            [
                'ref' => 'seed:mat:mock:modul-complex-eroare',
                'skill' => 'numere-complexe',
                'type' => ItemType::FindError,
                'difficulty' => 2,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Elevul a scris calculul de mai jos și s-a oprit, spunând că modulul nu există. Unde este greșeala?',
                    'problem' => '|3 - 4i| = \sqrt{3^2 - 4^2} = \sqrt{-7}',
                    'choices' => [
                        ['key' => 'a', 'text' => 'Modulul este $\sqrt{a^2 + b^2}$: pătratele se ADUNĂ, deci $|3 - 4i| = \sqrt{9 + 16} = 5$'],
                        ['key' => 'b', 'text' => 'Trebuia scris $\sqrt{3^2 - (4i)^2}$'],
                        ['key' => 'c', 'text' => 'Modulul unui număr complex cu parte imaginară negativă nu există'],
                        ['key' => 'd', 'text' => 'Rezultatul corect este $\sqrt{7}$'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Modulul e o DISTANȚĂ — distanța de la origine la punctul $(3, -4)$. Poate o distanță să fie imaginară?',
                    'Formula este $|a + bi| = \sqrt{a^2 + b^2}$, cu $b = -4$.',
                    '$b^2 = (-4)^2 = 16$, deci $\sqrt{9 + 16} = 5$. Semnul lui $b$ dispare la ridicarea la pătrat.',
                ],
                'explanation' => '$|a + bi| = \sqrt{a^2 + b^2}$, deci $|3 - 4i| = \sqrt{9 + 16} = 5$. Modulul e distanța până la origine, deci e întotdeauna un număr real nenegativ.',
            ],
            [
                'ref' => 'seed:mat:mock:det-scalar-eroare',
                'skill' => 'matrici',
                'type' => ItemType::FindError,
                'difficulty' => 4,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Matricea $A$ este de ordinul 2 și $\det A = 3$. Un elev a scris raționamentul de mai jos. Unde este greșeala?',
                    'problem' => '\det(2A) = 2 \cdot \det A = 6',
                    'choices' => [
                        ['key' => 'a', 'text' => 'Factorul $2$ iese din FIECARE linie a matricei, deci $\det(2A) = 2^2 \det A = 12$'],
                        ['key' => 'b', 'text' => 'Determinantul nu se poate calcula fără elementele matricei'],
                        ['key' => 'c', 'text' => 'Corect este $\det(2A) = \det A + 2 = 5$'],
                        ['key' => 'd', 'text' => 'Nu există nicio greșeală'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Verifică pe un exemplu: ia $A = I_2$, cu $\det A = 1$. Cât este $\det(2I_2)$?',
                    '$2I_2$ are pe diagonală $2$ și $2$, deci determinantul e $4$, nu $2$.',
                    'Pentru o matrice de ordinul $n$, $\det(kA) = k^n \det A$. Aici $n = 2$.',
                ],
                'explanation' => 'Înmulțirea matricei cu $2$ înmulțește fiecare linie cu $2$, iar fiecare linie scoate câte un factor $2$ din determinant: $\det(2A) = 2^2 \cdot 3 = 12$. În general, $\det(kA) = k^n \det A$.',
            ],
            [
                'ref' => 'seed:mat:mock:extreme-interval-ordonare',
                'skill' => 'derivate',
                'type' => ItemType::OrderSteps,
                'difficulty' => 3,
                'points' => 4,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Ordonează pașii pentru a afla valoarea minimă și maximă a funcției pe interval.',
                    'problem' => 'f(x) = x^3 - 3x,\ x \in [0, 2]',
                    'steps' => [
                        ['key' => 's1', 'text' => 'Calculez $f\'(x) = 3x^2 - 3$'],
                        ['key' => 's2', 'text' => 'Rezolv $f\'(x) = 0$ și păstrez doar soluția din interval: $x = 1$'],
                        ['key' => 's3', 'text' => 'Calculez valorile: $f(0) = 0$, $f(1) = -2$, $f(2) = 2$'],
                        ['key' => 's4', 'text' => 'Compar valorile: minimul este $-2$, maximul este $2$'],
                    ],
                ],
                'answer' => ['order' => ['s1', 's2', 's3', 's4']],
                'hints' => [
                    'Pe un interval închis, extremele se caută în două locuri: punctele critice din interval și capetele intervalului.',
                    'Punctele critice vin din $f\'(x) = 0$, deci derivata e primul pas.',
                    'Doar după ce ai TOATE valorile (critice și capete) le poți compara.',
                ],
                'explanation' => 'Pe $[0, 2]$, extremele se ating fie în punctele critice din interval, fie la capete. Soluția $x = -1$ a ecuației $f\'(x) = 0$ e în afara intervalului și se elimină. Compararea $f(0) = 0$, $f(1) = -2$, $f(2) = 2$ dă minimul $-2$ și maximul $2$.',
            ],
            [
                'ref' => 'seed:mat:mock:inecuatie-exponentiala-ordonare',
                'skill' => 'ecuatii-inecuatii',
                'type' => ItemType::OrderSteps,
                'difficulty' => 3,
                'points' => 4,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Ordonează pașii rezolvării inecuației.',
                    'problem' => '\left(\tfrac{1}{2}\right)^x > 8',
                    'steps' => [
                        ['key' => 's1', 'text' => 'Scriu $8$ ca putere a lui $\frac{1}{2}$: $8 = \left(\frac{1}{2}\right)^{-3}$'],
                        ['key' => 's2', 'text' => 'Obțin $\left(\frac{1}{2}\right)^x > \left(\frac{1}{2}\right)^{-3}$'],
                        ['key' => 's3', 'text' => 'Baza $\frac{1}{2} < 1$, deci funcția e descrescătoare: sensul se schimbă, $x < -3$'],
                        ['key' => 's4', 'text' => 'Scriu mulțimea soluțiilor: $S = (-\infty, -3)$'],
                    ],
                ],
                'answer' => ['order' => ['s1', 's2', 's3', 's4']],
                'hints' => [
                    'Ca să compari exponenții, ambii membri trebuie scriși ca puteri ale ACELEIAȘI baze.',
                    '$8 = 2^3 = \left(\frac{1}{2}\right)^{-3}$.',
                    'La baza subunitară, inecuația dintre puteri își schimbă sensul când treci la exponenți.',
                ],
                'explanation' => 'După aducerea la aceeași bază, $\left(\frac{1}{2}\right)^x > \left(\frac{1}{2}\right)^{-3}$. Funcția exponențială cu baza $\frac{1}{2}$ e descrescătoare, deci sensul se inversează: $x < -3$. Uitarea schimbării de sens e greșeala cea mai frecventă la acest tip de item.',
            ],

            // ============ Funcții ============
            [
                'ref' => 'seed:mat:functii:domeniu-radical',
                'hints' => [
                    'Un radical de ordin par cere ca expresia de sub el să fie mai mare sau egală cu zero.',
                    'Rezolvă inecuația $x - 3 \\ge 0$. Atenție, e „mai mare SAU EGAL", nu strict.',
                    'Din $x \\ge 3$ rezultă domeniul $[3, +\\infty)$. Paranteza dreaptă e închisă, pentru că $x = 3$ dă $\\sqrt{0} = 0$, valoare perfect validă.',
                ],
                'skill' => 'functii',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Care este domeniul maxim de definiție al funcției $f(x) = \sqrt{x - 3}$?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '[3, +\infty)'],
                        ['key' => 'b', 'latex' => '(3, +\infty)'],
                        ['key' => 'c', 'latex' => '(-\infty, 3]'],
                        ['key' => 'd', 'latex' => '\mathbb{R}'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Radicalul de ordin par cere $x - 3 \ge 0$, deci $x \ge 3$. Capătul 3 se include: $\sqrt{0} = 0$ există.',
            ],
            [
                'ref' => 'seed:mat:functii:compunere',
                'hints' => [
                    'În $(f \\circ g)(x)$ se aplică întâi $g$, apoi $f$ asupra rezultatului. Ordinea nu e cea din scriere.',
                    'Calculează mai întâi $g(2)$, apoi pune valoarea obținută în $f$.',
                    '$g(2) = 2 \\cdot 2 + 1 = 5$, apoi $f(5) = 5^2 = 25$.',
                ],
                'skill' => 'functii',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Pentru $f(x) = x^2$ și $g(x) = 2x + 1$, calculează $(f \circ g)(2)$.',
                    'stem_latex' => '(f \circ g)(2) = f\big(g(2)\big)',
                ],
                'answer' => ['value' => 25, 'tolerance' => 0],
                'explanation' => 'Se aplică întâi funcția din dreapta: $g(2) = 5$, apoi $f(5) = 25$. Ordinea inversă ar da $g(f(2)) = g(4) = 9$.',
            ],
            [
                'ref' => 'seed:mat:functii:paritate-eroare',
                'hints' => [
                    'Testul pentru paritate e $f(-x)$ comparat cu $f(x)$. Calculează efectiv $f(-x)$, nu doar privi formula.',
                    '$f(-x) = (-x)^3 = -x^3 = -f(x)$. Ce înseamnă asta: pară sau impară?',
                    'Când $f(-x) = -f(x)$, funcția e IMPARĂ, cu grafic simetric față de origine. Pară ar însemna $f(-x) = f(x)$.',
                ],
                'skill' => 'functii',
                'type' => ItemType::FindError,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Un elev a scris: „Funcția $f(x) = x^3$ este pară, pentru că exponentul e un număr întreg." Unde este greșeala?',
                    'problem' => 'f(x) = x^3, \quad f(-x) = ?',
                    'choices' => [
                        ['key' => 'a', 'text' => 'Funcția este impară: $f(-x) = -x^3 = -f(x)$'],
                        ['key' => 'b', 'text' => 'Funcția nu e nici pară, nici impară'],
                        ['key' => 'c', 'text' => 'Concluzia e corectă, dar justificarea e incompletă'],
                        ['key' => 'd', 'text' => 'Funcția nu e definită pentru $x$ negativ'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Paritatea se decide comparând $f(-x)$ cu $f(x)$, nu după exponent. Aici $f(-x) = -f(x)$, deci funcția e impară, cu grafic simetric față de origine.',
            ],

            // ============ Limite ============
            [
                'ref' => 'seed:mat:limite:nedeterminare-0-0',
                'hints' => [
                    'Înlocuind direct $x = 2$ obții $\\frac{0}{0}$ — o nedeterminare, nu un rezultat.',
                    'Când numărătorul se anulează în același punct ca numitorul, amândoi se divid prin $(x - 2)$. Descompune numărătorul.',
                    '$x^2 - 4 = (x-2)(x+2)$. După simplificare rămâne $x + 2$, care în $x = 2$ dă 4.',
                ],
                'skill' => 'limite',
                'type' => ItemType::Numeric,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Calculează limita.',
                    'stem_latex' => '\lim_{x \to 2} \frac{x^2 - 4}{x - 2}',
                ],
                'answer' => ['value' => 4, 'tolerance' => 0],
                'explanation' => 'Substituția directă dă $\frac{0}{0}$. Descompunem: $\frac{(x-2)(x+2)}{x-2} = x + 2 \to 4$. Nedeterminarea $\frac{0}{0}$ nu înseamnă că limita nu există.',
            ],
            [
                'ref' => 'seed:mat:limite:infinit-grade',
                'hints' => [
                    'La $\\frac{\\infty}{\\infty}$ cu polinoame, contează doar termenii de grad maxim. Restul devin neglijabili.',
                    'Compară gradul numărătorului cu al numitorului. Aici ambele sunt de gradul 2.',
                    'La grade egale, limita e raportul coeficienților dominanți: $\\frac{3}{5}$.',
                ],
                'skill' => 'limite',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Calculează $\displaystyle\lim_{x \to \infty} \frac{3x^2 + 7x - 1}{5x^2 - 2}$.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '\frac{3}{5}'],
                        ['key' => 'b', 'latex' => '+\infty'],
                        ['key' => 'c', 'latex' => '0'],
                        ['key' => 'd', 'latex' => '\frac{7}{-2}'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'La $x \to \infty$ decid termenii de grad maxim. Gradele fiind egale, limita e raportul coeficienților dominanți: $\frac{3}{5}$.',
            ],
            [
                'ref' => 'seed:mat:limite:ordonare-metoda',
                'hints' => [
                    'Nu începe cu descompunerea. Primul pas la orice limită e substituția directă — ea îți spune DACĂ ai o nedeterminare.',
                    'Abia după ce constați forma $\\frac{0}{0}$ alegi metoda de eliminare a ei.',
                    'Ordinea: substituție → recunoști nedeterminarea → descompui → simplifici → substitui din nou.',
                ],
                'skill' => 'limite',
                'type' => ItemType::OrderSteps,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Ordonează pașii pentru calculul limitei.',
                    'problem' => '\lim_{x \to 3} \frac{x^2 - 9}{x - 3}',
                    'steps' => [
                        ['key' => 's1', 'text' => 'Înlocuiesc $x = 3$ și obțin $\frac{0}{0}$'],
                        ['key' => 's2', 'text' => 'Recunosc nedeterminarea și caut factorul comun'],
                        ['key' => 's3', 'text' => 'Descompun numărătorul: $x^2 - 9 = (x-3)(x+3)$'],
                        ['key' => 's4', 'text' => 'Simplific prin $(x - 3)$ și rămâne $x + 3$'],
                        ['key' => 's5', 'text' => 'Înlocuiesc din nou $x = 3$ și obțin 6'],
                    ],
                ],
                'answer' => ['order' => ['s1', 's2', 's3', 's4', 's5']],
                'explanation' => 'Substituția directă e întotdeauna primul pas: fără ea nu știi dacă ai nevoie de vreo metodă. Sări peste ea și riști să descompui inutil o limită care se calcula direct.',
            ],

            // ============ Cercul ============
            [
                'ref' => 'seed:mat:cerc:arie-din-diametru',
                'hints' => [
                    'Enunțul dă DIAMETRUL, nu raza. Formula ariei cere raza.',
                    'Raza e jumătate din diametru: $r = 5$ cm.',
                    '$A = \\pi r^2 = \\pi \\cdot 25 = 25\\pi$. Dacă ai obținut $100\\pi$, ai folosit diametrul în locul razei.',
                ],
                'skill' => 'cerc',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Un cerc are diametrul de $10$ cm. Care este aria discului?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '25\pi \text{ cm}^2'],
                        ['key' => 'b', 'latex' => '100\pi \text{ cm}^2'],
                        ['key' => 'c', 'latex' => '10\pi \text{ cm}^2'],
                        ['key' => 'd', 'latex' => '5\pi \text{ cm}^2'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Din $d = 10$ rezultă $r = 5$, deci $A = \pi \cdot 5^2 = 25\pi$ cm². Varianta $100\pi$ apare când se folosește diametrul în formulă — greșeala cea mai frecventă la acest tip de item.',
            ],
            [
                'ref' => 'seed:mat:cerc:sector-120',
                'hints' => [
                    'Un sector de $\\alpha$ grade e fracția $\\frac{\\alpha}{360}$ din disc.',
                    '$\\frac{120}{360} = \\frac{1}{3}$. Calculează întâi aria întregului disc.',
                    'Aria discului e $\\pi \\cdot 6^2 = 36\\pi$, iar a treia parte din ea e $12\\pi$.',
                ],
                'skill' => 'cerc',
                'type' => ItemType::Numeric,
                'difficulty' => 3,
                'payload' => ['unit' => 'π',
                    'stem' => 'Un sector de cerc are raza $6$ și unghiul la centru $120°$. Calculează aria sectorului, exprimată ca multiplu de $\pi$ (scrie doar coeficientul).',
                    'stem_latex' => 'A_{\text{sector}} = \frac{\alpha}{360} \cdot \pi r^2',
                ],
                'answer' => ['value' => 12, 'tolerance' => 0],
                'explanation' => 'Sectorul e $\frac{120}{360} = \frac{1}{3}$ din disc. Aria discului fiind $36\pi$, sectorul are $12\pi$.',
            ],
            [
                'ref' => 'seed:mat:cerc:unghi-inscris',
                'hints' => [
                    'Unghiul la centru și cel înscris nu sunt egale, chiar dacă se sprijină pe același arc.',
                    'Cel de la centru e DUBLUL celui înscris.',
                    'Dacă unghiul înscris are $35°$, cel la centru are $70°$.',
                ],
                'skill' => 'cerc',
                'type' => ItemType::FillBlanks,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Completează relația dintre unghiul la centru și unghiul înscris care se sprijină pe același arc.',
                    'stem_template' => 'unghiul la centru = ___ · unghiul înscris',
                    'blanks' => [
                        ['key' => 'factor', 'options' => ['2', '1', '1/2', '4']],
                    ],
                ],
                'answer' => ['values' => ['factor' => ['2']]],
                'explanation' => 'Unghiul la centru este dublul unghiului înscris pe același arc. Consecința folosită cel mai des: un unghi înscris care se sprijină pe diametru are $90°$.',
            ],

            // ============ Numere complexe ============
            [
                'ref' => 'seed:mat:complexe:puteri-i',
                'hints' => [
                    'Puterile lui $i$ se repetă din 4 în 4. Nu calcula nimic lung.',
                    'Împarte exponentul la 4 și uită-te doar la rest: $2027 : 4 = 506$ rest $3$.',
                    '$i^3 = -i$, deci $i^{2027} = -i$.',
                ],
                'skill' => 'numere-complexe',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Calculează $i^{2027}$.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '-i'],
                        ['key' => 'b', 'latex' => 'i'],
                        ['key' => 'c', 'latex' => '-1'],
                        ['key' => 'd', 'latex' => '1'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Ciclul are perioada 4. Restul împărțirii $2027 : 4$ este 3, iar $i^3 = -i$.',
            ],
            [
                'ref' => 'seed:mat:complexe:impartire-pas',
                'hints' => [
                    'Nu poți împărți direct la un număr complex. Trebuie să faci numitorul real.',
                    'Amplifică fracția cu conjugatul numitorului — conjugatul lui $1 - i$ este $1 + i$.',
                    'Produsul $(1-i)(1+i) = 1 - i^2 = 2$, deci numitorul devine real. Cu $1 - i$ la numărător nu se schimbă nimic.',
                ],
                'skill' => 'numere-complexe',
                'type' => ItemType::NextStep,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Care este pasul următor pentru a aduce fracția la forma algebrică?',
                    'problem' => '\frac{2 + 3i}{1 - i}',
                    'choices' => [
                        ['key' => 'a', 'latex' => '\text{amplific cu } 1 + i'],
                        ['key' => 'b', 'latex' => '\text{amplific cu } 1 - i'],
                        ['key' => 'c', 'latex' => '\text{împart fiecare termen la } 1 - i'],
                        ['key' => 'd', 'latex' => '\text{înlocuiesc } i = \sqrt{-1}'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Amplificarea cu conjugatul $1 + i$ face numitorul real: $(1-i)(1+i) = 2$. Amplificarea cu $1 - i$ ar da $(1-i)^2 = -2i$, tot complex.',
            ],

            // ============ Trigonometrie ============
            [
                'ref' => 'seed:mat:trig:reducere-cadran-2',
                'hints' => [
                    'Stabilește întâi cadranul: $150°$ este între $90°$ și $180°$, deci cadranul II.',
                    'În cadranul II sinusul e pozitiv. Scrie $150° = 180° - 30°$.',
                    '$\\sin(180° - \\alpha) = \\sin \\alpha$, deci $\\sin 150° = \\sin 30° = \\frac{1}{2}$.',
                ],
                'skill' => 'valori-trigonometrice',
                'type' => ItemType::Numeric,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Calculează valoarea expresiei.',
                    'stem_latex' => '\sin 150^\circ',
                ],
                'answer' => ['value' => 0.5, 'accept' => [0.5], 'tolerance' => 0.001],
                'explanation' => 'Reducem la primul cadran: $\sin 150° = \sin(180° - 30°) = \sin 30° = \frac{1}{2}$. Semnul e pozitiv, pentru că în cadranul II sinusul e pozitiv.',
            ],
            [
                'ref' => 'seed:mat:trig:unghi-dublu',
                'hints' => [
                    'Ai nevoie și de cosinus. Scoate-l din identitatea fundamentală $\\sin^2 + \\cos^2 = 1$.',
                    '$\\cos^2 \\alpha = 1 - \\frac{9}{25} = \\frac{16}{25}$. Cadranul I îți spune ce semn să alegi.',
                    'Cu $\\cos \\alpha = \\frac{4}{5}$, formula $\\sin 2\\alpha = 2 \\sin \\alpha \\cos \\alpha$ dă $\\frac{24}{25}$.',
                ],
                'skill' => 'identitati-trigonometrice',
                'type' => ItemType::Numeric,
                'difficulty' => 4,
                'points' => 2,
                'payload' => [
                    'stem' => 'Se dă $\sin \alpha = \frac{3}{5}$, cu $\alpha$ în cadranul I. Calculează $\sin 2\alpha$ (rezultat zecimal).',
                    'stem_latex' => '\sin 2\alpha = 2 \sin \alpha \cos \alpha',
                ],
                'answer' => ['value' => 0.96, 'tolerance' => 0.001],
                'explanation' => 'Din identitatea fundamentală, $\cos \alpha = \frac{4}{5}$ (pozitiv, fiind cadranul I). Atunci $\sin 2\alpha = 2 \cdot \frac{3}{5} \cdot \frac{4}{5} = \frac{24}{25} = 0{,}96$.',
            ],
            [
                'ref' => 'seed:mat:trig:suma-unghiuri-eroare',
                'hints' => [
                    'Verifică afirmația cu numere concrete, nu cu formule: ia $a = b = 30°$.',
                    'Stânga: $\\sin 60° \\approx 0{,}866$. Dreapta: $0{,}5 + 0{,}5 = 1$. Nu sunt egale.',
                    'Sinusul NU e o funcție liniară. Formula corectă e $\\sin a \\cos b + \\cos a \\sin b$.',
                ],
                'skill' => 'identitati-trigonometrice',
                'type' => ItemType::FindError,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Un elev a scris rezolvarea de mai jos. Unde este greșeala?',
                    'problem' => '\sin(a + b) = \sin a + \sin b',
                    'choices' => [
                        ['key' => 'a', 'text' => 'Sinusul nu e liniar: $\sin(a+b) = \sin a \cos b + \cos a \sin b$'],
                        ['key' => 'b', 'text' => 'Formula e corectă doar pentru unghiuri ascuțite'],
                        ['key' => 'c', 'text' => 'Lipsește condiția $a + b < 90°$'],
                        ['key' => 'd', 'text' => 'Nu există nicio greșeală'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Se verifică imediat cu $a = b = 30°$: stânga dă $\frac{\sqrt{3}}{2} \approx 0{,}87$, dreapta dă $1$. Aceeași capcană apare la $\cos$, la $\operatorname{tg}$ și la logaritmi.',
            ],

            // ============ Triunghiuri ============
            [
                'ref' => 'seed:mat:triunghi:alegerea-teoremei',
                'hints' => [
                    'Uită-te la ce ți se dă: două laturi și unghiul DINTRE ele. Teorema sinusurilor are nevoie de un unghi opus unei laturi cunoscute.',
                    'Teorema cosinusului e singura care leagă trei laturi și un unghi cuprins între două dintre ele.',
                    '$a^2 = b^2 + c^2 - 2bc\\cos A$. Pentru $A = 90°$ termenul cu cosinus dispare și rămâne Pitagora.',
                ],
                'skill' => 'triunghiuri',
                'type' => ItemType::NextStep,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'În triunghiul $ABC$ se cunosc $b = 5$, $c = 8$ și $\widehat{A} = 60°$. Ce scrii ca să afli latura $a$?',
                    'problem' => 'b = 5, \quad c = 8, \quad \widehat{A} = 60^\circ',
                    'choices' => [
                        ['key' => 'a', 'latex' => 'a^2 = b^2 + c^2 - 2bc\cos A'],
                        ['key' => 'b', 'latex' => 'a^2 = b^2 + c^2'],
                        ['key' => 'c', 'latex' => '\frac{a}{\sin A} = \frac{b}{\sin B}'],
                        ['key' => 'd', 'latex' => 'a = b + c - 2\sqrt{bc}'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Cu două laturi și unghiul cuprins între ele se aplică teorema cosinusului. Pitagora ar cere un unghi drept, iar teorema sinusurilor ar cere un unghi opus unei laturi cunoscute — aici $\widehat{B}$ și $\widehat{C}$ sunt necunoscute.',
            ],
            [
                'ref' => 'seed:mat:triunghi:potrivire-arii',
                'hints' => [
                    'Fiecare formulă folosește exact datele pe care le ai — nu alege după cât de familiară ți se pare.',
                    'Formula lui Heron e singura care nu conține niciun unghi și are nevoie de semiperimetru.',
                    'Formula cu $\\sin$ cere unghiul cuprins ÎNTRE cele două laturi date, iar cea cu $\\frac{b \\cdot h}{2}$ cere o înălțime.',
                ],
                'skill' => 'triunghiuri',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Potrivește datele din enunț cu formula de arie potrivită.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'O latură și înălțimea pe ea'],
                        ['key' => 'l2', 'text' => 'Două laturi și unghiul dintre ele'],
                        ['key' => 'l3', 'text' => 'Toate cele trei laturi'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'latex' => '\sqrt{p(p-a)(p-b)(p-c)}'],
                        ['key' => 'r2', 'latex' => '\frac{b \cdot h}{2}'],
                        ['key' => 'r3', 'latex' => '\frac{1}{2}ab\sin C'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r2', 'l2' => 'r3', 'l3' => 'r1']],
                'explanation' => 'Alegerea formulei se face după datele din enunț, nu după preferință. Heron e singura fără unghiuri; formula cu sinus cere unghiul cuprins între laturile date.',
            ],

            // ============ Corpuri geometrice ============
            [
                'ref' => 'seed:mat:corpuri:con-generatoare',
                'hints' => [
                    'Aria laterală a conului se calculează cu GENERATOAREA, nu cu înălțimea. Enunțul îți dă înălțimea.',
                    'Raza, înălțimea și generatoarea formează un triunghi dreptunghic: $G^2 = r^2 + h^2$.',
                    '$G = \\sqrt{9 + 16} = 5$, deci $A_l = \\pi r G = \\pi \\cdot 3 \\cdot 5 = 15\\pi$.',
                ],
                'skill' => 'corpuri',
                'type' => ItemType::Numeric,
                'difficulty' => 4,
                'points' => 2,
                'payload' => ['unit' => 'π',
                    'stem' => 'Un con are raza $3$ și înălțimea $4$. Calculează aria laterală ca multiplu de $\pi$ (scrie doar coeficientul).',
                    'stem_latex' => 'A_l = \pi r G',
                ],
                'answer' => ['value' => 15, 'tolerance' => 0],
                'explanation' => 'Întâi generatoarea: $G = \sqrt{3^2 + 4^2} = 5$. Apoi $A_l = \pi \cdot 3 \cdot 5 = 15\pi$. Folosind înălțimea în locul generatoarei s-ar obține greșit $12\pi$.',
            ],
            [
                'ref' => 'seed:mat:corpuri:piramida-treime-eroare',
                'hints' => [
                    'Compară formula folosită de elev cu cea a prismei. Ce lipsește?',
                    'Corpurile ascuțite — piramida și conul — au o TREIME din volumul corpului drept cu aceeași bază și aceeași înălțime.',
                    'Formula corectă e $V = \\frac{A_b \\cdot h}{3}$, deci rezultatul elevului e de trei ori mai mare.',
                ],
                'skill' => 'corpuri',
                'type' => ItemType::FindError,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Un elev calculează volumul unei piramide cu baza de arie $12$ și înălțimea $5$, și scrie rezultatul $60$. Unde este greșeala?',
                    'problem' => 'V_{\text{piramidă}} = A_b \cdot h = 12 \cdot 5 = 60',
                    'choices' => [
                        ['key' => 'a', 'text' => 'A omis factorul $\frac{1}{3}$: volumul corect este $20$'],
                        ['key' => 'b', 'text' => 'Trebuia să folosească apotema, nu înălțimea'],
                        ['key' => 'c', 'text' => 'Aria bazei trebuia înmulțită cu 2'],
                        ['key' => 'd', 'text' => 'Rezultatul e corect, lipsește doar unitatea de măsură'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Formula scrisă e a prismei. Piramida are $V = \frac{A_b \cdot h}{3} = \frac{12 \cdot 5}{3} = 20$. Apotema intervine la aria laterală, nu la volum.',
            ],
        ];
    }

    /**
     * Algebră: numere reale, ecuații și inecuații, logaritmi, numere complexe,
     * matrici și determinanți.
     *
     * @return list<array<string, mixed>>
     */
    private function itemsAlgebra(): array
    {
        return [
            // ---------- Numere reale ----------
            [
                'ref' => 'seed:mat:reale:puteri-negativ',
                'hints' => [
                    'Exponentul negativ nu face numărul negativ. Înseamnă „inversul".',
                    '$a^{-n} = \\frac{1}{a^n}$. Calculează întâi $2^3$.',
                    '$2^{-3} = \\frac{1}{2^3} = \\frac{1}{8} = 0{,}125$.',
                ],
                'skill' => 'numere-reale',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'payload' => ['stem' => 'Calculează valoarea puterii.', 'stem_latex' => '2^{-3}'],
                'answer' => ['value' => 0.125, 'tolerance' => 0.0001],
                'explanation' => 'Exponentul negativ dă inversul: $2^{-3} = \frac{1}{2^3} = \frac{1}{8}$. Rezultatul $-8$ apare când se confundă exponentul negativ cu semnul bazei.',
            ],
            [
                'ref' => 'seed:mat:reale:modul-ecuatie',
                'hints' => [
                    'Modulul e distanța până la zero, deci e întotdeauna pozitiv. Două numere diferite pot avea același modul.',
                    '$|x - 2| = 5$ înseamnă că $x - 2$ e la distanța 5 de zero: fie $5$, fie $-5$.',
                    'Din $x - 2 = 5$ iese $x = 7$; din $x - 2 = -5$ iese $x = -3$. Suma soluțiilor e 4.',
                ],
                'skill' => 'numere-reale',
                'type' => ItemType::Numeric,
                'difficulty' => 3,
                'payload' => ['stem' => 'Rezolvă ecuația și calculează SUMA soluțiilor.', 'stem_latex' => '|x - 2| = 5'],
                'answer' => ['value' => 4, 'tolerance' => 0],
                'explanation' => 'O ecuație cu modul are două ramuri: $x - 2 = 5 \Rightarrow x = 7$ și $x - 2 = -5 \Rightarrow x = -3$. Suma e $7 + (-3) = 4$. Uitarea ramurii negative e greșeala clasică.',
            ],
            [
                'ref' => 'seed:mat:reale:rationalizare-pas',
                'hints' => [
                    'Nu se lasă radical la numitor. Trebuie să-l faci să dispară.',
                    'Amplifică cu CONJUGATA numitorului — aceeași expresie cu semnul schimbat.',
                    'Conjugata lui $\\sqrt{5} - 2$ este $\\sqrt{5} + 2$; produsul lor dă $5 - 4 = 1$, fără radical.',
                ],
                'skill' => 'numere-reale',
                'type' => ItemType::NextStep,
                'difficulty' => 3,
                'points' => 2,
                'payload' => [
                    'stem' => 'Care este pasul următor pentru raționalizarea fracției?',
                    'problem' => '\frac{1}{\sqrt{5} - 2}',
                    'choices' => [
                        ['key' => 'a', 'latex' => '\text{amplific cu } \sqrt{5} + 2'],
                        ['key' => 'b', 'latex' => '\text{amplific cu } \sqrt{5} - 2'],
                        ['key' => 'c', 'latex' => '\text{amplific cu } \sqrt{5}'],
                        ['key' => 'd', 'latex' => '\text{ridic totul la pătrat}'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Conjugata face să apară diferența de pătrate: $(\sqrt{5}-2)(\sqrt{5}+2) = 5 - 4 = 1$. Amplificarea cu $\sqrt{5}$ ar lăsa tot un radical la numitor.',
            ],
            [
                'ref' => 'seed:mat:reale:ordinea-operatiilor',
                'hints' => [
                    'Puterile se calculează înaintea înmulțirii, iar înmulțirea înaintea adunării.',
                    'Calculează întâi $4^2 = 16$, apoi $3 \\cdot 16$, abia la final aduni 2.',
                    '$2 + 3 \\cdot 16 = 2 + 48 = 50$. Dacă ai obținut 80, ai adunat înainte să înmulțești.',
                ],
                'skill' => 'numere-reale',
                'type' => ItemType::Numeric,
                'difficulty' => 1,
                'payload' => ['stem' => 'Calculează expresia.', 'stem_latex' => '2 + 3 \cdot 4^2'],
                'answer' => ['value' => 50, 'tolerance' => 0],
                'explanation' => 'Ordinea: puteri → înmulțiri → adunări. Deci $4^2 = 16$, apoi $3 \cdot 16 = 48$, apoi $2 + 48 = 50$. Rezultatul 80 vine din $(2+3) \cdot 16$.',
            ],
            [
                'ref' => 'seed:mat:reale:procent-eroare',
                'hints' => [
                    'O creștere cu 20% urmată de o scădere cu 20% nu readuce la valoarea inițială.',
                    'Cele două procente se aplică la baze DIFERITE: al doilea se calculează din valoarea deja crescută.',
                    '$100 \\to 120 \\to 120 - 24 = 96$. Se pierde 4%, nu 0%.',
                ],
                'skill' => 'numere-reale',
                'type' => ItemType::FindError,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Un preț crește cu 20%, apoi noul preț scade cu 20%. Un elev afirmă că prețul revine la valoarea inițială. Unde este greșeala?',
                    'problem' => '100 \xrightarrow{+20\%} ? \xrightarrow{-20\%} ?',
                    'choices' => [
                        ['key' => 'a', 'text' => 'Procentele se aplică la baze diferite: se ajunge la 96, nu la 100'],
                        ['key' => 'b', 'text' => 'Scăderea trebuia făcută cu 25% ca să revină'],
                        ['key' => 'c', 'text' => 'Afirmația e corectă, operațiile se anulează'],
                        ['key' => 'd', 'text' => 'Prețul crește la 104'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Creșterea se aplică la 100 (deci $+20$), iar scăderea la 120 (deci $-24$). Rezultatul e 96. Procentele nu se adună și nu se anulează între ele.',
            ],

            // ---------- Ecuații și inecuații ----------
            [
                'ref' => 'seed:mat:ecuatii:discriminant',
                'hints' => [
                    'Numărul de soluții reale îl decide discriminantul, nu rezolvarea efectivă.',
                    '$\\Delta = b^2 - 4ac$. Aici $a = 1$, $b = -4$, $c = 5$.',
                    '$\\Delta = 16 - 20 = -4 < 0$, deci ecuația nu are soluții reale.',
                ],
                'skill' => 'ecuatii-inecuatii',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Câte soluții REALE are ecuația $x^2 - 4x + 5 = 0$?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '\text{niciuna}'],
                        ['key' => 'b', 'latex' => '\text{una}'],
                        ['key' => 'c', 'latex' => '\text{două}'],
                        ['key' => 'd', 'latex' => '\text{o infinitate}'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '$\Delta = (-4)^2 - 4 \cdot 1 \cdot 5 = -4 < 0$, deci nu există soluții reale. În mulțimea numerelor complexe ar exista două.',
            ],
            [
                'ref' => 'seed:mat:ecuatii:viete-suma',
                'hints' => [
                    'Nu e nevoie să rezolvi ecuația ca să afli suma rădăcinilor.',
                    'Relațiile lui Viète: $x_1 + x_2 = -\\frac{b}{a}$ și $x_1 x_2 = \\frac{c}{a}$.',
                    'Aici $a = 2$, $b = -10$, deci suma este $\\frac{10}{2} = 5$.',
                ],
                'skill' => 'ecuatii-inecuatii',
                'type' => ItemType::Numeric,
                'difficulty' => 3,
                'payload' => ['stem' => 'Calculează suma rădăcinilor, fără să rezolvi ecuația.', 'stem_latex' => '2x^2 - 10x + 8 = 0'],
                'answer' => ['value' => 5, 'tolerance' => 0],
                'explanation' => 'Din Viète, $x_1 + x_2 = -\frac{b}{a} = \frac{10}{2} = 5$. Atenție la semn: formula are minus în față, iar $b$ e deja negativ.',
            ],
            [
                'ref' => 'seed:mat:ecuatii:inecuatie-semn',
                'hints' => [
                    'Când împarți o inecuație la un număr NEGATIV, sensul inegalității se schimbă.',
                    'Din $-2x > 6$, împărțind la $-2$, obții $x < -3$, nu $x > -3$.',
                    'Verifică: $x = -4$ dă $-2 \\cdot (-4) = 8 > 6$. Adevărat. Deci soluția e $x < -3$.',
                ],
                'skill' => 'ecuatii-inecuatii',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Rezolvă inecuația $-2x > 6$.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => 'x < -3'],
                        ['key' => 'b', 'latex' => 'x > -3'],
                        ['key' => 'c', 'latex' => 'x > 3'],
                        ['key' => 'd', 'latex' => 'x < 3'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Împărțirea la $-2$ schimbă sensul: $x < -3$. E cea mai frecventă greșeală la inecuații și se verifică într-o secundă cu o valoare de probă.',
            ],
            [
                'ref' => 'seed:mat:ecuatii:sistem-ordonare',
                'hints' => [
                    'Metoda substituției: exprimi o necunoscută din ecuația cea mai simplă.',
                    'Din prima ecuație scoți $y$, apoi îl înlocuiești în a doua ca să rămână o singură necunoscută.',
                    'Ordinea: exprim $y$ → substitui → rezolv în $x$ → revin pentru $y$ → verific.',
                ],
                'skill' => 'ecuatii-inecuatii',
                'type' => ItemType::OrderSteps,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Ordonează pașii rezolvării prin substituție.',
                    'problem' => '\begin{cases} x + y = 5 \\\\ 2x - y = 1 \end{cases}',
                    'steps' => [
                        ['key' => 's1', 'text' => 'Din prima ecuație exprim $y = 5 - x$'],
                        ['key' => 's2', 'text' => 'Înlocuiesc în a doua: $2x - (5 - x) = 1$'],
                        ['key' => 's3', 'text' => 'Rezolv: $3x = 6$, deci $x = 2$'],
                        ['key' => 's4', 'text' => 'Revin și obțin $y = 5 - 2 = 3$'],
                        ['key' => 's5', 'text' => 'Verific perechea $(2, 3)$ în ambele ecuații'],
                    ],
                ],
                'answer' => ['order' => ['s1', 's2', 's3', 's4', 's5']],
                'explanation' => 'Verificarea finală nu e opțională: la BAC, o soluție neverificată care nu respectă o condiție din enunț pierde punctaj.',
            ],
            [
                'ref' => 'seed:mat:ecuatii:irationala-conditie',
                'hints' => [
                    'Ridicarea la pătrat poate introduce soluții false — cele care nu verifică ecuația inițială.',
                    'După ce obții soluțiile, pune fiecare în ecuația de la început.',
                    '$\\sqrt{x+6} = x$ cere $x \\ge 0$. Din $x^2 - x - 6 = 0$ ies $3$ și $-2$, dar $-2 < 0$ se elimină.',
                ],
                'skill' => 'ecuatii-inecuatii',
                'type' => ItemType::FindError,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'Un elev a ridicat la pătrat, a obținut $x_1 = 3$ și $x_2 = -2$ și a scris ambele ca soluții. A primit jumătate din punctaj. De ce?',
                    'problem' => '\sqrt{x + 6} = x',
                    'choices' => [
                        ['key' => 'a', 'text' => '$x = -2$ nu verifică ecuația: radicalul e pozitiv, deci $x \ge 0$'],
                        ['key' => 'b', 'text' => 'A greșit ridicarea la pătrat'],
                        ['key' => 'c', 'text' => 'Trebuia să ridice la cub'],
                        ['key' => 'd', 'text' => 'Ambele soluții sunt corecte, punctajul e greșit'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Ridicarea la pătrat nu e o operație reversibilă: poate adăuga soluții. Cu $x = -2$, stânga dă $\sqrt{4} = 2$, dreapta dă $-2$. Nu e egalitate.',
            ],
            [
                'ref' => 'seed:mat:ecuatii:factorizare-completare',
                'hints' => [
                    'Ecuația nu are termen liber, deci se poate da factor comun.',
                    '$x^2 - 5x = x(x - 5)$. Un produs e zero când unul dintre factori e zero.',
                    'Soluțiile sunt $x = 0$ și $x = 5$. Împărțirea prin $x$ ar pierde soluția $x = 0$.',
                ],
                'skill' => 'ecuatii-inecuatii',
                'type' => ItemType::FillBlanks,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Completează factorizarea, apoi observă câte soluții are ecuația $x^2 - 5x = 0$.',
                    'stem_template' => 'x^2 - 5x = x · ( ___ )',
                    'blanks' => [['key' => 'factor', 'options' => ['x - 5', 'x + 5', '5 - x', 'x - 25']]],
                ],
                'answer' => ['values' => ['factor' => ['x - 5', 'x-5']]],
                'explanation' => '$x^2 - 5x = x(x-5)$, deci soluțiile sunt $0$ și $5$. Dacă împarți ecuația prin $x$, pierzi soluția $x = 0$ — greșeală care costă jumătate din punctaj.',
            ],
            [
                'ref' => 'seed:mat:ecuatii:potrivire-metode',
                'hints' => [
                    'Fiecare tip de ecuație are o primă mișcare specifică — nu începe cu aceeași metodă peste tot.',
                    'La ecuația cu radical scapi de radical; la cea cu modul desfaci în cazuri.',
                    'Ecuația de gradul II fără termen liber se rezolvă prin factor comun, nu cu discriminantul.',
                ],
                'skill' => 'ecuatii-inecuatii',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Potrivește ecuația cu primul pas al rezolvării.',
                    'left' => [
                        ['key' => 'l1', 'latex' => '\sqrt{x + 1} = 3'],
                        ['key' => 'l2', 'latex' => '|x - 4| = 2'],
                        ['key' => 'l3', 'latex' => 'x^2 - 7x = 0'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'Dau factor comun'],
                        ['key' => 'r2', 'text' => 'Ridic la pătrat, apoi verific soluțiile'],
                        ['key' => 'r3', 'text' => 'Desfac în două cazuri, cu $+$ și cu $-$'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r2', 'l2' => 'r3', 'l3' => 'r1']],
                'explanation' => 'Recunoașterea tipului de ecuație decide metoda. Aplicarea discriminantului la $x^2 - 7x = 0$ funcționează, dar e mai lungă și mai expusă la erori decât factorul comun.',
            ],
            // ---------- Logaritmi ----------
            [
                'ref' => 'seed:mat:log:definitie-valoare',
                'hints' => [
                    '$\\log_a b$ răspunde la întrebarea: la ce putere trebuie ridicat $a$ ca să obțin $b$?',
                    'Aici: la ce putere ridic 3 ca să obțin 81?',
                    '$3^4 = 81$, deci $\\log_3 81 = 4$.',
                ],
                'skill' => 'logaritmi',
                'type' => ItemType::Numeric,
                'difficulty' => 1,
                'payload' => ['stem' => 'Calculează logaritmul.', 'stem_latex' => '\log_3 81'],
                'answer' => ['value' => 4, 'tolerance' => 0],
                'explanation' => 'Logaritmul e exponentul: $3^4 = 81$, deci $\log_3 81 = 4$.',
            ],
            [
                'ref' => 'seed:mat:log:proprietati-suma',
                'hints' => [
                    'Suma a doi logaritmi cu ACEEAȘI bază se strânge într-unul singur.',
                    '$\\log_a x + \\log_a y = \\log_a (xy)$ — suma devine produs, nu sumă.',
                    '$\\log_2 4 + \\log_2 8 = \\log_2 32 = 5$. Verificare: $4 + 8 = 12$, dar $\\log_2 12$ nu e întreg.',
                ],
                'skill' => 'logaritmi',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'payload' => ['stem' => 'Calculează suma.', 'stem_latex' => '\log_2 4 + \log_2 8'],
                'answer' => ['value' => 5, 'tolerance' => 0],
                'explanation' => 'Se poate direct ($2 + 3 = 5$) sau prin proprietate: $\log_2(4 \cdot 8) = \log_2 32 = 5$. Logaritmul transformă produsul în sumă — de aceea a fost inventat.',
            ],
            [
                'ref' => 'seed:mat:log:domeniu',
                'hints' => [
                    'Logaritmul se poate calcula doar pentru numere strict pozitive.',
                    'Condiția de existență e $x - 3 > 0$, cu inegalitate STRICTĂ.',
                    '$\\log_a 0$ nu există, deci $x = 3$ se exclude: domeniul e $(3, +\\infty)$.',
                ],
                'skill' => 'logaritmi',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Care este domeniul de definiție al funcției $f(x) = \log_2(x - 3)$?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '(3, +\infty)'],
                        ['key' => 'b', 'latex' => '[3, +\infty)'],
                        ['key' => 'c', 'latex' => '\mathbb{R}'],
                        ['key' => 'd', 'latex' => '(-\infty, 3)'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Argumentul logaritmului trebuie strict pozitiv: $x - 3 > 0$. Spre deosebire de radical, capătul NU se include, pentru că $\log_2 0$ nu există.',
            ],
            [
                'ref' => 'seed:mat:log:schimbare-baza',
                'hints' => [
                    'Când bazele diferă, nu poți combina logaritmii direct.',
                    'Formula de schimbare a bazei: $\\log_a b = \\frac{\\log_c b}{\\log_c a}$, cu orice bază $c$ convenabilă.',
                    'Alegând $c = 2$: $\\log_4 8 = \\frac{\\log_2 8}{\\log_2 4} = \\frac{3}{2}$.',
                ],
                'skill' => 'logaritmi',
                'type' => ItemType::Numeric,
                'difficulty' => 4,
                'points' => 2,
                'payload' => ['stem' => 'Calculează, folosind schimbarea de bază.', 'stem_latex' => '\log_4 8'],
                'answer' => ['value' => 1.5, 'tolerance' => 0.001],
                'explanation' => 'Trecem la baza 2: $\log_4 8 = \frac{\log_2 8}{\log_2 4} = \frac{3}{2} = 1{,}5$. Verificare: $4^{1,5} = \sqrt{4^3} = 8$.',
            ],

            // ---------- Numere complexe ----------
            [
                'ref' => 'seed:mat:complexe:inmultire',
                'hints' => [
                    'Se înmulțesc ca două binoame, termen cu termen.',
                    'După desfacere vei avea un termen cu $i^2$. Înlocuiește-l cu $-1$.',
                    '$(2+i)(3-2i) = 6 - 4i + 3i - 2i^2 = 6 - i + 2 = 8 - i$.',
                ],
                'skill' => 'numere-complexe',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Calculează produsul $(2 + i)(3 - 2i)$.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '8 - i'],
                        ['key' => 'b', 'latex' => '6 - 2i'],
                        ['key' => 'c', 'latex' => '4 - i'],
                        ['key' => 'd', 'latex' => '8 + i'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '$6 - 4i + 3i - 2i^2$. Cum $i^2 = -1$, termenul $-2i^2$ devine $+2$, deci rezultatul e $8 - i$. Uitarea acestei înlocuiri dă $6 - i$.',
            ],
            [
                'ref' => 'seed:mat:complexe:conjugat-proprietate',
                'hints' => [
                    'Produsul unui număr complex cu conjugatul lui are o formă specială.',
                    '$(a+bi)(a-bi)$ e o diferență de pătrate: $a^2 - (bi)^2 = a^2 + b^2$.',
                    'Rezultatul e întotdeauna REAL și pozitiv — este exact $|z|^2$.',
                ],
                'skill' => 'numere-complexe',
                'type' => ItemType::FillBlanks,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Completează: produsul dintre un număr complex și conjugatul său.',
                    'stem_template' => '(a + bi)(a − bi) = ___',
                    'blanks' => [['key' => 'rezultat', 'options' => ['a² + b²', 'a² − b²', 'a² + b²i', '2a']]],
                ],
                'answer' => ['values' => ['rezultat' => ['a² + b²', 'a^2 + b^2']]],
                'explanation' => '$(a+bi)(a-bi) = a^2 - b^2 i^2 = a^2 + b^2$. Rezultatul e real — de aceea conjugatul e instrumentul pentru împărțire.',
            ],
            [
                'ref' => 'seed:mat:complexe:radical-negativ-eroare',
                'hints' => [
                    'Regula $\\sqrt{a} \\cdot \\sqrt{b} = \\sqrt{ab}$ are o condiție pe care mulți o uită.',
                    'Ea se aplică doar pentru $a, b \\ge 0$. Cu numere negative duce la contradicții.',
                    '$\\sqrt{-1} \\cdot \\sqrt{-1} = i \\cdot i = i^2 = -1$, nu $\\sqrt{1} = 1$.',
                ],
                'skill' => 'numere-complexe',
                'type' => ItemType::FindError,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'Un elev a scris rezolvarea de mai jos și a obținut $1 = -1$. Unde este greșeala?',
                    'problem' => '\sqrt{-1} \cdot \sqrt{-1} = \sqrt{(-1)(-1)} = \sqrt{1} = 1',
                    'choices' => [
                        ['key' => 'a', 'text' => 'Regula $\sqrt{a}\sqrt{b} = \sqrt{ab}$ cere $a, b \ge 0$'],
                        ['key' => 'b', 'text' => '$\sqrt{-1}$ nu există'],
                        ['key' => 'c', 'text' => '$(-1)(-1) = -1$, nu $1$'],
                        ['key' => 'd', 'text' => 'Nu există nicio greșeală'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Corect este $\sqrt{-1} \cdot \sqrt{-1} = i \cdot i = -1$. Regula produsului de radicali nu se extinde la numere negative — exact de aici vin „demonstrațiile" că $1 = -1$.',
            ],

            // ---------- Matrici și determinanți ----------
            [
                'ref' => 'seed:mat:matrici:determinant-2x2',
                'hints' => [
                    'Pentru o matrice $2 \\times 2$ determinantul e o diferență de două produse.',
                    '$\\begin{vmatrix} a & b \\\\ c & d \\end{vmatrix} = ad - bc$ — diagonala principală minus cea secundară.',
                    '$4 \\cdot 6 - 3 \\cdot 5 = 24 - 15 = 9$.',
                ],
                'skill' => 'matrici',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'payload' => ['stem' => 'Calculează determinantul.', 'stem_latex' => '\begin{vmatrix} 4 & 3 \\\\ 5 & 6 \end{vmatrix}'],
                'answer' => ['value' => 9, 'tolerance' => 0],
                'explanation' => '$\det = 4 \cdot 6 - 3 \cdot 5 = 9$. Ordinea contează: produsul diagonalei principale MINUS cel al diagonalei secundare.',
            ],
            [
                'ref' => 'seed:mat:matrici:conditie-inmultire',
                'hints' => [
                    'Două matrici nu se pot înmulți întotdeauna — trebuie să se „potrivească".',
                    'Numărul de COLOANE ale primei trebuie să fie egal cu numărul de LINII ale celei de-a doua.',
                    'O matrice $2 \\times 3$ se poate înmulți cu una $3 \\times 4$, iar rezultatul e $2 \\times 4$.',
                ],
                'skill' => 'matrici',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'O matrice $A$ are dimensiunea $2 \times 3$, iar $B$ are $3 \times 4$. Ce dimensiune are produsul $A \cdot B$?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '2 \times 4'],
                        ['key' => 'b', 'latex' => '3 \times 3'],
                        ['key' => 'c', 'latex' => '4 \times 2'],
                        ['key' => 'd', 'latex' => '\text{produsul nu există}'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Se potrivesc dimensiunile din mijloc ($3 = 3$), iar rezultatul păstrează extremele: $2 \times 4$. Produsul $B \cdot A$ nu ar exista, pentru că $4 \neq 2$.',
            ],
            [
                'ref' => 'seed:mat:matrici:necomutativ',
                'hints' => [
                    'La numere, $ab = ba$. La matrici, această regulă NU se păstrează.',
                    'Chiar când ambele produse există și au aceeași dimensiune, ele pot fi diferite.',
                    'Înmulțirea matricelor nu e comutativă — e una dintre primele diferențe față de numere.',
                ],
                'skill' => 'matrici',
                'type' => ItemType::FindError,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Un elev simplifică o expresie matriceală scriind $AB = BA$ și pierde punctajul. De ce?',
                    'problem' => 'AB \overset{?}{=} BA',
                    'choices' => [
                        ['key' => 'a', 'text' => 'Înmulțirea matricelor nu e comutativă: în general $AB \neq BA$'],
                        ['key' => 'b', 'text' => 'Egalitatea e validă doar pentru matrici pătratice'],
                        ['key' => 'c', 'text' => 'Trebuia scris $AB = -BA$'],
                        ['key' => 'd', 'text' => 'Afirmația e corectă'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Necomutativitatea e proprietatea care deosebește cel mai clar matricele de numere. Egalitatea $AB = BA$ e adevărată doar în cazuri particulare, care trebuie demonstrate.',
            ],
            [
                'ref' => 'seed:mat:matrici:inversabila',
                'hints' => [
                    'O matrice pătratică are inversă doar într-o anumită condiție.',
                    'Condiția se verifică printr-un singur număr: determinantul.',
                    'Matricea e inversabilă exact atunci când $\\det A \\neq 0$.',
                ],
                'skill' => 'matrici',
                'type' => ItemType::FillBlanks,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Completează condiția de inversabilitate a unei matrici pătratice.',
                    'stem_template' => 'A este inversabilă ⟺ det A ___ 0',
                    'blanks' => [['key' => 'relatie', 'options' => ['≠', '=', '>', '<']]],
                ],
                'answer' => ['values' => ['relatie' => ['≠', '!=', '<>']]],
                'explanation' => 'Inversa conține împărțirea la determinant, deci un determinant nul o face imposibilă. Condiția e $\det A \neq 0$, nu $\det A > 0$: un determinant negativ e perfect acceptabil.',
            ],
            [
                'ref' => 'seed:mat:matrici:determinant-3x3-pas',
                'hints' => [
                    'Regula lui Sarrus se aplică doar la matrici $3 \\times 3$, nu la orice dimensiune.',
                    'Se copiază primele două coloane în dreapta matricei, apoi se adună diagonalele.',
                    'Diagonalele descendente se adună, cele ascendente se scad.',
                ],
                'skill' => 'matrici',
                'type' => ItemType::NextStep,
                'difficulty' => 3,
                'points' => 2,
                'payload' => [
                    'stem' => 'Pentru calculul determinantului de ordin 3 prin regula lui Sarrus, care e primul pas?',
                    'problem' => '\begin{vmatrix} 1 & 2 & 0 \\\\ 3 & 1 & 4 \\\\ 2 & 0 & 1 \end{vmatrix}',
                    'choices' => [
                        ['key' => 'a', 'latex' => '\text{copiez primele două COLOANE în dreapta}'],
                        ['key' => 'b', 'latex' => '\text{copiez primele două LINII dedesubt}'],
                        ['key' => 'c', 'latex' => '\text{calculez } ad - bc'],
                        ['key' => 'd', 'latex' => '\text{transpun matricea}'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Sarrus cere repetarea primelor două coloane la dreapta, apoi suma diagonalelor descendente minus suma celor ascendente. Formula $ad - bc$ e pentru ordinul 2.',
            ],
            [
                'ref' => 'seed:mat:matrici:potrivire-operatii',
                'hints' => [
                    'Adunarea și înmulțirea matricelor au condiții de compatibilitate diferite.',
                    'Adunarea cere dimensiuni IDENTICE; înmulțirea cere doar potrivirea din mijloc.',
                    'Înmulțirea cu un scalar nu are nicio condiție: se aplică fiecărui element.',
                ],
                'skill' => 'matrici',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Potrivește operația cu condiția ei de existență.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'Adunarea $A + B$'],
                        ['key' => 'l2', 'text' => 'Înmulțirea $A \cdot B$'],
                        ['key' => 'l3', 'text' => 'Înmulțirea cu scalarul $k$'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'Coloanele lui $A$ = liniile lui $B$'],
                        ['key' => 'r2', 'text' => 'Fără nicio condiție'],
                        ['key' => 'r3', 'text' => 'Dimensiuni identice'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r3', 'l2' => 'r1', 'l3' => 'r2']],
                'explanation' => 'Confuzia dintre condiția adunării și cea a înmulțirii e sursa celor mai multe erori de început la matrici.',
            ],
        ];
    }

    /**
     * Trigonometrie: valori și identități.
     *
     * @return list<array<string, mixed>>
     */
    private function itemsTrigonometrie(): array
    {
        return [
            [
                'ref' => 'seed:mat:trig:valoare-45',
                'hints' => [
                    'Valorile pentru $30°$, $45°$ și $60°$ se știu pe de rost — nu se deduc la examen.',
                    'La $45°$ triunghiul e dreptunghic isoscel, deci sinusul și cosinusul sunt egale.',
                    '$\\sin 45° = \\cos 45° = \\frac{\\sqrt{2}}{2} \\approx 0{,}707$.',
                ],
                'skill' => 'valori-trigonometrice',
                'type' => ItemType::Numeric,
                'difficulty' => 1,
                'payload' => ['stem' => 'Calculează, cu trei zecimale.', 'stem_latex' => '\sin 45^\circ'],
                'answer' => ['value' => 0.707, 'tolerance' => 0.002],
                'explanation' => '$\sin 45° = \frac{\sqrt{2}}{2} \approx 0{,}707$. La $45°$ cele două catete sunt egale, deci sinusul și cosinusul coincid.',
            ],
            [
                'ref' => 'seed:mat:trig:semn-cadran-3',
                'hints' => [
                    'Pe cercul trigonometric, cosinusul e coordonata $x$, iar sinusul, coordonata $y$.',
                    'În cadranul III ambele coordonate sunt negative.',
                    'Tangenta fiind raportul lor, împărțirea a două numere negative dă un rezultat POZITIV.',
                ],
                'skill' => 'valori-trigonometrice',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Un unghi $\alpha$ se află în cadranul III. Ce semne au $\sin \alpha$, $\cos \alpha$ și $\operatorname{tg} \alpha$?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '\sin < 0,\ \cos < 0,\ \operatorname{tg} > 0'],
                        ['key' => 'b', 'latex' => '\sin < 0,\ \cos < 0,\ \operatorname{tg} < 0'],
                        ['key' => 'c', 'latex' => '\sin > 0,\ \cos < 0,\ \operatorname{tg} < 0'],
                        ['key' => 'd', 'latex' => '\text{toate negative}'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'În cadranul III, $x < 0$ și $y < 0$, deci sinusul și cosinusul sunt negative. Tangenta, fiind raportul lor, iese pozitivă.',
            ],
            [
                'ref' => 'seed:mat:trig:grade-radiani',
                'hints' => [
                    'Toată conversia stă într-o singură egalitate: $180° = \\pi$ radiani.',
                    'Din grade în radiani înmulțești cu $\\frac{\\pi}{180}$.',
                    '$135 \\cdot \\frac{\\pi}{180} = \\frac{3\\pi}{4}$.',
                ],
                'skill' => 'valori-trigonometrice',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Exprimă $135°$ în radiani.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '\frac{3\pi}{4}'],
                        ['key' => 'b', 'latex' => '\frac{4\pi}{3}'],
                        ['key' => 'c', 'latex' => '\frac{2\pi}{3}'],
                        ['key' => 'd', 'latex' => '\frac{3\pi}{2}'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '$135° = 135 \cdot \frac{\pi}{180} = \frac{3\pi}{4}$. Verificare rapidă: $135°$ e între $90° = \frac{\pi}{2}$ și $180° = \pi$, iar $\frac{3\pi}{4}$ chiar e acolo.',
            ],
            [
                'ref' => 'seed:mat:trig:identitate-fundamentala',
                'hints' => [
                    'Identitatea fundamentală se folosește și invers: un $1$ poate fi înlocuit cu $\\sin^2 + \\cos^2$.',
                    'Expresia $1 - \\sin^2 \\alpha$ e exact ce rămâne din identitate.',
                    '$\\sin^2 \\alpha + \\cos^2 \\alpha = 1 \\Rightarrow 1 - \\sin^2 \\alpha = \\cos^2 \\alpha$.',
                ],
                'skill' => 'identitati-trigonometrice',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Simplifică expresia $1 - \sin^2 \alpha$.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '\cos^2 \alpha'],
                        ['key' => 'b', 'latex' => '\cos \alpha'],
                        ['key' => 'c', 'latex' => '-\cos^2 \alpha'],
                        ['key' => 'd', 'latex' => '\operatorname{tg}^2 \alpha'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Direct din identitatea fundamentală: $1 - \sin^2 \alpha = \cos^2 \alpha$. Atenție, rezultatul e cosinus la PĂTRAT, nu cosinus.',
            ],
            [
                'ref' => 'seed:mat:trig:tangenta-raport',
                'hints' => [
                    'Tangenta se poate scrie prin sinus și cosinus.',
                    '$\\operatorname{tg} \\alpha = \\frac{\\sin \\alpha}{\\cos \\alpha}$, definită doar unde $\\cos \\alpha \\neq 0$.',
                    'Cu $\\sin = \\frac{3}{5}$ și $\\cos = \\frac{4}{5}$, raportul e $\\frac{3}{4} = 0{,}75$.',
                ],
                'skill' => 'identitati-trigonometrice',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Se dau $\sin \alpha = \frac{3}{5}$ și $\cos \alpha = \frac{4}{5}$. Calculează $\operatorname{tg} \alpha$.',
                    'stem_latex' => '\operatorname{tg} \alpha = \frac{\sin \alpha}{\cos \alpha}',
                ],
                'answer' => ['value' => 0.75, 'tolerance' => 0.001],
                'explanation' => '$\operatorname{tg} \alpha = \frac{3/5}{4/5} = \frac{3}{4} = 0{,}75$. Numitorii $5$ se simplifică — nu e nevoie de calcul zecimal intermediar.',
            ],
            [
                'ref' => 'seed:mat:trig:cos-dublu-forme',
                'hints' => [
                    'Cosinusul unghiului dublu are trei forme echivalente. Alegi forma după ce ai deja în problemă.',
                    'Dacă în enunț apare doar $\\sin a$, vrei forma care nu conține cosinus.',
                    '$\\cos 2a = 1 - 2\\sin^2 a$ e forma care elimină complet cosinusul.',
                ],
                'skill' => 'identitati-trigonometrice',
                'type' => ItemType::NextStep,
                'difficulty' => 4,
                'points' => 2,
                'payload' => [
                    'stem' => 'Se cunoaște doar $\sin a$. Ce formă a lui $\cos 2a$ alegi ca să nu mai ai nevoie de altceva?',
                    'problem' => '\cos 2a = ?',
                    'choices' => [
                        ['key' => 'a', 'latex' => '1 - 2\sin^2 a'],
                        ['key' => 'b', 'latex' => '2\cos^2 a - 1'],
                        ['key' => 'c', 'latex' => '\cos^2 a - \sin^2 a'],
                        ['key' => 'd', 'latex' => '2 \sin a \cos a'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Toate primele trei sunt corecte matematic, dar doar $1 - 2\sin^2 a$ se calculează direct din $\sin a$. Varianta $2\sin a \cos a$ e $\sin 2a$, nu cosinus.',
            ],
        ];
    }

    /**
     * Analiză matematică: funcții, limite, derivate, integrale.
     *
     * @return list<array<string, mixed>>
     */
    private function itemsAnaliza(): array
    {
        return [
            // ---------- Funcții ----------
            [
                'ref' => 'seed:mat:functii:domeniu-fractie',
                'hints' => [
                    'O fracție nu are sens când numitorul se anulează.',
                    'Rezolvă $x - 2 = 0$ și scoate valoarea găsită din mulțimea numerelor reale.',
                    'Domeniul este $\\mathbb{R} \\setminus \\{2\\}$ — toate numerele reale în afară de 2.',
                ],
                'skill' => 'functii',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Care este domeniul maxim de definiție al funcției $f(x) = \dfrac{x+1}{x-2}$?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '\mathbb{R} \setminus \{2\}'],
                        ['key' => 'b', 'latex' => '\mathbb{R} \setminus \{-1\}'],
                        ['key' => 'c', 'latex' => '(2, +\infty)'],
                        ['key' => 'd', 'latex' => '\mathbb{R}'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Se exclude doar valoarea care anulează NUMITORUL. Zeroul numărătorului, $x = -1$, e perfect valid: acolo funcția are valoarea 0.',
            ],
            [
                'ref' => 'seed:mat:functii:inversa',
                'hints' => [
                    'Ca să afli inversa, schimbi rolurile: scrii $y = 2x + 3$ și scoți $x$ în funcție de $y$.',
                    'Din $y = 2x + 3$ obții $x = \\frac{y - 3}{2}$.',
                    'Apoi redenumești variabila: $f^{-1}(x) = \\frac{x - 3}{2}$.',
                ],
                'skill' => 'functii',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Care este inversa funcției $f(x) = 2x + 3$?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => 'f^{-1}(x) = \frac{x - 3}{2}'],
                        ['key' => 'b', 'latex' => 'f^{-1}(x) = \frac{1}{2x + 3}'],
                        ['key' => 'c', 'latex' => 'f^{-1}(x) = 2x - 3'],
                        ['key' => 'd', 'latex' => 'f^{-1}(x) = \frac{x + 3}{2}'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Inversa unei funcții nu e inversul ei aritmetic: $f^{-1} \neq \frac{1}{f}$. Verificare: $f(f^{-1}(x)) = 2 \cdot \frac{x-3}{2} + 3 = x$.',
            ],
            [
                'ref' => 'seed:mat:functii:monotonie',
                'hints' => [
                    'Monotonia unei funcții de gradul I e dată de un singur număr.',
                    'Coeficientul lui $x$ decide: pozitiv înseamnă crescătoare, negativ înseamnă descrescătoare.',
                    'Aici coeficientul e $-3 < 0$, deci funcția e strict descrescătoare.',
                ],
                'skill' => 'functii',
                'type' => ItemType::FillBlanks,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Completează: funcția $f(x) = -3x + 7$ este pe $\mathbb{R}$...',
                    'stem_template' => 'f(x) = −3x + 7 este strict ___',
                    'blanks' => [['key' => 'monotonie', 'options' => ['descrescătoare', 'crescătoare', 'constantă', 'pară']]],
                ],
                'answer' => ['values' => ['monotonie' => ['descrescătoare', 'descrescatoare']]],
                'explanation' => 'Pentru $f(x) = ax + b$, semnul lui $a$ decide monotonia. Aici $a = -3 < 0$, deci funcția scade. Termenul liber $7$ doar translatează graficul.',
            ],

            // ---------- Limite ----------
            [
                'ref' => 'seed:mat:limite:substitutie-directa',
                'hints' => [
                    'Începe întotdeauna cu substituția directă — de multe ori e și ultimul pas.',
                    'Funcția e continuă în $x = 1$, deci limita e chiar valoarea funcției acolo.',
                    '$3 \\cdot 1^2 + 2 \\cdot 1 - 1 = 4$.',
                ],
                'skill' => 'limite',
                'type' => ItemType::Numeric,
                'difficulty' => 1,
                'payload' => ['stem' => 'Calculează limita.', 'stem_latex' => '\lim_{x \to 1} (3x^2 + 2x - 1)'],
                'answer' => ['value' => 4, 'tolerance' => 0],
                'explanation' => 'Polinoamele sunt continue peste tot, deci limita e valoarea funcției în punct: $3 + 2 - 1 = 4$. Nu orice limită are nevoie de o metodă specială.',
            ],
            [
                'ref' => 'seed:mat:limite:grade-diferite',
                'hints' => [
                    'La $\\frac{\\infty}{\\infty}$ compară gradul numărătorului cu al numitorului.',
                    'Aici numărătorul e de gradul 1, numitorul de gradul 2. Numitorul crește mult mai repede.',
                    'Când gradul de jos e mai mare, limita este 0.',
                ],
                'skill' => 'limite',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Calculează $\displaystyle\lim_{x \to \infty} \frac{2x + 1}{x^2 - 3}$.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '0'],
                        ['key' => 'b', 'latex' => '2'],
                        ['key' => 'c', 'latex' => '+\infty'],
                        ['key' => 'd', 'latex' => '\frac{1}{3}'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Gradul numitorului fiind mai mare, fracția tinde la 0. Regula completă: grad jos mai mare → 0; grade egale → raportul coeficienților; grad sus mai mare → $\pm\infty$.',
            ],
            [
                'ref' => 'seed:mat:limite:asimptota-verticala',
                'hints' => [
                    'O asimptotă verticală apare acolo unde funcția „explodează" — de obicei unde se anulează numitorul.',
                    'Rezolvă $x - 3 = 0$ și verifică dacă numărătorul rămâne nenul acolo.',
                    'În $x = 3$ numitorul e 0, iar numărătorul e 1, deci $x = 3$ e asimptotă verticală.',
                ],
                'skill' => 'limite',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Ce asimptotă verticală are funcția $f(x) = \dfrac{1}{x - 3}$?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => 'x = 3'],
                        ['key' => 'b', 'latex' => 'y = 3'],
                        ['key' => 'c', 'latex' => 'x = 0'],
                        ['key' => 'd', 'latex' => '\text{nu are}'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Asimptota verticală e o dreaptă de forma $x = a$, nu $y = a$. Aici numitorul se anulează în $x = 3$, iar numărătorul nu, deci limita e infinită acolo.',
            ],
            // ---------- Derivate ----------
            [
                'ref' => 'seed:mat:derivate:putere',
                'hints' => [
                    'Regula puterii: exponentul coboară ca factor, iar noul exponent scade cu 1.',
                    '$(x^n)\' = n x^{n-1}$. Aici $n = 5$.',
                    '$(x^5)\' = 5x^4$. Nu $5x^5$ și nu $x^4$.',
                ],
                'skill' => 'derivate',
                'type' => ItemType::Mcq,
                'difficulty' => 1,
                'payload' => [
                    'stem' => 'Calculează derivata funcției $f(x) = x^5$.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '5x^4'],
                        ['key' => 'b', 'latex' => '5x^5'],
                        ['key' => 'c', 'latex' => 'x^4'],
                        ['key' => 'd', 'latex' => '\frac{x^6}{6}'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '$(x^n)\' = nx^{n-1}$, deci $(x^5)\' = 5x^4$. Varianta $\frac{x^6}{6}$ e primitiva, nu derivata.',
            ],
            [
                'ref' => 'seed:mat:derivate:constanta',
                'hints' => [
                    'O funcție constantă nu se schimbă deloc, deci viteza ei de variație este...',
                    'Graficul e o dreaptă orizontală, iar panta unei orizontale e zero.',
                    'Derivata oricărei constante este 0.',
                ],
                'skill' => 'derivate',
                'type' => ItemType::Numeric,
                'difficulty' => 1,
                'payload' => ['stem' => 'Calculează derivata funcției constante $f(x) = 2027$.', 'stem_latex' => "f'(x) = ?"],
                'answer' => ['value' => 0, 'tolerance' => 0],
                'explanation' => 'Derivata măsoară variația. O constantă nu variază, deci derivata e 0 — indiferent cât de mare e constanta.',
            ],
            [
                'ref' => 'seed:mat:derivate:cat',
                'hints' => [
                    'Regula câtului are numitorul la pătrat și un MINUS la numărător.',
                    '$\\left(\\frac{f}{g}\\right)\' = \\frac{f\'g - fg\'}{g^2}$ — ordinea termenilor contează, fiind scădere.',
                    'Varianta cu $f\'g + fg\'$ e regula produsului, iar cea cu $+$ la numărător și $g^2$ la numitor nu există.',
                ],
                'skill' => 'derivate',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Care este formula derivatei unui cât?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '\frac{f\'g - fg\'}{g^2}'],
                        ['key' => 'b', 'latex' => '\frac{f\'g + fg\'}{g^2}'],
                        ['key' => 'c', 'latex' => '\frac{f\'}{g\'}'],
                        ['key' => 'd', 'latex' => '\frac{fg\' - f\'g}{g^2}'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Ordinea la numărător nu e arbitrară: $f\'g - fg\'$. Varianta $d$ e aceeași expresie cu semn schimbat, deci greșită.',
            ],
            [
                'ref' => 'seed:mat:derivate:compusa',
                'hints' => [
                    'Funcția $\\sin(3x)$ e o compunere: sinus aplicat lui $3x$.',
                    'Derivezi funcția exterioară, apoi înmulțești cu derivata celei interioare.',
                    '$(\\sin u)\' = u\' \\cos u$, cu $u = 3x$ și $u\' = 3$, deci rezultatul e $3\\cos 3x$.',
                ],
                'skill' => 'derivate',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Calculează derivata funcției $f(x) = \sin(3x)$.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '3\cos 3x'],
                        ['key' => 'b', 'latex' => '\cos 3x'],
                        ['key' => 'c', 'latex' => '3\sin 3x'],
                        ['key' => 'd', 'latex' => '-3\cos 3x'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Regula funcției compuse cere înmulțirea cu derivata interiorului. Uitarea factorului 3 e greșeala cea mai frecventă la derivate compuse.',
            ],
            [
                'ref' => 'seed:mat:derivate:punct-critic',
                'hints' => [
                    'Punctele de extrem se caută acolo unde derivata se anulează.',
                    'Calculează $f\'(x) = 2x - 6$ și rezolvă $f\'(x) = 0$.',
                    'Din $2x - 6 = 0$ iese $x = 3$. Fiind o parabolă cu ramurile în sus, acolo e minimul.',
                ],
                'skill' => 'derivate',
                'type' => ItemType::Numeric,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'În ce punct are funcția $f(x) = x^2 - 6x + 5$ un extrem?',
                    'stem_latex' => "f'(x) = 0 \\Rightarrow x = ?",
                ],
                'answer' => ['value' => 3, 'tolerance' => 0],
                'explanation' => '$f\'(x) = 2x - 6 = 0$ dă $x = 3$. Anularea derivatei e condiția NECESARĂ de extrem; natura lui se stabilește din semnul derivatei sau din forma graficului.',
            ],
            [
                'ref' => 'seed:mat:derivate:constanta-eroare',
                'hints' => [
                    'Verifică fiecare termen separat. Care dintre ei a fost derivat greșit?',
                    'Termenul $3x$ are derivata 3, iar constanta $-7$ are derivata 0.',
                    'Elevul a păstrat constanta: derivata corectă e $2x + 3$, nu $2x + 3 - 7$.',
                ],
                'skill' => 'derivate',
                'type' => ItemType::FindError,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Un elev a derivat funcția de mai jos. Unde este greșeala?',
                    'problem' => "f(x) = x^2 + 3x - 7 \\quad\\Rightarrow\\quad f'(x) = 2x + 3 - 7",
                    'choices' => [
                        ['key' => 'a', 'text' => 'Derivata constantei $-7$ este 0, deci $f\'(x) = 2x + 3$'],
                        ['key' => 'b', 'text' => 'Derivata lui $3x$ este $3x$, nu 3'],
                        ['key' => 'c', 'text' => 'Derivata lui $x^2$ este $x$'],
                        ['key' => 'd', 'text' => 'Nu există nicio greșeală'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Constantele dispar la derivare. Păstrarea lor e una dintre cele mai frecvente greșeli de neatenție și se corectează verificând termen cu termen.',
            ],

            // ---------- Integrale ----------
            [
                'ref' => 'seed:mat:integrale:putere',
                'hints' => [
                    'Integrarea e operația inversă derivării: exponentul CREȘTE cu 1.',
                    '$\\int x^n dx = \\frac{x^{n+1}}{n+1} + C$, valabil pentru $n \\neq -1$.',
                    'Pentru $n = 3$: $\\frac{x^4}{4} + C$. Constanta $C$ nu se uită niciodată.',
                ],
                'skill' => 'integrale',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Calculează $\displaystyle\int x^3 \, dx$.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '\frac{x^4}{4} + C'],
                        ['key' => 'b', 'latex' => '3x^2 + C'],
                        ['key' => 'c', 'latex' => '\frac{x^4}{3} + C'],
                        ['key' => 'd', 'latex' => '4x^3 + C'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Exponentul crește la 4 și se împarte la noul exponent. Varianta $3x^2$ e derivata, nu primitiva — confuzia de sens e frecventă sub presiune.',
            ],
            [
                'ref' => 'seed:mat:integrale:constanta-uitata',
                'hints' => [
                    'Verifică ce lipsește la finalul rezultatului unei integrale NEDEFINITE.',
                    'Derivata oricărei constante e 0, deci există o infinitate de primitive.',
                    'Fără $+ C$ răspunsul e incomplet și se depunctează la corectare.',
                ],
                'skill' => 'integrale',
                'type' => ItemType::FindError,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Un elev a scris rezultatul de mai jos și a pierdut un punct. De ce?',
                    'problem' => '\int 2x \, dx = x^2',
                    'choices' => [
                        ['key' => 'a', 'text' => 'Lipsește constanta de integrare: rezultatul e $x^2 + C$'],
                        ['key' => 'b', 'text' => 'Rezultatul corect este $2x^2$'],
                        ['key' => 'c', 'text' => 'Trebuia scris $\frac{x^2}{2}$'],
                        ['key' => 'd', 'text' => 'Nu există nicio greșeală'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'La integrala nedefinită, orice constantă adăugată dă tot o primitivă. Omiterea lui $+C$ e cea mai ieftină pierdere de punct de la analiză.',
            ],
            [
                'ref' => 'seed:mat:integrale:definita-leibniz',
                'hints' => [
                    'Formula Leibniz-Newton: calculezi primitiva, apoi o evaluezi la capete și scazi.',
                    'Primitiva lui $2x$ este $x^2$. Evaluează în 3 și în 1.',
                    '$3^2 - 1^2 = 9 - 1 = 8$. La integrala definită constanta $C$ se simplifică singură.',
                ],
                'skill' => 'integrale',
                'type' => ItemType::Numeric,
                'difficulty' => 3,
                'payload' => ['stem' => 'Calculează integrala definită.', 'stem_latex' => '\int_{1}^{3} 2x \, dx'],
                'answer' => ['value' => 8, 'tolerance' => 0],
                'explanation' => 'Primitiva e $x^2$, deci rezultatul e $F(3) - F(1) = 9 - 1 = 8$. Ordinea scăderii contează: capătul de sus minus cel de jos.',
            ],
            [
                'ref' => 'seed:mat:integrale:arie-sub-grafic',
                'hints' => [
                    'Aria dintre grafic și axa $Ox$ se calculează cu o integrală definită.',
                    'Primitiva lui $x^2$ este $\\frac{x^3}{3}$.',
                    '$\\frac{3^3}{3} - \\frac{0^3}{3} = 9$.',
                ],
                'skill' => 'integrale',
                'type' => ItemType::Numeric,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Calculează aria cuprinsă între graficul funcției $f(x) = x^2$, axa $Ox$ și dreptele $x = 0$, $x = 3$.',
                    'stem_latex' => 'A = \int_{0}^{3} x^2 \, dx',
                ],
                'answer' => ['value' => 9, 'tolerance' => 0],
                'explanation' => 'Aria e $\frac{x^3}{3}$ evaluată între 0 și 3, adică $9 - 0 = 9$. Funcția fiind pozitivă pe interval, integrala dă direct aria, fără modul.',
            ],
            [
                'ref' => 'seed:mat:integrale:liniaritate',
                'hints' => [
                    'Integrala unei sume se desface în suma integralelor, iar constantele ies în față.',
                    'Nu există însă nicio regulă pentru integrala unui PRODUS de funcții.',
                    'Pentru produse ai nevoie de integrare prin părți sau de substituție.',
                ],
                'skill' => 'integrale',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Care dintre următoarele egalități NU este adevărată în general?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '\int f g \, dx = \int f dx \cdot \int g \, dx'],
                        ['key' => 'b', 'latex' => '\int (f + g) dx = \int f dx + \int g \, dx'],
                        ['key' => 'c', 'latex' => '\int k f \, dx = k \int f \, dx'],
                        ['key' => 'd', 'latex' => '\int_a^b f dx = -\int_b^a f \, dx'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Integrala e liniară: merge peste sume și peste constante. Peste produse NU merge — de aceea există integrarea prin părți.',
            ],
            [
                'ref' => 'seed:mat:integrale:substitutie-alegere',
                'hints' => [
                    'Caută în integrand o funcție și derivata ei, până la o constantă.',
                    'Derivata lui $x^2 + 1$ este $2x$, iar $2x$ apare chiar în integrand.',
                    'Notând $u = x^2 + 1$, obții $du = 2x\\,dx$, iar integrala devine $\\int \\frac{du}{u}$.',
                ],
                'skill' => 'integrale',
                'type' => ItemType::NextStep,
                'difficulty' => 4,
                'points' => 3,
                'payload' => [
                    'stem' => 'Ce substituție duce la soluție?',
                    'problem' => '\int \frac{2x}{x^2 + 1} \, dx',
                    'choices' => [
                        ['key' => 'a', 'latex' => 'u = x^2 + 1'],
                        ['key' => 'b', 'latex' => 'u = 2x'],
                        ['key' => 'c', 'latex' => 'u = x'],
                        ['key' => 'd', 'latex' => 'u = \frac{1}{x^2+1}'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Se alege ca $u$ expresia a cărei derivată apare deja în integrand. Cu $u = x^2+1$, integrala devine $\int \frac{du}{u} = \ln|u| + C = \ln(x^2+1) + C$.',
            ],
            [
                'ref' => 'seed:mat:integrale:ordonare-definita',
                'hints' => [
                    'Nu evalua la capete înainte de a avea primitiva completă.',
                    'Ordinea e: găsesc primitiva → evaluez la capătul de sus → evaluez la cel de jos → scad.',
                    'Verificarea prin derivare a primitivei e pasul care prinde majoritatea greșelilor.',
                ],
                'skill' => 'integrale',
                'type' => ItemType::OrderSteps,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Ordonează pașii pentru calculul integralei definite.',
                    'problem' => '\int_{0}^{2} 3x^2 \, dx',
                    'steps' => [
                        ['key' => 's1', 'text' => 'Determin primitiva: $F(x) = x^3$'],
                        ['key' => 's2', 'text' => 'Verific prin derivare că $F\'(x) = 3x^2$'],
                        ['key' => 's3', 'text' => 'Evaluez la capătul superior: $F(2) = 8$'],
                        ['key' => 's4', 'text' => 'Evaluez la capătul inferior: $F(0) = 0$'],
                        ['key' => 's5', 'text' => 'Scad: $8 - 0 = 8$'],
                    ],
                ],
                'answer' => ['order' => ['s1', 's2', 's3', 's4', 's5']],
                'explanation' => 'Verificarea primitivei prin derivare costă cinci secunde și prinde majoritatea greșelilor de calcul — merită făcută înainte de evaluare.',
            ],
        ];
    }

    /**
     * Geometrie: triunghiuri, cercul, corpuri.
     *
     * @return list<array<string, mixed>>
     */
    private function itemsGeometrie(): array
    {
        return [
            // ---------- Triunghiuri ----------
            [
                'ref' => 'seed:mat:triunghi:pitagora-cateta',
                'hints' => [
                    'Ipotenuza e latura opusă unghiului drept — întotdeauna cea mai lungă.',
                    'Aici ipotenuza e 13, deci ea stă singură în membrul cu pătratul: $5^2 + b^2 = 13^2$.',
                    '$b^2 = 169 - 25 = 144$, deci $b = 12$. Tripletul $(5, 12, 13)$ merită memorat.',
                ],
                'skill' => 'triunghiuri',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Într-un triunghi dreptunghic, ipotenuza are 13 cm și o catetă are 5 cm. Cât are cealaltă catetă?',
                    'stem_latex' => 'a^2 + b^2 = c^2',
                ],
                'answer' => ['value' => 12, 'tolerance' => 0],
                'explanation' => 'Din $5^2 + b^2 = 13^2$ rezultă $b = 12$. Greșeala tipică e $13^2 + 5^2$, care tratează ipotenuza ca pe o catetă și dă un rezultat mai mare decât ipotenuza.',
            ],
            [
                'ref' => 'seed:mat:triunghi:echilateral-arie',
                'hints' => [
                    'Triunghiul echilateral are o formulă proprie de arie, în funcție doar de latură.',
                    '$S = \\frac{a^2 \\sqrt{3}}{4}$.',
                    'Pentru $a = 6$: $\\frac{36\\sqrt{3}}{4} = 9\\sqrt{3}$.',
                ],
                'skill' => 'triunghiuri',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Care este aria unui triunghi echilateral cu latura de 6 cm?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '9\sqrt{3} \text{ cm}^2'],
                        ['key' => 'b', 'latex' => '18 \text{ cm}^2'],
                        ['key' => 'c', 'latex' => '36\sqrt{3} \text{ cm}^2'],
                        ['key' => 'd', 'latex' => '12\sqrt{3} \text{ cm}^2'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '$S = \frac{a^2\sqrt{3}}{4} = \frac{36\sqrt{3}}{4} = 9\sqrt{3} \approx 15{,}6$ cm². Formula $\frac{b \cdot h}{2}$ ar cere calculul înălțimii, care e tocmai $\frac{a\sqrt{3}}{2}$.',
            ],
            [
                'ref' => 'seed:mat:triunghi:inegalitate',
                'hints' => [
                    'Nu orice trei lungimi formează un triunghi.',
                    'Fiecare latură trebuie să fie mai MICĂ decât suma celorlalte două.',
                    'Verifică $2 + 3$ față de $9$: $5 < 9$, deci laturile nu se pot închide.',
                ],
                'skill' => 'triunghiuri',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Care dintre următoarele triplete NU poate fi setul de laturi al unui triunghi?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '(2,\ 3,\ 9)'],
                        ['key' => 'b', 'latex' => '(3,\ 4,\ 5)'],
                        ['key' => 'c', 'latex' => '(6,\ 6,\ 6)'],
                        ['key' => 'd', 'latex' => '(7,\ 8,\ 10)'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Inegalitatea triunghiului cere $a < b + c$ pentru fiecare latură. Cu $(2, 3, 9)$: $2 + 3 = 5 < 9$, deci laturile nu se pot uni într-un triunghi.',
            ],

            // ---------- Cercul ----------
            [
                'ref' => 'seed:mat:cerc:lungime',
                'hints' => [
                    'Lungimea cercului se măsoară în centimetri, deci raza apare la puterea întâi.',
                    '$L = 2\\pi r$, spre deosebire de arie, care e $\\pi r^2$.',
                    'Pentru $r = 7$: $L = 14\\pi$.',
                ],
                'skill' => 'cerc',
                'type' => ItemType::Numeric,
                'difficulty' => 1,
                'payload' => ['unit' => 'π',
                    'stem' => 'Un cerc are raza 7. Calculează lungimea cercului ca multiplu de $\pi$ (scrie doar coeficientul).',
                    'stem_latex' => 'L = 2\pi r',
                ],
                'answer' => ['value' => 14, 'tolerance' => 0],
                'explanation' => '$L = 2\pi \cdot 7 = 14\pi$. Rezultatul $49\pi$ ar fi aria, nu lungimea — confuzia dintre cele două formule e cea mai frecventă la acest capitol.',
            ],
            [
                'ref' => 'seed:mat:cerc:unghi-diametru',
                'hints' => [
                    'Unghiul la centru care se sprijină pe un diametru este un unghi alungit, de $180°$.',
                    'Unghiul înscris e jumătate din cel la centru.',
                    '$\\frac{180°}{2} = 90°$: orice triunghi cu o latură diametru este dreptunghic.',
                ],
                'skill' => 'cerc',
                'type' => ItemType::Numeric,
                'difficulty' => 3,
                'payload' => ['unit' => '°',
                    'stem' => 'Un triunghi înscris într-un cerc are una dintre laturi egală cu diametrul. Câte grade are unghiul opus acestei laturi?',
                    'stem_latex' => '\widehat{\text{înscris}} = \frac{\widehat{\text{centru}}}{2}',
                ],
                'answer' => ['value' => 90, 'tolerance' => 0],
                'explanation' => 'Diametrul corespunde unui unghi la centru de $180°$, deci unghiul înscris are $90°$. E rezultatul care transformă multe probleme de cerc în probleme de triunghi dreptunghic.',
            ],
            [
                'ref' => 'seed:mat:cerc:tangenta-perpendiculara',
                'hints' => [
                    'Tangenta atinge cercul într-un singur punct. Ce unghi face cu raza dusă în acel punct?',
                    'Unghiul e drept — de aici apare mereu un triunghi dreptunghic în probleme.',
                    'Tangenta este perpendiculară pe rază în punctul de tangență.',
                ],
                'skill' => 'cerc',
                'type' => ItemType::FillBlanks,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Completează proprietatea tangentei la cerc.',
                    'stem_template' => 'Tangenta este ___ pe rază în punctul de tangență',
                    'blanks' => [['key' => 'relatie', 'options' => ['perpendiculară', 'paralelă', 'egală', 'oblică']]],
                ],
                'answer' => ['values' => ['relatie' => ['perpendiculară', 'perpendiculara']]],
                'explanation' => 'Perpendicularitatea pe rază e cheia majorității problemelor cu tangente: îți dă imediat un unghi drept, deci Pitagora.',
            ],

            // ---------- Corpuri ----------
            [
                'ref' => 'seed:mat:corpuri:sfera-volum',
                'hints' => [
                    'Sfera are singura formulă cu raza la puterea a treia din programă.',
                    '$V = \\frac{4\\pi r^3}{3}$. Nu confunda cu aria, care e $4\\pi r^2$.',
                    'Pentru $r = 3$: $\\frac{4\\pi \\cdot 27}{3} = 36\\pi$.',
                ],
                'skill' => 'corpuri',
                'type' => ItemType::Numeric,
                'difficulty' => 3,
                'payload' => ['unit' => 'π',
                    'stem' => 'O sferă are raza 3. Calculează volumul ca multiplu de $\pi$ (scrie doar coeficientul).',
                    'stem_latex' => 'V = \frac{4\pi r^3}{3}',
                ],
                'answer' => ['value' => 36, 'tolerance' => 0],
                'explanation' => '$V = \frac{4\pi \cdot 27}{3} = 36\pi$. Aria sferei ar fi $4\pi \cdot 9 = 36\pi$ — aceeași valoare numerică aici, dar unități diferite: cm³ față de cm².',
            ],
            [
                'ref' => 'seed:mat:corpuri:cilindru-volum',
                'hints' => [
                    'Cilindrul e un corp drept: volumul e aria bazei ori înălțimea, fără nicio treime.',
                    'Baza e un disc, deci $A_b = \\pi r^2$.',
                    '$V = \\pi \\cdot 2^2 \\cdot 5 = 20\\pi$.',
                ],
                'skill' => 'corpuri',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'payload' => ['unit' => 'π',
                    'stem' => 'Un cilindru are raza bazei 2 și înălțimea 5. Calculează volumul ca multiplu de $\pi$ (scrie doar coeficientul).',
                    'stem_latex' => 'V = \pi r^2 h',
                ],
                'answer' => ['value' => 20, 'tolerance' => 0],
                'explanation' => '$V = \pi \cdot 4 \cdot 5 = 20\pi$. Factorul $\frac{1}{3}$ apare doar la corpurile ascuțite — con și piramidă — nu la cilindru.',
            ],
            [
                'ref' => 'seed:mat:corpuri:potrivire-arii',
                'hints' => [
                    'Fiecare corp folosește o mărime specifică pentru aria laterală.',
                    'Conul folosește generatoarea, piramida folosește apotema, cilindrul folosește înălțimea.',
                    'Confuzia dintre generatoare și înălțime la con e cea mai costisitoare.',
                ],
                'skill' => 'corpuri',
                'type' => ItemType::Match,
                'difficulty' => 4,
                'points' => 3,
                'payload' => [
                    'stem' => 'Potrivește corpul cu formula ariei sale laterale.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'Cilindru'],
                        ['key' => 'l2', 'text' => 'Con'],
                        ['key' => 'l3', 'text' => 'Piramidă'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'latex' => '\pi r G'],
                        ['key' => 'r2', 'latex' => '\frac{P_b \cdot a_p}{2}'],
                        ['key' => 'r3', 'latex' => '2\pi r h'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r3', 'l2' => 'r1', 'l3' => 'r2']],
                'explanation' => 'Cilindrul desfășurat dă un dreptunghi de laturi $2\pi r$ și $h$. Conul dă un sector de rază $G$. Piramida are fețe triunghiulare de înălțime $a_p$.',
            ],
            [
                'ref' => 'seed:mat:corpuri:unitati-eroare',
                'hints' => [
                    'Verifică transformarea de unități, nu calculul volumului.',
                    'Un decimetru are 10 centimetri, dar un decimetru CUB nu are 10 centimetri cubi.',
                    'Raportul se ridică la cub: $1$ dm³ $= 10^3 = 1000$ cm³.',
                ],
                'skill' => 'corpuri',
                'type' => ItemType::FindError,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Un elev a calculat corect un volum de $2$ dm³ și l-a transformat ca mai jos. Unde este greșeala?',
                    'problem' => '2 \text{ dm}^3 = 20 \text{ cm}^3',
                    'choices' => [
                        ['key' => 'a', 'text' => 'Raportul se ridică la cub: $2$ dm³ $= 2000$ cm³'],
                        ['key' => 'b', 'text' => 'Rezultatul corect este $200$ cm³'],
                        ['key' => 'c', 'text' => 'Transformarea se face împărțind, nu înmulțind'],
                        ['key' => 'd', 'text' => 'Nu există nicio greșeală'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'La volume, factorul de conversie liniar se ridică la a treia putere: $1$ dm $= 10$ cm, deci $1$ dm³ $= 1000$ cm³. La arii se ridică la pătrat: $1$ dm² $= 100$ cm².',
            ],
        ];
    }
}
