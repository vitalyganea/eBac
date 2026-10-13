<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Skill;
use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Teoria pentru istorie.
 *
 * Am ales competențele de LUCRU CU SURSA, nu epocile, dintr-un motiv care se
 * vede în programa ANCE: examenul nu cere memorarea datelor, ci „identifică în
 * sursă", „compară opiniile autorilor", „distinge faptul de opinie". Subiectul
 * al IV-lea, care valorează 35 de puncte, e răspuns extins argumentat pe 2–3
 * surse.
 *
 * Un elev care știe toate datele dar nu știe să citească o sursă ia notă mică.
 * Invers, nu.
 */
class IstorieLessonSeeder extends Seeder
{
    public function run(): void
    {
        $subject = Subject::where('slug', 'istoria')->firstOrFail();

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
            'analiza-surselor' => [
                'title' => 'Analiza surselor: fapt și opinie',
                'summary' => 'Competența pe care se sprijină aproape tot examenul, inclusiv Subiectul al IV-lea. Cum citești o sursă și cum deosebești ce s-a întâmplat de ce crede autorul.',
                'objectives' => [
                    'Să distingi faptul de opinie după semnalele din text',
                    'Să răspunzi la itemii „identifică în sursă" fără să adaugi din memorie',
                    'Să compari două surse pe același subiect',
                    'Să folosești sursele ca argumente, nu ca citate decorative',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'De ce examenul e construit pe surse'],

                    ['type' => 'text', 'text' => 'Programa cere explicit „să distingă faptul de opinie din informația oferită de surse" și „să evalueze diferite abordări, puncte de vedere, interpretări". Nu e o formulare decorativă: aproape fiecare item pornește de la un text, o hartă sau un tabel.'],

                    ['type' => 'text', 'text' => 'Consecința practică e că **memorarea datelor nu ajunge**. Un elev care știe pe de rost anii, dar citește sursa superficial, pierde puncte la fiecare subiect. Invers, cine știe să citească o sursă poate răspunde corect chiar și la un eveniment pe care îl stăpânește mai puțin.'],

                    ['type' => 'heading', 'text' => 'Faptul și opinia'],

                    ['type' => 'text', 'text' => 'Un **fapt** se poate verifica într-un document: o dată, un tratat, o instituție, o cifră. O **opinie** exprimă evaluarea autorului și poate fi contestată de un alt istoric, fără ca vreunul să mintă.'],

                    [
                        'type' => 'table',
                        'caption' => 'Semnalele de opinie sunt lingvistice și se învață. Odată văzute, nu se mai ratează.',
                        'headers' => ['Semnal', 'Exemple', 'Ce indică'],
                        'rows' => [
                            ['Verbe de atitudine', 'considerăm, apreciem, credem, socotim', 'Autorul își declară poziția'],
                            ['Adjective evaluative', 'formală, insuficientă, greșită, remarcabilă', 'Judecată, nu descriere'],
                            ['Afirmații despre intenții', '„fără intenția reală de a…", „a urmărit să…"', 'Intențiile nu se verifică direct în documente'],
                            ['Adverbe de comentariu', 'din păcate, în mod evident, desigur', 'Comentariu al autorului asupra faptului'],
                            ['Comparative fără măsură', '„cel mai important", „mai puțin reușit"', 'Ierarhizare subiectivă'],
                        ],
                    ],

                    [
                        'type' => 'example',
                        'title' => 'Aplicat pe o frază',
                        'problem' => '„Prin Tratatul de la București, semnat în 1812, teritoriul dintre Prut și Nistru a intrat în componența Imperiului Rus. Considerăm că autonomia acordată ulterior a fost de la început una formală, fără intenția reală de a fi păstrată."',
                        'steps' => [
                            ['text' => 'Prima frază: „Tratatul de la București", „1812", „Imperiul Rus" — nume, dată, instituție. Toate verificabile într-un document. **Fapt**.'],
                            ['text' => 'A doua frază începe cu „Considerăm că" — verb de atitudine. Autorul semnalează singur că urmează poziția lui.'],
                            ['text' => 'Conține și „formală" (adjectiv evaluativ) și „fără intenția reală" (afirmație despre intenții).'],
                            ['text' => 'Deci a doua frază e **opinie** — un alt istoric poate susține contrariul fără să contrazică niciun document.'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Itemii „identifică în sursă"'],

                    [
                        'type' => 'warning',
                        'title' => 'Răspunzi din SURSĂ, nu din memorie',
                        'text' => 'E greșeala care costă cel mai mult. Dacă întrebarea spune „conform sursei", răspunsul trebuie să existe în text — chiar dacă tu știi mai multe despre subiect. Un răspuns corect istoric, dar absent din sursă, se punctează cu zero. Sarcina evaluată e citirea, nu cunoștințele generale.',
                    ],

                    ['type' => 'text', 'text' => 'Tehnica: subliniază în enunț **ce anume** se cere (o cauză? o consecință? două măsuri?), apoi caută în sursă exact acel număr de elemente. Dacă se cer două, dă exact două — nu una, nu patru.'],

                    ['type' => 'heading', 'text' => 'Compararea a două surse'],

                    ['type' => 'text', 'text' => 'La Subiectul al III-lea și al IV-lea apar frecvent două surse pe același eveniment. Întrebarea nu e „care are dreptate", ci ce anume le deosebește și de ce.'],

                    [
                        'type' => 'table',
                        'headers' => ['Întreabă-te', 'De ce contează'],
                        'rows' => [
                            ['Cine scrie și când?', 'Un contemporan al evenimentului și un istoric de azi au acces la informații diferite'],
                            ['Ce tip de sursă e?', 'O scrisoare diplomatică urmărește un scop; o lucrare de istorie, altul'],
                            ['Ce afirmă amândouă la fel?', 'Punctele comune sunt de obicei fapte'],
                            ['Unde diferă?', 'Diferențele sunt aproape întotdeauna interpretări, nu contraziceri de fapt'],
                        ],
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'La Subiectul IV, sursa e argument, nu decor',
                        'text' => 'Un răspuns extins care menționează sursele fără să le folosească — „după cum reiese din sursa A" și atât — nu ia punctele de argumentare. Baremul cere ca afirmația ta să fie SUSȚINUTĂ de un element concret din sursă: citează scurt, apoi explică ce dovedește citatul.',
                    ],
                ],
            ],

            // ==================================================================
            'cronologie' => [
                'title' => 'Cronologie și periodizare',
                'summary' => 'Reperele care se cer, calculul secolelor și legătura dintre succesiune și cauzalitate.',
                'objectives' => [
                    'Să încadrezi corect un an în secolul lui',
                    'Să ordonezi evenimente și să explici legătura dintre ele',
                    'Să reții reperele majore din istoria românilor și cea universală',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Cronologia nu e o listă de date'],

                    ['type' => 'text', 'text' => 'Programa cere „să încadreze relația de cauzalitate din informația oferită de surse". Cu alte cuvinte, ordinea contează pentru că explică: un eveniment ulterior nu poate fi cauza unuia anterior, iar succesiunea corectă e adesea singurul lucru care face argumentul valid.'],

                    ['type' => 'text', 'text' => 'De aceea itemii de cronologie nu cer doar „pune în ordine", ci și „explică ce a făcut posibil evenimentul următor".'],

                    ['type' => 'heading', 'text' => 'Calculul secolului'],

                    [
                        'type' => 'warning',
                        'title' => 'Anul 1812 e în secolul al XIX-lea, nu al XVIII-lea',
                        'text' => 'E capcana clasică. Secolul I cuprinde anii 1–100, deci secolul al XIX-lea începe în 1801 și se termină în 1900. Regula: iei primele două cifre ale anului și **adaugi unu**, cu excepția anilor care se termină în „00". Astfel 1812 → 18 + 1 = secolul al XIX-lea, dar 1800 → secolul al XVIII-lea.',
                    ],

                    [
                        'type' => 'table',
                        'headers' => ['Anul', 'Secolul', 'De ce'],
                        'rows' => [
                            ['1600', 'al XVI-lea', 'Se termină în „00" — ultimul an al secolului'],
                            ['1601', 'al XVII-lea', 'Primul an al secolului următor'],
                            ['1812', 'al XIX-lea', '18 + 1'],
                            ['1918', 'al XX-lea', '19 + 1'],
                            ['2027', 'al XXI-lea', '20 + 1'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Reperele care se cer'],

                    ['type' => 'text', 'text' => 'Nu trebuie să știi sute de date. Douăzeci de repere bine fixate acoperă majoritatea itemilor de cronologie, pentru că restul se poziționează în raport cu ele.'],

                    [
                        'type' => 'table',
                        'caption' => 'Istoria românilor — reperele cel mai des solicitate.',
                        'headers' => ['Anul', 'Evenimentul'],
                        'rows' => [
                            ['106', 'Dacia devine provincie romană'],
                            ['1457–1504', 'Domnia lui Ștefan cel Mare'],
                            ['1600', 'Unirea celor trei țări sub Mihai Viteazul'],
                            ['1812', 'Pacea de la București; Basarabia intră în Imperiul Rus'],
                            ['1821', 'Revoluția condusă de Tudor Vladimirescu'],
                            ['1859', 'Unirea Principatelor sub Alexandru Ioan Cuza'],
                            ['1866', 'Constituția; monarhie constituțională'],
                            ['1877–1878', 'Independența României'],
                            ['27 martie 1918', 'Sfatul Țării votează unirea Basarabiei'],
                            ['1 decembrie 1918', 'Marea Adunare Națională de la Alba Iulia'],
                            ['23 august 1939', 'Pactul Ribbentrop-Molotov'],
                            ['28 iunie 1940', 'Ocuparea Basarabiei și a nordului Bucovinei'],
                            ['2 august 1940', 'Formarea RSS Moldovenești'],
                            ['23 iunie 1990', 'Declarația de suveranitate'],
                            ['27 august 1991', 'Declarația de Independență a Republicii Moldova'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'De la succesiune la cauzalitate'],

                    [
                        'type' => 'example',
                        'title' => 'Ordinea explică',
                        'steps' => [
                            ['text' => '**1939** — Pactul Ribbentrop-Molotov, cu protocolul adițional secret care delimitează sferele de influență.'],
                            ['text' => '**iunie 1940** — ultimatumurile sovietice și ocuparea Basarabiei. Nu e o coincidență de calendar: protocolul din 1939 o făcuse posibilă.'],
                            ['text' => '**august 1940** — formarea RSSM, la scurt timp după ocupare.'],
                            ['text' => 'Succesiunea arată legătura. Un răspuns care doar enumeră datele ia mai puține puncte decât unul care spune „ca urmare a".'],
                        ],
                        'result' => 'La itemii de cauzalitate, formulările „a făcut posibil", „ca urmare a", „în consecință" sunt exact ce caută baremul.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Succesiunea nu dovedește singură cauzalitatea',
                        'text' => 'Faptul că B urmează după A nu înseamnă automat că A a cauzat B. La Subiectul al IV-lea, unde se cere argumentare, legătura trebuie susținută cu un element din sursă — altfel e o simplă presupunere, iar baremul o tratează ca atare.',
                    ],
                ],
            ],
        ];
    }
}
