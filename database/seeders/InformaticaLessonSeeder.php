<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Skill;
use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Teoria pentru informatică.
 *
 * Fiecare bloc de cod apare în AMBELE limbaje acceptate la examen — Pascal și
 * C/C++, la alegerea candidatului, conform programei ANCE. Elevul comută dintr-un
 * singur click, iar preferința se ține minte între lecții.
 *
 * Diferențele dintre limbaje nu sunt ascunse, ci predate: `=` înseamnă altceva
 * în Pascal decât în C++, tablourile se indexează de la 1 sau de la 0, iar
 * împărțirea întreagă are operatori diferiți. Sunt exact sursele de greșeli la
 * un examen unde poți alege limbajul.
 */
class InformaticaLessonSeeder extends Seeder
{
    public function run(): void
    {
        $subject = Subject::where('slug', 'informatica')->firstOrFail();

        foreach ($this->lessons() as $slug => $data) {
            $skill = Skill::where('subject_id', $subject->id)->where('slug', $slug)->first();

            if ($skill === null) {
                continue;
            }

            Lesson::updateOrCreate(
                ['skill_id' => $skill->id],
                [
                    'title' => $data['title'],
                    'summary' => $data['summary'],
                    'objectives' => $data['objectives'],
                    'blocks' => $data['blocks'],
                    'status' => 'published',
                    'language' => 'ro',
                ],
            );
        }
    }

