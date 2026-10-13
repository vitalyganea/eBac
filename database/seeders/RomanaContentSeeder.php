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
 * Conținut pentru LIMBA ȘI LITERATURA ROMÂNĂ, construit EXCLUSIV din documente
 * oficiale (§11b):
 *
 *   - programa ANCE (Chișinău 2021, ordinul MEC nr. 1499 din 04.11.2021) —
 *     arborele de competențe;
 *   - testele și baremele ANCE de bacalaureat 2021–2026, sesiunea de bază (sb)
 *     și suplimentară (ss), profil real (r) și umanist (u) — itemii.
 *
 * Fiecare item trimite la baremul din care vine (`source_ref` = PDF + item).
 * Cheia de corectare e CEEA CE ACCEPTĂ BAREMUL, nu o variantă aleasă de noi.
 *
 * Ce NU e aici, deliberat: proba de română e integral cu răspuns deschis —
 * chiar și sinonimul se cere argumentat într-un enunț. Itemii de mai jos
 * antrenează părțile care au răspuns verificabil (sinonimia contextuală,
 * sensurile expresiilor, mărcile genului, elementele punctate de barem la
 * anunț, invitație și eseu). Interpretarea, portretul moral și eseul se
 * evaluează pe barem de un om, iar proba nu se simulează (ExamBlueprintSeeder).
 *
 * Textele literare se citează doar în fragmente scurte (o propoziție), cu
 * autorul și opera, pentru analiză — nu se reproduc textele testelor.
 */
class RomanaContentSeeder extends Seeder
{
    private const ANCE = 'https://ance.gov.md/sites/default/files/';

    public function run(): void
    {
        $subject = Subject::where('slug', 'limba-romana')->firstOrFail();

        $skills = $this->seedSkills($subject->id);
        $this->seedPrerequisites($skills);
        $this->seedItems($subject->id, $skills);
        $this->retire();
    }

    /**
     * Itemi scoși din antrenament. Întrebau despre barem („câte puncte
     * primește…"), nu despre materie — la BAC nu apare o asemenea întrebare.
     * Informația s-a mutat în lecția „Cum se notează eseul și redactarea"
     * (RomanaLessonSeeder). Se retrag, nu se șterg: `attempts` îi referă (§1).
     */
    private function retire(): void
    {
        $refs = [
            self::ance('u_sb25', 9)['ref'],
            self::ance('u_ss24', 15)['ref'],
            self::ance('u_sb25', 'redactare')['ref'],
        ];

        Item::whereIn('source_ref', $refs)
            ->where('status', '!=', ItemStatus::Retired)
            ->each(fn (Item $item) => $item->update(['status' => ItemStatus::Retired]));
    }

