<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Skill;
use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Teoria pentru limba și literatura română (§11b: doar din baremele ANCE).
 *
 * Cum se notează proba stă AICI, nu în Teste: testul ANCE cere să rezolvi
 * sarcini, nu întreabă niciodată câte puncte valorează una. Un item „câte
 * puncte primește…" ar antrena o întrebare care nu apare la examen. Elevului
 * îi folosește însă ca strategie — de aceea e teorie.
 *
 * Cifrele vin din baremele din 2026, sesiunea de bază:
 *   - profil real, itemul 10 (eseu argumentativ) — 12_llro_barem_r_sb26;
 *   - profil umanist, itemul 12 (eseu structurat) — 12_llro_barem_u_sb26;
 *   - redactarea, comună ambelor profiluri;
 *   - regula „raționament + exemplu" — baremele 2022–2026 (ex. 12_llro_barem_u_sb25, itemul 9).
 */
class RomanaLessonSeeder extends Seeder
{
    public function run(): void
    {
        $subject = Subject::where('slug', 'limba-romana')->firstOrFail();

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
            'eseu' => [
                'title' => 'Cum se notează eseul și redactarea',
                'summary' => 'Eseul (24 de puncte) și redactarea (20 de puncte) aduc aproape jumătate din nota la română. Aici vezi pe ce anume dă baremul ANCE fiecare punct, ca să nu pierzi puncte pe lucruri pe care le știai.',
                'objectives' => [
                    'Să știi ce reper al eseului aduce cele mai multe puncte',
                    'Să construiești fiecare argument din raționament și exemplu',
                    'Să eviți greșelile care scad punctajul la redactare',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'De unde vin cifrele'],

                    ['type' => 'text', 'text' => 'Tot ce urmează e luat din baremele ANCE pentru BAC 2026, sesiunea de bază. Baremul e documentul după care corectează profesorii: pentru fiecare item spune ce se acceptă și câte puncte se dau pe fiecare element. La examen nu te întreabă nimeni aceste cifre — dar dacă le știi, scrii exact ce se punctează.'],

                    ['type' => 'heading', 'text' => 'Regula care se repetă în tot testul: raționament + exemplu'],

                    ['type' => 'text', 'text' => 'Ori de câte ori ți se cere să argumentezi — apartenența unui fragment la gen, o trăsătură de stil, o teză din eseu — baremul punctează separat **raționamentul** (de ce) și **exemplul** din text (unde se vede).'],

                    [
                        'type' => 'warning',
                        'title' => 'Citatul singur nu e argument',
                        'text' => 'Baremele ANCE scriu explicit: „Se va acorda 1 punct pentru raționamentul neînsoțit de exemplu; 0 puncte pentru exemplu fără raționament." Un citat lipit fără explicație nu aduce nimic. Scrie întâi ideea, apoi dovada.',
                    ],

                    [
                        'type' => 'example',
                        'title' => 'Argumentul pentru genul epic, construit corect',
                        'problem' => 'Sarcina: „Prezintă două argumente, ilustrate cu câte o secvență din text, care califică fragmentul ca pe o narațiune." (BAC 2025, profil real, itemul 9)',
                        'steps' => [
                            ['text' => '**Raționamentul:** textul are un narator care relatează întâmplări. Baremul acceptă ca argumente: narator, acțiune, personaj, relația dintre personaje, fir narativ, coordonate temporale și spațiale.'],
                            ['text' => '**Exemplul:** o secvență scurtă din fragment în care se vede naratorul — o frază la persoana a III-a care povestește o întâmplare.'],
                            ['text' => 'Repeți pentru al doilea argument (de exemplu, coordonatele de timp și loc), cu altă secvență. Fiecare argument complet aduce 2 puncte: 1 pentru raționament, 1 pentru exemplu.'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Eseul argumentativ (profil real) — 24 de puncte'],

                    ['type' => 'text', 'text' => 'Cerința tipică: „Redactează un eseu argumentativ de 1–1,5 pagini, în raport cu afirmația…". Baremul din 2026 împarte punctele așa:'],

                    [
                        'type' => 'table',
                        'caption' => 'Baremul ANCE, BAC 2026, sesiunea de bază, profil real, itemul 10.',
                        'headers' => ['Reper', 'Puncte', 'Pe ce se dau'],
                        'rows' => [
                            ['Opinia', '2', '1 — opinie clar exprimată; 1 — legată de afirmația din cerință'],
                            ['Două teze distincte', '4', 'pentru fiecare teză: 1 — formulată explicit; 1 — corelată cu afirmația'],
                            ['Câte un argument pentru fiecare teză', '4', 'pentru fiecare argument: 1 — valid; 1 — concordant și logic'],
                            ['Referința la două texte din literatura română', '8', 'pentru fiecare text: titlul 1, autorul 1, referința exactă la text 1, exemplul concret / citatul / rezumarea secvenței 1'],
                            ['Structura eseului', '5', 'concluzia rezultată din argumente 2; alineate și justificare graduală 1; conectori logici 1; aspect grafic îngrijit 1'],
                            ['Limita de întindere', '1', '1–1,5 pagini'],
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Reperul cel mai greu e **referința la cele două texte literare: 8 puncte**, o treime din eseu. Titlul și autorul aduc jumătate din ele — puncte pe care le pierzi doar dacă nu le scrii. Cealaltă jumătate cere să arăți că ai citit textul: o situație, un personaj, o secvență, nu doar numele operei.'],

                    [
                        'type' => 'warning',
                        'title' => 'Concluzia nu e o formalitate',
                        'text' => 'Concluzia valorează 2 puncte. Baremul dă „doar 1 punct pentru o formulare schematică a concluziei, necorelată cu argumentele/ideile anterioare". O frază de tipul „În concluzie, sunt de acord cu afirmația" pierde un punct; una care reia cele două teze și arată ce rezultă din ele îl păstrează.',
                    ],

                    ['type' => 'heading', 'text' => 'Eseul structurat (profil umanist, arte, sport) — 24 de puncte'],

                    ['type' => 'text', 'text' => 'La umanist, eseul pornește de la o temă cu mai multe concepte subliniate și de la o aserțiune. Reperele sunt altele decât la real:'],

                    [
                        'type' => 'table',
                        'caption' => 'Baremul ANCE, BAC 2026, sesiunea de bază, profil umanist, itemul 12.',
                        'headers' => ['Reper', 'Puncte', 'Pe ce se dau'],
                        'rows' => [
                            ['Explicarea a două concepte din temă', '2', '1 pentru fiecare concept'],
                            ['Raportarea conceptelor la experiența de lectură', '2', '1 pentru fiecare: text concret, titlu și autor, relația concept – exemplu'],
                            ['Alegerea argumentată a două texte literare', '6', 'pentru fiecare text: titlul și autorul 1; motivarea alegerii, legată de temă, 2'],
                            ['Comentarea câte unui aspect din fiecare text', '6', 'pentru fiecare: aspectul relevant 1; comentarea particularităților 2'],
                            ['Ilustrarea prin câte un exemplu convingător', '4', '2 pentru fiecare text'],
                            ['Dezvoltarea unei idei din aserțiune', '2', 'formularea ideii 1; dezvoltarea ei 1'],
                            ['Opinia argumentată față de temă', '2', 'doar 1 pentru o formulare generală, fără legătură explicită cu tema'],
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Și aici textele literare aduc cele mai multe puncte: alegerea, comentariul și exemplul fac împreună 16 din 24. Baremul repetă la aproape fiecare reper același avertisment — se dă doar 1 punct pentru un răspuns „evaziv, superficial" sau „insuficient corelat". Generalitățile costă.'],

                    ['type' => 'heading', 'text' => 'Redactarea — 20 de puncte, pentru tot ce scrii'],

                    ['type' => 'text', 'text' => 'Testul spune: „Pentru redactarea tuturor secvențelor scrise vei obține 20 de puncte." Se notează separat de conținut, pe cinci criterii, la ambele profiluri:'],

                    [
                        'type' => 'table',
                        'caption' => 'Baremul ANCE, BAC 2026, sesiunea de bază — redactarea.',
                        'headers' => ['Criteriu', 'Puncte', 'Cum se acordă'],
                        'rows' => [
                            ['Organizarea ideilor și limita de întindere', '2', 'claritatea și succesiunea ideilor 1; limita de întindere 1'],
                            ['Analiza și interpretarea critică', '3', 'înțelegerea sarcinii 1; date și fapte elocvente 1; interpretarea critică 1'],
                            ['Corectitudinea stilistică', '5', 'se scade 1 punct la fiecare 2 greșeli de exprimare'],
                            ['Normele ortografice și gramaticale', '5', 'se scade 1 punct la fiecare 2 greșeli'],
                            ['Normele de punctuație', '5', 'se scade 1 punct la fiecare 2 greșeli'],
                        ],
                    ],

                    [
                        'type' => 'example',
                        'title' => 'Cât costă greșelile de ortografie',
                        'problem' => 'Un elev face 5 greșeli de ortografie și gramatică în toată lucrarea. Câte puncte primește la acest criteriu?',
                        'steps' => [
                            ['text' => 'Punctajul întreg (5) se dă dacă numărul de greșeli „nu depășește 1".'],
                            ['text' => 'Baremul: 4 puncte pentru 2–3 greșeli, **3 puncte pentru 4–5 greșeli**, 2 puncte pentru 6–7, 1 punct pentru 8–9, 0 puncte pentru 10 și mai multe.'],
                            ['text' => 'Deci 5 greșeli înseamnă 3 puncte din 5. Aceeași scară se aplică separat punctuației și stilului — trei criterii de câte 5 puncte, adică 15 puncte care depind doar de atenție la recitire.'],
                        ],
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Lasă timp pentru recitire',
                        'text' => 'Ortografia, punctuația și stilul valorează împreună 15 puncte și se pierd câte unul la fiecare două greșeli. Păstrează timp la final pentru recitirea întregii lucrări, cu atenție la virgule și la cratime.',
                    ],
                ],
            ],
        ];
    }
}