    /** @return array<string, array<string, mixed>> */
    private function lessons(): array
    {
        return [
            // ==================================================================
            'instructiuni' => [
                'title' => 'Instrucțiuni și expresii',
                'summary' => 'Structura unui program, instrucțiunile de bază și cum se urmărește execuția pas cu pas — competența cerută de aproape fiecare item de examen.',
                'objectives' => [
                    'Să recunoști părțile componente ale unui program',
                    'Să folosești corect instrucțiunile dacă, pentru, cât timp',
                    'Să urmărești execuția unui program pe hârtie și să determini ce afișează',
                    'Să eviți greșelile care apar din diferențele dintre Pascal și C++',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Structura unui program'],

                    ['type' => 'text', 'text' => 'Orice program are aceleași părți: o zonă de **declarații**, unde spui ce variabile folosești și de ce tip, și o zonă de **instrucțiuni**, care se execută în ordine, de sus în jos.'],

                    [
                        'type' => 'code',
                        'caption' => 'Același program în ambele limbaje: citește un număr și afișează dublul lui.',
                        'variants' => [
                            'cpp' => "#include <iostream>\nusing namespace std;\n\nint main() {\n    int n;\n    cin >> n;\n    cout << n * 2;\n    return 0;\n}",
                            'pascal' => "program Dublu;\nvar\n  n: integer;\nbegin\n  read(n);\n  write(n * 2);\nend.",
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Observă că în Pascal declarațiile stau într-o secțiune `var` separată, înaintea corpului, iar în C++ pot apărea oriunde. La examen contează doar să fie declarate **înainte de prima folosire**.'],

                    ['type' => 'heading', 'text' => 'Instrucțiunea dacă'],

                    [
                        'type' => 'code',
                        'caption' => 'Ramificarea: se execută o ramură sau cealaltă, niciodată ambele.',
                        'variants' => [
                            'cpp' => "if (n % 2 == 0)\n    cout << \"par\";\nelse\n    cout << \"impar\";",
                            'pascal' => "if n mod 2 = 0 then\n  write('par')\nelse\n  write('impar');",
                        ],
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Cea mai perfidă diferență între limbaje',
                        'text' => 'În Pascal, `=` înseamnă **comparație**, iar atribuirea se scrie `:=`. În C++ e exact invers: `=` atribuie, iar comparația se scrie `==`. Scrierea `if (x = 10)` în C++ nu compară nimic — atribuie 10 lui x și condiția devine mereu adevărată. Aceeași linie în Pascal e perfect corectă. Alege un limbaj și rămâi la el.',
                    ],

                    ['type' => 'heading', 'text' => 'Ciclul cu contor: pentru'],

                    ['type' => 'text', 'text' => 'Se folosește când știi **dinainte** de câte ori se repetă ceva. Contorul crește automat, iar tu nu trebuie să-l modifici în interior.'],

                    [
                        'type' => 'code',
                        'caption' => 'Suma numerelor de la 1 la n.',
                        'variants' => [
                            'cpp' => "int s = 0;\nfor (int i = 1; i <= n; i++)\n    s = s + i;\ncout << s;",
                            'pascal' => "s := 0;\nfor i := 1 to n do\n  s := s + i;\nwrite(s);",
                        ],
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Acumulatorul trebuie inițializat',
                        'text' => 'Fără `s := 0` înaintea ciclului, primul `s := s + i` citește o valoare pe care programul nu a scris-o niciodată. Rezultatul devine imprevizibil — simptomul tipic e „merge uneori". Pentru sumă pornești de la 0, pentru produs de la 1.',
                    ],

                    ['type' => 'heading', 'text' => 'Ciclul cu condiție: cât timp'],

                    ['type' => 'text', 'text' => 'Se folosește când **nu** știi dinainte câte repetări sunt necesare — te oprești când o condiție încetează să fie adevărată. Aici tu ești responsabil să modifici ceva în interior, altfel ciclul nu se termină niciodată.'],

                    [
                        'type' => 'example',
                        'title' => 'Exemplu urmărit pas cu pas',
                        'problem' => 'Ce afișează programul pentru n = 345?',
                        'variants' => [
                            'cpp' => "int n = 345, c = 0;\nwhile (n > 0) {\n    c = c + n % 10;\n    n = n / 10;\n}\ncout << c;",
                            'pascal' => "n := 345; c := 0;\nwhile n > 0 do\nbegin\n  c := c + n mod 10;\n  n := n div 10;\nend;\nwrite(c);",
                        ],
                        'steps' => [
                            ['text' => 'Restul împărțirii la 10 dă ultima cifră, iar împărțirea întreagă la 10 o șterge. Pornim cu n = 345, c = 0.'],
                            ['text' => 'Prima repetare: ultima cifră e 5, deci c devine 5, iar n devine 34.'],
                            ['text' => 'A doua repetare: ultima cifră e 4, deci c devine 9, iar n devine 3.'],
                            ['text' => 'A treia repetare: ultima cifră e 3, deci c devine 12, iar n devine 0.'],
                            ['text' => 'Condiția n > 0 e falsă, ciclul se oprește și se afișează c.'],
                        ],
                        'result' => 'Se afișează **12**, adică suma cifrelor lui 345.',
                    ],

                    ['type' => 'text', 'text' => 'Combinația „rest la 10 + împărțire întreagă la 10" e **algoritmul standard de parcurgere a cifrelor**. Apare la examen în zeci de variații: suma cifrelor, numărul de cifre pare, inversarea numărului, verificarea palindromului.'],

                    [
                        'type' => 'warning',
                        'title' => 'Împărțirea întreagă are operatori diferiți',
                        'text' => 'În Pascal, `div` e împărțirea întreagă și `mod` e restul. În C++, `/` face împărțire întreagă **doar dacă ambii operanzi sunt întregi** — dacă unul e real, rezultatul e real. Iar restul se scrie `%`, nu `mod`.',
                    ],

                    ['type' => 'heading', 'text' => 'Cum se rezolvă un item de tip „ce afișează programul"'],

                    ['type' => 'text', 'text' => 'Nu citi programul „în minte". Fă un **tabel de urmărire**: o coloană pentru fiecare variabilă, un rând pentru fiecare repetare. E metoda pe care o folosesc și evaluatorii, și te scapă de aproape toate greșelile de neatenție.'],
                ],
            ],

            // ==================================================================
            'tipuri-structurate' => [
                'title' => 'Tipuri de date structurate',
                'summary' => 'Tablouri și șiruri de caractere: declarare, parcurgere și capcanele de indexare dintre Pascal și C++.',
                'objectives' => [
                    'Să declari și să parcurgi un tablou unidimensional',
                    'Să lucrezi cu șiruri de caractere',
                    'Să eviți depășirea limitelor tabloului',
                    'Să știi de la ce indice începe numărătoarea în fiecare limbaj',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'De ce avem nevoie de tablouri'],

                    ['type' => 'text', 'text' => 'Dacă ai de prelucrat 100 de note, nu declari 100 de variabile. Un **tablou** e o colecție de valori de același tip, accesibile printr-un indice — ca o stradă cu case numerotate.'],

                    [
                        'type' => 'code',
                        'caption' => 'Declararea unui tablou de 100 de numere întregi.',
                        'variants' => [
                            'cpp' => "int a[100];\n\n// elementele sunt a[0], a[1], ..., a[99]",
                            'pascal' => "var\n  a: array[1..100] of integer;\n\n{ elementele sunt a[1], a[2], ..., a[100] }",
                        ],
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Indicele de start diferă',
                        'text' => 'În C++ tablourile încep **întotdeauna** de la 0, iar un tablou de dimensiune n are ultimul element pe poziția n−1. În Pascal alegi tu intervalul, iar convenția obișnuită e de la 1. Aceeași eroare „off-by-one" arată diferit în cele două limbaje — de aceea ciclurile de parcurgere nu se copiază între ele fără gândire.',
                    ],

                    ['type' => 'heading', 'text' => 'Parcurgerea'],

                    [
                        'type' => 'code',
                        'caption' => 'Citirea a n elemente și calculul sumei lor.',
                        'variants' => [
                            'cpp' => "int s = 0;\nfor (int i = 0; i < n; i++) {\n    cin >> a[i];\n    s = s + a[i];\n}\ncout << s;",
                            'pascal' => "s := 0;\nfor i := 1 to n do\nbegin\n  read(a[i]);\n  s := s + a[i];\nend;\nwrite(s);",
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Observă limitele ciclului: în C++ mergi de la `0` la `n-1` cu condiția `i < n`, în Pascal de la `1` la `n`. Ambele parcurg exact n elemente.'],

                    [
                        'type' => 'example',
                        'title' => 'Exemplu rezolvat',
                        'problem' => 'Găsește valoarea maximă dintr-un tablou cu n elemente.',
                        'variants' => [
                            'cpp' => "int maxim = a[0];\nfor (int i = 1; i < n; i++)\n    if (a[i] > maxim)\n        maxim = a[i];\ncout << maxim;",
                            'pascal' => "maxim := a[1];\nfor i := 2 to n do\n  if a[i] > maxim then\n    maxim := a[i];\nwrite(maxim);",
                        ],
                        'steps' => [
                            ['text' => 'Presupunem că primul element e cel mai mare — nu pornim de la zero, pentru că numerele pot fi negative.'],
                            ['text' => 'Parcurgem restul elementelor, începând de la al doilea.'],
                            ['text' => 'Ori de câte ori găsim ceva mai mare, actualizăm maximul.'],
                            ['text' => 'La final, maximul conține cea mai mare valoare din tablou.'],
                        ],
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Nu porni maximul de la 0',
                        'text' => 'E greșeala clasică: dacă inițializezi `maxim := 0` și toate valorile din tablou sunt negative, programul afișează 0 — o valoare care nici nu există în tablou. Pornește întotdeauna de la primul element.',
                    ],

                    ['type' => 'heading', 'text' => 'Șiruri de caractere'],

                    ['type' => 'text', 'text' => 'Un șir de caractere e, în esență, un tablou de caractere. Diferența majoră între limbaje: în C++ șirul se termină cu un caracter special, `\\0`, iar în Pascal lungimea se află direct cu funcția `length`.'],

                    [
                        'type' => 'code',
                        'caption' => 'Numărarea aparițiilor literei „a" în cuvântul „informatica".',
                        'variants' => [
                            'cpp' => "char s[] = \"informatica\";\nint k = 0;\nfor (int i = 0; s[i] != '\\0'; i++)\n    if (s[i] == 'a')\n        k++;\ncout << k;",
                            'pascal' => "s := 'informatica';\nk := 0;\nfor i := 1 to length(s) do\n  if s[i] = 'a' then\n    k := k + 1;\nwrite(k);",
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Rezultatul e 2. Atenție și la ghilimele: în Pascal, șirurile și caracterele se scriu cu **apostrof**, iar în C++ șirul folosește ghilimele duble, dar un singur caracter folosește apostrof.'],
                ],
            ],

            // ==================================================================
            'subprograme' => [
                'title' => 'Subprograme',
                'summary' => 'Funcții și proceduri, parametri, domeniul de vizibilitate și ce se întâmplă efectiv la un apel.',
                'objectives' => [
                    'Să distingi o funcție de o procedură și să știi când folosești fiecare',
                    'Să transmiți parametri prin valoare și prin referință',
                    'Să explici de ce o variabilă locală nu supraviețuiește între apeluri',
                    'Să urmărești ordinea etapelor la executarea unui apel',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'De ce se sparge un program în subprograme'],

                    ['type' => 'text', 'text' => 'Un subprogram e o bucată de cod cu nume, pe care o scrii o dată și o folosești de câte ori vrei. Câștigul nu e doar scurtimea: un subprogram se poate testa separat, iar o greșeală se repară într-un singur loc.'],

                    ['type' => 'text', 'text' => 'O **funcție** întoarce o valoare și se folosește într-o expresie. O **procedură** face ceva — afișează, modifică — dar nu întoarce nimic. În C++ ambele se scriu la fel, iar diferența e că procedura are tipul `void`.'],

                    [
                        'type' => 'code',
                        'caption' => 'O funcție care calculează suma cifrelor unui număr.',
                        'variants' => [
                            'cpp' => "int sumaCifrelor(int n) {\n    int s = 0;\n    while (n > 0) {\n        s = s + n % 10;\n        n = n / 10;\n    }\n    return s;\n}\n\n// apel: cout << sumaCifrelor(345);",
                            'pascal' => "function SumaCifrelor(n: integer): integer;\nvar\n  s: integer;\nbegin\n  s := 0;\n  while n > 0 do\n  begin\n    s := s + n mod 10;\n    n := n div 10;\n  end;\n  SumaCifrelor := s;\nend;\n\n{ apel: write(SumaCifrelor(345)); }",
                        ],
                    ],

                    ['type' => 'text', 'text' => 'În Pascal, rezultatul se întoarce atribuindu-l **numelui funcției**. În C++ se folosește `return`, care în plus oprește imediat execuția subprogramului.'],

                    ['type' => 'heading', 'text' => 'Ce se întâmplă, de fapt, la un apel'],

                    ['type' => 'text', 'text' => 'Programa cere explicit „interpretarea modului de execuție a apelului". Etapele sunt mereu aceleași, în această ordine:'],

                    [
                        'type' => 'example',
                        'title' => 'Ordinea execuției',
                        'steps' => [
                            ['text' => 'Se evaluează expresiile-argument din instrucțiunea de apel.'],
                            ['text' => 'Se alocă memorie pentru parametrii formali și pentru variabilele locale.'],
                            ['text' => 'Valorile argumentelor se transmit parametrilor formali.'],
                            ['text' => 'Se execută instrucțiunile din corpul subprogramului.'],
                            ['text' => 'Se eliberează memoria alocată variabilelor locale.'],
                            ['text' => 'Controlul revine la instrucțiunea de după apel.'],
                        ],
                        'result' => 'Pasul 5 explică de ce o variabilă locală nu-și păstrează valoarea între apeluri — memoria ei dispare.',
                    ],

                    ['type' => 'heading', 'text' => 'Prin valoare sau prin referință'],

                    ['type' => 'text', 'text' => 'Implicit, subprogramul primește o **copie** a argumentului: modificările din interior nu se văd afară. Dacă vrei ca subprogramul să modifice variabila originală, transmiți prin **referință**.'],

                    [
                        'type' => 'code',
                        'caption' => 'Interschimbarea a două valori — funcționează doar prin referință.',
                        'variants' => [
                            'cpp' => "void interschimba(int &a, int &b) {\n    int aux = a;\n    a = b;\n    b = aux;\n}",
                            'pascal' => "procedure Interschimba(var a, b: integer);\nvar\n  aux: integer;\nbegin\n  aux := a;\n  a := b;\n  b := aux;\nend;",
                        ],
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Fără & sau var, nu se schimbă nimic',
                        'text' => 'Dacă scoți `&` din C++ sau `var` din Pascal, subprogramul lucrează pe copii: interschimbarea are loc în interior, dar afară valorile rămân neschimbate. E o greșeală care nu produce nicio eroare la compilare — programul pur și simplu nu face ce trebuie.',
                    ],

                    ['type' => 'heading', 'text' => 'Domeniul de vizibilitate'],

                    ['type' => 'text', 'text' => 'O variabilă **locală** există doar în subprogramul care o declară. O variabilă **globală** e vizibilă peste tot. Regula practică la examen: folosește variabile locale și transmite ce ai nevoie prin parametri — globalele fac programul greu de urmărit și sunt penalizate la criteriul de claritate.'],
                ],
            ],

            // ==================================================================
            'sisteme-numeratie' => [
                'title' => 'Sisteme de numerație',
                'summary' => 'De ce calculatorul lucrează în binar, cum se convertesc numerele între baze și de ce hexazecimalul scurtează totul.',
                'objectives' => [
                    'Să explici ce înseamnă un sistem pozițional de numerație',
                    'Să convertești din binar, octal și hexazecimal în zecimal',
                    'Să convertești din zecimal în orice bază, prin împărțiri succesive',
                    'Să grupezi biții pentru conversii rapide între binar, octal și hexazecimal',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Sistemul pozițional'],

                    ['type' => 'text', 'text' => 'Într-un sistem pozițional, valoarea unei cifre depinde de **locul** unde stă. În 345, cifra 3 nu valorează 3, ci 300 — pentru că e pe poziția sutelor. Fiecare poziție are o pondere: o putere a bazei.'],

                    [
                        'type' => 'formula',
                        'latex' => '345_{(10)} = 3 \\cdot 10^2 + 4 \\cdot 10^1 + 5 \\cdot 10^0',
                        'caption' => 'Aceeași regulă funcționează în orice bază — se schimbă doar numărul care se ridică la putere.',
                    ],

                    ['type' => 'text', 'text' => 'Calculatorul lucrează în **baza 2** pentru un motiv fizic: un circuit distinge fiabil două stări — tensiune prezentă sau absentă. Zece stări distincte ar fi mult mai greu de deosebit.'],

                    ['type' => 'heading', 'text' => 'Din orice bază în zecimal'],

                    ['type' => 'text', 'text' => 'Înmulțești fiecare cifră cu ponderea poziției ei și aduni. Ponderile sunt puterile bazei, crescând de la dreapta spre stânga, începând de la $b^0 = 1$.'],

                    [
                        'type' => 'example',
                        'title' => 'Exemplu rezolvat',
                        'problem' => 'Convertește $1101_{(2)}$ în sistemul zecimal.',
                        'steps' => [
                            ['text' => 'Scriem ponderile pozițiilor, de la dreapta spre stânga.', 'latex' => '2^3 = 8,\\quad 2^2 = 4,\\quad 2^1 = 2,\\quad 2^0 = 1'],
                            ['text' => 'Înmulțim fiecare cifră cu ponderea ei.', 'latex' => '1 \\cdot 8 + 1 \\cdot 4 + 0 \\cdot 2 + 1 \\cdot 1'],
                            ['text' => 'Adunăm.', 'latex' => '8 + 4 + 0 + 1 = 13'],
                        ],
                        'result' => '$1101_{(2)} = 13_{(10)}$',
                    ],

                    ['type' => 'heading', 'text' => 'Din zecimal în orice bază'],

                    ['type' => 'text', 'text' => 'Împarți succesiv la bază și reții resturile. Numărul se citește din **resturile luate invers**, de la ultimul spre primul.'],

                    [
                        'type' => 'example',
                        'title' => 'Exemplu rezolvat',
                        'problem' => 'Convertește $13_{(10)}$ în baza 2.',
                        'steps' => [
                            ['text' => 'Împărțim la 2 și reținem restul.', 'latex' => '13 : 2 = 6 \\ \\text{rest}\\ 1'],
                            ['text' => 'Continuăm cu câtul.', 'latex' => '6 : 2 = 3 \\ \\text{rest}\\ 0'],
                            ['text' => 'Și mai departe.', 'latex' => '3 : 2 = 1 \\ \\text{rest}\\ 1'],
                            ['text' => 'Ultima împărțire.', 'latex' => '1 : 2 = 0 \\ \\text{rest}\\ 1'],
                            ['text' => 'Citim resturile de jos în sus.', 'latex' => '1101_{(2)}'],
                        ],
                        'result' => 'Verificare: am obținut exact numărul de la exemplul anterior.',
                    ],

                    ['type' => 'heading', 'text' => 'Scurtătura: gruparea biților'],

                    ['type' => 'text', 'text' => 'Octalul și hexazecimalul există ca să scurteze scrierea binară. Pentru că $8 = 2^3$ și $16 = 2^4$, conversia se face prin simplă **grupare**, fără niciun calcul: câte 3 biți pentru octal, câte 4 pentru hexazecimal, întotdeauna de la dreapta.'],

                    [
                        'type' => 'formula',
                        'latex' => '255_{(10)} = \\underbrace{1111}_{\\text{F}}\\ \\underbrace{1111}_{\\text{F}} = \\text{FF}_{(16)} = \\underbrace{011}_{3}\\ \\underbrace{111}_{7}\\ \\underbrace{111}_{7} = 377_{(8)}',
                        'caption' => 'Același număr, trei scrieri. 255 e cel mai mare număr reprezentabil pe 8 biți — un octet.',
                    ],

                    ['type' => 'text', 'text' => 'În hexazecimal, cifrele merg de la 0 la 9, apoi literele A, B, C, D, E, F reprezintă valorile 10–15. De aceea un octet se scrie exact cu două cifre hexazecimale.'],

                    [
                        'type' => 'warning',
                        'title' => 'Bit și octet nu sunt același lucru',
                        'text' => 'Un **bit** e o singură cifră binară, 0 sau 1. Un **octet** (byte) are 8 biți și poate reprezenta 256 de valori distincte, de la 0 la 255. Confuzia apare des la itemii despre cantitatea de informație: cu $k$ biți distingi $2^k$ mesaje, nu $k$ mesaje.',
                    ],
                ],
            ],
            // ==================================================================
            'tipuri-simple' => [
                'title' => 'Tipuri de date simple',
                'summary' => 'Ce încape într-un întreg, de ce zecimalele nu se compară cu „=" și cum alegi tipul potrivit pentru o variabilă.',
                'objectives' => [
                    'Să alegi tipul de date potrivit domeniului de valori',
                    'Să recunoști depășirea de domeniu și împărțirea întreagă',
                    'Să compari corect valori reale, fără egalitate strictă',
                    'Să folosești aceleași noțiuni în C++ și în Pascal',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Tipul spune două lucruri'],

                    ['type' => 'text', 'text' => 'Când declari o variabilă, tipul stabilește **ce valori încap** în ea și **ce operații** are voie să facă. Nu e o formalitate: majoritatea erorilor de la examen vin din alegerea greșită a tipului, nu din algoritm.'],

                    [
                        'type' => 'table',
                        'caption' => 'Domeniile sunt cele uzuale pe 32 de biți; la examen se lucrează cu aceste valori.',
                        'headers' => ['C++', 'Pascal', 'Ce reține', 'Domeniu orientativ'],
                        'rows' => [
                            ['int', 'integer', 'Numere întregi', 'aprox. ±2 miliarde'],
                            ['long long', 'int64', 'Întregi mari', 'aprox. ±9·10¹⁸'],
                            ['double', 'real', 'Numere reale', 'cu precizie limitată'],
                            ['char', 'char', 'Un singur caracter', '256 de valori'],
                            ['bool', 'boolean', 'Adevărat sau fals', 'true / false'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Împărțirea întreagă'],

                    ['type' => 'text', 'text' => 'Când ambii operanzi sunt întregi, rezultatul împărțirii e tot întreg — partea zecimală se pierde, nu se rotunjește. E comportamentul dorit în multe algoritmi, dar și o sursă clasică de rezultate greșite.'],

                    [
                        'type' => 'code',
                        'caption' => 'Ambele programe afișează 2, nu 2,5. În Pascal operatorul se numește „div"; „/" ar da un rezultat real.',
                        'variants' => [
                            'cpp' => "int a = 5, b = 2;\ncout << a / b;      // 2\ncout << a % b;      // 1  (restul)\ncout << (double)a / b;  // 2.5",
                            'pascal' => "a := 5; b := 2;\nwrite(a div b);   { 2 }\nwrite(a mod b);   { 1  (restul) }\nwrite(a / b);     { 2.5 — rezultat real }",
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Operatorul de rest — `%` în C++, `mod` în Pascal — e cel mai folosit instrument la examen: testarea parității (`n % 2 == 0`), extragerea ultimei cifre (`n % 10`), verificarea divizibilității.'],

                    ['type' => 'heading', 'text' => 'Depășirea de domeniu'],

                    ['type' => 'text', 'text' => 'Dacă rezultatul unui calcul nu încape în tip, valoarea „se rotește" și devine complet greșită, **fără niciun mesaj de eroare**. Programul rulează liniștit și afișează un număr fals.'],

                    [
                        'type' => 'warning',
                        'title' => 'Factorialul depășește repede',
                        'text' => '$13!$ nu mai încape într-un `int`, iar $21!$ nu încape nici în `long long`. Dacă o problemă cere factorial sau produse mari, verifică domeniul ÎNAINTE de a scrie codul — altfel pierzi punctele pe testele mari, deși algoritmul e corect.',
                    ],

                    ['type' => 'heading', 'text' => 'Numerele reale nu se compară cu egalitate'],

                    ['type' => 'text', 'text' => 'Tipurile reale stochează valorile aproximativ. `0.1 + 0.2` nu dă exact `0.3`, ci ceva foarte apropiat. De aceea comparația directă poate fi falsă chiar când matematic ar trebui să fie adevărată.'],

                    [
                        'type' => 'code',
                        'caption' => 'Se compară diferența cu o toleranță mică, numită epsilon.',
                        'variants' => [
                            'cpp' => "// GREȘIT\nif (x == y) { ... }\n\n// CORECT\nif (fabs(x - y) < 1e-9) { ... }",
                            'pascal' => "{ GREȘIT }\nif x = y then ...\n\n{ CORECT }\nif abs(x - y) < 1e-9 then ...",
                        ],
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Inițializează înainte de a folosi',
                        'text' => 'O variabilă nedeclarată explicit cu o valoare conține gunoi — ce se afla în memorie. Un contor sau o sumă trebuie pusă pe zero înainte de buclă. E greșeala care produce rezultate diferite la fiecare rulare și e foarte greu de depistat la corectare.',
                    ],
                ],
            ],

            // ==================================================================
            'recursie' => [
                'title' => 'Recursia',
                'summary' => 'Cum se construiește o funcție care se autoapelează, de ce cazul de bază nu e opțional și când recursia costă mai mult decât o buclă.',
                'objectives' => [
                    'Să identifici cazul de bază și pasul recursiv',
                    'Să urmărești execuția unui apel recursiv pe hârtie',
                    'Să eviți recursia infinită și depășirea stivei',
                    'Să decizi între recursie și iterație',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Două părți, întotdeauna'],

                    ['type' => 'text', 'text' => 'O funcție recursivă se rezolvă apelându-se pe ea însăși pentru o problemă **mai mică**. Ca să se oprească vreodată, are nevoie de exact două componente:'],

                    ['type' => 'text', 'text' => '**Cazul de bază** — situația atât de simplă încât răspunsul se dă direct, fără alt apel. **Pasul recursiv** — reducerea problemei spre cazul de bază.'],

                    [
                        'type' => 'code',
                        'caption' => 'Factorial: cazul de bază e n ≤ 1, iar pasul recursiv coboară spre el.',
                        'variants' => [
                            'cpp' => "int fact(int n) {\n    if (n <= 1) return 1;   // caz de bază\n    return n * fact(n - 1); // pas recursiv\n}",
                            'pascal' => "function fact(n: integer): integer;\nbegin\n  if n <= 1 then\n    fact := 1            { caz de bază }\n  else\n    fact := n * fact(n - 1); { pas recursiv }\nend;",
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Cum se desfășoară apelurile'],

                    ['type' => 'text', 'text' => 'Apelurile se așază unul peste altul pe **stivă** și se rezolvă în ordine inversă. Nimic nu se calculează până nu se atinge cazul de bază.'],

                    [
                        'type' => 'table',
                        'caption' => 'Coborârea până la cazul de bază, apoi întoarcerea cu rezultate.',
                        'headers' => ['Apel', 'Ce returnează', 'Când'],
                        'rows' => [
                            ['fact(4)', '4 · fact(3) = 24', 'ultimul'],
                            ['fact(3)', '3 · fact(2) = 6', 'al treilea'],
                            ['fact(2)', '2 · fact(1) = 2', 'al doilea'],
                            ['fact(1)', '1 — caz de bază', 'primul'],
                        ],
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Fără caz de bază, programul se oprește brutal',
                        'text' => 'O funcție care se apelează la nesfârșit umple stiva și produce eroarea *stack overflow*. La examen, o recursie fără caz de bază nu ia niciun punct, chiar dacă restul raționamentului e corect.',
                    ],

                    ['type' => 'heading', 'text' => 'Când NU merită'],

                    ['type' => 'text', 'text' => 'Recursia naivă pentru șirul lui Fibonacci recalculează aceleași valori de mii de ori: `fib(5)` îl calculează pe `fib(2)` de trei ori. Numărul de apeluri crește exponențial, iar pentru `n = 50` programul nu se mai termină într-un timp rezonabil.'],

                    [
                        'type' => 'example',
                        'title' => 'Aceeași problemă, două costuri',
                        'problem' => 'Calculul lui fib(n).',
                        'steps' => [
                            ['text' => 'Recursiv naiv: numărul de apeluri crește exponențial cu n. Pentru n = 50 e practic imposibil.'],
                            ['text' => 'Iterativ, cu o buclă și două variabile: n pași, indiferent cât de mare e n.'],
                            ['text' => 'Recursiv cu memorare (reținerea rezultatelor deja calculate): tot n pași, dar cu memorie în plus.'],
                        ],
                        'result' => 'Recursia e potrivită când problema are structură recursivă naturală — parcurgerea unui arbore, divide et impera. Pentru un simplu contor, bucla e mai bună.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Ce se cere efectiv la examen',
                        'text' => 'Cele mai frecvente cerințe sunt: să scrii o funcție recursivă simplă (factorial, sumă de cifre, cmmdc), să urmărești pe hârtie ce afișează un program recursiv dat, sau să identifici cazul de bază lipsă. Exersează mai ales **urmărirea execuției**, nu doar scrierea.',
                    ],
                ],
            ],

            // ==================================================================
            'conversii-baze' => [
                'title' => 'Conversia numerelor dintr-o bază în alta',
                'summary' => 'Cele două metode de bază, scurtătura dintre binar, octal și hexazecimal, și verificarea care prinde greșelile de calcul.',
                'objectives' => [
                    'Să treci din orice bază în baza 10 prin descompunere',
                    'Să treci din baza 10 în orice bază prin împărțiri succesive',
                    'Să folosești gruparea de biți pentru octal și hexazecimal',
                    'Să verifici rezultatul printr-o conversie inversă rapidă',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Din orice bază în baza 10'],

                    ['type' => 'text', 'text' => 'Fiecare cifră are o **pondere** dată de poziția ei: baza ridicată la puterea egală cu poziția, numărând de la 0, de la dreapta spre stânga. Se înmulțește fiecare cifră cu ponderea ei și se adună.'],

                    [
                        'type' => 'example',
                        'title' => 'Din binar în zecimal',
                        'problem' => 'Convertește $1011_2$ în baza 10.',
                        'steps' => [
                            ['text' => 'Scriu ponderile de la dreapta: 1, 2, 4, 8.'],
                            ['text' => 'Înmulțesc fiecare cifră cu ponderea ei: $1 \\cdot 8 + 0 \\cdot 4 + 1 \\cdot 2 + 1 \\cdot 1$.'],
                            ['text' => 'Adun: $8 + 0 + 2 + 1 = 11$.'],
                        ],
                        'result' => '$1011_2 = 11_{10}$. Metoda funcționează identic pentru orice bază — se schimbă doar ponderile.',
                    ],

                    ['type' => 'heading', 'text' => 'Din baza 10 în orice bază'],

                    ['type' => 'text', 'text' => 'Se împarte repetat la bază, reținând resturile. Rezultatul se citește din **resturi, de jos în sus** — adică în ordinea inversă obținerii lor. Inversarea uitată e greșeala numărul unu la acest capitol.'],

                    [
                        'type' => 'table',
                        'caption' => 'Conversia lui 25 în baza 2. Citit de jos în sus: 11001.',
                        'headers' => ['Împărțire', 'Cât', 'Rest'],
                        'rows' => [
                            ['25 : 2', '12', '1'],
                            ['12 : 2', '6', '0'],
                            ['6 : 2', '3', '0'],
                            ['3 : 2', '1', '1'],
                            ['1 : 2', '0', '1'],
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Verificare: $11001_2 = 16 + 8 + 0 + 0 + 1 = 25$. Conversia inversă durează zece secunde și prinde majoritatea greșelilor de calcul.'],

                    ['type' => 'heading', 'text' => 'Scurtătura: binar ↔ octal ↔ hexazecimal'],

                    ['type' => 'text', 'text' => 'Între aceste baze nu se trece prin baza 10. Fiindcă $8 = 2^3$ și $16 = 2^4$, se grupează biții: **câte 3 pentru octal**, **câte 4 pentru hexazecimal**, întotdeauna începând din dreapta.'],

                    [
                        'type' => 'table',
                        'caption' => 'Tabelul cifrelor hexazecimale. Literele continuă numărătoarea de la 10.',
                        'headers' => ['Hexa', 'Zecimal', 'Binar (4 biți)'],
                        'rows' => [
                            ['8', '8', '1000'],
                            ['9', '9', '1001'],
                            ['A', '10', '1010'],
                            ['B', '11', '1011'],
                            ['C', '12', '1100'],
                            ['F', '15', '1111'],
                        ],
                    ],

                    [
                        'type' => 'example',
                        'title' => 'Binar în hexazecimal, prin grupare',
                        'problem' => 'Convertește $11010110_2$ în baza 16.',
                        'steps' => [
                            ['text' => 'Grupez câte 4 biți, din dreapta: 1101 | 0110.'],
                            ['text' => 'Convertesc fiecare grup separat: $1101_2 = 13 = D$, iar $0110_2 = 6$.'],
                            ['text' => 'Scriu grupurile în aceeași ordine: D6.'],
                        ],
                        'result' => '$11010110_2 = D6_{16}$. Dacă biții nu se împart exact, se completează cu zerouri LA STÂNGA — nu la dreapta.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Două capcane',
                        'text' => 'Prima: gruparea se face întotdeauna dinspre DREAPTA, pentru că acolo sunt ponderile mici. A doua: într-o bază $b$, cifrele merg de la 0 la $b-1$ — deci $1012_2$ nu există, cifra 2 e imposibilă în baza 2. Dacă un enunț îți dă așa ceva, verifică baza.',
                    ],
                ],
            ],

            // ==================================================================
            'algebra-booleana' => [
                'title' => 'Algebra booleană',
                'summary' => 'Cele trei operații de bază, tabelele de adevăr și legile care permit simplificarea unei expresii logice.',
                'objectives' => [
                    'Să construiești tabelul de adevăr al unei expresii',
                    'Să aplici legile lui De Morgan pentru a nega o expresie',
                    'Să simplifici expresii folosind absorbția și distributivitatea',
                    'Să legi operațiile logice de condițiile din program',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Trei operații, două valori'],

                    ['type' => 'text', 'text' => 'O variabilă booleană ia doar două valori: **0** (fals) sau **1** (adevărat). Peste ele se definesc trei operații, din care se construiește orice altceva.'],

                    [
                        'type' => 'table',
                        'caption' => 'Notațiile diferă între manuale, dar operațiile sunt aceleași.',
                        'headers' => ['Operație', 'Notație', 'În C++', 'În Pascal', 'Când dă 1'],
                        'rows' => [
                            ['Conjuncție (ȘI)', '$A \cdot B$', '&&', 'and', 'Ambele sunt 1'],
                            ['Disjuncție (SAU)', '$A + B$', '||', 'or', 'Cel puțin una e 1'],
                            ['Negație (NU)', '$\overline{A}$', '!', 'not', 'Când A este 0'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Tabelul de adevăr'],

                    ['type' => 'text', 'text' => 'Se scriu toate combinațiile posibile ale variabilelor și se calculează rezultatul pentru fiecare. Pentru $n$ variabile există $2^n$ linii — deci 4 linii la două variabile, 8 la trei.'],

                    [
                        'type' => 'table',
                        'caption' => 'Conjuncția e adevărată într-un singur caz; disjuncția, în trei.',
                        'headers' => ['A', 'B', '$A \cdot B$', '$A + B$', '$\overline{A}$'],
                        'rows' => [
                            ['0', '0', '0', '0', '1'],
                            ['0', '1', '0', '1', '1'],
                            ['1', '0', '0', '1', '0'],
                            ['1', '1', '1', '1', '0'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Legile lui De Morgan'],

                    ['type' => 'text', 'text' => 'Sunt cele mai utile din toată algebra booleană, pentru că apar direct în cod: îți spun cum se neagă o condiție compusă.'],

                    ['type' => 'formula', 'latex' => '\overline{A \cdot B} = \overline{A} + \overline{B}, \qquad \overline{A + B} = \overline{A} \cdot \overline{B}', 'caption' => 'Negația schimbă ȘI în SAU și invers, și se distribuie pe fiecare termen.'],

                    ['type' => 'text', 'text' => 'În practică: negația lui „x > 0 **și** x < 10" nu e „x ≤ 0 **și** x ≥ 10" — asta e imposibil. Este „x ≤ 0 **sau** x ≥ 10". Uitarea schimbării operatorului e o greșeală frecventă și în cod, nu doar pe hârtie.'],

                    ['type' => 'heading', 'text' => 'Legi pentru simplificare'],

                    [
                        'type' => 'table',
                        'caption' => 'Absorbția e cea care scurtează cel mai mult expresiile la examen.',
                        'headers' => ['Lege', 'Formă'],
                        'rows' => [
                            ['Idempotență', '$A \cdot A = A$; $A + A = A$'],
                            ['Element neutru', '$A \cdot 1 = A$; $A + 0 = A$'],
                            ['Element absorbant', '$A \cdot 0 = 0$; $A + 1 = 1$'],
                            ['Complement', '$A \cdot \overline{A} = 0$; $A + \overline{A} = 1$'],
                            ['Absorbție', '$A + A \cdot B = A$'],
                            ['Distributivitate', '$A \cdot (B + C) = A \cdot B + A \cdot C$'],
                        ],
                    ],

                    [
                        'type' => 'example',
                        'title' => 'Simplificare pas cu pas',
                        'problem' => 'Simplifică $A \cdot B + A \cdot \overline{B}$.',
                        'steps' => [
                            ['text' => 'Dau factor comun pe $A$.', 'latex' => 'A \cdot (B + \overline{B})'],
                            ['text' => 'Aplic legea complementului: o variabilă sau negația ei acoperă tot.', 'latex' => 'B + \overline{B} = 1'],
                            ['text' => 'Rămâne $A \cdot 1$, adică $A$.', 'latex' => 'A'],
                        ],
                        'result' => 'Expresia inițială depindea aparent de $B$, dar rezultatul nu depinde de el deloc.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => '„+" nu e adunare',
                        'text' => 'În algebra booleană $1 + 1 = 1$, nu 2. Simbolurile sunt împrumutate din aritmetică, dar operațiile sunt logice. Dacă la un exercițiu îți iese o valoare diferită de 0 sau 1, ai amestecat cele două sisteme.',
                    ],
                ],
            ],

            // ==================================================================
            'circuite-logice' => [
                'title' => 'Circuite logice',
                'summary' => 'Porțile logice ca hardware al operațiilor booleene, cum se combină și cum se trece de la circuit la expresie și înapoi.',
                'objectives' => [
                    'Să asociezi fiecare poartă cu operația ei logică',
                    'Să scrii expresia corespunzătoare unui circuit dat',
                    'Să construiești tabelul de adevăr al unui circuit',
                    'Să recunoști sumatorul elementar',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Porțile sunt algebra booleană în hardware'],

                    ['type' => 'text', 'text' => 'O poartă logică e un circuit electronic care primește unul sau mai multe semnale de intrare (0 sau 1, adică tensiune joasă sau înaltă) și produce o ieșire după o regulă fixă. Fiecare operație booleană are poarta ei.'],

                    [
                        'type' => 'table',
                        'caption' => 'Porțile NAND și NOR sunt „universale": din oricare dintre ele se poate construi orice circuit.',
                        'headers' => ['Poartă', 'Operație', 'Ieșirea e 1 când...'],
                        'rows' => [
                            ['AND', '$A \cdot B$', 'ambele intrări sunt 1'],
                            ['OR', '$A + B$', 'cel puțin o intrare e 1'],
                            ['NOT', '$\overline{A}$', 'intrarea e 0'],
                            ['NAND', '$\overline{A \cdot B}$', 'NU ambele sunt 1'],
                            ['NOR', '$\overline{A + B}$', 'ambele sunt 0'],
                            ['XOR', '$A \oplus B$', 'intrările sunt DIFERITE'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'De la circuit la expresie'],

                    ['type' => 'text', 'text' => 'Un circuit se citește **de la intrări spre ieșire**. Fiecare poartă adaugă o operație în jurul a ce s-a calculat până acolo. Dacă ieșirea unei porți AND intră într-o poartă OR împreună cu $C$, expresia devine $A \cdot B + C$.'],

                    [
                        'type' => 'example',
                        'title' => 'Citirea unui circuit',
                        'problem' => 'Intrările $A$ și $B$ intră într-o poartă AND. Ieșirea ei, împreună cu $C$ negat, intră într-o poartă OR.',
                        'steps' => [
                            ['text' => 'Prima poartă produce $A \\cdot B$.'],
                            ['text' => 'Poarta NOT pe $C$ produce $\\overline{C}$.'],
                            ['text' => 'Poarta OR combină cele două rezultate.', 'latex' => 'A \cdot B + \overline{C}'],
                        ],
                        'result' => 'Expresia are 3 variabile, deci tabelul ei de adevăr are $2^3 = 8$ linii.',
                    ],

                    ['type' => 'heading', 'text' => 'XOR și sumatorul elementar'],

                    ['type' => 'text', 'text' => 'Poarta XOR (SAU exclusiv) dă 1 exact când intrările diferă. E poarta care face adunarea binară posibilă.'],

                    [
                        'type' => 'table',
                        'caption' => 'Sumatorul elementar (half adder): adună doi biți și produce suma plus transportul.',
                        'headers' => ['A', 'B', 'Suma (A ⊕ B)', 'Transport (A · B)'],
                        'rows' => [
                            ['0', '0', '0', '0'],
                            ['0', '1', '1', '0'],
                            ['1', '0', '1', '0'],
                            ['1', '1', '0', '1'],
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Ultima linie explică totul: $1 + 1 = 10_2$, adică suma e 0 și se transportă 1 la poziția următoare. Exact cum se adună pe hârtie, doar că în baza 2.'],

                    [
                        'type' => 'warning',
                        'title' => 'XOR nu e același lucru cu OR',
                        'text' => 'La ambele intrări egale cu 1, OR dă 1, iar XOR dă 0. E singura linie în care diferă, dar e linia care contează. Verifică întotdeauna acest caz când construiești un tabel de adevăr.',
                    ],
                ],
            ],

            // ==================================================================
            'cantitatea-informatie' => [
                'title' => 'Cantitatea de informație',
                'summary' => 'Ce măsoară un bit, cum se calculează informația unui mesaj și de ce unitățile se confundă atât de des.',
                'objectives' => [
                    'Să explici bitul ca alegere între două posibilități egale',
                    'Să calculezi cantitatea de informație pentru evenimente echiprobabile',
                    'Să convertești corect între biți, octeți și multiplii lor',
                    'Să afli câți biți sunt necesari pentru a codifica n valori',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Bitul: răspunsul la o întrebare cu două variante'],

                    ['type' => 'text', 'text' => 'Un **bit** e cantitatea de informație obținută când afli rezultatul unei alegeri între două posibilități la fel de probabile. Aruncarea unei monede aduce exact un bit: erau două variante, acum știi care.'],

                    ['type' => 'text', 'text' => 'Dacă posibilitățile sunt mai multe, informația crește logaritmic, nu liniar. Formula pentru $m$ evenimente echiprobabile:'],

                    ['type' => 'formula', 'latex' => 'I = \log_2 m \quad \text{(biți)}', 'caption' => 'Pentru un mesaj de $n$ simboluri dintr-un alfabet de $m$ simboluri: $I = n \cdot \log_2 m$.'],

                    [
                        'type' => 'table',
                        'caption' => 'Dublarea numărului de variante adaugă exact un bit.',
                        'headers' => ['Număr de variante', 'Informație'],
                        'rows' => [
                            ['2', '1 bit'],
                            ['4', '2 biți'],
                            ['8', '3 biți'],
                            ['16', '4 biți'],
                            ['256', '8 biți (1 octet)'],
                        ],
                    ],

                    [
                        'type' => 'example',
                        'title' => 'Câți biți pentru un mesaj',
                        'problem' => 'Un mesaj are 100 de caractere dintr-un alfabet de 32 de simboluri. Câți biți sunt necesari?',
                        'steps' => [
                            ['text' => 'Aflu informația pe simbol: $\\log_2 32 = 5$ biți, pentru că $2^5 = 32$.'],
                            ['text' => 'Înmulțesc cu numărul de simboluri.', 'latex' => 'I = 100 \cdot 5 = 500 \text{ biți}'],
                            ['text' => 'Dacă se cere în octeți, împart la 8.', 'latex' => '500 : 8 = 62{,}5 \text{ octeți}'],
                        ],
                        'result' => '500 de biți. În practică se rotunjește în sus la 63 de octeți — nu se poate aloca o fracțiune de octet.',
                    ],

                    ['type' => 'heading', 'text' => 'Problema inversă: câți biți îmi trebuie'],

                    ['type' => 'text', 'text' => 'Dacă ai $n$ valori de codificat, numărul de biți necesari e cel mai mic $k$ pentru care $2^k \ge n$. Pentru 1000 de valori: $2^9 = 512$ e prea puțin, $2^{10} = 1024$ ajunge, deci 10 biți.'],

                    ['type' => 'heading', 'text' => 'Unitățile'],

                    [
                        'type' => 'table',
                        'caption' => 'Atenție la notație: „b" mic înseamnă bit, „B" mare înseamnă octet (byte).',
                        'headers' => ['Unitate', 'Echivalent'],
                        'rows' => [
                            ['1 octet (byte)', '8 biți'],
                            ['1 KiB', '1024 octeți'],
                            ['1 MiB', '1024 KiB'],
                            ['1 GiB', '1024 MiB'],
                        ],
                    ],

                    [
                        'type' => 'warning',
                        'title' => '1024, nu 1000',
                        'text' => 'În informatică multiplii cresc din 1024 în 1024, pentru că $1024 = 2^{10}$. Producătorii de discuri folosesc însă 1000 — de aceea un disc „de 500 GB" apare în sistem ca aproximativ 465 GiB. La examen se folosește 1024.',
                    ],
                ],
            ],

            // ==================================================================
            'codificare' => [
                'title' => 'Codificarea și decodificarea mesajelor',
                'summary' => 'Cum devin caracterele numere, diferența dintre ASCII și Unicode și de ce un cod trebuie să fie decodificabil unic.',
                'objectives' => [
                    'Să explici rolul unui tabel de coduri precum ASCII',
                    'Să calculezi poziția unei litere folosind aritmetica pe caractere',
                    'Să distingi codurile de lungime fixă de cele variabile',
                    'Să verifici dacă un cod se poate decodifica fără ambiguitate',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Calculatorul nu cunoaște litere'],

                    ['type' => 'text', 'text' => 'În memorie totul e o succesiune de biți. O literă devine text doar pentru că există o **convenție** care spune ce număr reprezintă ce caracter. Tabelul ASCII e una dintre primele asemenea convenții.'],

                    [
                        'type' => 'table',
                        'caption' => 'Codurile sunt consecutive în interiorul fiecărei grupe — de aici vin toate trucurile de calcul.',
                        'headers' => ['Caracter', 'Cod ASCII'],
                        'rows' => [
                            ['\'0\'', '48'],
                            ['\'9\'', '57'],
                            ['\'A\'', '65'],
                            ['\'Z\'', '90'],
                            ['\'a\'', '97'],
                            ['\'z\'', '122'],
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Consecutivitatea permite calcule directe: poziția unei litere mari în alfabet e `c - \'A\' + 1`, iar diferența constantă de 32 între litera mică și cea mare permite conversia între ele.'],

                    [
                        'type' => 'code',
                        'caption' => 'Transformarea unei litere mari în literă mică, folosind doar aritmetica pe coduri.',
                        'variants' => [
                            'cpp' => "char c = 'B';\nchar mic = c + 32;      // 'b'\nint pozitie = c - 'A' + 1;  // 2\ncout << mic << ' ' << pozitie;",
                            'pascal' => "c := 'B';\nmic := chr(ord(c) + 32);       { 'b' }\npozitie := ord(c) - ord('A') + 1; { 2 }\nwrite(mic, ' ', pozitie);",
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'De la ASCII la Unicode'],

                    ['type' => 'text', 'text' => 'ASCII folosește 7 biți, deci acoperă 128 de caractere — suficient pentru engleză, insuficient pentru orice altceva. **Unicode** atribuie un cod unic fiecărui caracter din toate scrierile lumii, inclusiv diacriticelor românești și emoji-urilor.'],

                    ['type' => 'text', 'text' => '**UTF-8** e modul cel mai răspândit de a stoca Unicode: folosește 1 octet pentru caracterele ASCII și 2–4 octeți pentru restul. De aceea un text cu diacritice ocupă mai mulți octeți decât are caractere.'],

                    ['type' => 'heading', 'text' => 'Lungime fixă sau variabilă'],

                    ['type' => 'text', 'text' => 'Un cod de **lungime fixă** alocă același număr de biți fiecărui simbol — simplu de decodificat, dar risipitor. Un cod de **lungime variabilă** dă coduri scurte simbolurilor frecvente, exact ideea din spatele compresiei.'],

                    [
                        'type' => 'warning',
                        'title' => 'Condiția de prefix',
                        'text' => 'Un cod de lungime variabilă e decodificabil unic doar dacă niciun cod nu e prefixul altuia. Cu A = 0, B = 01, secvența „001" se poate citi în două feluri — codul e ambiguu și inutilizabil. Cu A = 0, B = 10, C = 11 nu există ambiguitate.',
                    ],

                    [
                        'type' => 'example',
                        'title' => 'Decodificare cu cod-prefix',
                        'problem' => 'Se dau A = 0, B = 10, C = 110, D = 111. Decodifică secvența 010110111.',
                        'steps' => [
                            ['text' => 'Citesc de la stânga. Primul bit e 0 — corespunde lui A, niciun alt cod nu începe cu 0.'],
                            ['text' => 'Urmează 10, adică B. Apoi 110, adică C.'],
                            ['text' => 'Rămâne 111, adică D.'],
                        ],
                        'result' => 'ABCD. Decodificarea e unică tocmai pentru că niciun cod nu e prefixul altuia.',
                    ],
                ],
            ],

            // ==================================================================
            'modelare-erori' => [
                'title' => 'Modelare matematică și erori de calcul',
                'summary' => 'De unde vin erorile într-un calcul numeric, cum se măsoară și de ce eroarea relativă spune mai mult decât cea absolută.',
                'objectives' => [
                    'Să descrii etapele construirii unui model matematic',
                    'Să calculezi eroarea absolută și pe cea relativă',
                    'Să identifici sursa unei erori: model, date, metodă sau reprezentare',
                    'Să eviți scăderea a două valori apropiate',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Ce înseamnă „model matematic"'],

                    ['type' => 'text', 'text' => 'Un model e o descriere simplificată a realității, scrisă în limbaj matematic, ca să poată fi calculată. Simplificarea e deliberată: un model care ar include totul ar fi la fel de greu de înțeles ca realitatea însăși.'],

                    [
                        'type' => 'table',
                        'caption' => 'Fiecare etapă poate introduce erori proprii.',
                        'headers' => ['Etapă', 'Ce se face'],
                        'rows' => [
                            ['Formularea', 'Se stabilesc mărimile, ipotezele și relațiile dintre ele'],
                            ['Alegerea metodei', 'Se decide cum se rezolvă: exact sau aproximativ'],
                            ['Implementarea', 'Se scrie programul'],
                            ['Validarea', 'Se compară rezultatele cu date cunoscute'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Cele patru surse de eroare'],

                    [
                        'type' => 'table',
                        'caption' => 'Doar ultimele două țin de calculator; primele două există și pe hârtie.',
                        'headers' => ['Sursă', 'De unde vine'],
                        'rows' => [
                            ['Eroare de model', 'Simplificările asumate (se neglijează frecarea etc.)'],
                            ['Eroare a datelor', 'Măsurătorile de intrare sunt imprecise'],
                            ['Eroare de metodă', 'Metoda e aproximativă prin construcție (trunchiere)'],
                            ['Eroare de reprezentare', 'Numerele reale se stochează cu precizie finită'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Cum se măsoară'],

                    ['type' => 'formula', 'latex' => '\Delta = |x_{\text{exact}} - x_{\text{aprox}}|, \qquad \delta = \frac{\Delta}{|x_{\text{exact}}|}', 'caption' => 'Eroarea absolută $\Delta$ are unitatea mărimii; eroarea relativă $\delta$ e adimensională și se exprimă adesea în procente.'],

                    [
                        'type' => 'example',
                        'title' => 'De ce contează eroarea relativă',
                        'problem' => 'Două măsurători au aceeași eroare absolută de 1 cm.',
                        'steps' => [
                            ['text' => 'Lungimea unui creion, 20 cm: eroarea relativă e $\\frac{1}{20} = 5\\%$. Enormă.'],
                            ['text' => 'Lungimea unui teren, 10 000 cm: eroarea relativă e $\\frac{1}{10000} = 0{,}01\\%$. Neglijabilă.'],
                        ],
                        'result' => 'Aceeași eroare absolută înseamnă lucruri complet diferite. De aceea precizia se raportează aproape întotdeauna relativ.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Scăderea valorilor apropiate distruge precizia',
                        'text' => 'Dacă scazi două numere aproape egale, cifrele semnificative se anulează între ele și rămâne mai ales eroarea. E fenomenul numit *anulare catastrofală*. Când poți, rescrie formula ca să eviți scăderea — de exemplu prin amplificare cu conjugata.',
                    ],
                ],
            ],

            // ==================================================================
            'metode-iterative' => [
                'title' => 'Metoda bisecției și a coardelor',
                'summary' => 'Cum se găsește numeric o soluție a ecuației $f(x) = 0$ atunci când formula exactă nu există sau e prea complicată.',
                'objectives' => [
                    'Să verifici condiția de existență a soluției pe un interval',
                    'Să aplici metoda bisecției pas cu pas',
                    'Să compari bisecția cu metoda coardelor',
                    'Să alegi un criteriu de oprire corect',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Condiția de pornire'],

                    ['type' => 'text', 'text' => 'Ambele metode pornesc de la aceeași observație: dacă o funcție continuă are **semne diferite** la capetele unui interval, atunci undeva înăuntru trece prin zero.'],

                    ['type' => 'formula', 'latex' => 'f(a) \cdot f(b) < 0 \Rightarrow \exists\, c \in (a,b) \text{ cu } f(c) = 0', 'caption' => 'Produsul negativ e modul concis de a spune „semne opuse".'],

                    ['type' => 'text', 'text' => 'Dacă produsul e pozitiv, metoda nu se poate aplica pe acel interval — nu înseamnă că nu există soluție, ci că trebuie căutat alt interval.'],

                    ['type' => 'heading', 'text' => 'Bisecția: înjumătățire repetată'],

                    ['type' => 'text', 'text' => 'Se ia mijlocul intervalului și se verifică în ce jumătate se păstrează schimbarea de semn. Acea jumătate devine noul interval. La fiecare pas, incertitudinea se reduce exact la jumătate.'],

                    [
                        'type' => 'code',
                        'caption' => 'Structura metodei. Condiția de oprire folosește o toleranță, nu egalitatea cu zero.',
                        'variants' => [
                            'cpp' => "while (b - a > eps) {\n    double m = (a + b) / 2;\n    if (f(a) * f(m) < 0)\n        b = m;\n    else\n        a = m;\n}\ncout << (a + b) / 2;",
                            'pascal' => "while b - a > eps do\nbegin\n  m := (a + b) / 2;\n  if f(a) * f(m) < 0 then\n    b := m\n  else\n    a := m;\nend;\nwrite((a + b) / 2);",
                        ],
                    ],

                    [
                        'type' => 'table',
                        'caption' => 'Bisecția pentru $f(x) = x^2 - 2$ pe intervalul $[1, 2]$; soluția e $\sqrt{2} \approx 1{,}414$.',
                        'headers' => ['Pas', 'a', 'b', 'Mijloc', 'Semnul lui f(m)'],
                        'rows' => [
                            ['1', '1', '2', '1,5', '+'],
                            ['2', '1', '1,5', '1,25', '−'],
                            ['3', '1,25', '1,5', '1,375', '−'],
                            ['4', '1,375', '1,5', '1,4375', '+'],
                        ],
                    ],

                    ['type' => 'text', 'text' => 'După $n$ pași, lungimea intervalului e $\frac{b-a}{2^n}$. Pentru a ajunge de la un interval de lungime 1 la o precizie de $10^{-3}$ sunt necesari 10 pași, pentru că $2^{10} = 1024 > 1000$.'],

                    ['type' => 'heading', 'text' => 'Metoda coardelor'],

                    ['type' => 'text', 'text' => 'În loc de mijlocul geometric, se ia punctul în care **coarda** dintre $(a, f(a))$ și $(b, f(b))$ taie axa $Ox$. Punctul ține cont de valorile funcției, nu doar de poziții.'],

                    ['type' => 'formula', 'latex' => 'x = a - f(a) \cdot \frac{b - a}{f(b) - f(a)}', 'caption' => 'Restul algoritmului e identic cu bisecția: se păstrează jumătatea cu schimbare de semn.'],

                    [
                        'type' => 'table',
                        'caption' => 'Alegerea depinde de ce contează mai mult: siguranța sau viteza.',
                        'headers' => ['Criteriu', 'Bisecție', 'Coarde'],
                        'rows' => [
                            ['Convergență garantată', 'Da, întotdeauna', 'Da, dar poate fi lentă'],
                            ['Viteză tipică', 'Constantă, previzibilă', 'De obicei mai mare'],
                            ['Simplitate', 'Foarte simplă', 'O formulă în plus'],
                        ],
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Nu opri bucla cu „f(x) = 0"',
                        'text' => 'Din cauza reprezentării aproximative, `f(x)` nu va fi niciodată exact zero. Oprirea se face pe lungimea intervalului (`b - a < eps`) sau pe valoarea funcției (`|f(m)| < eps`). O condiție de egalitate strictă produce buclă infinită.',
                    ],
                ],
            ],

            // ==================================================================
            'integrare-numerica' => [
                'title' => 'Calculul numeric al integralelor',
                'summary' => 'Aproximarea ariei de sub grafic prin dreptunghiuri, trapeze și parabole — și cât de precisă e fiecare metodă.',
                'objectives' => [
                    'Să explici de ce integrala se poate aproxima prin arii simple',
                    'Să aplici formula trapezelor',
                    'Să compari precizia celor trei metode',
                    'Să alegi numărul de subintervale în cunoștință de cauză',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Ideea: tai aria în felii'],

                    ['type' => 'text', 'text' => 'Integrala definită e aria de sub grafic. Când primitiva nu se poate calcula — și există multe funcții pentru care chiar nu se poate — aria se aproximează tăind intervalul în $n$ felii înguste și însumând arii simple.'],

                    ['type' => 'text', 'text' => 'Fiecare felie are lățimea $h = \frac{b-a}{n}$. Metodele diferă doar prin forma cu care aproximează bucata de grafic de deasupra feliei.'],

                    [
                        'type' => 'table',
                        'caption' => 'Cu cât forma se potrivește mai bine graficului, cu atât eroarea scade mai repede.',
                        'headers' => ['Metodă', 'Ce aproximează felia', 'Eroarea scade ca'],
                        'rows' => [
                            ['Dreptunghiuri', 'Un dreptunghi (funcția e constantă)', '$h$'],
                            ['Trapeze', 'Un trapez (funcția e liniară)', '$h^2$'],
                            ['Simpson', 'O parabolă', '$h^4$'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Formula trapezelor'],

                    ['type' => 'formula', 'latex' => 'I \approx h \left( \frac{f(a) + f(b)}{2} + \sum_{i=1}^{n-1} f(x_i) \right)', 'caption' => 'Capetele se iau pe jumătate, pentru că aparțin unui singur trapez; punctele interioare sunt comune la două trapeze.'],

                    [
                        'type' => 'code',
                        'caption' => 'Implementarea urmează formula literă cu literă.',
                        'variants' => [
                            'cpp' => "double h = (b - a) / n;\ndouble s = (f(a) + f(b)) / 2;\nfor (int i = 1; i < n; i++)\n    s = s + f(a + i * h);\ncout << s * h;",
                            'pascal' => "h := (b - a) / n;\ns := (f(a) + f(b)) / 2;\nfor i := 1 to n - 1 do\n  s := s + f(a + i * h);\nwrite(s * h);",
                        ],
                    ],

                    [
                        'type' => 'example',
                        'title' => 'Verificare pe o integrală cunoscută',
                        'problem' => 'Aproximează $\int_0^2 x^2\,dx$ cu metoda trapezelor, pentru $n = 2$.',
                        'steps' => [
                            ['text' => 'Lățimea feliei: $h = \\frac{2-0}{2} = 1$. Punctele sunt 0, 1, 2.'],
                            ['text' => 'Capetele, pe jumătate: $\\frac{f(0) + f(2)}{2} = \\frac{0 + 4}{2} = 2$.'],
                            ['text' => 'Punctul interior: $f(1) = 1$.'],
                            ['text' => 'Rezultatul: $1 \\cdot (2 + 1) = 3$.'],
                        ],
                        'result' => 'Valoarea exactă e $\frac{8}{3} \approx 2{,}67$. Cu doar două trapeze eroarea e de 12%; la $n = 10$ scade sub 0,5%.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Mai multe felii nu înseamnă mereu mai bine',
                        'text' => 'Mărind $n$ scade eroarea de metodă, dar crește numărul de operații — deci și eroarea de rotunjire acumulată. Există un $n$ dincolo de care rezultatul începe să se înrăutățească. La examen se lucrează cu valori mici, unde fenomenul nu apare, dar merită știut de ce.',
                    ],
                ],
            ],

            // ==================================================================
            'tabele' => [
                'title' => 'Tabele, chei și corelații',
                'summary' => 'Cum se organizează datele într-o bază relațională: înregistrări, câmpuri, cheia primară și legăturile dintre tabele.',
                'objectives' => [
                    'Să descrii structura unui tabel: câmpuri, înregistrări, tipuri',
                    'Să alegi o cheie primară potrivită',
                    'Să legi două tabele printr-o cheie externă',
                    'Să recunoști tipurile de corelație și să tratezi relația mulți-la-mulți',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Tabelul: linii și coloane, cu reguli'],

                    ['type' => 'text', 'text' => 'Într-o bază de date relațională, informația stă în tabele. **Coloanele** (câmpurile) definesc ce se reține și de ce tip; **liniile** (înregistrările) sunt datele propriu-zise. Toate valorile de pe o coloană au același tip — asta deosebește un tabel de o foaie de calcul.'],

                    [
                        'type' => 'table',
                        'caption' => 'Tabelul ELEVI. Câmpul „id" identifică unic fiecare înregistrare.',
                        'headers' => ['id', 'nume', 'clasa', 'media'],
                        'rows' => [
                            ['1', 'Ana Munteanu', '12A', '9.20'],
                            ['2', 'Ion Rusu', '12A', '8.45'],
                            ['3', 'Maria Cebotari', '12B', '9.80'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Cheia primară'],

                    ['type' => 'text', 'text' => '**Cheia primară** e câmpul (sau grupul de câmpuri) care identifică unic o înregistrare. Ea nu poate fi nulă și nu se poate repeta. Fără ea, două înregistrări identice ar fi imposibil de deosebit.'],

                    [
                        'type' => 'warning',
                        'title' => 'Numele nu e cheie primară',
                        'text' => 'Pare tentant să folosești numele elevului, dar două persoane pot avea același nume, iar un nume se poate schimba. O cheie primară bună e **stabilă** și **garantat unică** — de obicei un număr generat automat.',
                    ],

                    ['type' => 'heading', 'text' => 'Cheia externă leagă tabelele'],

                    ['type' => 'text', 'text' => 'O **cheie externă** e un câmp care conține valoarea cheii primare dintr-alt tabel. Ea materializează legătura: în tabelul NOTE, câmpul `elev_id` arată cărui elev îi aparține nota.'],

                    [
                        'type' => 'table',
                        'caption' => 'Tabelul NOTE. Coloana „elev_id" trimite la „id" din tabelul ELEVI.',
                        'headers' => ['id', 'elev_id', 'disciplina', 'nota'],
                        'rows' => [
                            ['1', '1', 'Matematică', '9'],
                            ['2', '1', 'Informatică', '10'],
                            ['3', '2', 'Matematică', '8'],
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Avantajul e că datele elevului se scriu **o singură dată**. Dacă s-ar repeta numele în fiecare rând din NOTE, o corectare ar trebui făcută în zeci de locuri, iar o omisiune ar lăsa baza inconsistentă.'],

                    ['type' => 'heading', 'text' => 'Tipurile de corelație'],

                    [
                        'type' => 'table',
                        'caption' => 'Relația mulți-la-mulți nu se poate reprezenta direct — are nevoie de un tabel intermediar.',
                        'headers' => ['Tip', 'Exemplu', 'Cum se implementează'],
                        'rows' => [
                            ['Unu la unu (1:1)', 'Elev — act de identitate', 'Cheie externă în oricare tabel'],
                            ['Unu la mulți (1:n)', 'Elev — note', 'Cheie externă în tabelul „mulți"'],
                            ['Mulți la mulți (n:m)', 'Elevi — discipline opționale', 'Un al treilea tabel, de legătură'],
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Tabelul de legătură pentru o relație n:m conține doar cele două chei externe: `elev_id` și `disciplina_id`. O linie în el înseamnă „acest elev studiază această disciplină".'],

                    [
                        'type' => 'warning',
                        'title' => 'Redundanța e sursa inconsistenței',
                        'text' => 'Dacă aceeași informație apare în două locuri, mai devreme sau mai târziu cele două vor diferi. Regula practică: fiecare fapt se stochează într-un singur loc, iar restul tabelelor trimit la el prin cheie.',
                    ],
                ],
            ],

            // ==================================================================
            'interogari' => [
                'title' => 'Interogări',
                'summary' => 'Cum se extrag datele dintr-o bază: selecție, filtrare, sortare, funcții de agregare și legarea tabelelor.',
                'objectives' => [
                    'Să scrii o interogare cu selecție și filtrare',
                    'Să sortezi și să limitezi rezultatele',
                    'Să folosești funcțiile de agregare și gruparea',
                    'Să combini două tabele printr-o operație de legare',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Structura unei interogări'],

                    ['type' => 'text', 'text' => 'O interogare răspunde la întrebarea „ce date vreau, din ce tabel și în ce condiții". Cele trei părți se scriu întotdeauna în aceeași ordine.'],

                    [
                        'type' => 'code',
                        'caption' => 'SELECT alege coloanele, FROM alege tabelul, WHERE filtrează liniile.',
                        'code' => "SELECT nume, media\nFROM elevi\nWHERE clasa = '12A';",
                    ],

                    ['type' => 'text', 'text' => 'Steluța `*` selectează toate coloanele. E comodă când explorezi, dar în programele reale se scriu coloanele dorite: aduci mai puține date și nu depinzi de ordinea lor.'],

                    ['type' => 'heading', 'text' => 'Filtrare, sortare, limitare'],

                    [
                        'type' => 'table',
                        'caption' => 'Operatorii din WHERE se pot combina cu AND, OR și NOT.',
                        'headers' => ['Operator', 'Ce face', 'Exemplu'],
                        'rows' => [
                            ['=, <, >, <=, >=', 'Comparație', 'media >= 9'],
                            ['<>', 'Diferit de', 'clasa <> \'12A\''],
                            ['BETWEEN', 'În interval, capete incluse', 'media BETWEEN 8 AND 9'],
                            ['IN', 'Într-o listă de valori', 'clasa IN (\'12A\', \'12B\')'],
                            ['LIKE', 'Potrivire de șablon', 'nume LIKE \'A%\''],
                            ['IS NULL', 'Valoare lipsă', 'media IS NULL'],
                        ],
                    ],

                    [
                        'type' => 'code',
                        'caption' => 'Sortare descrescătoare după medie, cu primele trei rezultate.',
                        'code' => "SELECT nume, media\nFROM elevi\nORDER BY media DESC\nLIMIT 3;",
                    ],

                    ['type' => 'heading', 'text' => 'Funcții de agregare'],

                    ['type' => 'text', 'text' => 'Ele condensează mai multe linii într-o singură valoare: `COUNT` numără, `SUM` adună, `AVG` face media, `MIN` și `MAX` găsesc extremele.'],

                    [
                        'type' => 'code',
                        'caption' => 'GROUP BY aplică agregarea separat pe fiecare grup, nu pe tot tabelul.',
                        'code' => "SELECT clasa, COUNT(*) AS numar, AVG(media) AS media_clasei\nFROM elevi\nGROUP BY clasa;",
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'WHERE filtrează linii, HAVING filtrează grupuri',
                        'text' => 'O condiție pe rezultatul unei agregări nu poate sta în WHERE, pentru că WHERE se aplică înainte de grupare. „Clasele cu media peste 9" se scrie cu `GROUP BY clasa HAVING AVG(media) > 9`.',
                    ],

                    ['type' => 'heading', 'text' => 'Legarea tabelelor'],

                    ['type' => 'text', 'text' => 'Când informația e împărțită între tabele, se folosește `JOIN` pentru a le combina pe baza cheii. Condiția de legare spune ce câmp din primul tabel corespunde cărui câmp din al doilea.'],

                    [
                        'type' => 'code',
                        'caption' => 'Numele elevului vine din ELEVI, nota din NOTE, iar legătura se face pe id.',
                        'code' => "SELECT e.nume, n.disciplina, n.nota\nFROM elevi e\nJOIN note n ON n.elev_id = e.id\nWHERE n.nota >= 9\nORDER BY e.nume;",
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'JOIN fără condiție produce produsul cartezian',
                        'text' => 'Dacă uiți clauza `ON`, fiecare linie din primul tabel se combină cu fiecare linie din al doilea. La 100 și 1000 de înregistrări obții 100 000 de rezultate, toate fără sens. E o greșeală care se observă imediat după numărul absurd de linii returnate.',
                    ],
                ],
            ],
        ];
    }
}
