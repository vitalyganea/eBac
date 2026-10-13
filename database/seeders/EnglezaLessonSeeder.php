<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Skill;
use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Teoria pentru limba engleză.
 *
 * Aici cursul contează cel mai mult din tot proiectul, și dintr-un motiv precis:
 * **70 din 100 de puncte sunt producere scrisă** — Subiectul II (eseu, 40p) și
 * Subiectul III (articol/scrisoare, 30p). Niciun item nu le poate corecta
 * automat. Nu poți evalua un eseu cu variante de răspuns, dar poți PREDA
 * structura lui, iar asta mută nota.
 *
 * Nivelul țintă e B1 (CECRL 2018), conform programei ANCE. Explicațiile sunt în
 * română, exemplele în engleză — așa explică un profesor din Moldova.
 */
class EnglezaLessonSeeder extends Seeder
{
    public function run(): void
    {
        $subject = Subject::where('slug', 'limba-engleza')->firstOrFail();

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
            'eseu-structurat' => [
                'title' => 'Eseul structurat',
                'summary' => 'Subiectul II valorează 40 de puncte. Structura, teza, paragrafele și registrul — tot ce punctează baremul, în 180–200 de cuvinte.',
                'objectives' => [
                    'Să planifici eseul înainte de a-l scrie',
                    'Să formulezi o teză care ia poziție și o justifică',
                    'Să construiești un paragraf după schema idee → exemplu → explicație',
                    'Să folosești registrul formal cerut de barem',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Ce punctează, de fapt, baremul'],

                    ['type' => 'text', 'text' => 'Eseul nu se notează „la impresie". Baremul are criterii separate, iar cel mai mare dintre ele nu e vocabularul, ci **competența pragmatică** — dacă textul e organizat, coerent și răspunde la sarcina dată. Un eseu cu engleză modestă dar bine structurat ia mai multe puncte decât unul cu vocabular bogat și fără plan.'],

                    ['type' => 'text', 'text' => 'De aici rezultă ceva contraintuitiv: **primul lucru de făcut nu e să scrii, ci să planifici**. Ai 180–200 de cuvinte, adică loc pentru exact cinci părți. Nu poți improviza și spera că iese.'],

                    ['type' => 'heading', 'text' => 'Structura, în cinci părți'],

                    [
                        'type' => 'table',
                        'caption' => 'Repartizarea pe 180–200 de cuvinte. Introducerea și concluzia rămân scurte: punctele stau în mijloc.',
                        'headers' => ['Partea', 'Cuvinte', 'Ce conține'],
                        'rows' => [
                            ['Introducere', '~30', 'Prezinți tema și îți declari poziția (teza)'],
                            ['Paragraful 1', '~50', 'Argumentul cel mai puternic, cu exemplu'],
                            ['Paragraful 2', '~50', 'Al doilea argument sau punctul de vedere opus'],
                            ['Răspuns / soluție', '~40', 'Răspunzi obiecției sau propui o soluție'],
                            ['Concluzie', '~25', 'Reformulezi poziția, cu alte cuvinte'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Teza: propoziția care decide tot restul'],

                    ['type' => 'text', 'text' => 'O teză bună face **două** lucruri: ia o poziție clară și spune de ce. Dacă lipsește unul dintre ele, restul eseului n-are ce susține.'],

                    [
                        'type' => 'table',
                        'caption' => 'Aceeași temă, patru introduceri. Doar una funcționează.',
                        'headers' => ['Formulare', 'Ce e greșit'],
                        'rows' => [
                            ['„In this essay I am going to write about social media."', 'Anunță subiectul, dar nu ia nicio poziție'],
                            ['„Social media is a very important topic today."', 'Vag; nu se poate susține sau contrazice'],
                            ['„Everybody knows social media is bad for students."', 'Generalizare nesusținută — penalizată la barem'],
                            ['„Schools should teach digital habits, because young people learn boundaries more effectively than rules."', '✅ Poziție clară plus motiv'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Paragraful: idee → exemplu → explicație'],

                    ['type' => 'text', 'text' => 'Fiecare paragraf din mijloc are aceeași schemă. Cele mai multe eseuri slabe se opresc după exemplu și nu explică de ce exemplul susține ideea — exact pasul care aduce punctele.'],

                    [
                        'type' => 'example',
                        'title' => 'Un paragraf, pas cu pas',
                        'steps' => [
                            ['text' => '**Ideea** — o propoziție care spune despre ce e paragraful: *„Firstly, phones interrupt concentration far more often than students realise."*'],
                            ['text' => '**Exemplul** — concret, nu general: *„A recent study found that teenagers check their phones around 78 times a day, usually for no particular reason."*'],
                            ['text' => '**Explicația** — de ce exemplul susține ideea: *„This means that even a short study session is broken into fragments, which makes deep concentration almost impossible."*'],
                            ['text' => '**Legătura** cu teza: *„Learning to manage this is therefore a skill, not a matter of willpower."*'],
                        ],
                        'result' => 'Patru propoziții, ~55 de cuvinte — exact cât trebuie pentru un paragraf.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Exemplul fără explicație nu contează',
                        'text' => 'Un exemplu lăsat singur cere cititorului să facă el legătura. Baremul punctează argumentarea, nu enumerarea. Regula practică: după fiecare exemplu, scrie o propoziție care începe cu *„This means…"*, *„This shows…"* sau *„As a result…"*.',
                    ],

                    ['type' => 'heading', 'text' => 'Registrul'],

                    ['type' => 'text', 'text' => 'Eseul cere limbaj formal. Nu înseamnă cuvinte complicate — înseamnă absența celor colocviale.'],

                    [
                        'type' => 'table',
                        'caption' => 'Formele din stânga se penalizează la criteriul de registru.',
                        'headers' => ['Evită', 'Folosește'],
                        'rows' => [
                            ['gonna, wanna, don\'t, can\'t', 'going to, want to, do not, cannot'],
                            ['stuff, things, a lot of', 'aspects, factors, a great deal of'],
                            ['kids', 'children, young people'],
                            ['I think it\'s bad', 'It can be argued that this is harmful'],
                            ['And also, But', 'Moreover, However'],
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Atenție însă: *„In my opinion"* și *„I believe"* sunt perfect acceptabile într-un eseu de opinie. Nu confunda formalitatea cu impersonalitatea.'],

                    ['type' => 'heading', 'text' => 'Numărul de cuvinte'],

                    [
                        'type' => 'warning',
                        'title' => 'Sub 180 sau peste 200 se penalizează',
                        'text' => 'Nu număra cuvintele la final — n-ai timp. Numără **rândurile**: scrisul tău are un număr aproape constant de cuvinte pe rând. Află-l acasă, pe un eseu de probă, și la examen vei ști din priviri dacă ești în limite.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Concluzia nu repetă introducerea',
                        'text' => 'Reformulează poziția cu ALTE cuvinte și, dacă poți, adaugă o consecință sau o recomandare. O concluzie care copiază introducerea semnalează evaluatorului că nu mai aveai ce spune.',
                    ],
                ],
            ],

            // ==================================================================
            'comprehensiune-scrisa' => [
                'title' => 'Comprehensiunea scrisă',
                'summary' => 'Subiectul I: un text necunoscut de 400–450 de cuvinte, 30 de puncte. Strategia de citire, tipurile de întrebări și capcanele lor.',
                'objectives' => [
                    'Să abordezi textul în ordinea eficientă, nu de la început la sfârșit',
                    'Să recunoști tipul fiecărei întrebări și ce caută',
                    'Să eviți cele trei capcane care se repetă la fiecare examen',
                    'Să deduci sensul unui cuvânt necunoscut din context',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Ordinea în care lucrezi'],

                    ['type' => 'text', 'text' => 'Greșeala cea mai costisitoare e să citești textul integral, atent, de la prima literă. Un text de 450 de cuvinte citit „complet" îți mănâncă timpul, iar la a treia întrebare tot te întorci la el.'],

                    [
                        'type' => 'table',
                        'caption' => 'Trei treceri, fiecare cu alt scop.',
                        'headers' => ['Pas', 'Ce faci', 'De ce'],
                        'rows' => [
                            ['1. Parcurgere rapidă', 'Citești titlul, prima și ultima frază a fiecărui paragraf', 'Afli despre ce e textul și cum e organizat, în 60 de secunde'],
                            ['2. Citirea întrebărilor', 'Le citești pe toate, înainte de a reveni la text', 'Știi ce cauți; altfel citești totul cu aceeași atenție inutilă'],
                            ['3. Căutarea țintită', 'Pentru fiecare întrebare, găsești zona din text și o citești atent', 'Întrebările urmează de obicei ordinea textului'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Tipurile de întrebări'],

                    [
                        'type' => 'table',
                        'headers' => ['Tip', 'Cum sună', 'Unde e răspunsul'],
                        'rows' => [
                            ['Ideea principală', 'What is the main idea of the text?', 'De obicei în ultimul paragraf, unde autorul concluzionează'],
                            ['Detaliu', 'According to the text, what surprised the researchers?', 'Într-o singură frază, care se poate arăta cu degetul'],
                            ['Deducție', 'What does the writer suggest about…?', 'Nu e scris direct; rezultă din ce spune textul'],
                            ['Vocabular în context', 'The word „boundaries" in line 8 means…', 'Din fraza dinainte și de după'],
                            ['Referință', 'What does „it" in line 12 refer to?', 'Aproape întotdeauna în fraza anterioară'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Cele trei capcane'],

                    ['type' => 'text', 'text' => 'La întrebările cu variante, distractorii nu sunt aleatori. Se construiesc după trei tipare care se repetă la fiecare sesiune:'],

                    [
                        'type' => 'table',
                        'headers' => ['Capcana', 'Cum arată', 'Cum o eviți'],
                        'rows' => [
                            ['Adevărat, dar prea îngust', 'Un detaliu real din text, oferit ca „idee principală"', 'Întreabă-te: acoperă TOT textul sau doar un rând?'],
                            ['Contrazice textul', 'Sună plauzibil, dar textul spune opusul', 'Caută negațiile: „not", „however", „rather than"'],
                            ['Nemenționat', 'Adevărat în realitate, dar absent din text', 'Răspunzi din TEXT, nu din ce știi tu despre subiect'],
                        ],
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Structura „not X but Y" e o mină de întrebări',
                        'text' => 'Ori de câte ori un text spune *„What surprised them was not the number itself, but what the students said"*, acolo se construiește aproape sigur un item. Autorul îți oferă explicit un răspuns greșit („the number") și unul corect. Subliniază aceste structuri la prima trecere.',
                    ],

                    ['type' => 'heading', 'text' => 'Cuvintele necunoscute'],

                    ['type' => 'text', 'text' => 'Vei întâlni cuvinte pe care nu le știi — e normal, textul e „necunoscut" prin definiție. Nu te opri. Contextul îți dă de obicei destul:'],

                    [
                        'type' => 'example',
                        'title' => 'Deducerea din context',
                        'problem' => '„Students who kept their phones in another room reported better concentration. Those who simply promised themselves to use their phones less saw almost no change at all."',
                        'steps' => [
                            ['text' => 'Nu știi „saw no change". Dar vezi contrastul cu prima frază, unde apare „better concentration".'],
                            ['text' => 'Cuvântul de legătură lipsește, dar opoziția e clară: unii au avut rezultate, ceilalți nu.'],
                            ['text' => 'Deci „saw almost no change" înseamnă că la ei nu s-a îmbunătățit nimic.'],
                        ],
                        'result' => 'Ai dedus sensul fără dicționar, doar din structura frazei.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Nu copia fraze întregi ca răspuns',
                        'text' => 'La întrebările cu răspuns scurt, copierea unui rând întreg din text arată că ai găsit zona, dar nu că ai înțeles. Răspunde cu propriile cuvinte, scurt și exact la ce s-a cerut.',
                    ],
                ],
            ],

            // ==================================================================
            'timpuri-verbale' => [
                'title' => 'Timpuri verbale',
                'summary' => 'Timpurile cerute la nivelul B1, cu accent pe distincția care le dă cele mai mari bătăi de cap vorbitorilor de română.',
                'objectives' => [
                    'Să folosești corect timpurile de bază la nivel B1',
                    'Să distingi Past Simple de Present Perfect',
                    'Să recunoști cuvintele-semnal care indică timpul potrivit',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Timpurile de care ai nevoie'],

                    [
                        'type' => 'table',
                        'caption' => 'Nivelul B1 nu cere mai mult de atât. Le folosești corect pe acestea și eseul e în regulă.',
                        'headers' => ['Timp', 'Formă', 'Când se folosește'],
                        'rows' => [
                            ['Present Simple', 'I study', 'Obiceiuri, adevăruri generale'],
                            ['Present Continuous', 'I am studying', 'Acțiune în desfășurare acum sau în perioada actuală'],
                            ['Past Simple', 'I studied', 'Acțiune încheiată, într-un moment din trecut'],
                            ['Past Continuous', 'I was studying', 'Fundal în trecut, întrerupt de altceva'],
                            ['Present Perfect', 'I have studied', 'Trecut legat de prezent; momentul nu contează'],
                            ['Future (will / going to)', 'I will study', 'Decizie de moment / intenție planificată'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Past Simple sau Present Perfect'],

                    ['type' => 'text', 'text' => 'Asta e distincția care se greșește cel mai des, și există un motiv structural: **româna are un singur timp** unde engleza are două. „Am studiat" se traduce și „I studied", și „I have studied" — depinde de ceva ce româna nu marchează.'],

                    ['type' => 'text', 'text' => 'Regula practică: dacă **momentul e precizat sau subînțeles ca încheiat**, folosești Past Simple. Dacă acțiunea e legată de prezent sau momentul nu contează, folosești Present Perfect.'],

                    [
                        'type' => 'table',
                        'headers' => ['Present Perfect', 'Past Simple'],
                        'rows' => [
                            ['I have lived in Chișinău since 2019.', 'I lived in Chișinău in 2019.'],
                            ['She has finished her homework. (e gata acum)', 'She finished her homework at eight.'],
                            ['Have you ever been to London?', 'Did you go to London last summer?'],
                            ['We have already studied this chapter.', 'We studied this chapter yesterday.'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Cuvintele-semnal'],

                    [
                        'type' => 'table',
                        'caption' => 'Prezența unuia dintre ele decide timpul aproape întotdeauna.',
                        'headers' => ['Cere Present Perfect', 'Cere Past Simple'],
                        'rows' => [
                            ['since, for', 'yesterday, last week, in 2019'],
                            ['already, yet, just', 'ago, then, when I was…'],
                            ['ever, never, so far', 'at eight o\'clock'],
                        ],
                    ],

                    [
                        'type' => 'warning',
                        'title' => '„Since" și „ago" nu stau împreună',
                        'text' => 'Formele *„I have studied English since three years"* și *„I have finished it yesterday"* sunt greșite. „Since" cere un MOMENT de start („since 2019"), „for" cere o DURATĂ („for three years"), iar „yesterday" și „ago" cer obligatoriu Past Simple.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Acordul la substantivele colective',
                        'text' => 'În engleza standard, „family", „team" și „government" se acordă la singular: *„My family **has** lived here since 2019"*, nu „have". E o greșeală frecventă tocmai pentru că logica românească sugerează pluralul.',
                    ],
                ],
            ],

            // ==================================================================
            'coeziune-conectori' => [
                'title' => 'Coeziune și conectori',
                'summary' => 'Conectorii sunt cea mai ieftină sursă de puncte la eseu: baremul evaluează explicit coerența textului.',
                'objectives' => [
                    'Să alegi conectorul potrivit funcției logice, nu după sonoritate',
                    'Să îi poziționezi și să îi punctuezi corect',
                    'Să eviți supraîncărcarea, la fel de penalizată ca absența lor',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'De ce contează disproporționat'],

                    ['type' => 'text', 'text' => 'Conectorii sunt puțini, se învață într-o oră, și baremul îi punctează direct prin criteriul de coerență. Raportul efort/puncte e cel mai bun din tot examenul de limbă străină.'],

                    ['type' => 'text', 'text' => 'Dar alegerea lor nu e o chestiune de stil, ci de **logică**. Un „Moreover" pus acolo unde ideea se schimbă face textul incoerent și se penalizează — chiar dacă propoziția în sine e corectă gramatical.'],

                    ['type' => 'heading', 'text' => 'Cei care îți trebuie'],

                    [
                        'type' => 'table',
                        'caption' => 'Șase funcții acoperă tot ce ai nevoie într-un eseu de 200 de cuvinte.',
                        'headers' => ['Funcția', 'Conectori', 'Exemplu'],
                        'rows' => [
                            ['Adăugare', 'Moreover, Furthermore, In addition', 'Moreover, it saves time.'],
                            ['Contrast', 'However, Nevertheless, On the other hand', 'However, it has a cost.'],
                            ['Cauză', 'Because, Since, As', 'Since prices rose, fewer people travel.'],
                            ['Rezultat', 'Therefore, As a result, Consequently', 'Therefore, schools should act.'],
                            ['Exemplificare', 'For instance, For example, Such as', 'For instance, many students…'],
                            ['Concluzie', 'In conclusion, To sum up, Overall', 'In conclusion, the benefits outweigh…'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Poziție și punctuație'],

                    ['type' => 'text', 'text' => 'Majoritatea conectorilor de mai sus stau la **începutul frazei, urmați de virgulă**. E poziția cea mai sigură și cea mai clară.'],

                    [
                        'type' => 'example',
                        'title' => 'Aceeași idee, trei poziții',
                        'steps' => [
                            ['text' => 'La început, cel mai clar: *„However, many students disagree."*'],
                            ['text' => 'La mijloc, între virgule: *„Many students, however, disagree."*'],
                            ['text' => 'Cu „but" e altă construcție — leagă două propoziții, nu două fraze: *„Many students disagree, but they rarely say so."*'],
                        ],
                        'result' => '„However" nu poate înlocui „but" în interiorul aceleiași fraze fără punct sau punct și virgulă înainte.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Prea mulți conectori strică la fel de mult ca lipsa lor',
                        'text' => 'Un eseu în care fiecare frază începe cu „Moreover", „Furthermore", „In addition" sună mecanic și se penalizează. Regula practică: un conector la începutul fiecărui paragraf, plus unul sau două în interior. Nu mai mult.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Coeziunea nu se face doar din conectori',
                        'text' => 'Un text coerent leagă ideile și prin pronume („this problem", „such measures") și prin repetarea controlată a cuvintelor-cheie. Un eseu care folosește doar conectori, fără aceste legături, rămâne o listă de propoziții.',
                    ],
                ],
            ],
            // ==================================================================
            'conditionale-pasiv' => [
                'title' => 'Condiționale și diateza pasivă',
                'summary' => 'Cele patru tipuri de condiționale, de ce „if" nu se combină cu „will", și când diateza pasivă e alegerea corectă, nu doar o variantă.',
                'objectives' => [
                    'Să alegi tipul de condițional potrivit gradului de probabilitate',
                    'Să eviți construcția „if ... will", cea mai penalizată la B1',
                    'Să formezi corect pasivul la timpurile cerute de programă',
                    'Să folosești pasivul acolo unde chiar aduce ceva, nu peste tot',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Condiționalele: patru situații, nu patru formule'],

                    ['type' => 'text', 'text' => 'Diferența dintre tipuri nu e gramaticală, ci de **probabilitate**. Întreabă-te cât de posibil e ce descrii, iar forma vine singură.'],

                    [
                        'type' => 'table',
                        'caption' => 'Tipul 0 exprimă adevăruri generale; tipul 3 se referă la trecut, care nu se mai poate schimba.',
                        'headers' => ['Tip', 'Când se folosește', 'Propoziția cu „if"', 'Propoziția principală'],
                        'rows' => [
                            ['0', 'Adevăr general, mereu valabil', 'Present Simple', 'Present Simple'],
                            ['1', 'Situație reală, probabilă', 'Present Simple', 'will + infinitiv'],
                            ['2', 'Ipotetic, improbabil în prezent', 'Past Simple', 'would + infinitiv'],
                            ['3', 'Regret despre trecut, imposibil', 'Past Perfect', 'would have + participiu'],
                        ],
                    ],

                    [
                        'type' => 'example',
                        'title' => 'Aceeași idee, patru grade de probabilitate',
                        'problem' => 'Observă cum se schimbă sensul, nu subiectul.',
                        'steps' => [
                            ['text' => 'Tip 0 — lege a naturii: „If you heat water to 100°C, it boils."'],
                            ['text' => 'Tip 1 — chiar se poate întâmpla: „If I study tonight, I will pass the exam."'],
                            ['text' => 'Tip 2 — ipoteză puțin probabilă: „If I studied every day, I would speak fluently."'],
                            ['text' => 'Tip 3 — regret, trecutul s-a consumat: „If I had studied, I would have passed."'],
                        ],
                        'result' => 'Aceeași propoziție, patru distanțe față de realitate. Alegerea greșită schimbă sensul, nu doar forma.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Greșeala numărul unu la B1',
                        'text' => 'Nu se scrie **„If it will rain"**. După „if" nu se pune „will" — se folosește prezentul: „If it rains, we will stay home." Excepția (o cerere politicoasă: „If you will follow me...") nu apare la examen și nu merită riscul. Aceeași regulă și pentru „when", „as soon as", „until", „before".',
                    ],

                    ['type' => 'text', 'text' => 'Mai reține o formă care apare des la Subiectul I: **„unless" înseamnă „if not"**. „Unless you hurry, you will miss the bus" e același lucru cu „If you do not hurry...". Dublarea negației („unless you do not hurry") e greșită.'],

                    ['type' => 'heading', 'text' => 'Diateza pasivă'],

                    ['type' => 'text', 'text' => 'Formula e aceeași la toate timpurile: **forma potrivită a lui „to be" + participiul trecut**. Ce se schimbă e doar timpul verbului „be".'],

                    [
                        'type' => 'table',
                        'caption' => 'Participiul rămâne neschimbat; timpul îl poartă verbul „be".',
                        'headers' => ['Timp', 'Activ', 'Pasiv'],
                        'rows' => [
                            ['Present Simple', 'They build houses.', 'Houses **are built**.'],
                            ['Past Simple', 'They built the bridge.', 'The bridge **was built**.'],
                            ['Present Perfect', 'They have signed it.', 'It **has been signed**.'],
                            ['Future', 'They will announce it.', 'It **will be announced**.'],
                            ['Modal', 'They must repair it.', 'It **must be repaired**.'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Când merită folosit pasivul'],

                    ['type' => 'text', 'text' => 'Pasivul nu e „mai elegant" în sine. E util în trei situații: când **autorul nu se cunoaște** („My bike was stolen"), când **nu contează cine a făcut** („The road is being repaired") sau când vrei să pui accentul pe **rezultat**, nu pe făptaș („English is spoken here").'],

                    ['type' => 'text', 'text' => 'Dacă vrei totuși să menționezi autorul, el vine cu „by": „The novel was written **by** Orwell." Dacă însă prepoziția indică instrumentul, se folosește „with": „It was cut **with** a knife."'],

                    [
                        'type' => 'warning',
                        'title' => 'Pasivul în exces strică eseul',
                        'text' => 'La Subiectul II, un text integral la pasiv devine greoi și impersonal, iar baremul penalizează lipsa de claritate. Folosește-l unde are rost, nu ca să pari formal. Și atenție la verbele care nu au pasiv: „happen", „occur", „arrive", „belong" nu se pot pasiviza.',
                    ],
                ],
            ],

            // ==================================================================
            'vorbire-indirecta' => [
                'title' => 'Vorbire indirectă și verbe modale',
                'summary' => 'Cum se transformă o replică în relatare — timpuri, pronume, repere temporale — și ce exprimă fiecare verb modal.',
                'objectives' => [
                    'Să aplici decalajul de timp corect în vorbirea indirectă',
                    'Să schimbi pronumele și reperele de timp odată cu timpul verbal',
                    'Să alegi verbul de relatare potrivit intenției vorbitorului',
                    'Să distingi gradele de certitudine exprimate de modale',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Regula decalajului'],

                    ['type' => 'text', 'text' => 'Când relatezi ce a spus cineva, verbul face **un pas înapoi în timp**. Motivul e logic: momentul vorbirii a trecut deja, deci și verbul se îndepărtează.'],

                    [
                        'type' => 'table',
                        'caption' => 'Past Perfect nu mai are unde coborî, deci rămâne neschimbat.',
                        'headers' => ['Vorbire directă', 'Vorbire indirectă'],
                        'rows' => [
                            ['Present Simple: „I work"', 'Past Simple: „...he worked"'],
                            ['Present Continuous: „I am working"', 'Past Continuous: „...he was working"'],
                            ['Past Simple: „I worked"', 'Past Perfect: „...he had worked"'],
                            ['Present Perfect: „I have worked"', 'Past Perfect: „...he had worked"'],
                            ['will', 'would'],
                            ['can', 'could'],
                            ['must', 'had to'],
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Decalajul NU se aplică dacă afirmația e încă valabilă: „She said the Earth **is** round" rămâne la prezent, pentru că adevărul nu s-a schimbat între timp.'],

                    ['type' => 'heading', 'text' => 'Ce se mai schimbă odată cu verbul'],

                    ['type' => 'text', 'text' => 'Nu doar timpul se mută. Se mută și **persoana** și **reperele de timp și loc**, pentru că relatarea se face din alt punct de vedere și în alt moment.'],

                    [
                        'type' => 'table',
                        'caption' => 'Uitarea acestor schimbări e a doua cauză de depunctare, după timpuri.',
                        'headers' => ['Direct', 'Indirect'],
                        'rows' => [
                            ['now', 'then'],
                            ['today', 'that day'],
                            ['tomorrow', 'the next day / the following day'],
                            ['yesterday', 'the day before / the previous day'],
                            ['here', 'there'],
                            ['this', 'that'],
                        ],
                    ],

                    [
                        'type' => 'example',
                        'title' => 'Transformare completă',
                        'problem' => 'Directă: Ana said: „I am reading this book here today."',
                        'steps' => [
                            ['text' => 'Verbul coboară: „am reading" devine „was reading".'],
                            ['text' => 'Pronumele se schimbă: „I" devine „she".'],
                            ['text' => 'Reperele se mută: „this" → „that", „here" → „there", „today" → „that day".'],
                            ['text' => 'Rezultatul: Ana said (that) she was reading that book there that day.'],
                        ],
                        'result' => '„That" după verbul de relatare e opțional în engleza modernă, dar restul schimbărilor nu sunt.',
                    ],

                    ['type' => 'heading', 'text' => 'Verbul de relatare spune ce a vrut vorbitorul'],

                    ['type' => 'text', 'text' => '„Say" și „tell" sunt neutre, dar un verb mai precis arată intenția și îți ridică nota la Subiectul II: „She **suggested** going out", „He **refused** to answer", „They **admitted** making a mistake".'],

                    ['type' => 'text', 'text' => 'Atenție la construcție: **„tell" cere obiect** („he told **me** that..."), iar **„say" nu îl ia direct** („he said that...", dar „he said **to me** that..."). „He told that..." e greșit.'],

                    ['type' => 'heading', 'text' => 'Verbele modale'],

                    [
                        'type' => 'table',
                        'caption' => 'Modalele nu primesc „-s" la persoana a III-a și sunt urmate de infinitiv fără „to".',
                        'headers' => ['Modal', 'Ce exprimă', 'Exemplu'],
                        'rows' => [
                            ['must', 'Obligație internă, certitudine', 'You must be tired.'],
                            ['have to', 'Obligație venită din exterior', 'I have to wear a uniform.'],
                            ['should', 'Sfat, recomandare', 'You should see a doctor.'],
                            ['can / could', 'Abilitate, permisiune', 'She can swim.'],
                            ['may / might', 'Posibilitate incertă', 'It might rain.'],
                            ['mustn\'t', 'Interdicție', 'You mustn\'t smoke here.'],
                            ['don\'t have to', 'Absența obligației', 'You don\'t have to come.'],
                        ],
                    ],

                    [
                        'type' => 'warning',
                        'title' => '„Mustn\'t" și „don\'t have to" nu înseamnă același lucru',
                        'text' => 'Sunt opuse, nu sinonime. „You **mustn\'t** park here" = e interzis. „You **don\'t have to** park here" = poți, dar nu ești obligat. Confuzia schimbă complet sensul propoziției și e testată aproape sigur la Subiectul I.',
                    ],
                ],
            ],

            // ==================================================================
            'articole-prepozitii' => [
                'title' => 'Articole, prepoziții și acord',
                'summary' => 'Zonele în care româna te trage în direcția greșită: articolul hotărât, prepozițiile de timp și loc, acordul subiect-predicat.',
                'objectives' => [
                    'Să decizi între „a", „an", „the" și absența articolului',
                    'Să folosești corect „in", „on", „at" pentru timp și loc',
                    'Să faci acordul cu substantive care par plural, dar nu sunt',
                    'Să eviți calcurile din română care se penalizează sistematic',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Articolul: trei întrebări, în ordine'],

                    ['type' => 'text', 'text' => 'Nu memora liste. Pune-ți trei întrebări, în ordinea asta: substantivul e **numărabil**? E la **singular**? Ascultătorul știe **exact despre care** e vorba?'],

                    [
                        'type' => 'table',
                        'caption' => '„An" se alege după SUNET, nu după literă: „an hour" (h mut), „a university" (se citește „iu").',
                        'headers' => ['Situație', 'Articol', 'Exemplu'],
                        'rows' => [
                            ['Prima mențiune, numărabil singular', 'a / an', 'I saw **a** film last night.'],
                            ['Deja știm despre care e vorba', 'the', '**The** film was excellent.'],
                            ['Unic în lume', 'the', '**the** sun, **the** Internet'],
                            ['Generalizare, plural sau necontabil', 'fără articol', 'Books are expensive.'],
                            ['Nume de țări, de regulă', 'fără articol', 'She lives in Moldova.'],
                            ['Țări cu formă de plural sau „republic"', 'the', '**the** United States, **the** UK'],
                        ],
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Capcana pentru vorbitorii de română',
                        'text' => 'În română articolul se lipește de cuvânt („viața"), deci pare că e mereu acolo. În engleză, la generalizări NU se pune: „**Life** is beautiful", nu „The life is beautiful". La fel: „I like **music**", „**Children** need attention".',
                    ],

                    ['type' => 'heading', 'text' => 'In, on, at — timp'],

                    ['type' => 'text', 'text' => 'Regula se ține minte ca o pâlnie: de la mare la mic.'],

                    [
                        'type' => 'table',
                        'caption' => 'Excepții de reținut: „at night", „at the weekend" (britanic), „on Monday morning" — ziua învinge momentul.',
                        'headers' => ['Prepoziție', 'Pentru ce', 'Exemple'],
                        'rows' => [
                            ['in', 'Perioade lungi', 'in 2027, in July, in the morning'],
                            ['on', 'Zile și date', 'on Monday, on 5 June, on my birthday'],
                            ['at', 'Momente precise', 'at 7 o\'clock, at noon, at night'],
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Pentru loc, aceeași logică: **in** un spațiu închis („in the room"), **on** o suprafață („on the table"), **at** un punct sau o instituție („at the station", „at school").'],

                    ['type' => 'heading', 'text' => 'Acordul subiect-predicat'],

                    ['type' => 'text', 'text' => 'Verbul se acordă cu subiectul real, nu cu substantivul cel mai apropiat. În „The **box** of chocolates **is** on the table", subiectul e „box", nu „chocolates".'],

                    [
                        'type' => 'table',
                        'caption' => 'Substantivele din prima coloană par plural, dar cer verb la singular.',
                        'headers' => ['Cuvânt', 'Acord', 'Exemplu'],
                        'rows' => [
                            ['news', 'singular', 'The news **is** good.'],
                            ['information, advice', 'singular, fără plural', 'This information **is** useful.'],
                            ['everyone, everybody', 'singular', 'Everyone **has** arrived.'],
                            ['people', 'plural', 'People **are** waiting.'],
                            ['police', 'plural', 'The police **are** investigating.'],
                        ],
                    ],

                    [
                        'type' => 'example',
                        'title' => 'Trei calcuri frecvente din română',
                        'problem' => 'Toate trei sună natural în română și sunt greșite în engleză.',
                        'steps' => [
                            ['text' => '„I have 18 years" → corect: **I am 18 years old.** Vârsta se exprimă cu „be", nu cu „have".'],
                            ['text' => '„I am agree" → corect: **I agree.** „Agree" e verb în engleză, nu adjectiv.'],
                            ['text' => '„It depends of" → corect: **It depends on.** Prepoziția se învață odată cu verbul.'],
                        ],
                        'result' => 'Prepoziția face parte din verb și se memorează împreună cu el: depend **on**, listen **to**, wait **for**, arrive **at/in**.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Adjectivul nu are plural',
                        'text' => 'În engleză adjectivul nu se acordă niciodată: „two **beautiful** houses", nu „two beautifuls houses". E o economie față de română, dar tocmai de aceea se uită.',
                    ],
                ],
            ],

            // ==================================================================
            'vocabular-tematic' => [
                'title' => 'Vocabular tematic',
                'summary' => 'Câmpurile lexicale din programă, colocațiile care se testează, formarea cuvintelor și prietenii falși care costă puncte.',
                'objectives' => [
                    'Să folosești vocabular precis pe temele din programa ANCE',
                    'Să reții colocațiile ca blocuri, nu cuvânt cu cuvânt',
                    'Să formezi substantive și adjective din același radical',
                    'Să recunoști prietenii falși dintre română și engleză',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Temele care apar efectiv'],

                    ['type' => 'text', 'text' => 'Programa ANCE nu cere vocabular la întâmplare. Textele de la Subiectul I și temele de la Subiectul II se învârt în jurul acelorași câmpuri. Pregătește-le pe acestea, nu dicționarul.'],

                    [
                        'type' => 'table',
                        'caption' => 'Pentru fiecare temă, ține minte 10–15 cuvinte pe care le poți folosi în eseu, nu 50 pe care le recunoști pasiv.',
                        'headers' => ['Temă', 'Cuvinte-cheie'],
                        'rows' => [
                            ['Educație și carieră', 'apply for, degree, skills, training, employer, deadline, achievement'],
                            ['Mediu', 'pollution, waste, recycle, climate change, sustainable, renewable'],
                            ['Tehnologie', 'device, network, privacy, screen time, reliable, share'],
                            ['Sănătate și stil de viață', 'balanced diet, exercise, stress, habit, wellbeing'],
                            ['Relații și societate', 'community, volunteer, support, generation, tradition'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Colocațiile se învață în bloc'],

                    ['type' => 'text', 'text' => 'O colocație e o combinație fixă, fără logică de dedus. „Make a decision" e corect, „do a decision" nu — și nu există nicio regulă care să-ți spună de ce. Se memorează ca o singură unitate.'],

                    [
                        'type' => 'table',
                        'caption' => 'Regula aproximativă: „do" pentru activități și sarcini, „make" pentru lucruri care se creează sau se stabilesc. Nu e infailibilă, dar acoperă majoritatea cazurilor.',
                        'headers' => ['Verb', 'Colocații frecvente'],
                        'rows' => [
                            ['make', 'a decision, a mistake, progress, an effort, friends, a suggestion'],
                            ['do', 'homework, research, the shopping, a favour, business, your best'],
                            ['take', 'a break, a photo, part in, responsibility, an exam, place'],
                            ['have', 'breakfast, a shower, a conversation, fun, an opportunity'],
                            ['pay', 'attention, a visit, a compliment, respect'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Formarea cuvintelor'],

                    ['type' => 'text', 'text' => 'La Subiectul I apar frecvent exerciții în care primești un cuvânt și trebuie să-l transformi. Cu câteva sufixe acoperi majoritatea cazurilor.'],

                    [
                        'type' => 'table',
                        'caption' => 'Sufixul spune ce parte de vorbire obții, deci și unde se potrivește în propoziție.',
                        'headers' => ['Verb', 'Substantiv', 'Adjectiv', 'Adverb'],
                        'rows' => [
                            ['decide', 'decision', 'decisive', 'decisively'],
                            ['succeed', 'success', 'successful', 'successfully'],
                            ['inform', 'information', 'informative', '—'],
                            ['develop', 'development', 'developed', '—'],
                            ['pollute', 'pollution', 'polluted', '—'],
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Prefixele negative merită și ele reținute: **un-** (unhappy, unable), **im-** înainte de „m" și „p" (impossible, impatient), **in-** (incorrect), **ir-** înainte de „r" (irregular), **il-** înainte de „l" (illegal), **dis-** (disagree, dishonest).'],

                    ['type' => 'heading', 'text' => 'Prietenii falși'],

                    [
                        'type' => 'table',
                        'caption' => 'Seamănă cu un cuvânt din română, dar înseamnă altceva. Sunt testate tocmai pentru că par evidente.',
                        'headers' => ['Cuvânt englez', 'NU înseamnă', 'Înseamnă de fapt'],
                        'rows' => [
                            ['actually', 'actualmente', 'de fapt, în realitate'],
                            ['eventually', 'eventual', 'în cele din urmă'],
                            ['sensible', 'sensibil', 'chibzuit, rezonabil'],
                            ['library', 'librărie', 'bibliotecă'],
                            ['pretend', 'a pretinde', 'a se preface'],
                            ['support', 'a suporta (a îndura)', 'a sprijini'],
                        ],
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Sinonimul nesigur strică mai mult decât repetiția',
                        'text' => 'La Subiectul II, un cuvânt „impresionant" folosit greșit se penalizează mai tare decât repetarea unuia simplu. Scrie ce controlezi. Variază mai degrabă STRUCTURA frazelor decât vocabularul.',
                    ],
                ],
            ],

            // ==================================================================
            'registru' => [
                'title' => 'Registru și ton',
                'summary' => 'Ce deosebește un eseu formal de un mesaj către un prieten și de ce Subiectul III cere alt ton decât Subiectul II.',
                'objectives' => [
                    'Să recunoști registrul cerut de sarcina din enunț',
                    'Să eviți formele contrase și colocviale în textul formal',
                    'Să folosești formulele de deschidere și încheiere potrivite',
                    'Să păstrezi un singur registru de la început până la final',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Registrul nu e opțional, e o cerință a baremului'],

                    ['type' => 'text', 'text' => 'Enunțul îți spune întotdeauna **cui** scrii: un articol pentru revista școlii, o scrisoare către un prieten, un e-mail către o instituție. Tonul potrivit e punctat separat, iar amestecul lor e penalizat chiar dacă gramatica e impecabilă.'],

                    [
                        'type' => 'table',
                        'caption' => 'Verbele frazale (get, put off) sunt informale; echivalentele de origine latină (receive, postpone) sunt formale.',
                        'headers' => ['Trăsătură', 'Formal', 'Informal'],
                        'rows' => [
                            ['Forme contrase', 'do not, cannot, it is', 'don\'t, can\'t, it\'s'],
                            ['Verbe', 'receive, postpone, require', 'get, put off, need'],
                            ['Adresare', 'Dear Sir or Madam', 'Hi Ana, Hey'],
                            ['Încheiere', 'Yours faithfully / sincerely', 'Best, See you, Love'],
                            ['Persoană', 'It is often argued that...', 'I think, you know'],
                            ['Abrevieri', 'evitate', 'ASAP, btw, etc.'],
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Regula pentru scrisoarea formală: **„Yours faithfully"** când nu știi numele destinatarului (ai deschis cu „Dear Sir or Madam"), **„Yours sincerely"** când îl știi (ai deschis cu „Dear Mr Popescu"). Sunt asociate în pereche, nu se amestecă.'],

                    ['type' => 'heading', 'text' => 'Ce cere fiecare subiect'],

                    [
                        'type' => 'table',
                        'caption' => 'Subiectul III poate cere un articol SAU o scrisoare — citește enunțul, formatul diferă.',
                        'headers' => ['Subiect', 'Format', 'Registru'],
                        'rows' => [
                            ['Subiectul II', 'Eseu structurat, 180–200 de cuvinte', 'Formal, impersonal'],
                            ['Subiectul III', 'Articol sau scrisoare, temă de cultură', 'Semi-formal, adaptat cititorului'],
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Articolul de presă admite o întrebare retorică la început și o adresare directă către cititor. Eseul formal nu: acolo se preferă construcții impersonale („It can be argued that...", „Many people believe...").'],

                    [
                        'type' => 'example',
                        'title' => 'Aceeași idee, două registre',
                        'problem' => 'Ideea: tinerii petrec prea mult timp pe telefon.',
                        'steps' => [
                            ['text' => 'Informal: „Loads of teens are glued to their phones all day — it\'s crazy!"'],
                            ['text' => 'Formal: „A considerable number of young people spend excessive amounts of time using their mobile devices."'],
                            ['text' => 'Semi-formal, pentru articol: „Have you ever counted how many hours you spend on your phone? For many teenagers, the answer is surprising."'],
                        ],
                        'result' => 'Conținutul e identic. Diferă doar distanța față de cititor — și exact asta se punctează.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Amestecul de registru e mai grav decât o greșeală de gramatică',
                        'text' => 'Un eseu formal care începe cu „It is widely believed that..." și se încheie cu „Anyway, that\'s my opinion, thanks!" pierde puncte la coerență. Alege registrul din prima frază și ține-l până la ultima.',
                    ],
                ],
            ],

            // ==================================================================
            'spatiul-anglofon' => [
                'title' => 'Țările și instituțiile spațiului anglofon',
                'summary' => 'Cine e cine în lumea de limbă engleză: state, capitale, forme de guvernare și instituțiile care apar la Subiectul III.',
                'objectives' => [
                    'Să distingi Regatul Unit de Marea Britanie și de Anglia',
                    'Să numești capitalele reale, nu orașele cele mai cunoscute',
                    'Să descrii pe scurt instituțiile politice britanice și americane',
                    'Să ai material concret pentru un articol pe temă de cultură',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Regatul Unit, Marea Britanie, Anglia'],

                    ['type' => 'text', 'text' => 'Sunt trei lucruri diferite, iar confuzia lor e prima greșeală de conținut la Subiectul III.'],

                    [
                        'type' => 'table',
                        'caption' => 'Marea Britanie e o INSULĂ; Irlanda de Nord nu se află pe ea, dar face parte din Regatul Unit.',
                        'headers' => ['Termen', 'Ce cuprinde'],
                        'rows' => [
                            ['England', 'O singură națiune constitutivă, capitala London'],
                            ['Great Britain', 'Insula: England, Scotland, Wales'],
                            ['The United Kingdom (UK)', 'Great Britain + Northern Ireland'],
                            ['The British Isles', 'Termen geografic: UK + Republica Irlanda + insulele mici'],
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Capitalele națiunilor constitutive: **London** (England), **Edinburgh** (Scotland), **Cardiff** (Wales), **Belfast** (Northern Ireland).'],

                    ['type' => 'heading', 'text' => 'Capitale care nu sunt orașul cel mai mare'],

                    [
                        'type' => 'table',
                        'caption' => 'Coloana a treia e exact sursa greșelilor: orașul cel mai cunoscut nu e capitala.',
                        'headers' => ['Țară', 'Capitala', 'Cel mai mare oraș'],
                        'rows' => [
                            ['Australia', 'Canberra', 'Sydney'],
                            ['Canada', 'Ottawa', 'Toronto'],
                            ['New Zealand', 'Wellington', 'Auckland'],
                            ['The USA', 'Washington, D.C.', 'New York City'],
                            ['Ireland', 'Dublin', 'Dublin'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Instituțiile britanice'],

                    ['type' => 'text', 'text' => 'Regatul Unit este o **monarhie constituțională**: monarhul are un rol ceremonial, iar puterea executivă aparține primului-ministru și Cabinetului. Nu există o constituție scrisă într-un singur document.'],

                    ['type' => 'text', 'text' => 'Parlamentul de la Westminster are două camere: **House of Commons**, aleasă prin vot (650 de deputați, numiți MPs), și **House of Lords**, nealeasă, cu rol de revizuire a legilor.'],

                    ['type' => 'heading', 'text' => 'Instituțiile americane'],

                    ['type' => 'text', 'text' => 'Statele Unite sunt o **republică federală** cu 50 de state și o constituție scrisă, din 1787. Puterile sunt separate strict între executiv, legislativ și judiciar, cu un sistem de control reciproc numit *checks and balances*.'],

                    [
                        'type' => 'table',
                        'caption' => 'Senatul are câte doi senatori de stat, indiferent de populație; Camera Reprezentanților e proporțională cu populația.',
                        'headers' => ['Instituție', 'Rol', 'Componență'],
                        'rows' => [
                            ['Congress', 'Puterea legislativă', 'Senate + House of Representatives'],
                            ['Senate', 'Camera superioară', '100 de senatori (2 de stat)'],
                            ['House of Representatives', 'Camera inferioară', '435 de membri'],
                            ['The President', 'Puterea executivă', 'Ales pentru 4 ani, maximum două mandate'],
                            ['The Supreme Court', 'Puterea judiciară', '9 judecători, numiți pe viață'],
                        ],
                    ],

                    ['type' => 'text', 'text' => '**Commonwealth of Nations** reunește peste 50 de state, majoritatea foste colonii britanice. E o asociere voluntară, fără putere legislativă asupra membrilor — nu trebuie confundată cu Regatul Unit.'],

                    [
                        'type' => 'warning',
                        'title' => 'Precizia contează la Subiectul III',
                        'text' => 'Un articol care spune „Sydney, the capital of Australia" pierde puncte de conținut chiar dacă engleza e corectă. Dacă nu ești sigur de un detaliu, scrie despre altceva pe care îl stăpânești — un text corect și modest bate unul ambițios și greșit.',
                    ],
                ],
            ],

            // ==================================================================
            'patrimoniu-personalitati' => [
                'title' => 'Patrimoniu și personalități ilustre',
                'summary' => 'Monumentele și oamenii care apar cel mai des la Subiectul III, cu detaliile care fac diferența între un text generic și unul informat.',
                'objectives' => [
                    'Să descrii câteva obiective de patrimoniu din spațiul anglofon',
                    'Să asociezi personalitățile cu domeniul și contribuția lor',
                    'Să folosești detalii concrete în locul formulărilor vagi',
                    'Să construiești un paragraf de articol pe o temă culturală',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Monumente și locuri'],

                    ['type' => 'text', 'text' => 'Pentru Subiectul III nu ai nevoie de o enciclopedie. Îți trebuie **trei-patru obiective pe care le poți descrie în câte două fraze**, cu un detaliu concret fiecare.'],

                    [
                        'type' => 'table',
                        'caption' => 'Detaliul din ultima coloană transformă o enumerare într-un text informat.',
                        'headers' => ['Obiectiv', 'Unde', 'Detaliul care contează'],
                        'rows' => [
                            ['Stonehenge', 'Wiltshire, England', 'Monument neolitic, sit UNESCO'],
                            ['The Tower of London', 'London', 'Fortăreață, închisoare și tezaur al coroanei'],
                            ['The Elizabeth Tower', 'Westminster, London', '„Big Ben" e numele CLOPOTULUI, nu al turnului'],
                            ['The Statue of Liberty', 'New York', 'Dar al Franței, inaugurat în 1886'],
                            ['The Grand Canyon', 'Arizona, USA', 'Săpat de râul Colorado, parc național'],
                            ['Uluru', 'Northern Territory, Australia', 'Sit sacru pentru populația aborigenă'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Literatură'],

                    ['type' => 'text', 'text' => '**William Shakespeare** (1564–1616), născut la Stratford-upon-Avon, a scris aproximativ 38 de piese și 154 de sonete. Piesele se jucau la teatrul **The Globe** din Londra. E cel mai sigur subiect de articol: despre el se poate scrie fără riscul unei erori de fapt.'],

                    ['type' => 'text', 'text' => 'Alte nume utile: **Jane Austen** (romane despre societatea engleză de la începutul secolului al XIX-lea), **Charles Dickens** (condiția săracilor în Anglia victoriană), **George Orwell** (*1984* și *Animal Farm*, despre totalitarism).'],

                    ['type' => 'heading', 'text' => 'Știință și inovație'],

                    [
                        'type' => 'table',
                        'caption' => 'Alege una singură și scrie despre ea în profunzime, în loc să le enumeri pe toate.',
                        'headers' => ['Personalitate', 'Domeniu', 'Contribuție'],
                        'rows' => [
                            ['Isaac Newton', 'Fizică, matematică', 'Legile mișcării și gravitația universală'],
                            ['Charles Darwin', 'Biologie', 'Teoria evoluției prin selecție naturală'],
                            ['Alexander Fleming', 'Medicină', 'A descoperit penicilina, în 1928'],
                            ['Alan Turing', 'Informatică', 'Bazele calculatorului; decriptare la Bletchley Park'],
                            ['Ada Lovelace', 'Matematică', 'Considerată prima programatoare'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Societate și drepturi civile'],

                    ['type' => 'text', 'text' => '**Martin Luther King Jr.** a condus mișcarea americană pentru drepturi civile prin mijloace nonviolente; discursul „I Have a Dream" a fost rostit la Washington în 1963. **Rosa Parks**, refuzând în 1955 să cedeze locul în autobuz, a declanșat boicotul din Montgomery.'],

                    [
                        'type' => 'example',
                        'title' => 'Cum se construiește un paragraf de articol',
                        'problem' => 'Temă: un obiectiv cultural din spațiul anglofon pe care l-ai recomanda.',
                        'steps' => [
                            ['text' => 'Afirmația: „Stonehenge is one of the most remarkable prehistoric monuments in Europe."'],
                            ['text' => 'Detaliul concret: „Built more than 4,000 years ago, it consists of a circle of massive standing stones."'],
                            ['text' => 'Explicația care leagă de cititor: „What makes it fascinating is that we still do not know exactly how or why it was built."'],
                            ['text' => 'Încheierea: „For anyone interested in ancient history, it is well worth the visit."'],
                        ],
                        'result' => 'Patru fraze, un singur obiectiv, un detaliu verificabil. Exact schema idee → exemplu → explicație din lecția despre eseu.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Mai bine puțin și exact',
                        'text' => 'Baremul Subiectului III punctează relevanța conținutului, nu cantitatea de nume. O listă de zece personalități fără nicio informație despre ele valorează mai puțin decât un paragraf bine construit despre una singură. Iar o dată greșită sau o atribuire eronată se penalizează.',
                    ],
                ],
            ],
        ];
    }
}