    /** @return array<string, Skill> */
    private function seedSkills(int $subjectId): array
    {
        // Codurile urmează competențele specifice din programă: 3 — lectura și
        // interpretarea textelor, 4 — producerea textelor scrise, 5 — normele
        // limbii române literare. Ponderile aproximează testul real din 2026.
        $tree = [
            ['limba-in-dinamica', 'Limba română în dinamică', 'LLR.C5', 0,
                'Competența specifică 5 din programă: lexicologie, stilistică funcțională.', [
                    ['sinonimie-contextuala', 'Sinonimia contextuală', 'LLR.5.1', 4,
                        'Itemul 1 al fiecărui test ANCE. Sinonimul respectă partea de vorbire și sensul din context.'],
                    ['frazeologie', 'Expresii și locuțiuni', 'LLR.5.2', 3, null],
                    ['polisemie', 'Sensurile cuvântului: polisemie și sens figurat', 'LLR.5.3', 5, null],
                    ['stiluri-functionale', 'Stilurile funcționale', 'LLR.5.4', 5, null],
                ]],
            ['cititorul-si-opera', 'Cititorul și opera', 'LLR.C3', 0,
                'Competența specifică 3 din programă: lectura și interpretarea textelor literare.', [
                    ['genuri-literare', 'Genul literar: epic, liric, dramatic', 'LLR.3.3', 6,
                        'Argumentarea apartenenței la gen: raționament + exemplu din text.'],
                    ['rezumat', 'Rezumatul', 'LLR.3.2', 5, null],
                ]],
            ['comunicarea-scrisa', 'Comunicarea scrisă', 'LLR.C4', 0,
                'Competența specifică 4 din programă: producerea textelor scrise.', [
                    ['scriere-functionala', 'Scrierea funcțională: anunț, invitație', 'LLR.4.1', 9, null],
                    ['eseu', 'Eseul argumentativ și redactarea', 'LLR.4.2', 44,
                        'Eseul (24 p) și redactarea (20 p) cer evaluare pe barem, nu se corectează automat.'],
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

    /** @param array<string, Skill> $s */
    private function seedPrerequisites(array $s): void
    {
        $edges = [
            // Un sens figurat se recunoaște după ce stăpânești sensul din context.
            ['polisemie', 'sinonimie-contextuala', 0.6],
            ['frazeologie', 'polisemie', 0.6],
            // Eseul cere referințe la texte literare și vocabular precis.
            ['eseu', 'genuri-literare', 0.6],
            ['eseu', 'sinonimie-contextuala', 0.4],
            ['eseu', 'stiluri-functionale', 0.4],
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
                    'difficulty_seed' => $data['difficulty'] ?? 2,
                    'points' => $data['points'] ?? 1,
                    'language' => 'ro',
                    'status' => ItemStatus::Published,
                    'source' => $data['source'],
                    'curriculum_code' => $skill->curriculum_code,
                    'is_exam_holdout' => false,
                ],
            );

            $item->skills()->syncWithoutDetaching([$skill->id => ['is_primary' => true, 'weight' => 1.0]]);

            ItemStat::updateOrCreate(['item_id' => $item->id], [
                'rating' => 900 + ($data['difficulty'] ?? 2) * 150,
            ]);
        }
    }

    /**
     * Documentul ANCE: `r_sb26` → baremul de la profil real, sesiunea de bază 2026.
     *
     * @return array{source: string, ref: string, label: string}
     */
    private static function ance(string $code, int|string $item, string $suffix = ''): array
    {
        [$profile, $session] = explode('_', $code);
        $year = '20'.substr($session, 2);
        $file = "12_llro_barem_{$code}.pdf";

        return [
            'source' => "ance:12_llro_barem_{$code}",
            'ref' => self::ANCE.$file.'#'.(is_int($item) ? "item{$item}" : $item).$suffix,
            'label' => sprintf(
                'BAC %s, sesiunea %s, profil %s, %s',
                $year,
                str_starts_with($session, 'sb') ? 'de bază' : 'suplimentară',
                $profile === 'r' ? 'real' : 'umanist',
                is_int($item) ? "itemul {$item}" : $item,
            ),
        ];
    }

    /**
     * „Rescrie, din lista propusă, un sinonim contextual adecvat" — itemul 1
     * din testele ANCE. Lista e cea din test, cheia e șirul acceptat de barem.
     *
     * @param  list<string>  $list
     * @param  list<string>  $accepted
     */
    private static function fromList(string $code, string $word, string $quote, string $author,
        array $list, array $accepted, string $clue): array
    {
        $doc = self::ance($code, 1);
        $choices = [];
        $correct = [];

        foreach ($list as $i => $option) {
            $key = chr(ord('a') + $i);
            $choices[] = ['key' => $key, 'text' => $option];
            if (in_array($option, $accepted, true)) {
                $correct[] = $key;
            }
        }

        return [
            'ref' => $doc['ref'],
            'source' => $doc['source'],
            'skill' => 'sinonimie-contextuala',
            'type' => ItemType::Mcq,
            'difficulty' => 3,
            'payload' => [
                'passage' => "„{$quote}”\n— {$author}",
                'stem' => "Alege, din lista propusă, un sinonim contextual adecvat pentru «{$word}».",
                'shuffle' => false,
                'choices' => $choices,
            ],
            'answer' => ['correct' => $correct, 'any' => true],
            'hints' => [
                "Nu căuta sensul de dicționar al cuvântului «{$word}», ci pe cel din propoziția dată: înlocuiește-l pe rând și vezi dacă fraza își păstrează sensul.",
                $clue,
                'Baremul acceptă aici: '.implode(', ', array_slice($accepted, 0, 2)).' și încă '.(count($accepted) - 2).' din listă.',
            ],
            'explanation' => sprintf(
                'Baremul ANCE (%s) acceptă: %s. Celelalte cuvinte din listă sunt sinonime ale lui «%s» în ALTE contexte — de aceea lista e o capcană. La examen, sinonimul se argumentează într-un enunț cu referire la text (încă 2–3 puncte), iar baremul admite și alt cuvânt din listă dacă argumentarea e plauzibilă.',
                $doc['label'], implode(', ', $accepted), $word,
            ),
        ];
    }

    /**
     * „Scrie câte un sinonim contextual pentru fiecare dintre cuvintele
     * evidențiate" — perechile vin din „variante acceptabile" ale baremului.
     *
     * @param  list<array{0: string, 1: string, 2: string}>  $pairs  [cuvânt, context, sinonim din barem]
     */
    private static function synonymPairs(string $code, string $author, array $pairs, string $clue): array
    {
        $doc = self::ance($code, 1);
        $left = $right = $answer = [];

        foreach ($pairs as $i => [$word, $context, $synonym]) {
            $left[] = ['key' => "l{$i}", 'text' => "{$word} — „{$context}”"];
            $right[] = ['key' => "r{$i}", 'text' => $synonym];
            $answer["l{$i}"] = "r{$i}";
        }

        return [
            'ref' => $doc['ref'],
            'source' => $doc['source'],
            'skill' => 'sinonimie-contextuala',
            'type' => ItemType::Match,
            'difficulty' => 2,
            'points' => count($pairs),
            'payload' => [
                'stem' => "Potrivește fiecare cuvânt din text cu sinonimul lui contextual. ({$author})",
                'left' => $left,
                'right' => $right,
            ],
            'answer' => ['pairs' => $answer],
            'hints' => [
                'Sinonimul respectă partea de vorbire: un adjectiv se înlocuiește cu un adjectiv, un verb la trecut cu un verb la trecut.',
                $clue,
                'Înlocuiește cuvântul în citat cu fiecare variantă: doar una păstrează și sensul, și forma gramaticală.',
            ],
            'explanation' => sprintf(
                'Baremul ANCE (%s) acceptă: %s. Notă din barem: „Sinonimele vor respecta categoriile gramaticale ale părților de vorbire cărora le aparțin.”',
                $doc['label'],
                implode('; ', array_map(fn ($p) => "{$p[0]} – {$p[2]}", $pairs)),
            ),
        ];
    }

    /**
     * Expresii / sensuri: perechile [unitate, sens] sunt textual cele din barem.
     *
     * @param  list<array{0: string, 1: string}>  $pairs
     */
    private static function meaningPairs(string $code, int $item, string $skill, string $stem,
        array $pairs, array $hints, string $note): array
    {
        $doc = self::ance($code, $item);
        $left = $right = $answer = [];

        foreach ($pairs as $i => [$unit, $meaning]) {
            $left[] = ['key' => "l{$i}", 'text' => $unit];
            $right[] = ['key' => "r{$i}", 'text' => $meaning];
            $answer["l{$i}"] = "r{$i}";
        }

        return [
            'ref' => $doc['ref'],
            'source' => $doc['source'],
            'skill' => $skill,
            'type' => ItemType::Match,
            'difficulty' => 2,
            'points' => count($pairs),
            'payload' => ['stem' => $stem, 'left' => $left, 'right' => $right],
            'answer' => ['pairs' => $answer],
            'hints' => $hints,
            'explanation' => "Sensurile sunt cele din baremul ANCE ({$doc['label']}). {$note}",
        ];
    }

    /**
     * Alegere simplă. `$correct` = cheile acceptate (una sau mai multe, `any`).
     *
     * @param  list<string>  $options
     * @param  list<int>  $correct  indicii variantelor corecte
     */
    private static function choice(array $doc, string $skill, string $stem, array $options, array $correct,
        array $hints, string $explanation, int $difficulty = 2, ?string $passage = null): array
    {
        $choices = [];
        foreach ($options as $i => $text) {
            $choices[] = ['key' => chr(ord('a') + $i), 'text' => $text];
        }

        $keys = array_map(fn (int $i) => chr(ord('a') + $i), $correct);

        return [
            'ref' => $doc['ref'],
            'source' => $doc['source'],
            'skill' => $skill,
            'type' => ItemType::Mcq,
            'difficulty' => $difficulty,
            'payload' => array_filter([
                'passage' => $passage,
                'stem' => $stem,
                'shuffle' => true,
                'choices' => $choices,
            ], fn ($v) => $v !== null),
            'answer' => count($keys) > 1 ? ['correct' => $keys, 'any' => true] : ['correct' => $keys],
            'hints' => $hints,
            'explanation' => $explanation,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function items(): array
    {
        return [
            ...$this->fromListItems(),
            ...$this->synonymItems(),
            ...$this->expressionItems(),
            ...$this->senseItems(),
            ...$this->genreAndStyleItems(),
            ...$this->writingItems(),
        ];
    }

    // ==================== SINONIMIE: „din lista propusă" ====================

    private function fromListItems(): array
    {
        return [
            self::fromList('u_sb21', 'blând', 'Cu blând miros de rai, e Poezia.', 'Vasile Voiculescu, Poezia',
                ['blajin', 'bun', 'plăcut', 'domol', 'delicat', 'mângâietor', 'amabil', 'dulce', 'duios'],
                ['dulce', 'bun', 'mângâietor', 'plăcut'],
                'Aici «blând» descrie un MIROS, nu un om. «Blajin» sau «amabil» se spun despre oameni.'),
            self::fromList('u_ss21', 'blând', 'Puterea nopții blând însenina-vei', 'Mihai Eminescu, Sonet III',
                ['blajin', 'bun', 'plăcut', 'domol', 'delicat', 'mângâietor', 'amabil', 'dulce', 'duios', 'temperat', 'moderat'],
                ['plăcut', 'domol', 'moderat'],
                'Aici «blând» arată CUM se face acțiunea (însenina): lin, fără violență — nu o trăsătură de caracter.'),
            self::fromList('u_sb22', 'a plăsmui',
                'Pare-se, și-au spus savanții șireți, s-ar putea plăsmui o mașină care, în genere, să îndeplinească funcțiile creierului omenesc, să cugete...',
                'George Meniuc',
                ['a crea', 'a produce', 'a făuri', 'a alcătui', 'a înfăptui', 'a inventa', 'a născoci', 'a scorni', 'a falsifica', 'a contrazice'],
                ['a crea', 'a inventa', 'a produce'],
                'Obiectul e o MAȘINĂ construită de savanți. «A născoci» și «a scorni» au nuanța de minciună, nu de invenție tehnică.'),
            self::fromList('u_ss22', 'brusc',
                'Schimbarea s-a produs brusc, într-o singură generație, și a luat forma unei adevărate răsturnări.',
                'Arcadie Suceveanu',
                ['aspru', 'iute', 'răstit', 'neașteptat', 'neprevăzut', 'subit', 'precipitat', 'deodată', 'fulgerător', 'instantaneu', 'brutal'],
                ['neașteptat', 'neprevăzut', 'deodată'],
                'Ce se întâmplă brusc aici e o SCHIMBARE de-a lungul unei generații — deci nu „aspru", nici „instantaneu", ci fără să fi fost anticipată.'),
            self::fromList('u_sb23', 'cucerise',
                'Îi cucerise pe tineri cu armoniile vii, cu lumina cerului însorit și culorile peisajelor de la noi',
                'Veronica D. Niculescu, Luchian. Ochii, sufletul, mâna',
                ['capturase', 'ocupase', 'fermecase', 'cuprinsese', 'încântase', 'supusese', 'atrăsese', 'câștigase', 'captivase', 'fascinase'],
                ['fermecase', 'încântase', 'fascinase'],
                'Pictorul cucerește prin CULORI și lumină, nu prin forță: alege un verb al admirației, nu unul militar.'),
            self::fromList('u_ss23', 'deslușit',
                'Ceva greu de deslușit o separa de turcii aceștia în costume de seară',
                'Simona Antonescu, Maria Tănase: o fântână pe un drum secetos',
                ['distins', 'deosebit', 'recunoscut', 'observat', 'văzut', 'conturat', 'limpezit', 'lămurit', 'clarificat', 'edificat', 'înțeles', 'explicat'],
                ['recunoscut', 'observat', 'văzut', 'limpezit', 'lămurit', 'clarificat', 'înțeles'],
                '„Greu de deslușit" = greu de PERCEPUT sau de ÎNȚELES. «Distins» și «deosebit» sunt sinonime ale adjectivului „deslușit" (clar), nu ale verbului de aici.'),
            self::fromList('r_sb24', 'mărire',
                'Sunt modest pentru că nu urmăresc mărire, ci valoare; nu câștig, ci calitate.',
                'Cosmin Perța, Labiș. Jurnalul pierdut',
                ['extindere', 'dezvoltare', 'faimă', 'cinste', 'elogiu', 'amplificare', 'laudă', 'rang', 'grandoare'],
                ['faimă', 'cinste', 'elogiu', 'laudă', 'rang'],
                'Fraza opune „mărire" lui „valoare" și vorbește despre modestie: e vorba de glorie și poziție socială, nu de creștere în dimensiuni.'),
            self::fromList('u_sb24', 'să trăim',
                'Nu se cuvine, Nichita, să trăim numai cu durerile.',
                'Dumitru Matcovschi, Pomul vieții',
                ['să locuim', 'să ființăm', 'să dăinuim', 'să durăm', 'să existăm', 'să fim', 'să petrecem', 'să coabităm', 'să viețuim'],
                ['să existăm', 'să ființăm', 'să viețuim'],
                'Nu e vorba de un loc („să locuim") sau de durata în timp („să dăinuim"), ci de felul în care îți duci viața.'),
            self::fromList('r_ss24', 'tare',
                'De-ar fi tare! Cu logica lui, cu intelectul lui…',
                'Aureliu Busuioc, Singur în fața dragostei',
                ['voinic', 'rezistent', 'viguros', 'puternic', 'energic', 'dârz', 'curajos', 'nemilos', 'rău', 'neclintit', 'tăios'],
                ['dârz', 'puternic', 'neclintit', 'rezistent'],
                'Tăria de aici e una de CARACTER, sprijinită pe logică și intelect — nu forță fizică („voinic", „viguros") și nici duritate („nemilos").'),
            self::fromList('u_ss24', 'ursită',
                'În loc să-i dedic ursitei mele o poezie de dragoste, i-am dedicat una patriotică.',
                'Dumitru Matcovschi, Același decor provincial, același pian de dincolo de perete',
                ['soartă', 'destin', 'iubită', 'mireasă', 'fatalitate', 'logodnică'],
                ['logodnică', 'mireasă', 'iubită'],
                'Cui îi dedici „o poezie de dragoste"? Unei persoane, nu sorții.'),
            self::fromList('r_sb25', 'interes',
                'Îmi petreceam timpul liber citind, iar interesul pentru astronomie, fizică și matematică deviase într-o obsesie cronică',
                'Oleg Serebrian, Pe contrasens',
                ['preocupare', 'avantaj', 'înclinație', 'grijă', 'atracție', 'câștig', 'curiozitate', 'beneficiu'],
                ['preocupare', 'înclinație', 'atracție', 'curiozitate'],
                '„Interes pentru astronomie" e o pasiune intelectuală. «Avantaj», «câștig» și «beneficiu» sunt sinonimele lui „interes" în sens bănesc.'),
            self::fromList('r_ss25', 'stârneau',
                'Copila nu uita niciodată întrebările ce stârneau tăceri învățătorului său, pentru că bănuia în spatele lor secrete',
                'Simona Antonescu, Chiajna din Casa Mușatinilor',
                ['provocau', 'mișcau', 'produceau', 'izbucneau', 'cauzau', 'inventau', 'agitau', 'generau'],
                ['provocau', 'produceau', 'cauzau', 'generau'],
                'Întrebările au drept EFECT tăcerea învățătorului: caută un verb tranzitiv al cauzei. «Izbucneau» nu poate avea complement.'),
        ];
    }

    // ==================== SINONIMIE: perechi din barem ====================

    private function synonymItems(): array
    {
        return [
            // 2021: „Rescrie, din textul propus, câte un sinonim adecvat" —
            // sinonimele se caută ÎN poezie, nu se propun.
            [
                ...self::synonymPairs('r_sb21', 'Grigore Vieru, Această ramură', [
                    ['menține', 'Suflarea ta mă ține!', 'ține'],
                    ['ancestralele', 'Aud străvechile porunci.', 'străvechile'],
                    ['învățături', 'Aud străvechile porunci.', 'porunci'],
                    ['fine', 'Cu ale tale urme dulci.', 'dulci'],
                ], 'Toate patru sinonimele sunt cuvinte DIN poezie — chiar din versurile citate.'),
                'payload' => [
                    'stem' => 'Testul ANCE cere sinonimele din textul poeziei (Grigore Vieru, Această ramură). Potrivește fiecare cuvânt dat cu sinonimul lui din vers.',
                    'left' => [
                        ['key' => 'l0', 'text' => 'menține'],
                        ['key' => 'l1', 'text' => 'ancestralele'],
                        ['key' => 'l2', 'text' => 'învățături'],
                        ['key' => 'l3', 'text' => 'fine'],
                    ],
                    'right' => [
                        ['key' => 'r0', 'text' => 'ține („Suflarea ta mă ține!”)'],
                        ['key' => 'r1', 'text' => 'străvechile („Aud străvechile porunci.”)'],
                        ['key' => 'r2', 'text' => 'porunci („Aud străvechile porunci.”)'],
                        ['key' => 'r3', 'text' => 'dulci („Cu ale tale urme dulci.”)'],
                    ],
                ],
            ],
            self::synonymPairs('r_ss21', 'Vasile Romanciuc', [
                ['curată', 'Iubirea ta mi-e aripă curată', 'neprihănită'],
                ['singura', 'Iubirea este singura comoară', 'unica'],
                ['comoară', 'Iubirea este singura comoară', 'tezaur'],
                ['lumii', 'În suflet, porți căldura lumii, toată', 'omenirii'],
            ], '«Curată» descrie iubirea, deci are sens moral: fără pată, fără păcat.'),
            self::synonymPairs('r_sb22', 'Liviu Rebreanu, Ion', [
                ['povesteau', 'își povesteau pățaniile', 'istoriseau'],
                ['zăpăcea', 'lumea aceasta îl zăpăcea și-i răcea speranțele', 'dezorienta'],
                ['rostea', 'Nici unul nu rostea un cuvânt despre vreun ideal superior.', 'pronunța'],
                ['dezvălui', 'De-abia acasă își dezvălui nedumerirea', 'destăinui'],
            ], 'Atenție la timp și persoană: «dezvălui» e la perfectul simplu, deci și sinonimul.'),
            self::synonymPairs('r_ss22', 'Octavian Soviany, Năluca', [
                ['a purtat-o', 'cariera actoricească a purtat-o pe scenele cele mai diverse', 'a dus-o'],
                ['s-o urmeze', 'începe s-o urmeze şi în viaţa de toate zilele', 's-o însoțească'],
                ['fals', 'jocul ei ar rămâne fals şi artificios', 'denaturat'],
                ['grandoarea', 'are ceva din grandoarea unui destin', 'măreția'],
            ], 'Formele cu pronume (-o) se păstrează în sinonim: „a purtat-o" → „a dus-o".'),
            self::synonymPairs('r_sb23', 'Horia Lovinescu, Moartea unui artist', [
                ['rodnice', 'nu sunt rodnice decât atunci când știi bine ce ai de spus', 'productive'],
                ['convenabilă', 'Vreau să-ți fac o propunere. Convenabilă pentru amândoi sper.', 'avantajoasă'],
                ['cumplit', 'Doctorul mi-a interzis să lucrez deocamdată, și asta mă deprimă cumplit.', 'groaznic'],
                ['să realizez', 'Vreau să realizez proiectul Zburătorul.', 'să înfăptuiesc'],
            ], '«Cumplit» determină aici un verb (mă deprimă CUM?), deci are valoare de adverb.'),
            self::synonymPairs('r_ss23', 'Camil Petrescu, Jocul ielelor', [
                ['hotărât', 'Veneam de la Paris hotărât să te iau înapoi', 'decis'],
                ['năuc', 'Asta era imaginea către care alergam năuc', 'zăpăcit'],
                ['neapărat', 'unde spuneai că trebuie să te duci neapărat', 'negreșit'],
                ['încercările', 'nu simți că peste toate încercările ea durează?', 'greutățile'],
            ], '«Încercările» are aici sensul de probe ale vieții, nu de tentative.'),
            self::synonymPairs('u_sb25', 'Ioana Pârvulescu, Aurul pisicii', [
                ['tainică', 'să ne ocupăm de partea tainică a orașului', 'misterioasă'],
                ['nervos', 'El, care se ocupa cu imunitatea, dimpotrivă, era extrem de nervos.', 'iritat'],
                ['dezlănțuire', 'era pur și simplu o dezlănțuire de culori, de petale și de verdeață', 'explozie'],
            ], '«Dezlănțuire» e un substantiv: sinonimul trebuie să fie tot substantiv.'),
            self::synonymPairs('u_ss25', 'Mircea Eliade, Nuntă în cer', [
                ['strivitoare', 'munca devenea strivitoare', 'copleșitoare'],
                ['tăcută', 'Se întorcea tăcută, așeza tacâmurile', 'discretă'],
                ['cerceta', 'mă cerceta din prag', 'scruta'],
            ], '«Strivitoare» descrie o muncă grea, apăsătoare — nu ceva care zdrobește la propriu.'),
            self::synonymPairs('r_sb26', 'George Cornilă, Hasdeu: duhuri', [
                ['impunătoare', 'Clădirile impunătoare din calcar orbitor', 'maiestuoase'],
                ['mânioși', 'trăsuri cu vizitii mânioși', 'furioși'],
                ['strălucit', 'fusese chiar un elev strălucit', 'eminent'],
            ], '«Strălucit» se spune aici despre un ELEV: e vorba de rezultate, nu de lumină.'),
            self::synonymPairs('r_ss26', 'Veronica D. Niculescu, Toți copiii librăresei', [
                ['adunați', 'am văzut norii adunați la orizont', 'strânși'],
                ['am convenit', 'am convenit să ne spunem pe nume', 'am stabilit'],
                ['continuu', 'un vâjâit continuu, ca o amenințare cumplită', 'neîntrerupt'],
            ], '«Am convenit» e o înțelegere între doi oameni: sinonimul păstrează persoana I plural.'),
        ];
    }

    // ==================== EXPRESII ȘI LOCUȚIUNI ====================

    private function expressionItems(): array
    {
        $hints = [
            'Expresia are un sens de ansamblu, diferit de suma cuvintelor: nu o traduce cuvânt cu cuvânt.',
            'Imaginează-ți o propoziție în care ai folosi expresia — sensul iese din situație.',
            'Începe cu expresia pe care o știi sigur și elimină sensul ei din listă.',
        ];
        $note = 'La examen se cere un enunț dezvoltat care ilustrează sensul; enunțul în care expresia e folosită cu alt sens nu se punctează.';

        $doc = self::ance('u_sb25', 3);

        return [
            self::meaningPairs('r_sb21', 3, 'frazeologie', 'Potrivește fiecare locuțiune sau expresie cu sensul ei.', [
                ['dintr-o suflare', 'foarte repede'],
                ['a fi de bună credință', 'a fi sincer'],
                ['a fi cu noroc', 'a fi favorizat de împrejurări în acțiunile întreprinse'],
                ['de când e lumea și pământul', 'dintotdeauna'],
            ], $hints, $note),
            self::meaningPairs('r_ss21', 3, 'frazeologie', 'Potrivește fiecare locuțiune sau expresie cu sensul ei.', [
                ['a-și pune sufletul (pentru cineva)', 'a fi în stare de orice sacrificiu pentru cineva'],
                ['a pune într-o lumină bună', 'a scoate în evidență aspectele pozitive din viața sau din activitatea cuiva'],
                ['a zbura cu gândul', 'a se gândi la cineva sau la ceva'],
                ['a fi stăpân pe sine', 'a fi în stare să-și stăpânească sentimentele, pornirile; a-și ține firea'],
            ], $hints, $note),
            self::meaningPairs('r_sb22', 2, 'frazeologie', 'Potrivește fiecare locuțiune sau expresie cu sensul ei.', [
                ['cu orice preț', 'în orice condiții'],
                ['a lua parte', 'a participa'],
                ['a-și face un rost în viață', 'a se aranja'],
                ['a avea însemnătate', 'a fi valoros'],
            ], $hints, $note),
            self::meaningPairs('r_ss22', 2, 'frazeologie', 'Potrivește fiecare locuțiune sau expresie cu sensul ei.', [
                ['în egală măsură', 'la fel, în mod egal'],
                ['a avea grijă', 'a tutela, a păzi'],
                ['a face carieră', 'a reuși într-o profesie'],
                ['a face teatru', 'a se preface'],
            ], $hints, $note),
            // Distractorii sunt sensurile OFICIALE ale altor expresii din
            // baremele ANCE — nu inventăm definiții.
            self::choice(['source' => $doc['source'], 'ref' => $doc['ref'].'-a'], 'frazeologie',
                'Ce înseamnă expresia «a scoate la iveală»?',
                ['a face cunoscut; a dezvălui', 'a-i face cuiva pe plac', 'a se preface', 'a se gândi la cineva sau la ceva'],
                [0],
                ['„Iveală" e înrudit cu „a ivi" — a apărea, a se arăta.', 'Ce se întâmplă cu un lucru ascuns când e scos la iveală?', 'Devine cunoscut tuturor.'],
                "Baremul ANCE ({$doc['label']}): a scoate la iveală = a face cunoscut; a dezvălui. Celelalte variante sunt sensurile altor expresii din baremele ANCE: a-i intra cuiva în voie, a face teatru, a zbura cu gândul."),
            self::choice(['source' => $doc['source'], 'ref' => $doc['ref'].'-b'], 'frazeologie',
                'Ce înseamnă expresia «a-i intra cuiva în voie»?',
                ['a-i face cuiva (toate) gusturile, a-i face pe plac', 'a face cunoscut; a dezvălui', 'a fi în stare de orice sacrificiu pentru cineva', 'a participa'],
                [0],
                ['„Voie" înseamnă aici dorință, plac.', 'Cine îți „intră în voie" îți face ce vrei tu.', 'E sensul de a face pe plac cuiva.'],
                "Baremul ANCE ({$doc['label']}): a-i intra cuiva în voie = a-i face cuiva (toate) gusturile, a-i face pe plac. Celelalte variante sunt sensurile altor expresii din baremele ANCE."),
        ];
    }

    // ==================== SENSURI: polisemie, sens figurat ====================

    private function senseItems(): array
    {
        $platou = self::ance('r_sb26', 2);

        return [
            self::meaningPairs('r_ss22', 3, 'polisemie',
                'Potrivește fiecare cuvânt cu un sens FIGURAT al lui.', [
                    ['scenă', 'ceartă, ieșire violentă; întâmplare interesantă'],
                    ['poezie', 'farmec, frumusețe, încântare'],
                    ['școală', 'izvor, sursă de cunoștințe, de învățături'],
                ], [
                    'Sensul figurat pleacă de la cel propriu: scena de teatru, poezia ca text, școala ca instituție.',
                    'Ce păstrează fiecare sens figurat din cel propriu? Teatrul de pe scenă, frumusețea poeziei, învățătura din școală.',
                    '„A face o scenă" = a face o ceartă; „poezia unui peisaj" = farmecul lui; „școala vieții" = sursa de învățătură.',
                ], 'La examen se cere câte un enunț care ilustrează sensul figurat; dacă enunțul e corect, dar cu sensul propriu, se acordă doar 1 punct din 2.'),
            self::meaningPairs('r_sb22', 3, 'polisemie',
                'Potrivește fiecare cuvânt cu un alt sens al lui decât cel din textul lui Liviu Rebreanu (Ion).', [
                    ['masă', 'mobilă formată dintr-o placă sprijinită pe unul sau pe mai multe picioare, pe care se mănâncă, se scrie'],
                    ['vis', 'aspirație irealizabilă; dorință arzătoare'],
                    ['spectacol', 'ansamblu de elemente, de lucruri, de fapte care atrag privirile sau atenția, care impresionează'],
                ], [
                    'Un cuvânt polisemantic are mai multe sensuri înrudite. Caută pentru fiecare cuvânt sensul care i se potrivește.',
                    '„Vis" poate fi ce vezi în somn, dar și ce îți dorești foarte mult.',
                    'Un „spectacol" nu e doar pe scenă: un apus poate fi un spectacol.',
                ], 'La examen, enunțul care folosește cuvântul cu sensul din text, chiar corect construit, primește doar 1 punct din 2.'),
            self::meaningPairs('r_sb24', 2, 'polisemie',
                'Potrivește fiecare cuvânt cu unul dintre sensurile lui din dicționar.', [
                    ['energie', 'hotărâre și perseverență în atitudini și în acțiuni'],
                    ['rol', 'partitură scenică ce revine unui actor într-o piesă de teatru'],
                    ['motiv', 'idee fundamentală sau temă principală a unei opere literare'],
                ], [
                    'Toate trei au și sensuri tehnice: energia din fizică, rolul dintr-un registru, motivul dintr-o piesă muzicală.',
                    'Care dintre ele ține de teatru? Care de literatură? Care de caracterul unui om?',
                    '„Motivul" e și termen de teorie literară — îl vei folosi la itemul despre motivul literar.',
                ], 'Baremul dă pentru fiecare cuvânt mai multe sensuri, din care elevul ilustrează unul diferit de cel din text.'),
            self::meaningPairs('r_sb25', 2, 'polisemie',
                'Potrivește fiecare cuvânt cu un sens FIGURAT al lui.', [
                    ['școală', 'curent, mișcare științifică, literară, artistică care grupează în jurul ei numeroși adepți'],
                    ['a citi', 'a descoperi, a sesiza gândul, sentimentele ascunse ale cuiva din atitudinea sau din expresia figurii sale'],
                    ['inimă', 'sediu al sentimentelor umane'],
                ], [
                    'Gândește-te la expresii: „a citi pe chipul cuiva", „a face școală", „a-l durea inima".',
                    'Care sens privește sentimentele? Care privește un grup de adepți?',
                    '„Școala ardeleană" e un curent; „i-am citit tristețea în ochi" — a citi figurat.',
                ], 'Baremul acceptă și sensurile figurate din locuțiuni și expresii.'),
            self::choice($platou, 'polisemie',
                'Care dintre următoarele NU este un sens al substantivului «platou»?',
                [
                    'sul de lemn pentru călcat pe care se înfășoară rufele',
                    'podiș',
                    'tavă mare de porțelan sau de metal pe care se aduc mâncărurile la masă',
                    'încăpere special amenajată pentru filmare într-un studio cinematografic',
                ],
                [0],
                [
                    'Un cuvânt polisemantic are sensuri înrudite: caută-l pe cel care nu are nimic comun cu „suprafață plană".',
                    'Podișul, tava și studioul de filmare sunt toate suprafețe sau spații plane.',
                    'Sulul pentru rufe e un sens al cuvântului «rol» — din alt barem ANCE (2024).',
                ],
                "Baremul ANCE ({$platou['label']}) dă patru sensuri pentru «platou»: podiș; dispozitiv plan pe care se fixează piese; tavă mare; încăpere pentru filmare. „Sul de lemn pentru călcat rufele\" e un sens al cuvântului «rol» (baremul ANCE 2024, profil real, itemul 2).",
                3),
        ];
    }

    // ==================== GEN LITERAR ȘI STIL FUNCȚIONAL ====================

    private function genreAndStyleItems(): array
    {
        $dramatic = self::ance('u_sb24', 13);
        $epic = self::ance('r_sb26', 8);
        $stil = self::ance('r_sb26', 3);

        return [
            self::choice($dramatic, 'genuri-literare',
                'Care dintre următoarele este un argument pentru apartenența unui text la genul DRAMATIC?',
                [
                    'replici precedate de numele personajelor',
                    'indicații scenice',
                    'prezența unui narator care relatează întâmplările',
                    'perspectiva narativă adoptată',
                ],
                [0, 1],
                [
                    'Textul dramatic e scris pentru a fi jucat pe scenă.',
                    'Cine „povestește" într-o piesă de teatru? Nimeni — personajele vorbesc direct.',
                    'Naratorul și perspectiva narativă sunt mărci ale genului EPIC.',
                ],
                "Baremul ANCE ({$dramatic['label']}) acceptă: mod de expunere direct, prin replici; replici precedate de numele personajelor; indicații scenice; conflict dramatic. Naratorul și perspectiva narativă apar în baremele pentru genul EPIC."),
            self::choice($epic, 'genuri-literare',
                'Care dintre următoarele este un argument pentru apartenența unui text la genul EPIC?',
                [
                    'narator și perspectivă narativă',
                    'coordonate temporale și spațiale ale evenimentelor narate',
                    'replici precedate de numele personajelor',
                    'indicații scenice',
                ],
                [0, 1],
                [
                    'Genul epic POVESTEȘTE: cineva relatează o întâmplare.',
                    'O întâmplare are loc undeva și cândva.',
                    'Replicile cu numele personajelor și indicațiile scenice sunt mărci ale genului DRAMATIC.',
                ],
                "Baremul ANCE ({$epic['label']}) acceptă ca argumente: narator, perspectivă narativă, acțiune, personaj, relația dintre personaje, fir narativ, coordonate temporale și spațiale. La examen, fiecare argument se susține cu un exemplu din text."),
            self::choice(self::ance('u_sb24', 13, '-fragment'), 'genuri-literare',
                'Cărui gen literar îi aparține fragmentul?',
                ['genul dramatic', 'genul epic', 'genul liric', 'text de graniță (eseu)'],
                [0],
                [
                    'Uită-te la felul în care e scris: cine vorbește și cum aflăm asta?',
                    'Fiecare replică începe cu numele personajului.',
                    'Replicile precedate de numele personajelor sunt marca genului dramatic.',
                ],
                "Fragmentul e din Pomul vieții de Dumitru Matcovschi, iar baremul ANCE ({$dramatic['label']}) cere tocmai argumentarea apartenenței la genul dramatic: expunere directă prin replici, replici precedate de numele personajelor, indicații scenice, conflict.",
                1,
                "GRIGORE: Care este vina mea, s-o știu?\nNICHITA: Pe mata nimeni nu te învinuiește de nimic.\n— Dumitru Matcovschi, Pomul vieții"),
            self::choice($stil, 'stiluri-functionale',
                'În ce stil funcțional se încadrează fragmentul?',
                ['stilul beletristic (artistic)', 'stilul științific', 'stilul publicistic', 'stilul oficial-administrativ'],
                [0],
                [
                    'Întreabă-te care e scopul textului: să informeze exact, să reglementeze sau să impresioneze?',
                    'Observă imaginile: „calcar orbitor", „cerul sleit".',
                    'Figurile de stil și exprimarea originală sunt marca stilului artistic.',
                ],
                "Baremul ANCE ({$stil['label']}): stilul beletristic/artistic, cu trăsăturile: diversitatea și expresivitatea limbajului, bogăția figurilor de stil, structurile sintactice expresive, originalitatea exprimării. La examen, fiecare trăsătură se ilustrează cu un citat.",
                1,
                "„Clădirile impunătoare din calcar orbitor, bisericile înalte până la cerul sleit, străzile pavate pline de trăsuri cu vizitii mânioși...”\n— George Cornilă, Hasdeu: duhuri"),
            self::choice(self::ance('r_sb26', 3, '-trasaturi'), 'stiluri-functionale',
                'Care dintre următoarele este o trăsătură a stilului beletristic?',
                [
                    'bogăția figurilor de stil',
                    'terminologia de specialitate și exprimarea neutră',
                    'formulele fixe și exprimarea standardizată a actelor',
                    'informația de actualitate adresată publicului larg',
                ],
                [0],
                [
                    'Stilul beletristic e stilul literaturii.',
                    'Ce găsești într-o poezie sau într-un roman și NU găsești într-un manual de fizică?',
                    'Metafore, epitete, comparații — figurile de stil.',
                ],
                'Baremul ANCE (BAC 2026, sesiunea de bază, profil real, itemul 3) numește ca trăsături ale stilului beletristic: diversitatea și expresivitatea limbajului, bogăția figurilor de stil, structurile sintactice expresive, originalitatea exprimării. Celelalte variante descriu stilul științific, oficial-administrativ și publicistic.'),
        ];
    }

    // ==================== SCRIERE FUNCȚIONALĂ, REZUMAT, ESEU ====================

    private function writingItems(): array
    {
        $anunt21 = self::ance('r_sb21', 'subiectul-III');
        $anunt26 = self::ance('u_ss26', 11);
        $invitatie26 = self::ance('u_sb26', 11);
        $invitatie25 = self::ance('r_sb25', 10);
        $rezumat = self::ance('r_sb23', 3);

        return [
            self::choice($anunt21, 'scriere-functionala',
                'Cu ce începe textul unui anunț?',
                ['cu denumirea documentului — cuvântul ANUNȚ', 'cu o formulă de adresare („Stimați colegi,")', 'cu data și semnătura', 'cu scopul evenimentului'],
                [0],
                [
                    'Gândește-te la titlul textului.',
                    'Cititorul trebuie să știe din primul cuvânt ce fel de text are în față.',
                    'Formula de adresare ține de invitație, nu de anunț.',
                ],
                'Baremul ANCE 2021 punctează „elaborarea unui anunț, cu plasarea cuvântului respectiv în startul textului”, iar baremele din 2022–2026 dau 1 punct pentru denumirea documentului.'),
            self::choice($anunt26, 'scriere-functionala',
                'Legenda: Cătălin/Cătălina Popescu, Liceul Teoretic „Marin Preda” din Râșcani, organizează cu Consiliul elevilor un atelier de discuție pe tema „Biblioteca – spațiu al cunoașterii”, în Sala de festivități a liceului, pe 11 august 2026, la ora 10:00. Ce element obligatoriu lipsește din anunțul de mai jos?',
                ['timpul desfășurării', 'denumirea documentului', 'locul desfășurării', 'nominalizarea organizatorilor', 'semnătura'],
                [0],
                [
                    'Un anunț complet are: denumirea documentului, a evenimentului, timpul, locul, organizatorii, marginea și alineatele, semnătura.',
                    'Bifează în anunț fiecare element din listă.',
                    'Când are loc atelierul?',
                ],
                "Baremul ANCE ({$anunt26['label']}) dă câte 1 punct pentru: denumirea documentului, denumirea evenimentului, timpul, locul, organizatorii, respectarea marginii și a alineatelor, semnătura — 7 puncte. Anunțul nu spune data și ora, deși legenda le dă.",
                2,
                "ANUNȚ\nConsiliul elevilor al Liceului Teoretic „Marin Preda” din orașul Râșcani organizează atelierul de discuție „Biblioteca – spațiu al cunoașterii”. Atelierul se va desfășura în Sala de festivități a liceului.\nVă așteptăm!\nCătălin Popescu"),
            self::choice($invitatie26, 'scriere-functionala',
                'Legenda: Gabriel/Gabriela Iorga, clasa a XI-a, Liceul Teoretic „Tudor Arghezi” din Ungheni, organizează în liceu activitatea „Inima mea e Moldova”, în vederea promovării valorilor și a identității naționale, pe 20 iunie 2026, la ora 15.00. Ce element obligatoriu lipsește din invitația de mai jos?',
                ['scopul activității', 'formula de adresare', 'timpul', 'semnătura'],
                [0],
                [
                    'O invitație completă are: formula de adresare, activitatea, scopul, locul, timpul, marginea și alineatele, semnătura.',
                    'Legenda spune nu doar CE se organizează, ci și DE CE.',
                    'Pentru ce se organizează activitatea? Invitația nu spune.',
                ],
                "Baremul ANCE ({$invitatie26['label']}) dă câte 1 punct pentru: formula de adresare, activitate, scopul evenimentului, loc, timp, margine și alineate, semnătură. Invitația omite scopul: promovarea valorilor și a identității naționale.",
                2,
                "INVITAȚIE\nDragi colegi,\nVă invit la activitatea „Inima mea e Moldova”, care va avea loc pe 20 iunie 2026, la ora 15.00, în incinta Liceului Teoretic „Tudor Arghezi”.\nVă aștept cu drag!\nGabriela Iorga"),
            self::choice($invitatie25, 'scriere-functionala',
                'Ce element e obligatoriu într-o INVITAȚIE, dar nu și într-un anunț?',
                ['formula de adresare', 'indicarea locului', 'semnătura', 'respectarea marginii și a alineatelor'],
                [0],
                [
                    'Compară cui se adresează fiecare: anunțul e pentru oricine îl citește, invitația — pentru cineva anume.',
                    'Cum începe o invitație după titlu?',
                    '„Dragi colegi," sau „Stimate domnule..." — formula de adresare.',
                ],
                "Baremul ANCE ({$invitatie25['label']}) dă „1 punct pentru prezența formulei de adresare”; baremele pentru anunț punctează în schimb denumirea documentului. Locul, semnătura și marginile se punctează la ambele."),
            self::choice($rezumat, 'rezumat',
                'Sarcina: „Rezumă, în trei enunțuri, esența dialogului.” Cum trebuie scris rezumatul?',
                [
                    'ideile esențiale, fără citate, cu pronume și verbe la persoana a III-a',
                    'ideile esențiale, cu citatele-cheie din dialog',
                    'redarea dialogului la persoana I, ca în text',
                    'ideile esențiale urmate de opinia proprie',
                ],
                [0],
                [
                    'Rezumatul spune pe scurt CE se întâmplă, cu vorbele tale.',
                    'Un citat e vorba altcuiva — are loc într-un rezumat?',
                    'Rezumatul se scrie ca o relatare despre alții: el/ea, ei.',
                ],
                "Baremul ANCE ({$rezumat['label']}): 2 puncte pentru redarea ideilor esențiale fără citate, 1 punct pentru pronume și verbe la persoana a III-a, 1 punct pentru coerență, 1 punct pentru respectarea limitei de întindere."),
            self::choice(self::ance('u_ss24', 15, '-structura'), 'eseu',
                'Ce face ca un eseu argumentativ să fie bine structurat?',
                [
                    'conectori logici, structurare în alineate, aspect grafic îngrijit',
                    'introducere, cuprins și încheiere de lungimi egale',
                    'un motto și un titlu propriu',
                    'citate din critica literară',
                ],
                [0],
                [
                    'Sunt trei elemente: unul ține de limbaj, două de felul în care arată pagina.',
                    'Ce face ca un text să se citească drept un raționament legat, pe idei separate?',
                    'Cuvintele care leagă ideile (în primul rând, prin urmare), alineatele și scrisul îngrijit.',
                ],
                'Baremul ANCE (BAC 2024, sesiunea suplimentară, profil umanist, itemul 15): „utilizarea conectorilor specifici/logici – 1 punct; structurarea în alineate – 1 punct; aspectul grafic îngrijit/lizibil – 1 punct”. Din 2026, la profilul real, structura cere în plus o concluzie corelată cu argumentele.',
                3),
            self::choice(self::ance('u_ss24', 15, '-referinta'), 'eseu',
                'Ce trebuie să conțină referința la un text literar într-un eseu argumentativ?',
                ['titlul, autorul și o referință directă la text', 'doar titlul și autorul', 'un rezumat al operei', 'o opinie a unui critic literar'],
                [0],
                [
                    'Sunt trei elemente.',
                    'Două dintre ele identifică opera. Al treilea arată că o cunoști.',
                    'Referința directă: o situație, un personaj sau un citat din text.',
                ],
                'Baremul ANCE (BAC 2024, sesiunea suplimentară, profil umanist, itemul 15): 6 (3 + 3) puncte pentru referința la două texte din literatura română „(titlu, autor, referinţă directă la text)”.',
                2),
        ];
    }
}
