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
 * Conținut de start pentru INFORMATICĂ.
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
            $this->itemsProgramare(),
            $this->itemsInformatie(),
            $this->itemsAritmetica(),
            $this->itemsLogica(),
            $this->itemsCalculNumeric(),
            $this->itemsBazeDeDate(),
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
            // ==================== TIPURI DE DATE SIMPLE ====================
            [
                'ref' => 'seed:inf:tipuri:enumerare',
                'skill' => 'tipuri-simple',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Fie definiția de tip: `type Zi = (Luni, Marti, Miercuri);` În ce categorie se încadrează tipul `Zi`?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Tip enumerare, ordinal'],
                        ['key' => 'b', 'text' => 'Tip real, neordinal'],
                        ['key' => 'c', 'text' => 'Tip structurat'],
                        ['key' => 'd', 'text' => 'Tip subdomeniu'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Valorile sunt enumerate explicit, una câte una, în definiție.',
                    'Un tip e ordinal dacă fiecărei valori i se poate asocia un număr de ordine și există predecesor/succesor.',
                    'Enumerarea are ordine bine definită (Luni < Marti < Miercuri), deci e un tip ordinal. Subdomeniul s-ar scrie ca interval, de ex. `1..10`.',
                ],
                'explanation' => 'Tipul enumerare este definit prin lista explicită a valorilor și este ordinal: fiecare valoare are un număr de ordine, cu predecesor și succesor. Nu e structurat, pentru că o variabilă de acest tip stochează o singură valoare.',
            ],
            [
                'ref' => 'seed:inf:tipuri:definitie-vs-declaratie',
                'skill' => 'tipuri-simple',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Care dintre următoarele este o **definiție de tip**, nu o declarație de variabilă sau o definiție de constantă?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => '`type Vector = array[1..10] of integer;`'],
                        ['key' => 'b', 'text' => '`var x: integer;`'],
                        ['key' => 'c', 'text' => '`const n = 10;`'],
                        ['key' => 'd', 'text' => '`x := 5;`'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Programa distinge trei lucruri diferite: definiții de constante, definiții de tipuri și declarații de variabile.',
                    'O definiție de tip introduce un NUME NOU pentru o structură de date, fără să rezerve memorie.',
                    '`type` introduce tipul; `var` rezervă memorie pentru o variabilă; `const` fixează o valoare; `:=` e o instrucțiune de atribuire, nu o declarație.',
                ],
                'explanation' => 'Doar `type` definește un tip nou. `var` declară o variabilă (rezervă memorie), `const` definește o constantă, iar `x := 5` este o instrucțiune executabilă, nu o declarație.',
            ],

            // ==================== INSTRUCȚIUNI ====================
            [
                'ref' => 'seed:inf:instr:for-suma-impare',
                'skill' => 'instructiuni',
                'type' => ItemType::CodeOutput,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Ce valoare se afișează la executarea programului?',
                    'code_variants' => [
                        'cpp' => "int s = 0;\nfor (int i = 1; i <= 5; i++)\n    if (i % 2 == 1)\n        s = s + i;\ncout << s;",
                        'pascal' => "s := 0;\nfor i := 1 to 5 do\n  if i mod 2 = 1 then\n    s := s + i;\nwrite(s);",
                    ],
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => '9'],
                        ['key' => 'b', 'text' => '15'],
                        ['key' => 'c', 'text' => '6'],
                        ['key' => 'd', 'text' => '5'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Urmărește ciclul pas cu pas și scrie pe hârtie valoarea lui `s` după fiecare iterație.',
                    'Condiția din interior selectează doar valorile impare ale lui `i` din intervalul 1…5.',
                    'Impare sunt 1, 3 și 5, deci $s = 1 + 3 + 5$. Varianta 15 ar fi suma TUTUROR numerelor de la 1 la 5.',
                ],
                'explanation' => 'Ciclul parcurge $i = 1..5$, iar condiția `i mod 2 = 1` reține doar impare: $1 + 3 + 5 = 9$. Capcana itemului e varianta 15, adică suma tuturor valorilor, ignorând condiția.',
            ],
            [
                'ref' => 'seed:inf:instr:while-suma-cifre',
                'skill' => 'instructiuni',
                'type' => ItemType::CodeOutput,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Ce valoare se afișează la executarea programului?',
                    'code_variants' => [
                        'cpp' => "int n = 345, c = 0;\nwhile (n > 0) {\n    c = c + n % 10;\n    n = n / 10;\n}\ncout << c;",
                        'pascal' => "n := 345; c := 0;\nwhile n > 0 do\nbegin\n  c := c + n mod 10;\n  n := n div 10;\nend;\nwrite(c);",
                    ],
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => '12'],
                        ['key' => 'b', 'text' => '345'],
                        ['key' => 'c', 'text' => '9'],
                        ['key' => 'd', 'text' => '15'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Operatorul `%` (respectiv `mod`) dă restul împărțirii la 10 — adică ultima cifră.',
                    'Împărțirea întreagă la 10 (`/` în C++ pe întregi, `div` în Pascal) șterge ultima cifră.',
                    'Ciclul adună cifrele una câte una: $3 + 4 + 5 = 12$. Varianta 345 apare dacă crezi că se concatenează cifrele, nu se adună.',
                ],
                'explanation' => 'Combinația „rest la 10" + „împărțire întreagă la 10" e algoritmul standard de parcurgere a cifrelor. Pentru 345 se obține $5 + 4 + 3 = 12$.',
            ],
            [
                'ref' => 'seed:inf:instr:acumulator-neinitializat',
                'skill' => 'instructiuni',
                'type' => ItemType::FindError,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Programul ar trebui să calculeze suma elementelor tabloului `a[1..n]`, dar dă rezultate imprevizibile. Unde este greșeala?',
                    'code_variants' => [
                        'cpp' => "int s;\nfor (int i = 1; i <= n; i++)\n    s = s + a[i];\ncout << s;",
                        'pascal' => "for i := 1 to n do\n  s := s + a[i];\nwrite(s);",
                    ],
                    'choices' => [
                        ['key' => 'a', 'text' => 'Variabila `s` nu este inițializată cu 0 înainte de ciclu'],
                        ['key' => 'b', 'text' => 'Ciclul ar trebui să înceapă de la 0, nu de la 1'],
                        ['key' => 'c', 'text' => 'Lipsește instrucțiunea de citire a tabloului'],
                        ['key' => 'd', 'text' => 'Trebuia folosit `while` în loc de `for`'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    '„Rezultate imprevizibile" e un indiciu puternic: valoarea depinde de ceva ce programul nu controlează.',
                    'Ce valoare are o variabilă înainte de prima atribuire? Uită-te la ce se face cu `s` la prima iterație.',
                    '`s = s + a[i]` citește `s` înainte să-i fi dat vreo valoare. Fără `s := 0` inițial, se adună la conținutul aleator al memoriei.',
                ],
                'explanation' => 'Orice acumulator trebuie inițializat înainte de ciclu: `s := 0` pentru sumă, `p := 1` pentru produs. E una dintre cele mai frecvente greșeli la examen, iar simptomul tipic e „merge uneori".',
            ],

            // ==================== TIPURI STRUCTURATE ====================
            [
                'ref' => 'seed:inf:struct:off-by-one',
                'skill' => 'tipuri-structurate',
                'type' => ItemType::FindError,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Programul trebuie să calculeze suma $1 + 2 + \ldots + n$, dar rezultatul e mereu mai mic decât cel corect. Unde este greșeala?',
                    'code_variants' => [
                        'cpp' => "int s = 0;\nfor (int i = 1; i < n; i++)\n    s = s + i;\ncout << s;",
                        'pascal' => "s := 0;\nfor i := 1 to n - 1 do\n  s := s + i;\nwrite(s);",
                    ],
                    'choices' => [
                        ['key' => 'a', 'text' => 'Ciclul se oprește înainte de `n`, deci ultimul termen lipsește'],
                        ['key' => 'b', 'text' => 'Acumulatorul `s` nu este inițializat'],
                        ['key' => 'c', 'text' => 'Ar trebui `s = s + 1` în loc de `s = s + i`'],
                        ['key' => 'd', 'text' => 'Nu există nicio greșeală'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Verifică pe un caz mic, de exemplu $n = 3$: ce ar trebui să dea și ce dă programul?',
                    'Pentru $n = 3$ suma corectă e 6, dar programul dă 3. Lipsește exact un termen.',
                    'Condiția `i < n` (respectiv `to n - 1`) exclude valoarea `n`. Corect ar fi `i <= n`, respectiv `to n`.',
                ],
                'explanation' => 'Eroarea „off-by-one" e cea mai frecventă la ciclurile cu limită. Verificarea pe un caz mic ($n = 3$) o depistează instant — o tehnică pe care merită să o aplici la fiecare ciclu scris la examen.',
            ],
            [
                'ref' => 'seed:inf:struct:numara-caractere',
                'skill' => 'tipuri-structurate',
                'type' => ItemType::CodeOutput,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Ce valoare se afișează pentru șirul `"informatica"`?',
                    'code_variants' => [
                        'cpp' => "char s[] = \"informatica\";\nint k = 0;\nfor (int i = 0; s[i] != '\\0'; i++)\n    if (s[i] == 'a')\n        k++;\ncout << k;",
                        'pascal' => "s := 'informatica';\nk := 0;\nfor i := 1 to length(s) do\n  if s[i] = 'a' then\n    k := k + 1;\nwrite(k);",
                    ],
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => '2'],
                        ['key' => 'b', 'text' => '3'],
                        ['key' => 'c', 'text' => '1'],
                        ['key' => 'd', 'text' => '11'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Programul numără aparițiile unui singur caracter. Scrie cuvântul pe litere și marchează-le.',
                    'i-n-f-o-r-m-a-t-i-c-a — câte litere `a` vezi?',
                    'Există `a` pe poziția 7 și pe ultima poziție: 2 apariții. Varianta 11 e lungimea totală a șirului.',
                ],
                'explanation' => 'În „informatica" litera `a` apare de două ori. Atenție la diferența de indexare: în C++ șirul începe de la indicele 0 și se termină cu caracterul `\'\\0\'`, în Pascal indicii merg de la 1 la `length(s)`.',
            ],

            // ==================== SUBPROGRAME ====================
            [
                'ref' => 'seed:inf:subprog:ordine-apel',
                'skill' => 'subprograme',
                'type' => ItemType::OrderSteps,
                'difficulty' => 3,
                'points' => 4,
                'payload' => [
                    'stem' => 'Pune în ordine etapele executării unui apel de subprogram.',
                    'steps' => [
                        ['key' => 's1', 'text' => 'Se evaluează expresiile-argument din instrucțiunea de apel'],
                        ['key' => 's2', 'text' => 'Se alocă memorie pentru parametrii formali și variabilele locale'],
                        ['key' => 's3', 'text' => 'Valorile argumentelor se transmit parametrilor formali'],
                        ['key' => 's4', 'text' => 'Se execută instrucțiunile din corpul subprogramului'],
                        ['key' => 's5', 'text' => 'Se eliberează memoria alocată variabilelor locale'],
                        ['key' => 's6', 'text' => 'Controlul revine la instrucțiunea de după apel'],
                    ],
                ],
                'answer' => ['order' => ['s1', 's2', 's3', 's4', 's5', 's6']],
                'hints' => [
                    'Argumentele trebuie să aibă valori înainte ca ele să poată fi transmise mai departe.',
                    'Memoria pentru parametri se alocă înainte de a primi valori, iar se eliberează după terminarea corpului.',
                    'Ordinea e: evaluare → alocare → transmitere → execuție → eliberare → revenire. Eliberarea memoriei locale e motivul pentru care o variabilă locală nu-și păstrează valoarea între apeluri.',
                ],
                'explanation' => 'Această secvență explică de ce variabilele locale nu supraviețuiesc între apeluri: memoria lor se eliberează la pasul 5. Tot ea explică și cum funcționează recursia — fiecare apel are propriul set de variabile locale.',
            ],
            [
                'ref' => 'seed:inf:subprog:recursie-factorial',
                'skill' => 'recursie',
                'type' => ItemType::CodeOutput,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Ce valoare se afișează la apelul `f(4)`?',
                    'code_variants' => [
                        'cpp' => "int f(int n) {\n    if (n <= 1)\n        return 1;\n    return n * f(n - 1);\n}\n\n// cout << f(4);",
                        'pascal' => "function f(n: integer): integer;\nbegin\n  if n <= 1 then\n    f := 1\n  else\n    f := n * f(n - 1);\nend;\n\n{ write(f(4)); }",
                    ],
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => '24'],
                        ['key' => 'b', 'text' => '10'],
                        ['key' => 'c', 'text' => '4'],
                        ['key' => 'd', 'text' => '12'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Desfășoară apelurile pe hârtie: $f(4)$ are nevoie de $f(3)$, care are nevoie de $f(2)$, și așa mai departe.',
                    'Cazul de oprire e $n \\le 1$, unde funcția întoarce 1. De acolo rezultatele se întorc înmulțindu-se.',
                    '$f(4) = 4 \\cdot f(3) = 4 \\cdot 3 \\cdot f(2) = 4 \\cdot 3 \\cdot 2 \\cdot f(1) = 4 \\cdot 3 \\cdot 2 \\cdot 1 = 24$. Varianta 10 e suma $4+3+2+1$.',
                ],
                'explanation' => 'Funcția calculează factorialul: $4! = 24$. Capcana e varianta 10 — suma în loc de produs. La itemii recursivi, desfășurarea explicită a apelurilor până la cazul de oprire e metoda sigură.',
            ],

            // ==================== SISTEME DE NUMERAȚIE ====================
            [
                'ref' => 'seed:inf:num:binar-zecimal',
                'skill' => 'sisteme-numeratie',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'payload' => [
                    'unit' => 'în baza 10',
                    'stem' => 'Convertește numărul $1101_{(2)}$ în sistemul zecimal.',
                ],
                'answer' => ['value' => 13, 'tolerance' => 0, 'base' => 10],
                'hints' => [
                    'Fiecare cifră binară are o pondere: puterile lui 2, crescând de la dreapta spre stânga.',
                    'Ponderile sunt $2^3, 2^2, 2^1, 2^0$, adică 8, 4, 2, 1. Adună doar ponderile unde cifra e 1.',
                    '$1 \\cdot 8 + 1 \\cdot 4 + 0 \\cdot 2 + 1 \\cdot 1 = 13$.',
                ],
                'explanation' => 'Într-un sistem pozițional, valoarea e suma cifrelor înmulțite cu puterile bazei: $1101_{(2)} = 8 + 4 + 0 + 1 = 13_{(10)}$.',
            ],
            [
                'ref' => 'seed:inf:num:conversii-255',
                'skill' => 'conversii-baze',
                'type' => ItemType::FillBlanks,
                'difficulty' => 3,
                'points' => 2,
                'payload' => [
                    'stem' => 'Completează reprezentările numărului $255_{(10)}$ în bazele indicate.',
                    'stem_template' => '255₁₀ = ___₁₆ = ___₈',
                    'blanks' => [
                        ['key' => 'hexazecimal'],
                        ['key' => 'octal'],
                    ],
                ],
                'answer' => ['values' => [
                    'hexazecimal' => ['FF', 'ff', '0xFF'],
                    'octal' => ['377', '0377'],
                ]],
                'hints' => [
                    'Trece mai întâi prin binar: 255 este cel mai mare număr reprezentabil pe 8 biți.',
                    '$255_{(10)} = 11111111_{(2)}$. Pentru hexazecimal grupezi biții câte 4, pentru octal câte 3, de la dreapta.',
                    'Hexazecimal: $1111\\,1111 \\to$ F F. Octal: $011\\,111\\,111 \\to$ 3 7 7.',
                ],
                'explanation' => 'Gruparea biților e metoda rapidă: 4 biți dau o cifră hexazecimală, 3 biți dau o cifră octală. De aceea 255 se scrie FF pe doi digiți hexazecimali — exact un octet.',
            ],

            // ==================== INFORMAȚIA ====================
            [
                'ref' => 'seed:inf:info:cantitate-32-mesaje',
                'skill' => 'cantitatea-informatie',
                'type' => ItemType::Numeric,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'O sursă emite 32 de mesaje echiprobabile. Câți biți sunt necesari pentru a codifica un mesaj?',
                    'unit' => 'biți',
                ],
                'answer' => ['value' => 5, 'tolerance' => 0],
                'hints' => [
                    'Cu $k$ biți poți distinge $2^k$ mesaje diferite. Caută $k$ pentru care ajunge exact.',
                    'Formula pentru mesaje echiprobabile este $I = \\log_2 N$, unde $N$ e numărul de mesaje.',
                    '$2^5 = 32$, deci $I = \\log_2 32 = 5$ biți.',
                ],
                'explanation' => 'Pentru $N$ mesaje echiprobabile, cantitatea de informație a unui mesaj este $I = \\log_2 N$. Cu 5 biți se codifică exact 32 de combinații distincte.',
            ],
            [
                'ref' => 'seed:inf:info:codificare-alfabet',
                'skill' => 'codificare',
                'type' => ItemType::Numeric,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Un alfabet conține 26 de semne. Care este numărul MINIM de biți necesari pentru a codifica un semn, folosind coduri de lungime fixă?',
                    'unit' => 'biți',
                ],
                'answer' => ['value' => 5, 'tolerance' => 0],
                'hints' => [
                    'Codurile au lungime fixă, deci ai nevoie de suficiente combinații pentru toate cele 26 de semne.',
                    'Cu 4 biți ai $2^4 = 16$ combinații — insuficient. Încearcă mai departe.',
                    '$2^5 = 32 \\ge 26$, deci 5 biți. Formula generală: $k = \\lceil \\log_2 N \\rceil$, adică rotunjire în SUS.',
                ],
                'explanation' => 'Când $N$ nu e o putere a lui 2, numărul de biți se rotunjește în sus: $\\lceil \\log_2 26 \\rceil = \\lceil 4{,}7 \\rceil = 5$. Rămân 6 combinații nefolosite — costul lungimii fixe.',
            ],

            // ==================== ALGEBRA BOOLEANĂ ====================
            [
                'ref' => 'seed:inf:bool:operatori',
                'skill' => 'algebra-booleana',
                'type' => ItemType::Match,
                'difficulty' => 2,
                'points' => 4,
                'payload' => [
                    'stem' => 'Potrivește fiecare operator logic cu comportamentul lui.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'NU (NOT)'],
                        ['key' => 'l2', 'text' => 'ȘI (AND)'],
                        ['key' => 'l3', 'text' => 'SAU (OR)'],
                        ['key' => 'l4', 'text' => 'SAU-EXCLUSIV (XOR)'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'Rezultatul e 1 doar când valorile de intrare diferă'],
                        ['key' => 'r2', 'text' => 'Inversează valoarea de intrare'],
                        ['key' => 'r3', 'text' => 'Rezultatul e 1 doar când ambele intrări sunt 1'],
                        ['key' => 'r4', 'text' => 'Rezultatul e 1 când cel puțin o intrare este 1'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r2', 'l2' => 'r3', 'l3' => 'r4', 'l4' => 'r1']],
                'hints' => [
                    'Începe cu NU: e singurul operator cu o singură intrare.',
                    'ȘI e cel mai „exigent" — cere ca toate intrările să fie 1. SAU e cel mai „permisiv".',
                    'Rămân SAU și XOR: diferența e că SAU dă 1 și când ambele sunt 1, iar XOR dă 0 în acel caz.',
                ],
                'explanation' => 'Diferența dintre SAU și XOR e capcana clasică: pentru intrările $1, 1$, SAU dă 1, iar XOR dă 0. XOR răspunde de fapt la întrebarea „valorile sunt diferite?".',
            ],
            [
                'ref' => 'seed:inf:bool:combinatii-expresie',
                'skill' => 'algebra-booleana',
                'type' => ItemType::Numeric,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'Pentru câte dintre cele 8 combinații ale variabilelor logice $x, y, z$ expresia $(x \\text{ ȘI } y) \\text{ SAU } (\\text{NU } z)$ are valoarea 1?',
                    'unit' => 'combinații',
                ],
                'answer' => ['value' => 5, 'tolerance' => 0],
                'hints' => [
                    'Construiește tabelul de adevăr, dar nu e nevoie de toate cele 8 rânduri dacă separi cazurile după $z$.',
                    'Când $z = 0$, termenul NU $z$ e 1, deci toată expresia e 1 — indiferent de $x$ și $y$. Câte combinații sunt astea?',
                    '$z = 0$ dă 4 combinații, toate 1. Pentru $z = 1$ rămâne doar $x$ ȘI $y$, adevărat într-un singur caz ($x = y = 1$). Total: $4 + 1 = 5$.',
                ],
                'explanation' => 'Separarea cazurilor după variabila care apare într-un singur termen scurtează mult lucrul: în loc de 8 rânduri de tabel, două ramuri. Rezultat: 5 combinații.',
            ],

            // ==================== CIRCUITE LOGICE ====================
            [
                'ref' => 'seed:inf:circuite:combinationale',
                'skill' => 'circuite-logice',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 4,
                'payload' => [
                    'stem' => 'Potrivește fiecare circuit combinațional cu funcția lui.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'Sumator'],
                        ['key' => 'l2', 'text' => 'Comparator'],
                        ['key' => 'l3', 'text' => 'Decodificator'],
                        ['key' => 'l4', 'text' => 'Multiplexor'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'Activează o singură ieșire, aleasă după codul aplicat la intrare'],
                        ['key' => 'r2', 'text' => 'Adună biți și produce suma împreună cu transportul'],
                        ['key' => 'r3', 'text' => 'Transmite la ieșire una dintre mai multe intrări, selectată prin adresă'],
                        ['key' => 'r4', 'text' => 'Indică relația dintre două numere aplicate la intrări'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r2', 'l2' => 'r4', 'l3' => 'r1', 'l4' => 'r3']],
                'hints' => [
                    'Două dintre ele au nume care spun direct ce fac: sumatorul și comparatorul.',
                    'Decodificatorul și multiplexorul se confundă des. Numără ieșirile: unul are MULTE ieșiri, celălalt are UNA.',
                    'Decodificatorul: un cod la intrare → activează una din multele ieșiri. Multiplexorul: multe intrări → o singură ieșire, aleasă prin adresă. Sunt operații inverse.',
                ],
                'explanation' => 'Decodificatorul și multiplexorul sunt duale: primul distribuie (1 cod → n ieșiri), al doilea selectează (n intrări → 1 ieșire). Reținerea numărului de ieșiri e cel mai simplu mod de a nu le încurca.',
            ],

            // ==================== CALCUL NUMERIC ====================
            [
                'ref' => 'seed:inf:numeric:bisectie-pasi',
                'skill' => 'metode-iterative',
                'type' => ItemType::OrderSteps,
                'difficulty' => 4,
                'points' => 4,
                'payload' => [
                    'stem' => 'Pune în ordine pașii metodei bisecției pentru rezolvarea ecuației $f(x) = 0$ pe intervalul $[a, b]$.',
                    'steps' => [
                        ['key' => 's1', 'text' => 'Se verifică condiția de aplicare: $f(a) \\cdot f(b) < 0$'],
                        ['key' => 's2', 'text' => 'Se calculează mijlocul intervalului: $c = (a + b) / 2$'],
                        ['key' => 's3', 'text' => 'Se calculează $f(c)$'],
                        ['key' => 's4', 'text' => 'Dacă $f(a) \\cdot f(c) < 0$ atunci $b := c$, altfel $a := c$'],
                        ['key' => 's5', 'text' => 'Se repetă până când $|b - a| < \\varepsilon$'],
                        ['key' => 's6', 'text' => 'Se afișează $c$ ca soluție aproximativă'],
                    ],
                ],
                'answer' => ['order' => ['s1', 's2', 's3', 's4', 's5', 's6']],
                'hints' => [
                    'Metoda nu poate începe fără să știi că soluția există în interval. Ce garantează asta?',
                    'Condiția $f(a) \\cdot f(b) < 0$ înseamnă că funcția schimbă semnul, deci taie axa — se verifică o singură dată, la început.',
                    'După verificare, ciclul e: mijloc → valoarea funcției în mijloc → alegerea jumătății care conține soluția → repetare până la precizia cerută.',
                ],
                'explanation' => 'Verificarea $f(a) \\cdot f(b) < 0$ e condiția de aplicare și se face O SINGURĂ DATĂ, înainte de ciclu — o greșeală frecventă e să o pui în interiorul buclei. La fiecare iterație intervalul se înjumătățește, de unde și numele metodei.',
            ],

            // ==================== BAZE DE DATE ====================
            [
                'ref' => 'seed:inf:bd:cheie-primara',
                'skill' => 'tabele',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Ce rol are cheia primară a unui tabel într-o bază de date?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Identifică în mod unic fiecare înregistrare din tabel'],
                        ['key' => 'b', 'text' => 'Sortează automat înregistrările la afișare'],
                        ['key' => 'c', 'text' => 'Stabilește tipurile de date ale câmpurilor'],
                        ['key' => 'd', 'text' => 'Creează un filtru aplicat implicit interogărilor'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Gândește-te ce s-ar întâmpla fără ea: cum ai deosebi două înregistrări cu exact aceleași valori?',
                    'Cuvântul-cheie e „unic". Cheia primară nu poate avea valori duplicate și nu poate fi vidă.',
                    'Ea identifică unic înregistrarea și, prin asta, face posibile corelațiile între tabele. Sortarea și filtrarea sunt operații separate.',
                ],
                'explanation' => 'Cheia primară garantează unicitatea înregistrărilor și e baza corelațiilor dintre tabele: un alt tabel se leagă de acesta referind exact valoarea cheii primare.',
            ],

            // ==================== HOLDOUT (rezervat simulărilor) ====================
            [
                'ref' => 'seed:inf:mock:recursie-fibonacci',
                'skill' => 'recursie',
                'type' => ItemType::CodeOutput,
                'difficulty' => 4,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Ce valoare se afișează la apelul `g(6)`?',
                    'code_variants' => [
                        'cpp' => "int g(int n) {\n    if (n <= 2)\n        return 1;\n    return g(n - 1) + g(n - 2);\n}\n\n// cout << g(6);",
                        'pascal' => "function g(n: integer): integer;\nbegin\n  if n <= 2 then\n    g := 1\n  else\n    g := g(n - 1) + g(n - 2);\nend;\n\n{ write(g(6)); }",
                    ],
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => '8'],
                        ['key' => 'b', 'text' => '13'],
                        ['key' => 'c', 'text' => '6'],
                        ['key' => 'd', 'text' => '5'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Construiește șirul de la cazul de oprire în sus, în loc să desfășori apelurile de sus în jos.',
                    '$g(1) = g(2) = 1$, iar fiecare termen următor e suma celor doi anteriori.',
                    '$1, 1, 2, 3, 5, 8$ — al șaselea termen e 8. Varianta 13 e al șaptelea.',
                ],
                'explanation' => 'Funcția generează șirul Fibonacci. Calculul „de jos în sus" e mult mai sigur decât desfășurarea recursivă și e exact ideea din spatele variantei iterative, mai eficiente.',
            ],
            [
                'ref' => 'seed:inf:mock:binar-zecimal',
                'skill' => 'conversii-baze',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'unit' => 'în baza 10',
                    'stem' => 'Scrie în baza 10 numărul $101101_{(2)}$.',
                ],
                'answer' => ['value' => 45, 'tolerance' => 0, 'base' => 10],
                'hints' => [
                    'Fiecare cifră se înmulțește cu puterea lui 2 corespunzătoare poziției, numărând de la dreapta, de la $2^0$.',
                    'Cifrele 1 stau pe pozițiile $2^5$, $2^3$, $2^2$ și $2^0$.',
                    '$32 + 8 + 4 + 1 = 45$.',
                ],
                'explanation' => '$101101_{(2)} = 1 \cdot 2^5 + 0 \cdot 2^4 + 1 \cdot 2^3 + 1 \cdot 2^2 + 0 \cdot 2^1 + 1 \cdot 2^0 = 32 + 8 + 4 + 1 = 45$.',
            ],
            [
                'ref' => 'seed:inf:mock:de-morgan',
                'skill' => 'algebra-booleana',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Care expresie este echivalentă cu `not (A and B)` pentru orice valori logice ale lui `A` și `B`?',
                    'choices' => [
                        ['key' => 'a', 'text' => '`(not A) or (not B)`'],
                        ['key' => 'b', 'text' => '`(not A) and (not B)`'],
                        ['key' => 'c', 'text' => '`A or B`'],
                        ['key' => 'd', 'text' => '`(not A) and B`'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Încearcă un caz concret: `A = true`, `B = false`. Cât dă expresia inițială și cât dă fiecare variantă?',
                    'Negația „ambele sunt adevărate" înseamnă „cel puțin una e falsă".',
                    'Legea lui De Morgan: negația unei conjuncții e disjuncția negațiilor — `and` devine `or`.',
                ],
                'explanation' => 'Conform legii lui De Morgan, `not (A and B)` = `(not A) or (not B)`. Varianta cu `and` este negația lui `A or B`, cealaltă lege a lui De Morgan.',
            ],
            [
                'ref' => 'seed:inf:mock:biti-simboluri',
                'skill' => 'cantitatea-informatie',
                'type' => ItemType::FillBlanks,
                'difficulty' => 2,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Completează enunțul.',
                    'stem_template' => 'Pentru a codifica 32 de simboluri distincte, fiecare cu același număr de biți, sunt necesari cel puțin ___ biți.',
                    'blanks' => [
                        ['key' => 'biti', 'options' => ['5', '4', '6', '32']],
                    ],
                ],
                'answer' => ['values' => ['biti' => ['5']]],
                'hints' => [
                    'Cu $k$ biți se pot forma $2^k$ coduri diferite.',
                    'Cauți cel mai mic $k$ pentru care $2^k \ge 32$.',
                    '$2^5 = 32$, deci 5 biți ajung exact. Cu 4 biți ai doar 16 coduri.',
                ],
                'explanation' => 'Numărul minim de biți este cel mai mic $k$ cu $2^k \ge N$. Pentru $N = 32$, $2^5 = 32$, deci $k = 5$. Cantitatea de informație a unui simbol din 32 egal probabile este $\log_2 32 = 5$ biți.',
            ],
            [
                'ref' => 'seed:inf:mock:cifre-baza-2',
                'skill' => 'sisteme-numeratie',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Câte cifre are numărul zecimal 255 scris în baza 2?',
                ],
                'answer' => ['value' => 8, 'tolerance' => 0],
                'hints' => [
                    'Un număr cu $k$ cifre binare e cel mult $2^k - 1$.',
                    '$2^8 - 1 = 255$.',
                    '$255 = 11111111_{(2)}$ — opt cifre de 1. Numărul următor, 256, ar avea deja nouă cifre.',
                ],
                'explanation' => '$255 = 2^8 - 1 = 11111111_{(2)}$, deci are 8 cifre binare. E cel mai mare număr care încape într-un octet.',
            ],
            [
                'ref' => 'seed:inf:mock:suma-impare',
                'skill' => 'instructiuni',
                'type' => ItemType::CodeOutput,
                'difficulty' => 2,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Ce se afișează după executarea secvenței?',
                    'code_variants' => [
                        'cpp' => "int s = 0;\nfor (int i = 1; i <= 6; i++)\n    if (i % 2 == 1)\n        s = s + i;\ncout << s;",
                        'pascal' => "s := 0;\nfor i := 1 to 6 do\n  if i mod 2 = 1 then\n    s := s + i;\nwrite(s);",
                    ],
                    'choices' => [
                        ['key' => 'a', 'text' => '9'],
                        ['key' => 'b', 'text' => '12'],
                        ['key' => 'c', 'text' => '21'],
                        ['key' => 'd', 'text' => '6'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Condiția `i % 2 == 1` (în Pascal, `i mod 2 = 1`) e adevărată doar pentru numerele impare.',
                    'Între 1 și 6, numerele impare sunt 1, 3 și 5.',
                    '$1 + 3 + 5 = 9$. Varianta 12 e suma numerelor pare, iar 21 e suma tuturor.',
                ],
                'explanation' => 'Se adună doar valorile impare ale lui `i`: $1 + 3 + 5 = 9$. Varianta 12 corespunde numerelor pare, iar 21 sumei fără condiție.',
            ],
            [
                'ref' => 'seed:inf:mock:maxim-initializare-eroare',
                'skill' => 'tipuri-structurate',
                'type' => ItemType::FindError,
                'difficulty' => 3,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Secvența trebuie să afișeze cel mai mare element al tabloului `a[1..n]`. Pentru tabloul $(-5, -2, -9)$ afișează însă 0. Unde este greșeala?',
                    'code_variants' => [
                        'cpp' => "int maxim = 0;\nfor (int i = 1; i <= n; i++)\n    if (a[i] > maxim)\n        maxim = a[i];\ncout << maxim;",
                        'pascal' => "maxim := 0;\nfor i := 1 to n do\n  if a[i] > maxim then\n    maxim := a[i];\nwrite(maxim);",
                    ],
                    'choices' => [
                        ['key' => 'a', 'text' => '`maxim` e inițializat cu 0, care poate să nu fie în tablou; trebuie inițializat cu `a[1]`'],
                        ['key' => 'b', 'text' => 'Condiția corectă este `a[i] < maxim`'],
                        ['key' => 'c', 'text' => 'Ciclul trebuie să înceapă de la 0'],
                        ['key' => 'd', 'text' => 'Nu există nicio greșeală: maximul unui tablou negativ este 0'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Rezultatul 0 nu apare nicăieri în tablou. De unde a venit?',
                    'Dacă toate elementele sunt negative, condiția `a[i] > maxim` nu e adevărată niciodată.',
                    'Valoarea de pornire trebuie să fie un element real al tabloului — de obicei primul.',
                ],
                'explanation' => 'Cu `maxim = 0` și toate elementele negative, condiția nu se îndeplinește niciodată, iar programul afișează o valoare care nu există în tablou. Inițializarea corectă e `maxim = a[1]`, iar ciclul poate porni apoi de la 2.',
            ],
            [
                'ref' => 'seed:inf:mock:numar-cifre-ordonare',
                'skill' => 'instructiuni',
                'type' => ItemType::OrderSteps,
                'difficulty' => 2,
                'points' => 4,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Ordonează pașii algoritmului care afișează numărul de cifre ale unui număr natural nenul `n`.',
                    'steps' => [
                        ['key' => 's1', 'text' => 'Citesc `n`'],
                        ['key' => 's2', 'text' => 'Inițializez contorul: `k ← 0`'],
                        ['key' => 's3', 'text' => 'Cât timp `n > 0`, repet pasul următor'],
                        ['key' => 's4', 'text' => '`n ← n div 10` și `k ← k + 1`'],
                        ['key' => 's5', 'text' => 'Afișez `k`'],
                    ],
                ],
                'answer' => ['order' => ['s1', 's2', 's3', 's4', 's5']],
                'hints' => [
                    'Orice contor se inițializează înainte de ciclul în care crește.',
                    'La fiecare împărțire întreagă la 10, numărul pierde ultima cifră.',
                    'Rezultatul se afișează abia după ce ciclul s-a terminat.',
                ],
                'explanation' => 'Structura e cea a oricărui algoritm de numărare: citire, inițializarea contorului, ciclul care elimină câte o cifră (`n div 10`) și numără, apoi afișarea. Afișarea în interiorul ciclului ar tipări toate valorile intermediare.',
            ],
            [
                'ref' => 'seed:inf:mock:parametru-referinta',
                'skill' => 'subprograme',
                'type' => ItemType::CodeOutput,
                'difficulty' => 3,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Ce se afișează după executarea secvenței?',
                    'code_variants' => [
                        'cpp' => "void f(int &a, int b) {\n    a = a + b;\n    b = b * 2;\n}\n\nint x = 3, y = 4;\nf(x, y);\ncout << x << \" \" << y;",
                        'pascal' => "procedure f(var a: integer; b: integer);\nbegin\n  a := a + b;\n  b := b * 2;\nend;\n\nx := 3;\ny := 4;\nf(x, y);\nwrite(x, ' ', y);",
                    ],
                    'choices' => [
                        ['key' => 'a', 'text' => '7 4'],
                        ['key' => 'b', 'text' => '7 8'],
                        ['key' => 'c', 'text' => '3 4'],
                        ['key' => 'd', 'text' => '3 8'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    'Uită-te la felul în care e declarat fiecare parametru: unul are `&` (în Pascal, `var`), celălalt nu.',
                    'Parametrul transmis prin referință modifică variabila originală; cel transmis prin valoare lucrează pe o copie.',
                    '`x` devine $3 + 4 = 7$; dublarea lui `b` se face pe copie, deci `y` rămâne 4.',
                ],
                'explanation' => '`a` e parametru prin referință (`&a`, respectiv `var a`), deci `x` devine 7. `b` e parametru prin valoare: dublarea se face pe o copie locală, iar `y` rămâne 4.',
            ],
            [
                'ref' => 'seed:inf:mock:porti-logice',
                'skill' => 'circuite-logice',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 4,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Potrivește fiecare poartă logică cu două intrări cu descrierea ieșirii ei.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'AND (ȘI)'],
                        ['key' => 'l2', 'text' => 'OR (SAU)'],
                        ['key' => 'l3', 'text' => 'XOR (SAU exclusiv)'],
                        ['key' => 'l4', 'text' => 'NAND (ȘI-NU)'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'Ieșirea e 1 dacă cel puțin o intrare e 1'],
                        ['key' => 'r2', 'text' => 'Ieșirea e 0 doar dacă ambele intrări sunt 1'],
                        ['key' => 'r3', 'text' => 'Ieșirea e 1 doar dacă ambele intrări sunt 1'],
                        ['key' => 'r4', 'text' => 'Ieșirea e 1 doar dacă intrările sunt diferite'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r3', 'l2' => 'r1', 'l3' => 'r4', 'l4' => 'r2']],
                'hints' => [
                    'Începe cu porțile de bază, AND și OR, apoi deduce-le pe celelalte.',
                    'NAND este negația lui AND: unde AND dă 1, NAND dă 0.',
                    'XOR diferă de OR într-un singur caz: când ambele intrări sunt 1, XOR dă 0.',
                ],
                'explanation' => 'AND: 1 doar pentru (1, 1). OR: 1 dacă cel puțin o intrare e 1. XOR: 1 dacă intrările diferă. NAND: negația lui AND, deci 0 doar pentru (1, 1).',
            ],
            [
                'ref' => 'seed:inf:mock:sql-count',
                'skill' => 'interogari',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'holdout' => true,
                'source' => 'holdout-simulare',
                'payload' => [
                    'stem' => 'Tabelul `Elevi` are câmpurile `Nume`, `Clasa` și `Nota`. Ce returnează interogarea de mai jos?',
                    // SQL, nu Pascal sau C++: se afișează o singură dată (§12 privește
                    // limbajele de programare, nu interogările).
                    'code' => "SELECT COUNT(*)\nFROM Elevi\nWHERE Nota >= 9;",
                    'choices' => [
                        ['key' => 'a', 'text' => 'Numărul elevilor care au nota cel puțin 9'],
                        ['key' => 'b', 'text' => 'Lista elevilor care au nota cel puțin 9'],
                        ['key' => 'c', 'text' => 'Suma notelor mai mari sau egale cu 9'],
                        ['key' => 'd', 'text' => 'Numărul total de elevi din tabel'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'hints' => [
                    '`WHERE` filtrează înregistrările înainte de orice calcul.',
                    '`COUNT(*)` nu întoarce înregistrările, ci numărul lor.',
                    'Deci rezultatul e un singur număr: câte înregistrări au trecut de filtrul `Nota >= 9`.',
                ],
                'explanation' => '`COUNT(*)` numără înregistrările care trec de condiția din `WHERE`, deci rezultatul e un singur număr: câți elevi au nota cel puțin 9. Lista lor s-ar obține cu `SELECT Nume`, iar suma cu `SUM(Nota)`.',
            ],
        ];
    }

    /**
     * Programare: tipuri simple, instrucțiuni, tipuri structurate, subprograme,
     * recursie.
     *
     * Orice item cu cod are `code_variants` cu C++ ȘI Pascal (§12) — examenul
     * acceptă ambele limbaje, la alegerea candidatului, iar ContentIntegrityTest
     * respinge un item scris într-unul singur.
     *
     * @return list<array<string, mixed>>
     */
    private function itemsProgramare(): array
    {
        return [
            // ---------- Tipuri de date simple ----------
            [
                'ref' => 'seed:inf:tipuri:impartire-intreaga',
                'hints' => [
                    'Ambii operanzi sunt întregi, deci împărțirea e întreagă.',
                    'Partea zecimală se TRUNCHIAZĂ, nu se rotunjește: $7 : 2$ dă 3, nu 4.',
                    'Apoi $3 \\cdot 2 = 6$, iar restul $7 \\bmod 2 = 1$. Se afișează 6 și 1.',
                ],
                'skill' => 'tipuri-simple',
                'type' => ItemType::CodeOutput,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Ce se afișează la executarea secvenței?',
                    'shuffle' => true,
                    'code_variants' => [
                        'cpp' => "int a = 7, b = 2;\ncout << (a / b) * b << \" \" << a % b;",
                        'pascal' => "a := 7; b := 2;\nwrite((a div b) * b, ' ', a mod b);",
                    ],
                    'choices' => [
                        ['key' => 'a', 'text' => '6 1'],
                        ['key' => 'b', 'text' => '7 1'],
                        ['key' => 'c', 'text' => '8 1'],
                        ['key' => 'd', 'text' => '6 3'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Împărțirea a doi întregi trunchiază: $7/2 = 3$, apoi $3 \cdot 2 = 6$. Restul e 1. Identitatea $(a/b) \cdot b + a \bmod b = a$ se verifică: $6 + 1 = 7$.',
            ],
            [
                'ref' => 'seed:inf:tipuri:ultima-cifra',
                'hints' => [
                    'Ultima cifră a unui număr se obține cu operatorul de rest.',
                    'Restul împărțirii la 10 lasă exact ultima cifră.',
                    'Pentru a ELIMINA ultima cifră se folosește împărțirea întreagă la 10.',
                ],
                'skill' => 'tipuri-simple',
                'type' => ItemType::Mcq,
                'difficulty' => 1,
                'payload' => [
                    'stem' => 'Ce expresie extrage ultima cifră a unui număr natural $n$?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'n % 10   (Pascal: n mod 10)'],
                        ['key' => 'b', 'text' => 'n / 10   (Pascal: n div 10)'],
                        ['key' => 'c', 'text' => 'n % 100  (Pascal: n mod 100)'],
                        ['key' => 'd', 'text' => 'n - 10'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Restul la 10 dă ultima cifră; împărțirea întreagă la 10 o elimină. Cele două împreună formează bucla clasică de parcurgere a cifrelor.',
            ],
            [
                'ref' => 'seed:inf:tipuri:depasire',
                'hints' => [
                    'Întrebarea nu e despre algoritm, ci despre domeniul tipului.',
                    'Un `int` pe 32 de biți ține până la aproximativ 2 miliarde.',
                    '$13! = 6\\,227\\,020\\,800$, deci depășește domeniul lui `int`.',
                ],
                'skill' => 'tipuri-simple',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Pentru ce valoare a lui $n$ începe factorialul $n!$ să depășească domeniul unui întreg pe 32 de biți?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'n = 13'],
                        ['key' => 'b', 'text' => 'n = 10'],
                        ['key' => 'c', 'text' => 'n = 20'],
                        ['key' => 'd', 'text' => 'n = 100'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '$12! = 479\,001\,600$ încape, dar $13! = 6\,227\,020\,800$ depășește limita de aproximativ $2{,}1$ miliarde. Depășirea nu dă eroare — programul afișează liniștit o valoare greșită.',
            ],
            [
                'ref' => 'seed:inf:tipuri:comparare-reale',
                'hints' => [
                    'Numerele reale se stochează aproximativ, deci egalitatea strictă e riscantă.',
                    '$0{,}1 + 0{,}2$ nu dă exact $0{,}3$ în reprezentarea binară.',
                    'Se compară diferența cu o toleranță mică: `fabs(x - y) < 1e-9`.',
                ],
                'skill' => 'tipuri-simple',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Cum se compară corect două valori reale $x$ și $y$?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Se verifică dacă |x − y| este mai mic decât o toleranță mică'],
                        ['key' => 'b', 'text' => 'Se folosește operatorul de egalitate, ca la întregi'],
                        ['key' => 'c', 'text' => 'Se convertesc întâi la întreg'],
                        ['key' => 'd', 'text' => 'Se compară doar partea întreagă'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Reprezentarea în virgulă mobilă e aproximativă, deci egalitatea strictă poate fi falsă chiar când matematic ar trebui să fie adevărată. Conversia la întreg ar pierde exact informația care contează.',
            ],
            [
                'ref' => 'seed:inf:tipuri:neinitializata',
                'hints' => [
                    'Verifică ce valoare are variabila `s` înainte de buclă.',
                    'O variabilă nedeclarată cu o valoare inițială conține ce se afla în memorie.',
                    'Suma trebuie pusă pe zero înainte de a aduna în ea.',
                ],
                'skill' => 'tipuri-simple',
                'type' => ItemType::FindError,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Secvența trebuie să calculeze suma primelor 5 numere naturale, dar dă rezultate diferite la fiecare rulare. Unde este greșeala?',
                    'code_variants' => [
                        'cpp' => "int s;\nfor (int i = 1; i <= 5; i++)\n    s = s + i;\ncout << s;",
                        'pascal' => "for i := 1 to 5 do\n  s := s + i;\nwrite(s);",
                    ],
                    'choices' => [
                        ['key' => 'a', 'text' => 'Variabila `s` nu a fost inițializată cu 0'],
                        ['key' => 'b', 'text' => 'Bucla trebuia să înceapă de la 0'],
                        ['key' => 'c', 'text' => 'Trebuia folosit `s = i` în loc de `s = s + i`'],
                        ['key' => 'd', 'text' => 'Nu există nicio greșeală'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Fără inițializare, `s` pornește de la o valoare arbitrară din memorie. De aceea rezultatul diferă între rulări — simptomul tipic al unei variabile neinițializate.',
            ],
            [
                'ref' => 'seed:inf:tipuri:potrivire-tipuri',
                'hints' => [
                    'Alegerea tipului pornește de la domeniul de valori cerut.',
                    'Pentru o valoare cu zecimale ai nevoie de tip real, nu întreg.',
                    'Pentru „da/nu" există un tip dedicat, care ocupă cel mai puțin.',
                ],
                'skill' => 'tipuri-simple',
                'type' => ItemType::Match,
                'difficulty' => 2,
                'points' => 3,
                'payload' => [
                    'stem' => 'Potrivește datele cu tipul potrivit.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'Media notelor unui elev'],
                        ['key' => 'l2', 'text' => 'Numărul de elevi dintr-o clasă'],
                        ['key' => 'l3', 'text' => 'Dacă elevul a promovat sau nu'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'int / integer'],
                        ['key' => 'r2', 'text' => 'bool / boolean'],
                        ['key' => 'r3', 'text' => 'double / real'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r3', 'l2' => 'r1', 'l3' => 'r2']],
                'explanation' => 'Media are zecimale, deci tip real. Numărul de elevi e întreg. Pentru o valoare de adevăr, tipul boolean e cel mai clar și cel mai economic.',
            ],

            // ---------- Instrucțiuni și expresii ----------
            [
                'ref' => 'seed:inf:instructiuni:while-contor',
                'hints' => [
                    'Urmărește valoarea lui `n` la fiecare iterație, nu doar rezultatul final.',
                    'Bucla elimină câte o cifră: 1234 → 123 → 12 → 1 → 0.',
                    'Sunt 4 iterații, deci se afișează 4 — numărul de cifre.',
                ],
                'skill' => 'instructiuni',
                'type' => ItemType::CodeOutput,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Ce se afișează la executarea secvenței, pentru n = 1234?',
                    'shuffle' => true,
                    'code_variants' => [
                        'cpp' => "int n = 1234, c = 0;\nwhile (n > 0) {\n    c++;\n    n = n / 10;\n}\ncout << c;",
                        'pascal' => "n := 1234; c := 0;\nwhile n > 0 do\nbegin\n  c := c + 1;\n  n := n div 10;\nend;\nwrite(c);",
                    ],
                    'choices' => [
                        ['key' => 'a', 'text' => '4'],
                        ['key' => 'b', 'text' => '1234'],
                        ['key' => 'c', 'text' => '10'],
                        ['key' => 'd', 'text' => '3'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Este algoritmul standard de numărare a cifrelor. Împărțirea întreagă la 10 elimină câte o cifră până când numărul devine 0.',
            ],
            [
                'ref' => 'seed:inf:instructiuni:for-pas',
                'hints' => [
                    'Atenție la valoarea de pornire și la condiția de oprire.',
                    'Bucla merge din 2 în 2: i ia valorile 1, 3, 5, 7, 9.',
                    'Suma lor este $1+3+5+7+9 = 25$.',
                ],
                'skill' => 'instructiuni',
                'type' => ItemType::CodeOutput,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Ce valoare se afișează?',
                    'shuffle' => true,
                    'code_variants' => [
                        'cpp' => "int s = 0;\nfor (int i = 1; i < 10; i = i + 2)\n    s = s + i;\ncout << s;",
                        'pascal' => "s := 0;\ni := 1;\nwhile i < 10 do\nbegin\n  s := s + i;\n  i := i + 2;\nend;\nwrite(s);",
                    ],
                    'choices' => [
                        ['key' => 'a', 'text' => '25'],
                        ['key' => 'b', 'text' => '20'],
                        ['key' => 'c', 'text' => '30'],
                        ['key' => 'd', 'text' => '45'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Se adună numerele impare sub 10: $1+3+5+7+9 = 25$. Suma primelor $k$ numere impare e întotdeauna $k^2$ — aici $5^2$.',
            ],
            [
                'ref' => 'seed:inf:instructiuni:conditie-compusa',
                'hints' => [
                    'Condiția cere ca numărul să fie divizibil cu 3 ȘI cu 5 în același timp.',
                    'Divizibil cu ambele înseamnă divizibil cu 15.',
                    'Între 1 și 30 sunt două astfel de numere: 15 și 30.',
                ],
                'skill' => 'instructiuni',
                'type' => ItemType::CodeOutput,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Câte numere afișează secvența?',
                    'shuffle' => true,
                    'code_variants' => [
                        'cpp' => "for (int i = 1; i <= 30; i++)\n    if (i % 3 == 0 && i % 5 == 0)\n        cout << i << ' ';",
                        'pascal' => "for i := 1 to 30 do\n  if (i mod 3 = 0) and (i mod 5 = 0) then\n    write(i, ' ');",
                    ],
                    'choices' => [
                        ['key' => 'a', 'text' => '2'],
                        ['key' => 'b', 'text' => '10'],
                        ['key' => 'c', 'text' => '16'],
                        ['key' => 'd', 'text' => '6'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Se afișează 15 și 30. Cu `||` (sau) în loc de `&&` ar fi fost 14 numere — confuzia dintre cei doi operatori schimbă complet rezultatul.',
            ],
            [
                'ref' => 'seed:inf:instructiuni:atribuire-vs-egalitate',
                'hints' => [
                    'În C++, `=` atribuie, iar `==` compară. Sunt operatori diferiți.',
                    'O atribuire într-o condiție dă valoarea atribuită, care e considerată adevărată dacă e diferită de zero.',
                    'Condiția devine mereu adevărată, indiferent de valoarea reală a lui x.',
                ],
                'skill' => 'instructiuni',
                'type' => ItemType::FindError,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Secvența trebuie să verifice dacă x este 5, dar afișează mereu mesajul. Unde este greșeala?',
                    'code_variants' => [
                        'cpp' => "int x = 3;\nif (x = 5)\n    cout << \"x este 5\";",
                        'pascal' => "{ echivalentul greșelii din C++: }\nx := 3;\nif (x := 5) then      { atribuire, nu comparație }\n  write('x este 5');",
                    ],
                    'choices' => [
                        ['key' => 'a', 'text' => 'S-a folosit atribuirea în loc de comparație'],
                        ['key' => 'b', 'text' => 'Lipsesc acoladele'],
                        ['key' => 'c', 'text' => 'Variabila x nu e inițializată'],
                        ['key' => 'd', 'text' => 'Nu există nicio greșeală'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'În C++, `x = 5` atribuie 5 lui x și returnează 5, valoare considerată adevărată. Comparația corectă e `x == 5`. Pascal nu permite atribuirea într-o condiție — motiv pentru care greșeala e specifică limbajului C.',
            ],
            [
                'ref' => 'seed:inf:instructiuni:ordonare-algoritm',
                'hints' => [
                    'Înainte de buclă trebuie stabilită o valoare de referință.',
                    'Prima valoare din șir e candidatul inițial la maxim.',
                    'Ordinea: inițializez maximul → parcurg → compar → actualizez → afișez.',
                ],
                'skill' => 'instructiuni',
                'type' => ItemType::OrderSteps,
                'difficulty' => 2,
                'points' => 3,
                'payload' => [
                    'stem' => 'Ordonează pașii algoritmului de determinare a maximului dintr-un șir.',
                    'problem' => 'Se dă un șir cu n elemente.',
                    'steps' => [
                        ['key' => 's1', 'text' => 'Consider maximul egal cu primul element'],
                        ['key' => 's2', 'text' => 'Parcurg elementele începând cu al doilea'],
                        ['key' => 's3', 'text' => 'Compar elementul curent cu maximul'],
                        ['key' => 's4', 'text' => 'Dacă e mai mare, actualizez maximul'],
                        ['key' => 's5', 'text' => 'După parcurgere, afișez maximul'],
                    ],
                ],
                'answer' => ['order' => ['s1', 's2', 's3', 's4', 's5']],
                'explanation' => 'Inițializarea cu primul element e mai sigură decât cu zero: un șir cu toate valorile negative ar da rezultat greșit dacă maximul pornește de la 0.',
            ],
            [
                'ref' => 'seed:inf:instructiuni:do-while',
                'hints' => [
                    'Diferența dintre `while` și `do-while` stă în momentul verificării condiției.',
                    'La `do-while`, corpul se execută cel puțin o dată, indiferent de condiție.',
                    'La `while`, dacă la prima verificare condiția e falsă, corpul nu se execută deloc.',
                ],
                'skill' => 'instructiuni',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Care este diferența esențială dintre `while` și `do-while` (Pascal: `repeat-until`)?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'La `do-while` corpul se execută cel puțin o dată'],
                        ['key' => 'b', 'text' => '`do-while` e mai rapid'],
                        ['key' => 'c', 'text' => '`while` nu poate avea contor'],
                        ['key' => 'd', 'text' => 'Nu există nicio diferență'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '`while` verifică înainte, `do-while` verifică după. Când corpul trebuie executat măcar o dată — de exemplu la citirea unei valori valide — se folosește a doua formă.',
            ],
            [
                'ref' => 'seed:inf:instructiuni:complexitate-bucle',
                'hints' => [
                    'Numără de câte ori se execută instrucțiunea din interior.',
                    'Bucla exterioară rulează de n ori, iar cea interioară tot de n ori pentru fiecare.',
                    'În total $n \\cdot n = n^2$ execuții.',
                ],
                'skill' => 'instructiuni',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'De câte ori se execută instrucțiunea din interiorul buclelor imbricate, în funcție de n?',
                    'shuffle' => true,
                    'code_variants' => [
                        'cpp' => "for (int i = 1; i <= n; i++)\n    for (int j = 1; j <= n; j++)\n        c++;",
                        'pascal' => "for i := 1 to n do\n  for j := 1 to n do\n    c := c + 1;",
                    ],
                    'choices' => [
                        ['key' => 'a', 'text' => 'n²'],
                        ['key' => 'b', 'text' => '2n'],
                        ['key' => 'c', 'text' => 'n'],
                        ['key' => 'd', 'text' => 'n + 2'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Buclele imbricate înmulțesc numărul de execuții, nu îl adună. Pentru $n = 1000$ înseamnă un milion de operații — de aici vine limita de timp la problemele mari.',
            ],
            // ---------- Tipuri de date structurate ----------
            [
                'ref' => 'seed:inf:structurate:indexare',
                'hints' => [
                    'În C++ indicii pornesc de la 0, în Pascal de la limita declarată.',
                    'Un tablou C++ cu n elemente are indici valizi de la 0 la n−1.',
                    'Accesarea indicelui n depășește tabloul și citește memorie străină.',
                ],
                'skill' => 'tipuri-structurate',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Un tablou declarat în C++ cu 10 elemente are indici valizi între:',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => '0 și 9'],
                        ['key' => 'b', 'text' => '1 și 10'],
                        ['key' => 'c', 'text' => '0 și 10'],
                        ['key' => 'd', 'text' => '1 și 9'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'În C++ indexarea începe de la 0. Accesarea elementului 10 nu dă eroare de compilare, dar citește memorie din afara tabloului — una dintre cele mai greu de depistat greșeli.',
            ],
            [
                'ref' => 'seed:inf:structurate:parcurgere-inversa',
                'hints' => [
                    'Urmărește ordinea în care se afișează elementele.',
                    'Bucla merge de la ultimul indice spre primul.',
                    'Pentru șirul 1 2 3 4 5 se afișează 5 4 3 2 1.',
                ],
                'skill' => 'tipuri-structurate',
                'type' => ItemType::CodeOutput,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Pentru tabloul v = [1, 2, 3, 4, 5], ce se afișează?',
                    'shuffle' => true,
                    'code_variants' => [
                        'cpp' => "for (int i = 4; i >= 0; i--)\n    cout << v[i] << ' ';",
                        'pascal' => "for i := 5 downto 1 do\n  write(v[i], ' ');",
                    ],
                    'choices' => [
                        ['key' => 'a', 'text' => '5 4 3 2 1'],
                        ['key' => 'b', 'text' => '1 2 3 4 5'],
                        ['key' => 'c', 'text' => '4 3 2 1 0'],
                        ['key' => 'd', 'text' => '5 4 3 2'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Parcurgerea inversă e algoritmul de bază pentru inversarea unui șir sau pentru afișarea cifrelor în ordine inversă.',
            ],
            [
                'ref' => 'seed:inf:structurate:matrice-diagonala',
                'hints' => [
                    'Elementele de pe diagonala principală au o proprietate comună a indicilor.',
                    'Linia și coloana coincid: $a[i][i]$.',
                    'Pentru diagonala secundară, suma indicilor e constantă: $i + j = n - 1$.',
                ],
                'skill' => 'tipuri-structurate',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Ce condiție identifică elementele de pe diagonala PRINCIPALĂ a unei matrice pătratice?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'i = j'],
                        ['key' => 'b', 'text' => 'i + j = n − 1'],
                        ['key' => 'c', 'text' => 'i > j'],
                        ['key' => 'd', 'text' => 'i · j = n'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Diagonala principală: $i = j$. Diagonala secundară: $i + j = n-1$ la indexare de la 0. Condițiile $i > j$ și $i < j$ dau triunghiurile de sub și de deasupra diagonalei.',
            ],
            [
                'ref' => 'seed:inf:structurate:suma-matrice',
                'hints' => [
                    'Urmărește ce elemente adună condiția din interiorul buclelor.',
                    'Condiția $i = j$ selectează doar diagonala principală.',
                    'Pentru matricea dată, diagonala conține 1, 5 și 9, deci suma e 15.',
                ],
                'skill' => 'tipuri-structurate',
                'type' => ItemType::CodeOutput,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Pentru matricea a = [[1,2,3],[4,5,6],[7,8,9]], ce valoare se afișează?',
                    'shuffle' => true,
                    'code_variants' => [
                        'cpp' => "int s = 0;\nfor (int i = 0; i < 3; i++)\n    for (int j = 0; j < 3; j++)\n        if (i == j)\n            s = s + a[i][j];\ncout << s;",
                        'pascal' => "s := 0;\nfor i := 1 to 3 do\n  for j := 1 to 3 do\n    if i = j then\n      s := s + a[i][j];\nwrite(s);",
                    ],
                    'choices' => [
                        ['key' => 'a', 'text' => '15'],
                        ['key' => 'b', 'text' => '45'],
                        ['key' => 'c', 'text' => '12'],
                        ['key' => 'd', 'text' => '9'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Se adună doar diagonala principală: $1 + 5 + 9 = 15$. Fără condiție s-ar fi adunat toate elementele, adică 45.',
            ],
            [
                'ref' => 'seed:inf:structurate:sir-caractere',
                'hints' => [
                    'Fiecare caracter e comparat cu un set de valori.',
                    'Se numără vocalele din șir.',
                    'În „informatica" vocalele sunt i, o, a, i, a — cinci.',
                ],
                'skill' => 'tipuri-structurate',
                'type' => ItemType::Numeric,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Câte vocale conține cuvântul „informatica"? (Se consideră vocale: a, e, i, o, u.)',
                    'stem_latex' => '\text{informatica}',
                ],
                'answer' => ['value' => 5, 'tolerance' => 0],
                'explanation' => 'i-n-f-o-r-m-a-t-i-c-a: vocalele sunt i, o, a, i, a, deci 5. E algoritmul clasic de parcurgere a unui șir de caractere cu test de apartenență.',
            ],
            [
                'ref' => 'seed:inf:structurate:interschimbare',
                'hints' => [
                    'Verifică ce se întâmplă cu valoarea lui a după prima atribuire.',
                    'Prima linie suprascrie a, deci valoarea lui inițială se pierde definitiv.',
                    'E nevoie de o variabilă temporară care să rețină valoarea înainte de suprascriere.',
                ],
                'skill' => 'tipuri-structurate',
                'type' => ItemType::FindError,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Secvența trebuie să interschimbe valorile a două variabile, dar ambele ajung egale. Unde este greșeala?',
                    'code_variants' => [
                        'cpp' => "a = b;\nb = a;",
                        'pascal' => "a := b;\nb := a;",
                    ],
                    'choices' => [
                        ['key' => 'a', 'text' => 'Lipsește variabila temporară: valoarea lui a se pierde la prima atribuire'],
                        ['key' => 'b', 'text' => 'Atribuirile trebuiau inversate ca ordine'],
                        ['key' => 'c', 'text' => 'Variabilele trebuiau să fie de același tip'],
                        ['key' => 'd', 'text' => 'Nu există nicio greșeală'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Corect: `t = a; a = b; b = t;`. După prima atribuire, valoarea inițială a lui a nu mai există nicăieri, deci nu mai poate fi recuperată.',
            ],

            // ---------- Subprograme ----------
            [
                'ref' => 'seed:inf:subprograme:transmitere-valoare',
                'hints' => [
                    'Contează dacă parametrul e transmis prin valoare sau prin referință.',
                    'Prin valoare, subprogramul primește o COPIE — modificarea nu se vede afară.',
                    'Deci se afișează valoarea inițială, 5.',
                ],
                'skill' => 'subprograme',
                'type' => ItemType::CodeOutput,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Ce se afișează la executarea programului?',
                    'shuffle' => true,
                    'code_variants' => [
                        'cpp' => "void f(int x) {\n    x = x * 2;\n}\n\nint main() {\n    int a = 5;\n    f(a);\n    cout << a;\n}",
                        'pascal' => "procedure f(x: integer);\nbegin\n  x := x * 2;\nend;\n\nbegin\n  a := 5;\n  f(a);\n  write(a);\nend.",
                    ],
                    'choices' => [
                        ['key' => 'a', 'text' => '5'],
                        ['key' => 'b', 'text' => '10'],
                        ['key' => 'c', 'text' => '0'],
                        ['key' => 'd', 'text' => '25'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Transmiterea prin valoare copiază argumentul. Pentru ca modificarea să se vadă în exterior, parametrul trebuie transmis prin referință: `int &x` în C++, `var x: integer` în Pascal.',
            ],
            [
                'ref' => 'seed:inf:subprograme:functie-vs-procedura',
                'hints' => [
                    'Diferența e dacă subprogramul întoarce sau nu o valoare.',
                    'Funcția returnează un rezultat care poate fi folosit într-o expresie.',
                    'Procedura execută acțiuni, dar nu întoarce nimic.',
                ],
                'skill' => 'subprograme',
                'type' => ItemType::Mcq,
                'difficulty' => 1,
                'payload' => [
                    'stem' => 'Care este diferența dintre o funcție și o procedură?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Funcția returnează o valoare, procedura nu'],
                        ['key' => 'b', 'text' => 'Procedura e mai rapidă'],
                        ['key' => 'c', 'text' => 'Funcția nu poate avea parametri'],
                        ['key' => 'd', 'text' => 'Nu există nicio diferență'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'În C++ distincția se face prin tipul de retur: `void` înseamnă procedură. În Pascal există cuvinte-cheie separate, `function` și `procedure`.',
            ],
            [
                'ref' => 'seed:inf:subprograme:variabila-locala',
                'hints' => [
                    'Verifică unde e declarată variabila și cât timp există.',
                    'O variabilă declarată în interiorul unui subprogram e locală.',
                    'Ea dispare la ieșirea din subprogram și nu e vizibilă în exterior.',
                ],
                'skill' => 'subprograme',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Ce se întâmplă cu o variabilă declarată în interiorul unui subprogram?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Există doar pe durata execuției subprogramului'],
                        ['key' => 'b', 'text' => 'Rămâne disponibilă în tot programul'],
                        ['key' => 'c', 'text' => 'Își păstrează valoarea între apeluri'],
                        ['key' => 'd', 'text' => 'Trebuie declarată și în programul principal'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Variabilele locale se creează la intrarea în subprogram și dispar la ieșire. De aceea două subprograme pot folosi același nume fără să se influențeze.',
            ],
            [
                'ref' => 'seed:inf:subprograme:cmmdc',
                'hints' => [
                    'Este algoritmul lui Euclid, prin resturi.',
                    'Se înlocuiește perechea $(a, b)$ cu $(b, a \\bmod b)$ până când al doilea devine 0.',
                    'Pentru 48 și 18: (48,18) → (18,12) → (12,6) → (6,0). Rezultatul e 6.',
                ],
                'skill' => 'subprograme',
                'type' => ItemType::Numeric,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Ce valoare returnează funcția cmmdc(48, 18), implementată prin algoritmul lui Euclid?',
                    'stem_latex' => '\text{cmmdc}(48, 18)',
                ],
                'answer' => ['value' => 6, 'tolerance' => 0],
                'explanation' => 'Algoritmul lui Euclid: se înlocuiește perechea cu (b, a mod b) până când restul devine 0. Ultimul rest nenul, 6, este cel mai mare divizor comun.',
            ],
            [
                'ref' => 'seed:inf:subprograme:return-lipsa',
                'hints' => [
                    'Verifică dacă toate ramurile funcției întorc o valoare.',
                    'Ramura „else" nu are instrucțiune de returnare.',
                    'O funcție care nu returnează nimic pe o ramură produce o valoare nedefinită.',
                ],
                'skill' => 'subprograme',
                'type' => ItemType::FindError,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Funcția trebuie să întoarcă maximul dintre două numere, dar uneori dă rezultate imprevizibile. Unde este greșeala?',
                    'code_variants' => [
                        'cpp' => "int maxim(int a, int b) {\n    if (a > b)\n        return a;\n    // lipsește cazul a <= b\n}",
                        'pascal' => "function maxim(a, b: integer): integer;\nbegin\n  if a > b then\n    maxim := a;\n  { lipsește cazul a <= b }\nend;",
                    ],
                    'choices' => [
                        ['key' => 'a', 'text' => 'Ramura în care a ≤ b nu returnează nicio valoare'],
                        ['key' => 'b', 'text' => 'Parametrii trebuiau transmiși prin referință'],
                        ['key' => 'c', 'text' => 'Comparația trebuia să fie a >= b'],
                        ['key' => 'd', 'text' => 'Nu există nicio greșeală'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Când $a \le b$, funcția se termină fără să returneze nimic, iar valoarea întoarsă e nedefinită. Orice ramură a unei funcții trebuie să producă un rezultat.',
            ],
            [
                'ref' => 'seed:inf:subprograme:ordonare-descompunere',
                'hints' => [
                    'Descompunerea unei probleme începe cu identificarea operațiilor repetate.',
                    'Ce se repetă devine subprogram; ce se schimbă devine parametru.',
                    'Ordinea: identific → stabilesc parametrii → stabilesc rezultatul → scriu → apelez.',
                ],
                'skill' => 'subprograme',
                'type' => ItemType::OrderSteps,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Ordonează pașii descompunerii unei probleme în subprograme.',
                    'problem' => 'Se cere afișarea tuturor numerelor prime dintr-un interval.',
                    'steps' => [
                        ['key' => 's1', 'text' => 'Identific operația care se repetă: verificarea dacă un număr e prim'],
                        ['key' => 's2', 'text' => 'Stabilesc parametrul: numărul de verificat'],
                        ['key' => 's3', 'text' => 'Stabilesc rezultatul: o valoare de adevăr'],
                        ['key' => 's4', 'text' => 'Scriu funcția estePrim(n)'],
                        ['key' => 's5', 'text' => 'O apelez în bucla care parcurge intervalul'],
                    ],
                ],
                'answer' => ['order' => ['s1', 's2', 's3', 's4', 's5']],
                'explanation' => 'Descompunerea se punctează separat la examen: un program scris ca un bloc unic pierde puncte chiar dacă funcționează corect.',
            ],

            // ---------- Recursia ----------
            [
                'ref' => 'seed:inf:recursie:caz-baza',
                'hints' => [
                    'Fără condiția de oprire, funcția se apelează la nesfârșit.',
                    'Cazul de bază e situația în care răspunsul se dă direct, fără alt apel.',
                    'Lipsa lui duce la umplerea stivei și la oprirea brutală a programului.',
                ],
                'skill' => 'recursie',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Ce se întâmplă cu o funcție recursivă fără caz de bază?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Se apelează la nesfârșit până la depășirea stivei'],
                        ['key' => 'b', 'text' => 'Returnează automat 0'],
                        ['key' => 'c', 'text' => 'Compilatorul refuză să o compileze'],
                        ['key' => 'd', 'text' => 'Se execută o singură dată'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Fiecare apel ocupă spațiu pe stivă. Fără o condiție care să oprească lanțul, stiva se umple și programul se închide cu eroarea *stack overflow*.',
            ],
            [
                'ref' => 'seed:inf:recursie:suma-cifre',
                'hints' => [
                    'Funcția desface numărul cifră cu cifră, recursiv.',
                    'Cazul de bază e n = 0, când suma e 0.',
                    'Pentru 423: $3 + (2 + (4 + 0)) = 9$.',
                ],
                'skill' => 'recursie',
                'type' => ItemType::CodeOutput,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Ce valoare returnează s(423)?',
                    'shuffle' => true,
                    'code_variants' => [
                        'cpp' => "int s(int n) {\n    if (n == 0) return 0;\n    return n % 10 + s(n / 10);\n}",
                        'pascal' => "function s(n: integer): integer;\nbegin\n  if n = 0 then\n    s := 0\n  else\n    s := n mod 10 + s(n div 10);\nend;",
                    ],
                    'choices' => [
                        ['key' => 'a', 'text' => '9'],
                        ['key' => 'b', 'text' => '423'],
                        ['key' => 'c', 'text' => '3'],
                        ['key' => 'd', 'text' => '24'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Funcția adună cifrele: $4 + 2 + 3 = 9$. Cazul de bază $n = 0$ oprește recursia când s-au consumat toate cifrele.',
            ],
            [
                'ref' => 'seed:inf:recursie:fibonacci-cost',
                'hints' => [
                    'Recursia naivă pentru Fibonacci recalculează aceleași valori de multe ori.',
                    'fib(5) calculează fib(3) de două ori, fib(2) de trei ori și așa mai departe.',
                    'Numărul de apeluri crește exponențial, nu liniar.',
                ],
                'skill' => 'recursie',
                'type' => ItemType::Mcq,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'De ce este ineficientă implementarea recursivă naivă a șirului lui Fibonacci?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Recalculează de mai multe ori aceleași valori'],
                        ['key' => 'b', 'text' => 'Ocupă prea multă memorie pentru tablouri'],
                        ['key' => 'c', 'text' => 'Nu poate calcula valori mai mari decât 10'],
                        ['key' => 'd', 'text' => 'Nu are caz de bază'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Numărul de apeluri crește exponențial. Soluțiile sunt memorarea rezultatelor deja calculate sau varianta iterativă, ambele în $n$ pași.',
            ],
            [
                'ref' => 'seed:inf:recursie:ordinea-afisarii',
                'hints' => [
                    'Contează dacă afișarea se face ÎNAINTE sau DUPĂ apelul recursiv.',
                    'Aici afișarea e după apel, deci se execută la întoarcerea din recursie.',
                    'Ordinea rezultată e inversă: 1 2 3.',
                ],
                'skill' => 'recursie',
                'type' => ItemType::CodeOutput,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'Ce se afișează la apelul f(3)?',
                    'shuffle' => true,
                    'code_variants' => [
                        'cpp' => "void f(int n) {\n    if (n == 0) return;\n    f(n - 1);\n    cout << n << ' ';\n}",
                        'pascal' => "procedure f(n: integer);\nbegin\n  if n = 0 then exit;\n  f(n - 1);\n  write(n, ' ');\nend;",
                    ],
                    'choices' => [
                        ['key' => 'a', 'text' => '1 2 3'],
                        ['key' => 'b', 'text' => '3 2 1'],
                        ['key' => 'c', 'text' => '0 1 2 3'],
                        ['key' => 'd', 'text' => '3'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Apelul recursiv coboară până la 0, apoi afișările se execută la întoarcere, în ordine inversă coborârii. Mutând afișarea ÎNAINTE de apel s-ar obține 3 2 1.',
            ],
            [
                'ref' => 'seed:inf:recursie:potrivire-componente',
                'hints' => [
                    'O funcție recursivă are întotdeauna aceleași două componente.',
                    'Una oprește lanțul de apeluri, cealaltă îl apropie de oprire.',
                    'Stiva e structura de date care ține minte apelurile nefinalizate.',
                ],
                'skill' => 'recursie',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Potrivește noțiunea cu rolul ei în recursie.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'Cazul de bază'],
                        ['key' => 'l2', 'text' => 'Pasul recursiv'],
                        ['key' => 'l3', 'text' => 'Stiva'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'Reduce problema spre o variantă mai mică'],
                        ['key' => 'r2', 'text' => 'Reține apelurile nefinalizate'],
                        ['key' => 'r3', 'text' => 'Oprește lanțul de apeluri'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r3', 'l2' => 'r1', 'l3' => 'r2']],
                'explanation' => 'Cele trei noțiuni explică împreună de ce o recursie se termină și de ce consumă memorie proporțional cu adâncimea ei.',
            ],
        ];
    }

    /**
     * Informația: cantitatea de informație și codificarea mesajelor.
     *
     * Fără itemi de tip `find_error` aici: regula din ContentIntegrityTest cere
     * `code_variants` cu C++ și Pascal pentru ORICE item de acest tip din
     * informatică, iar o greșeală de calcul al informației n-are echivalent în
     * cele două limbaje. Aceleași greșeli se testează prin `mcq`.
     *
     * @return list<array<string, mixed>>
     */
    private function itemsInformatie(): array
    {
        return [
            // ---------- Cantitatea de informație ----------
            [
                'ref' => 'seed:inf:cantitate:biti-necesari',
                'hints' => [
                    'Caută cel mai mic $k$ pentru care $2^k$ acoperă toate valorile.',
                    '$2^6 = 64$ e prea puțin pentru 100 de valori, iar $2^7 = 128$ ajunge.',
                    'Deci sunt necesari 7 biți.',
                ],
                'skill' => 'cantitatea-informatie',
                'type' => ItemType::Numeric,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Câți biți sunt necesari pentru a codifica 100 de valori distincte?',
                    'stem_latex' => '2^k \ge 100',
                ],
                'answer' => ['value' => 7, 'tolerance' => 0],
                'explanation' => '$2^6 = 64 < 100$, iar $2^7 = 128 \ge 100$. Se rotunjește întotdeauna în SUS: cu 6 biți ar rămâne 36 de valori necodificate.',
            ],
            [
                'ref' => 'seed:inf:cantitate:mesaj',
                'hints' => [
                    'Întâi afli câtă informație aduce un singur simbol.',
                    'Alfabetul are 16 simboluri, iar $\\log_2 16 = 4$ biți pe simbol.',
                    'Pentru 50 de simboluri: $50 \\cdot 4 = 200$ de biți.',
                ],
                'skill' => 'cantitatea-informatie',
                'type' => ItemType::Numeric,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Un mesaj are 50 de simboluri dintr-un alfabet de 16 simboluri echiprobabile. Câți biți conține?',
                    'stem_latex' => 'I = n \cdot \log_2 m',
                ],
                'answer' => ['value' => 200, 'tolerance' => 0],
                'explanation' => '$\log_2 16 = 4$ biți pe simbol, deci $50 \cdot 4 = 200$ de biți, adică 25 de octeți.',
            ],
            [
                'ref' => 'seed:inf:cantitate:unitati',
                'hints' => [
                    'În informatică multiplii cresc din 1024 în 1024, nu din 1000 în 1000.',
                    '$1024 = 2^{10}$, de aceea e alesă această valoare.',
                    '1 KiB = 1024 octeți, iar 1 octet = 8 biți.',
                ],
                'skill' => 'cantitatea-informatie',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Câți biți are 1 KiB?',
                    'stem_latex' => '1 \text{ KiB} = 1024 \text{ octeți}',
                ],
                'answer' => ['value' => 8192, 'tolerance' => 0],
                'explanation' => '$1024 \cdot 8 = 8192$ de biți. Atenție la notație: „b" mic înseamnă bit, „B" mare înseamnă octet — o viteză de „100 Mb/s" e de opt ori mai mică decât pare.',
            ],
            [
                'ref' => 'seed:inf:cantitate:dublarea',
                'hints' => [
                    'Relația dintre numărul de variante și informație este logaritmică.',
                    'Dublarea numărului de variante adaugă o cantitate FIXĂ de informație.',
                    'De la 8 la 16 variante: de la 3 la 4 biți, deci exact un bit în plus.',
                ],
                'skill' => 'cantitatea-informatie',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Dacă numărul de variante echiprobabile se dublează, cantitatea de informație:',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'crește cu exact 1 bit'],
                        ['key' => 'b', 'text' => 'se dublează'],
                        ['key' => 'c', 'text' => 'rămâne aceeași'],
                        ['key' => 'd', 'text' => 'crește de patru ori'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Pentru că $\log_2(2m) = \log_2 m + 1$. Creșterea e logaritmică: de la 2 la 1024 de variante informația crește doar de la 1 la 10 biți.',
            ],

            // ---------- Codificarea ----------
            [
                'ref' => 'seed:inf:codificare:pozitie-litera',
                'hints' => [
                    'Codurile literelor mari sunt consecutive, începând de la 65 pentru „A".',
                    'Poziția în alfabet se află scăzând codul lui „A" și adunând 1.',
                    'Pentru „E": $69 - 65 + 1 = 5$.',
                ],
                'skill' => 'codificare',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Litera „E" are codul ASCII 69, iar „A" are 65. Ce poziție ocupă „E" în alfabet?',
                    'stem_latex' => '\text{poziție} = \text{cod}(c) - \text{cod}(A) + 1',
                ],
                'answer' => ['value' => 5, 'tolerance' => 0],
                'explanation' => 'Consecutivitatea codurilor permite calculul direct, fără tabel. Uitarea lui „+1" dă 4 — greșeala clasică de indexare de la 0.',
            ],
            [
                'ref' => 'seed:inf:codificare:mic-mare',
                'hints' => [
                    'Diferența dintre codul unei litere mici și al celei mari e constantă.',
                    '„a" are 97, „A" are 65. Diferența e 32.',
                    'Deci adunând 32 la codul unei litere mari obții litera mică.',
                ],
                'skill' => 'codificare',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Cu cât diferă codul ASCII al unei litere mici față de litera mare corespunzătoare?',
                    'stem_latex' => '\text{cod}(a) - \text{cod}(A)',
                ],
                'answer' => ['value' => 32, 'tolerance' => 0],
                'explanation' => '$97 - 65 = 32$. Diferența e aceeași pentru toate literele, deci conversia se face printr-o simplă adunare sau scădere.',
            ],
            [
                'ref' => 'seed:inf:codificare:prefix',
                'hints' => [
                    'Un cod de lungime variabilă e utilizabil doar dacă decodificarea e unică.',
                    'Condiția: niciun cod nu are voie să fie începutul altui cod.',
                    'Cu A = 0 și B = 01, secvența „01" se poate citi și ca „AB" și ca „B".',
                ],
                'skill' => 'codificare',
                'type' => ItemType::Mcq,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'Care dintre următoarele seturi de coduri NU permite decodificarea unică?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'A = 0, B = 01, C = 11'],
                        ['key' => 'b', 'text' => 'A = 0, B = 10, C = 11'],
                        ['key' => 'c', 'text' => 'A = 00, B = 01, C = 10'],
                        ['key' => 'd', 'text' => 'A = 0, B = 10, C = 110'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'În varianta a, codul lui A (0) e prefixul codului lui B (01), deci „01" e ambiguu. Celelalte respectă condiția de prefix.',
            ],
            [
                'ref' => 'seed:inf:codificare:ascii-unicode',
                'hints' => [
                    'ASCII a fost creat pentru engleză și folosește puțini biți.',
                    'Cu 7 biți se pot codifica $2^7 = 128$ de caractere.',
                    'Unicode a apărut tocmai pentru că 128 sau 256 de caractere nu ajung.',
                ],
                'skill' => 'codificare',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Câte caractere se pot codifica în ASCII standard, pe 7 biți?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => '128'],
                        ['key' => 'b', 'text' => '256'],
                        ['key' => 'c', 'text' => '64'],
                        ['key' => 'd', 'text' => '1024'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '$2^7 = 128$. Insuficient pentru diacritice și alte scrieri, motiv pentru care s-a trecut la Unicode, unde UTF-8 folosește 1–4 octeți per caracter.',
            ],
        ];
    }

    /**
     * Bazele aritmetice: sisteme de numerație și conversii.
     *
     * @return list<array<string, mixed>>
     */
    private function itemsAritmetica(): array
    {
        return [
            [
                'ref' => 'seed:inf:sisteme:ponderi',
                'hints' => [
                    'Fiecare cifră are o pondere dată de poziția ei.',
                    'Ponderile în baza 2, de la dreapta: 1, 2, 4, 8, 16...',
                    '$10110_2 = 16 + 4 + 2 = 22$.',
                ],
                'skill' => 'sisteme-numeratie',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'payload' => ['unit' => 'în baza 10', 'stem' => 'Convertește în baza 10.', 'stem_latex' => '10110_2'],
                'answer' => ['value' => 22, 'tolerance' => 0, 'base' => 10],
                'explanation' => 'Se adună ponderile pozițiilor cu cifra 1: $16 + 0 + 4 + 2 + 0 = 22$.',
            ],
            [
                'ref' => 'seed:inf:sisteme:cifre-valide',
                'hints' => [
                    'Într-o bază $b$, cifrele merg de la 0 la $b-1$.',
                    'În baza 8, cifra maximă e 7.',
                    'Deci un număr care conține cifra 8 sau 9 nu poate fi octal.',
                ],
                'skill' => 'sisteme-numeratie',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Care dintre următoarele NU este un număr valid în baza 8?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => '1284'],
                        ['key' => 'b', 'text' => '777'],
                        ['key' => 'c', 'text' => '1057'],
                        ['key' => 'd', 'text' => '360'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'În baza 8 cifrele valide sunt 0–7. Prezența cifrei 8 face numărul imposibil în această bază.',
            ],
            [
                'ref' => 'seed:inf:sisteme:hexa-valoare',
                'hints' => [
                    'Literele continuă numărătoarea de la 10: A = 10, B = 11, ... F = 15.',
                    'Ponderile în baza 16, de la dreapta: 1, 16, 256.',
                    '$2F_{16} = 2 \\cdot 16 + 15 = 47$.',
                ],
                'skill' => 'sisteme-numeratie',
                'type' => ItemType::Numeric,
                'difficulty' => 3,
                'payload' => ['unit' => 'în baza 10', 'stem' => 'Convertește în baza 10.', 'stem_latex' => '2F_{16}'],
                'answer' => ['value' => 47, 'tolerance' => 0, 'base' => 10],
                'explanation' => '$2 \cdot 16 + F = 32 + 15 = 47$. Litera F reprezintă valoarea 15.',
            ],
            [
                'ref' => 'seed:inf:sisteme:numar-cifre-binar',
                'hints' => [
                    'Numărul de cifre binare depinde de cea mai mare putere a lui 2 care încape.',
                    '$2^7 = 128 \\le 200 < 256 = 2^8$.',
                    'Deci sunt necesare 8 cifre binare.',
                ],
                'skill' => 'sisteme-numeratie',
                'type' => ItemType::Numeric,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Câte cifre binare are reprezentarea numărului 200?',
                    'stem_latex' => '200_{10} = ?_2',
                ],
                'answer' => ['value' => 8, 'tolerance' => 0],
                'explanation' => '$200 = 11001000_2$, adică 8 cifre. General: numărul de cifre e $\lfloor \log_2 n \rfloor + 1$.',
            ],
            [
                'ref' => 'seed:inf:sisteme:potrivire-baze',
                'hints' => [
                    'Convertește fiecare valoare în baza 10 și compară.',
                    '$1010_2 = 10$, $12_8 = 10$, $A_{16} = 10$.',
                    'Toate trei reprezintă aceeași valoare, în baze diferite.',
                ],
                'skill' => 'sisteme-numeratie',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Potrivește fiecare reprezentare cu valoarea ei zecimală.',
                    'left' => [
                        ['key' => 'l1', 'latex' => '1010_2'],
                        ['key' => 'l2', 'latex' => '20_8'],
                        ['key' => 'l3', 'latex' => '1F_{16}'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => '31'],
                        ['key' => 'r2', 'text' => '10'],
                        ['key' => 'r3', 'text' => '16'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r2', 'l2' => 'r3', 'l3' => 'r1']],
                'explanation' => '$1010_2 = 10$; $20_8 = 2 \cdot 8 = 16$; $1F_{16} = 16 + 15 = 31$.',
            ],
            [
                'ref' => 'seed:inf:conversii:resturi',
                'hints' => [
                    'Se împarte repetat la bază și se rețin resturile.',
                    'Rezultatul se citește din resturi, DE JOS ÎN SUS.',
                    '25 în baza 2 dă resturile 1,0,0,1,1 — citite invers: 11001.',
                ],
                'skill' => 'conversii-baze',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Convertește 25 din baza 10 în baza 2.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => '11001'],
                        ['key' => 'b', 'text' => '10011'],
                        ['key' => 'c', 'text' => '11010'],
                        ['key' => 'd', 'text' => '10101'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Verificare: $16 + 8 + 1 = 25$. Varianta 10011 e același set de resturi citit în ordinea obținerii — greșeala numărul unu la acest capitol.',
            ],
            [
                'ref' => 'seed:inf:conversii:grupare-hexa',
                'hints' => [
                    'Între binar și hexazecimal nu se trece prin baza 10.',
                    'Se grupează biții câte 4, începând DIN DREAPTA.',
                    '1101 | 0110 devine D | 6, adică D6.',
                ],
                'skill' => 'conversii-baze',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Convertește $11010110_2$ în baza 16.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'D6'],
                        ['key' => 'b', 'text' => '6D'],
                        ['key' => 'c', 'text' => 'C6'],
                        ['key' => 'd', 'text' => 'B6'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '$1101_2 = 13 = D$ și $0110_2 = 6$, deci D6. Gruparea din stânga ar da un rezultat greșit când numărul de biți nu e multiplu de 4.',
            ],
            [
                'ref' => 'seed:inf:conversii:grupare-octal',
                'hints' => [
                    'Pentru octal se grupează câte 3 biți, pentru că $8 = 2^3$.',
                    'Gruparea începe tot din dreapta: 101 | 110.',
                    '$101_2 = 5$ și $110_2 = 6$, deci $56_8$.',
                ],
                'skill' => 'conversii-baze',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Convertește $101110_2$ în baza 8.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => '56'],
                        ['key' => 'b', 'text' => '65'],
                        ['key' => 'c', 'text' => '46'],
                        ['key' => 'd', 'text' => '116'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Câte 3 biți din dreapta: $101|110 \to 5|6 = 56_8$. Verificare: $5 \cdot 8 + 6 = 46 = 101110_2$.',
            ],
            [
                'ref' => 'seed:inf:conversii:baza-mare',
                'hints' => [
                    'Aplică ponderile bazei 5: 1, 5, 25.',
                    '$2 \\cdot 25 + 0 \\cdot 5 + 3 \\cdot 1$.',
                    'Rezultatul este 53.',
                ],
                'skill' => 'conversii-baze',
                'type' => ItemType::Numeric,
                'difficulty' => 3,
                'payload' => ['unit' => 'în baza 10', 'stem' => 'Convertește în baza 10.', 'stem_latex' => '203_5'],
                'answer' => ['value' => 53, 'tolerance' => 0, 'base' => 10],
                'explanation' => '$2 \cdot 25 + 0 \cdot 5 + 3 = 53$. Metoda ponderilor funcționează identic pentru orice bază.',
            ],
            [
                'ref' => 'seed:inf:conversii:ordonare-metoda',
                'hints' => [
                    'Conversia din baza 10 într-o altă bază se face prin împărțiri succesive.',
                    'Se rețin resturile la fiecare pas, până când câtul devine 0.',
                    'Ultimul pas — cel mai uitat — e citirea resturilor în ordine inversă.',
                ],
                'skill' => 'conversii-baze',
                'type' => ItemType::OrderSteps,
                'difficulty' => 2,
                'points' => 3,
                'payload' => [
                    'stem' => 'Ordonează pașii conversiei din baza 10 în baza 2.',
                    'problem' => 'Se dă numărul 25 în baza 10.',
                    'steps' => [
                        ['key' => 's1', 'text' => 'Împart numărul la 2 și rețin restul'],
                        ['key' => 's2', 'text' => 'Repet cu câtul obținut'],
                        ['key' => 's3', 'text' => 'Mă opresc când câtul devine 0'],
                        ['key' => 's4', 'text' => 'Citesc resturile de jos în sus'],
                        ['key' => 's5', 'text' => 'Verific prin conversia inversă'],
                    ],
                ],
                'answer' => ['order' => ['s1', 's2', 's3', 's4', 's5']],
                'explanation' => 'Verificarea finală durează zece secunde și prinde majoritatea greșelilor — mai ales citirea resturilor în ordinea greșită.',
            ],
        ];
    }

    /**
     * Logica digitală: algebră booleană și circuite.
     *
     * @return list<array<string, mixed>>
     */
    private function itemsLogica(): array
    {
        return [
            [
                'ref' => 'seed:inf:boole:de-morgan',
                'hints' => [
                    'Negația unei conjuncții nu e conjuncția negațiilor.',
                    'Legea lui De Morgan schimbă ȘI în SAU atunci când negația se distribuie.',
                    '$\\overline{A \\cdot B} = \\overline{A} + \\overline{B}$.',
                ],
                'skill' => 'algebra-booleana',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Cu ce este echivalentă expresia $\overline{A \cdot B}$?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => '\overline{A} + \overline{B}'],
                        ['key' => 'b', 'latex' => '\overline{A} \cdot \overline{B}'],
                        ['key' => 'c', 'latex' => 'A + B'],
                        ['key' => 'd', 'latex' => '\overline{A + B}'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Legea lui De Morgan: negația se distribuie și schimbă operatorul. În cod: negația lui „x > 0 și x < 10" e „x ≤ 0 sau x ≥ 10".',
            ],
            [
                'ref' => 'seed:inf:boole:simplificare',
                'hints' => [
                    'Dă factor comun pe $A$.',
                    'Rămâne $A \\cdot (B + \\overline{B})$, iar paranteza e întotdeauna 1.',
                    'Expresia se reduce la $A$ — nu depinde deloc de $B$.',
                ],
                'skill' => 'algebra-booleana',
                'type' => ItemType::Mcq,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'Simplifică expresia $A \cdot B + A \cdot \overline{B}$.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => 'A'],
                        ['key' => 'b', 'latex' => 'B'],
                        ['key' => 'c', 'latex' => 'A \cdot B'],
                        ['key' => 'd', 'latex' => '0'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => '$A(B + \overline{B}) = A \cdot 1 = A$. O variabilă sau negația ei acoperă toate cazurile, deci paranteza e mereu adevărată.',
            ],
            [
                'ref' => 'seed:inf:boole:tabel-linii',
                'hints' => [
                    'Fiecare variabilă poate lua două valori, independent de celelalte.',
                    'Numărul de combinații se dublează cu fiecare variabilă adăugată.',
                    'Pentru 4 variabile: $2^4 = 16$ linii.',
                ],
                'skill' => 'algebra-booleana',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Câte linii are tabelul de adevăr al unei expresii cu 4 variabile?',
                    'stem_latex' => '2^n',
                ],
                'answer' => ['value' => 16, 'tolerance' => 0],
                'explanation' => '$2^4 = 16$. Creșterea e exponențială: la 10 variabile tabelul ar avea 1024 de linii, motiv pentru care se folosesc metode de simplificare.',
            ],
            [
                'ref' => 'seed:inf:boole:absorbtie',
                'hints' => [
                    'Dacă $A$ e 1, întreaga expresie e 1 indiferent de $B$.',
                    'Dacă $A$ e 0, atunci și $A \\cdot B$ e 0, deci expresia e 0.',
                    'În ambele cazuri rezultatul coincide cu $A$: legea absorbției.',
                ],
                'skill' => 'algebra-booleana',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Simplifică expresia $A + A \cdot B$.',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => 'A'],
                        ['key' => 'b', 'latex' => 'A \cdot B'],
                        ['key' => 'c', 'latex' => 'A + B'],
                        ['key' => 'd', 'latex' => 'B'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Legea absorbției. Se verifică pe tabelul de adevăr: în toate cele patru combinații, rezultatul e egal cu $A$.',
            ],
            [
                'ref' => 'seed:inf:circuite:xor',
                'hints' => [
                    'XOR se numește „SAU exclusiv" — exclusiv înseamnă „nu amândouă".',
                    'Ieșirea e 1 când intrările sunt DIFERITE.',
                    'Deci la 1 și 1 ieșirea e 0, spre deosebire de OR.',
                ],
                'skill' => 'circuite-logice',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Când are poarta XOR ieșirea egală cu 1?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Când intrările au valori diferite'],
                        ['key' => 'b', 'text' => 'Când ambele intrări sunt 1'],
                        ['key' => 'c', 'text' => 'Când ambele intrări sunt 0'],
                        ['key' => 'd', 'text' => 'Întotdeauna'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'XOR diferă de OR într-o singură linie: la 1 și 1, OR dă 1, iar XOR dă 0. Acea linie face posibilă adunarea binară.',
            ],
            [
                'ref' => 'seed:inf:circuite:nand-universal',
                'hints' => [
                    'Unele porți pot înlocui toate celelalte, combinate între ele.',
                    'NAND și NOR au această proprietate, numită universalitate.',
                    'Din NAND se poate construi NOT, apoi AND, apoi OR.',
                ],
                'skill' => 'circuite-logice',
                'type' => ItemType::Mcq,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'Care poartă logică este „universală", adică permite construirea oricărui circuit folosind doar copii ale ei?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'NAND'],
                        ['key' => 'b', 'text' => 'AND'],
                        ['key' => 'c', 'text' => 'OR'],
                        ['key' => 'd', 'text' => 'XOR'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'NAND (și NOR) sunt universale. O poartă NAND cu ambele intrări legate împreună se comportă ca NOT, iar de acolo se construiesc AND și OR.',
            ],
            [
                'ref' => 'seed:inf:circuite:sumator',
                'hints' => [
                    'Sumatorul elementar adună doi biți și produce două ieșiri.',
                    'Suma e dată de XOR, iar transportul de AND.',
                    'La 1 + 1: suma e 0 și transportul e 1, adică $10_2$.',
                ],
                'skill' => 'circuite-logice',
                'type' => ItemType::Mcq,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'Într-un sumator elementar, ce produce bitul de transport?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Poarta AND'],
                        ['key' => 'b', 'text' => 'Poarta XOR'],
                        ['key' => 'c', 'text' => 'Poarta OR'],
                        ['key' => 'd', 'text' => 'Poarta NOT'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Transportul apare doar când ambii biți sunt 1 — exact comportamentul porții AND. Suma, care e 1 când biții diferă, vine de la XOR.',
            ],
            [
                'ref' => 'seed:inf:circuite:potrivire-porti',
                'hints' => [
                    'Fiecare poartă corespunde unei operații booleene.',
                    'NOR e negația lui SAU, nu a lui ȘI.',
                    'NAND e negația conjuncției.',
                ],
                'skill' => 'circuite-logice',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Potrivește poarta cu expresia ei booleană.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'NAND'],
                        ['key' => 'l2', 'text' => 'NOR'],
                        ['key' => 'l3', 'text' => 'XOR'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'latex' => '\overline{A + B}'],
                        ['key' => 'r2', 'latex' => 'A \oplus B'],
                        ['key' => 'r3', 'latex' => '\overline{A \cdot B}'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r3', 'l2' => 'r1', 'l3' => 'r2']],
                'explanation' => 'Prefixul „N" înseamnă negație aplicată la ieșire: NAND = NOT AND, NOR = NOT OR.',
            ],
        ];
    }

    /**
     * Modelare și calcul numeric.
     *
     * @return list<array<string, mixed>>
     */
    private function itemsCalculNumeric(): array
    {
        return [
            [
                'ref' => 'seed:inf:erori:relativa',
                'hints' => [
                    'Eroarea absolută e diferența în valoare absolută.',
                    'Eroarea relativă se obține împărțind-o la valoarea exactă.',
                    '$\\frac{|10 - 9{,}8|}{10} = \\frac{0{,}2}{10} = 0{,}02$, adică 2%.',
                ],
                'skill' => 'modelare-erori',
                'type' => ItemType::Numeric,
                'difficulty' => 3,
                'payload' => ['unit' => '%',
                    'stem' => 'Valoarea exactă este 10, iar cea aproximativă 9,8. Cât este eroarea relativă, în procente?',
                    'stem_latex' => '\delta = \frac{|x - \tilde{x}|}{|x|} \cdot 100\%',
                ],
                'answer' => ['value' => 2, 'tolerance' => 0.01],
                'explanation' => 'Eroarea absolută e 0,2, iar cea relativă $0{,}2/10 = 2\%$. Eroarea relativă e cea care spune dacă precizia e acceptabilă.',
            ],
            [
                'ref' => 'seed:inf:erori:absoluta-vs-relativa',
                'hints' => [
                    'Aceeași eroare absolută înseamnă lucruri diferite la mărimi diferite.',
                    'O eroare de 1 cm la un creion de 20 cm e mult; la un teren de 100 m e nimic.',
                    'De aceea precizia se raportează la mărimea măsurată.',
                ],
                'skill' => 'modelare-erori',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'De ce este eroarea relativă mai informativă decât cea absolută?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Pentru că raportează eroarea la mărimea măsurată'],
                        ['key' => 'b', 'text' => 'Pentru că este întotdeauna mai mică'],
                        ['key' => 'c', 'text' => 'Pentru că se exprimă în aceleași unități'],
                        ['key' => 'd', 'text' => 'Pentru că se calculează mai ușor'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Eroarea relativă e adimensională, deci permite comparații între mărimi de naturi diferite. Cea absolută păstrează unitatea și nu spune nimic despre proporție.',
            ],
            [
                'ref' => 'seed:inf:erori:surse',
                'hints' => [
                    'Unele erori apar din simplificările modelului, altele din calculator.',
                    'Neglijarea frecării într-un model fizic e o decizie luată înainte de orice calcul.',
                    'E o eroare de model, nu de reprezentare sau de metodă.',
                ],
                'skill' => 'modelare-erori',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Un model al căderii libere neglijează rezistența aerului. Ce tip de eroare introduce?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Eroare de model'],
                        ['key' => 'b', 'text' => 'Eroare de reprezentare'],
                        ['key' => 'c', 'text' => 'Eroare de rotunjire'],
                        ['key' => 'd', 'text' => 'Eroare de metodă'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Simplificarea e asumată la formularea modelului, înaintea oricărui calcul. Eroarea de reprezentare ține de precizia finită a numerelor reale în memorie.',
            ],
            [
                'ref' => 'seed:inf:erori:anulare',
                'hints' => [
                    'Problema apare când se scad două valori foarte apropiate.',
                    'Cifrele semnificative se anulează între ele și rămâne mai ales eroarea.',
                    'Fenomenul se numește anulare catastrofală.',
                ],
                'skill' => 'modelare-erori',
                'type' => ItemType::Mcq,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'Ce operație produce cea mai mare pierdere de precizie într-un calcul cu numere reale?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Scăderea a două valori foarte apropiate'],
                        ['key' => 'b', 'text' => 'Înmulțirea a două valori mari'],
                        ['key' => 'c', 'text' => 'Adunarea a două valori pozitive'],
                        ['key' => 'd', 'text' => 'Împărțirea la 2'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Cifrele comune se anulează, iar rezultatul păstrează puține cifre corecte. Soluția e rescrierea formulei — de exemplu prin amplificare cu conjugata.',
            ],
            [
                'ref' => 'seed:inf:bisectie:conditie',
                'hints' => [
                    'Metoda pornește de la o proprietate a funcțiilor continue.',
                    'Dacă funcția își schimbă semnul pe interval, trece prin zero.',
                    'Condiția se scrie compact: $f(a) \\cdot f(b) < 0$.',
                ],
                'skill' => 'metode-iterative',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Ce condiție trebuie îndeplinită pentru a aplica metoda bisecției pe intervalul $[a, b]$?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'latex' => 'f(a) \cdot f(b) < 0'],
                        ['key' => 'b', 'latex' => 'f(a) \cdot f(b) > 0'],
                        ['key' => 'c', 'latex' => 'f(a) = f(b)'],
                        ['key' => 'd', 'latex' => 'a < b < 0'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Produs negativ înseamnă semne opuse, deci funcția continuă trece prin zero undeva în interval. Dacă produsul e pozitiv, metoda nu se poate aplica acolo.',
            ],
            [
                'ref' => 'seed:inf:bisectie:pasi',
                'hints' => [
                    'La fiecare pas intervalul se reduce exact la jumătate.',
                    'După $n$ pași lungimea e $\\frac{b-a}{2^n}$.',
                    'Pentru a coborî de la 1 sub $10^{-3}$ trebuie $2^n > 1000$, deci $n = 10$.',
                ],
                'skill' => 'metode-iterative',
                'type' => ItemType::Numeric,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'Un interval de lungime 1 se înjumătățește prin bisecție. Câți pași sunt necesari pentru o precizie mai bună de 0,001?',
                    'stem_latex' => '\frac{1}{2^n} < 0{,}001',
                ],
                'answer' => ['value' => 10, 'tolerance' => 0],
                'explanation' => '$2^9 = 512$ nu ajunge, dar $2^{10} = 1024 > 1000$. Bisecția e lentă, dar numărul de pași e complet previzibil.',
            ],
            [
                'ref' => 'seed:inf:bisectie:oprire',
                'hints' => [
                    'Din cauza reprezentării aproximative, o valoare reală nu devine niciodată exact zero.',
                    'O condiție de egalitate strictă ar produce buclă infinită.',
                    'Oprirea se face pe o toleranță: lungimea intervalului sau valoarea funcției.',
                ],
                'skill' => 'metode-iterative',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Cum se scrie corect condiția de oprire a bisecției?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Când lungimea intervalului scade sub o toleranță dată'],
                        ['key' => 'b', 'text' => 'Când f(m) devine exact 0'],
                        ['key' => 'c', 'text' => 'Când a devine egal cu b'],
                        ['key' => 'd', 'text' => 'După exact 100 de pași'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Egalitatea strictă cu zero nu se atinge practic niciodată în virgulă mobilă. Condiția uzuală e `b - a < eps` sau `|f(m)| < eps`.',
            ],
            [
                'ref' => 'seed:inf:coarde:diferenta',
                'hints' => [
                    'Bisecția ia mijlocul geometric al intervalului.',
                    'Metoda coardelor ține cont și de VALORILE funcției la capete.',
                    'Punctul ales e intersecția coardei cu axa Ox.',
                ],
                'skill' => 'metode-iterative',
                'type' => ItemType::Mcq,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'Prin ce diferă metoda coardelor de metoda bisecției?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Alege punctul în care coarda taie axa Ox, nu mijlocul intervalului'],
                        ['key' => 'b', 'text' => 'Nu are nevoie de schimbare de semn'],
                        ['key' => 'c', 'text' => 'Funcționează doar pentru polinoame'],
                        ['key' => 'd', 'text' => 'Nu necesită condiție de oprire'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Ambele cer schimbarea de semn și o condiție de oprire. Coardele converg de obicei mai repede, pentru că folosesc informația dată de valorile funcției.',
            ],
            [
                'ref' => 'seed:inf:integrare:trapez',
                'hints' => [
                    'Lățimea feliei e $h = \\frac{2-0}{2} = 1$, iar punctele sunt 0, 1, 2.',
                    'Capetele intră pe jumătate: $\\frac{f(0)+f(2)}{2} = \\frac{0+4}{2} = 2$.',
                    'Se adaugă punctul interior $f(1) = 1$, iar totul se înmulțește cu $h = 1$.',
                ],
                'skill' => 'integrare-numerica',
                'type' => ItemType::Numeric,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'Aproximează $\int_0^2 x^2\,dx$ prin metoda trapezelor, cu n = 2 subintervale.',
                    'stem_latex' => 'I \approx h\left(\frac{f(a)+f(b)}{2} + \sum f(x_i)\right)',
                ],
                'answer' => ['value' => 3, 'tolerance' => 0.001],
                'explanation' => '$1 \cdot (2 + 1) = 3$. Valoarea exactă e $\frac{8}{3} \approx 2{,}67$, deci eroarea e de circa 12% — mare, pentru că sunt doar două trapeze.',
            ],
            [
                'ref' => 'seed:inf:integrare:precizie',
                'hints' => [
                    'Metodele diferă prin forma cu care aproximează bucata de grafic.',
                    'Dreptunghiul presupune funcția constantă, trapezul o presupune liniară.',
                    'Simpson folosește parabole, deci urmărește cel mai bine graficul.',
                ],
                'skill' => 'integrare-numerica',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Care metodă de integrare numerică dă, în general, cea mai bună precizie pentru același număr de subintervale?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Metoda lui Simpson'],
                        ['key' => 'b', 'text' => 'Metoda dreptunghiurilor'],
                        ['key' => 'c', 'text' => 'Metoda trapezelor'],
                        ['key' => 'd', 'text' => 'Toate au aceeași precizie'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Simpson aproximează cu parabole, deci eroarea scade ca $h^4$, față de $h^2$ la trapeze și $h$ la dreptunghiuri.',
            ],
            [
                'ref' => 'seed:inf:integrare:latime',
                'hints' => [
                    'Lățimea unei felii e lungimea intervalului împărțită la numărul de felii.',
                    '$h = \\frac{b-a}{n}$.',
                    'Pentru intervalul $[1, 5]$ cu 8 subintervale: $\\frac{4}{8} = 0{,}5$.',
                ],
                'skill' => 'integrare-numerica',
                'type' => ItemType::Numeric,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Care este lățimea unui subinterval pentru integrarea pe $[1, 5]$ cu n = 8?',
                    'stem_latex' => 'h = \frac{b - a}{n}',
                ],
                'answer' => ['value' => 0.5, 'tolerance' => 0.001],
                'explanation' => '$h = \frac{5-1}{8} = 0{,}5$. Toate formulele de integrare numerică pornesc de la această valoare.',
            ],
            [
                'ref' => 'seed:inf:integrare:sens',
                'hints' => [
                    'Integrala definită are o interpretare geometrică.',
                    'Metodele numerice însumează arii de figuri simple.',
                    'Ele aproximează aria cuprinsă între grafic și axa absciselor.',
                ],
                'skill' => 'integrare-numerica',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Ce aproximează metodele numerice de integrare?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Aria dintre graficul funcției și axa Ox'],
                        ['key' => 'b', 'text' => 'Panta tangentei la grafic'],
                        ['key' => 'c', 'text' => 'Soluția ecuației f(x) = 0'],
                        ['key' => 'd', 'text' => 'Valoarea maximă a funcției'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Integrala definită e aria de sub grafic. Panta tangentei e derivata, iar rezolvarea lui $f(x) = 0$ ține de bisecție și de metoda coardelor.',
            ],
        ];
    }

    /**
     * Baze de date: tabele și interogări.
     *
     * @return list<array<string, mixed>>
     */
    private function itemsBazeDeDate(): array
    {
        return [
            [
                'ref' => 'seed:inf:tabele:cheie-primara',
                'hints' => [
                    'Cheia primară trebuie să identifice UNIC fiecare înregistrare.',
                    'Numele se poate repeta și se poate schimba, deci nu e potrivit.',
                    'Un identificator numeric generat automat e stabil și garantat unic.',
                ],
                'skill' => 'tabele',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Ce câmp e cel mai potrivit ca cheie primară într-un tabel cu elevi?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Un identificator numeric generat automat'],
                        ['key' => 'b', 'text' => 'Numele și prenumele'],
                        ['key' => 'c', 'text' => 'Clasa'],
                        ['key' => 'd', 'text' => 'Media generală'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Cheia primară trebuie să fie unică și stabilă. Numele se poate repeta și se poate schimba, clasa se repetă sigur, iar media se modifică.',
            ],
            [
                'ref' => 'seed:inf:tabele:cheie-externa',
                'hints' => [
                    'Legătura dintre două tabele se face printr-un câmp special.',
                    'El conține valoarea cheii primare din celălalt tabel.',
                    'Se numește cheie externă.',
                ],
                'skill' => 'tabele',
                'type' => ItemType::FillBlanks,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Completează denumirea câmpului care leagă două tabele.',
                    'stem_template' => 'Câmpul care conține cheia primară a altui tabel se numește cheie ___ .',
                    'blanks' => [['key' => 'tip', 'options' => ['externă', 'primară', 'unică', 'compusă']]],
                ],
                'answer' => ['values' => ['tip' => ['externă', 'externa']]],
                'explanation' => 'Cheia externă materializează corelația dintre tabele și permite ca datele să fie scrise o singură dată.',
            ],
            [
                'ref' => 'seed:inf:tabele:corelatie-nm',
                'hints' => [
                    'Un elev poate avea mai multe opționale, iar un opțional mai mulți elevi.',
                    'E o corelație mulți-la-mulți, care nu se poate reprezenta cu o singură cheie externă.',
                    'Se rezolvă printr-un al treilea tabel, de legătură.',
                ],
                'skill' => 'tabele',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Cum se implementează o corelație mulți-la-mulți între ELEVI și DISCIPLINE?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Printr-un al treilea tabel care conține ambele chei externe'],
                        ['key' => 'b', 'text' => 'Printr-o cheie externă în tabelul ELEVI'],
                        ['key' => 'c', 'text' => 'Printr-o cheie externă în tabelul DISCIPLINE'],
                        ['key' => 'd', 'text' => 'Nu se poate implementa'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Tabelul de legătură conține perechi (elev_id, disciplina_id). O cheie externă simplă ar limita relația la unu-la-mulți.',
            ],
            [
                'ref' => 'seed:inf:tabele:redundanta',
                'hints' => [
                    'Dacă aceeași informație apare în mai multe locuri, ea trebuie actualizată peste tot.',
                    'O omisiune lasă baza cu două valori diferite pentru același fapt.',
                    'Problema se numește inconsistență și e motivul principal al normalizării.',
                ],
                'skill' => 'tabele',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Care este principalul risc al stocării aceleiași informații în mai multe tabele?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Inconsistența: copiile pot ajunge să difere'],
                        ['key' => 'b', 'text' => 'Interogările devin imposibile'],
                        ['key' => 'c', 'text' => 'Cheia primară se pierde'],
                        ['key' => 'd', 'text' => 'Nu există niciun risc'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Spațiul ocupat e problema mică. Problema mare e că, la o modificare, copiile pot rămâne nesincronizate, iar baza începe să se contrazică.',
            ],
            [
                'ref' => 'seed:inf:interogari:select-where',
                'hints' => [
                    'SELECT alege coloanele, FROM alege tabelul, WHERE filtrează liniile.',
                    'Ordinea clauzelor e fixă și nu se poate schimba.',
                    'Condiția de filtrare stă întotdeauna în WHERE.',
                ],
                'skill' => 'interogari',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Care clauză filtrează liniile returnate de o interogare?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'WHERE'],
                        ['key' => 'b', 'text' => 'SELECT'],
                        ['key' => 'c', 'text' => 'FROM'],
                        ['key' => 'd', 'text' => 'ORDER BY'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'SELECT alege coloanele, FROM sursa, WHERE filtrează liniile, iar ORDER BY doar sortează rezultatul deja obținut.',
            ],
            [
                'ref' => 'seed:inf:interogari:agregare',
                'hints' => [
                    'Funcțiile de agregare condensează mai multe linii într-o singură valoare.',
                    'Pentru medie există o funcție dedicată.',
                    'Se numește AVG, de la „average".',
                ],
                'skill' => 'interogari',
                'type' => ItemType::Mcq,
                'difficulty' => 2,
                'payload' => [
                    'stem' => 'Ce funcție calculează media valorilor dintr-o coloană?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'AVG'],
                        ['key' => 'b', 'text' => 'SUM'],
                        ['key' => 'c', 'text' => 'COUNT'],
                        ['key' => 'd', 'text' => 'MAX'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'AVG face media, SUM adună, COUNT numără liniile, iar MAX și MIN găsesc extremele.',
            ],
            [
                'ref' => 'seed:inf:interogari:group-having',
                'hints' => [
                    'WHERE se aplică înainte de grupare, deci nu poate filtra rezultate agregate.',
                    'Pentru o condiție pe medie ai nevoie de altă clauză.',
                    'Ea se numește HAVING și se scrie după GROUP BY.',
                ],
                'skill' => 'interogari',
                'type' => ItemType::Mcq,
                'difficulty' => 4,
                'payload' => [
                    'stem' => 'Cum se selectează doar clasele cu media peste 9?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'GROUP BY clasa HAVING AVG(media) > 9'],
                        ['key' => 'b', 'text' => 'WHERE AVG(media) > 9 GROUP BY clasa'],
                        ['key' => 'c', 'text' => 'ORDER BY AVG(media) > 9'],
                        ['key' => 'd', 'text' => 'SELECT AVG(media) > 9'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'WHERE filtrează liniile înainte de grupare, deci nu are acces la valoarea agregată. HAVING filtrează grupurile deja formate.',
            ],
            [
                'ref' => 'seed:inf:interogari:join',
                'hints' => [
                    'Când informația e împărțită între tabele, ele trebuie combinate.',
                    'Condiția de combinare spune ce câmpuri corespund.',
                    'Fără ea, fiecare linie se combină cu fiecare — produsul cartezian.',
                ],
                'skill' => 'interogari',
                'type' => ItemType::Mcq,
                'difficulty' => 3,
                'payload' => [
                    'stem' => 'Ce se întâmplă dacă se scrie un JOIN fără condiția ON?',
                    'shuffle' => true,
                    'choices' => [
                        ['key' => 'a', 'text' => 'Fiecare linie din primul tabel se combină cu fiecare din al doilea'],
                        ['key' => 'b', 'text' => 'Interogarea nu returnează nimic'],
                        ['key' => 'c', 'text' => 'Se folosește automat cheia primară'],
                        ['key' => 'd', 'text' => 'Se returnează doar primul tabel'],
                    ],
                ],
                'answer' => ['correct' => ['a']],
                'explanation' => 'Rezultatul e produsul cartezian: la 100 și 1000 de înregistrări se obțin 100 000 de linii fără sens. Numărul absurd de rezultate e simptomul imediat.',
            ],
            [
                'ref' => 'seed:inf:interogari:potrivire-clauze',
                'hints' => [
                    'Fiecare clauză are exact un rol în construcția interogării.',
                    'ORDER BY nu filtrează nimic, doar aranjează rezultatul.',
                    'GROUP BY formează grupuri pe care se aplică funcțiile de agregare.',
                ],
                'skill' => 'interogari',
                'type' => ItemType::Match,
                'difficulty' => 3,
                'points' => 3,
                'payload' => [
                    'stem' => 'Potrivește clauza cu rolul ei.',
                    'left' => [
                        ['key' => 'l1', 'text' => 'WHERE'],
                        ['key' => 'l2', 'text' => 'ORDER BY'],
                        ['key' => 'l3', 'text' => 'GROUP BY'],
                    ],
                    'right' => [
                        ['key' => 'r1', 'text' => 'Formează grupuri pentru agregare'],
                        ['key' => 'r2', 'text' => 'Filtrează liniile'],
                        ['key' => 'r3', 'text' => 'Sortează rezultatul'],
                    ],
                ],
                'answer' => ['pairs' => ['l1' => 'r2', 'l2' => 'r3', 'l3' => 'r1']],
                'explanation' => 'Ordinea logică de execuție e FROM → WHERE → GROUP BY → HAVING → SELECT → ORDER BY, diferită de ordinea în care se scriu.',
            ],
        ];
    }
}
