<?php

namespace Database\Seeders\Istorie;

use App\Enums\ItemType;

/**
 * Istoria, din testele ANCE 2024–2026 (§11b): sarcinile „Identifică în sursă",
 * singurele din test cu răspuns verificabil — baremul enumeră răspunsurile
 * acceptate.
 *
 * Restul testului (explicarea unui termen, compararea opiniilor, harta,
 * eseul de la Subiectul IV) cere răspuns argumentat, corectat pe barem, și
 * nu se transformă în grile.
 *
 * Sursa e cea din test, citată pe scurt — doar fraza sau frazele de care are
 * nevoie sarcina — cu autorul și lucrarea, ca în test. Variantele corecte sunt
 * „răspunsurile posibile" din barem. Variantele greșite sunt afirmații care
 * NU apar în fragment, de obicei adevărate în altă parte: exact capcana pe
 * care o testează „în sursă" — răspunzi din text, nu din memorie.
 */
final class Ance
{
    private const ANCE = 'https://ance.gov.md/sites/default/files/';

    /** @return list<array<string, mixed>> */
    public static function items(): array
    {
        return [...self::real(), ...self::umanist()];
    }

    /** @return array{source: string, ref: string, label: string} */
    private static function doc(string $code, string $item): array
    {
        [$profile, $session] = explode('_', $code);
        $file = "12_ist_barem_{$profile}_ro_{$session}";

        return [
            'source' => "ance:{$file}",
            'ref' => self::ANCE."{$file}.pdf#item{$item}",
            'label' => sprintf(
                'BAC 20%s, sesiunea %s, profil %s, itemul %s',
                substr($session, 2),
                str_starts_with($session, 'sb') ? 'de bază' : 'suplimentară',
                $profile === 'r' ? 'real' : 'umanist',
                $item,
            ),
        ];
    }

    /**
     * @param  list<string>  $correct  răspunsurile posibile din barem
     * @param  list<string>  $wrong  afirmații care nu apar în fragment
     * @param  list<string>  $hints
     */
    private static function ident(array $doc, string $skill, string $stem, string $passage,
        array $correct, array $wrong, array $hints, string $explanation): array
    {
        $options = [...$correct, ...$wrong];

        return [
            'ref' => $doc['ref'],
            'source' => $doc['source'],
            'skill' => $skill,
            'type' => ItemType::Mcq,
            'points' => 2,
            'difficulty' => 2,
            'payload' => [
                'passage' => $passage,
                'stem' => $stem.' (La examen se scriu două; aici alege unul.)',
                'shuffle' => true,
                'choices' => array_map(
                    fn (int $i, string $text) => ['key' => chr(ord('a') + $i), 'text' => $text],
                    array_keys($options), $options,
                ),
            ],
            'answer' => [
                'correct' => array_map(fn (int $i) => chr(ord('a') + $i), array_keys($correct)),
                'any' => true,
            ],
            'hints' => $hints,
            'explanation' => "{$explanation} (Baremul ANCE, {$doc['label']}.)",
        ];
    }

    private static function real(): array
    {
        return [
            self::ident(self::doc('r_sb24', 'I.2'), 'epoca-moderna',
                'Identifică în sursă o caracteristică a monarhiei absolute.',
                "„În Anglia, Franţa și Spania s-a dezvoltat o monarhie cu noi caracteristici: centrul unei extinse birocraţii, motor al unei reţele financiare şi organizatoare a unei puternice armate. Acestă formă de guvernare centralizată a fost denumită «monarhie absolută», şi în cadrul ei regele avea din ce în ce mai mult rolul de arbitru…”\n— Enciclopedie de istorie universală, DeAgostini",
                ['centrul unei extinse birocrații', 'organizatoare a unei puternice armate', 'guvernare centralizată'],
                ['puterea regelui limitată de un parlament ales', 'regele ales de adunarea nobililor'],
                [
                    'Caută în fragment enumerarea care urmează după „noi caracteristici".',
                    'Sursa numește trei roluri ale monarhiei, apoi forma de guvernare.',
                    'Variantele despre parlament și alegerea regelui descriu altceva decât absolutismul.',
                ],
                'Baremul acceptă: centrul unei extinse birocrații; motor al unei rețele financiare; organizatoare a unei puternice armate; guvernare centralizată; regele ca arbitru.'),

            self::ident(self::doc('r_sb24', 'II.1'), 'epoca-moderna',
                'Identifică în sursă un motiv invocat de autor pentru declararea independenței.',
                "„În stare de război, cu legăturile rupte, ce suntem? Suntem independenți; suntem națiune de sine stătătoare. Avem domn de sine stătător.”\n— Mihail Kogălniceanu, Discurs în Adunarea Deputaților, 9 mai 1877",
                ['starea de război', 'existența unui domn de sine stătător'],
                ['sprijinul marilor puteri europene', 'unirea cu Transilvania'],
                [
                    'Motivele sunt chiar primele cuvinte ale discursului.',
                    'Kogălniceanu pornește de la situația în care se află țara: legăturile sunt rupte.',
                    'Ce situație rupe legăturile? Și ce fel de domn are țara?',
                ],
                'Baremul acceptă: starea de război; domnul de sine stătător; legăturile sui-generis.'),

            self::ident(self::doc('r_sb24', 'II.2'), 'epoca-moderna',
                'Selectează din sursă o caracteristică a relațiilor româno-otomane dinaintea războiului.',
                "„Erau niște legături sui-generis, niște legături care erau slabe când românii erau tari; niște legături care erau tari când românii erau slabi.”\n— Mihail Kogălniceanu, Discurs în Adunarea Deputaților, 9 mai 1877",
                ['legături slabe când românii erau tari', 'legături tari când românii erau slabi'],
                ['o alianță militară între state egale', 'relații comerciale exclusive cu Imperiul Otoman'],
                [
                    'Autorul descrie legăturile printr-o opoziție.',
                    'Puterea legăturilor depinde de cât de tari sunt românii.',
                    'Variantele despre alianță și comerț nu apar în fragment.',
                ],
                'Baremul acceptă între altele: legături care erau slabe când românii erau tari; legături care erau tari când românii erau slabi.'),

            self::ident(self::doc('r_ss24', 'I.2'), 'antichitate',
                'Identifică în surse un factor al romanizării.',
                "„…începură a se întemeia în câmpie orașele romane […]. Drumuri clădite cu atâta temei […] au înlesnit pătrunderea elementelor de colonizare romană în toate părțile acestei provincii.”\n— I. Lupaș, Istoria unirii românilor\n\n„Școlile romane erau unul dintre factorii importanți în procesul de romanizare. În școli se învăța latina, limba oficială a administrației, a comerțului și a armatei.”\n— Academia Română, Istoria românilor",
                ['orașele romane', 'coloniștii romani', 'școlile romane', 'limba latină'],
                ['migrația slavilor', 'retragerea aureliană'],
                [
                    'Factorii romanizării sunt cei care aduc civilizația romană în Dacia.',
                    'Caută în fragmente ce construiesc și ce aduc romanii.',
                    'Migrația slavilor și retragerea aureliană vin după, iar fragmentul nu le pomenește.',
                ],
                'Baremul acceptă: orașele romane, coloniștii romani, administrația romană, școlile romane, limba latină.'),

            self::ident(self::doc('r_ss24', 'II.1'), 'ev-mediu',
                'Identifică în sursă un motiv invocat de autor pentru solicitarea ajutorului contra dușmanilor creștinătății.',
                "„…păgânul împărat al turcilor și-a pus în plan să se răzbune și el însuși în persoană cu toată puterea sa să vie în luna mai asupra noastră și să supună țara noastră care e poarta tuturor creștinilor…”\n— Scrisoarea lui Ștefan cel Mare către principii creștini, 25 ianuarie 1475",
                ['sultanul și-a pus în plan să se răzbune', 'țara e poarta tuturor creștinilor'],
                ['dorința de a cuceri Constantinopolul', 'conflictul cu Polonia'],
                [
                    'Ștefan explică de ce are nevoie de ajutor acum, în 1475.',
                    'Ce plănuiește sultanul după înfrângere?',
                    'Și de ce i-ar privi pe toți creștinii soarta Moldovei?',
                ],
                'Baremul acceptă: victoria asupra armatei otomane; planul sultanului de a se răzbuna; țara ca „poartă a tuturor creștinilor".'),

            self::ident(self::doc('r_sb25', 'I.2'), 'epoca-moderna',
                'Identifică în sursă o prerogativă a monarhului absolutist.',
                "„Regele este împărţitorul unui număr nesfârşit de slujbe, demnităţi, sarcini, bunuri ecleziastice, gratificaţii, daruri […]. Totul depinde de el: pacea şi războiul, impozitele şi tributurile […]. Pe scurt, regele este stăpân absolut, niciun consiliu, niciun magistrat nu poate să-i limiteze puterea.”\n— Raportul ambasadorului Veneției Michele Soriano, 1581",
                ['împarte slujbe, demnități și onoruri', 'decide pacea și războiul, impozitele și tributurile', 'niciun consiliu nu-i poate limita puterea'],
                ['este ales de Adunarea Stărilor', 'împarte puterea legislativă cu parlamentul'],
                [
                    'O prerogativă e un drept exclusiv al regelui.',
                    'Fragmentul spune ce „depinde de el".',
                    'Alegerea regelui și împărțirea puterii contrazic chiar ultima frază.',
                ],
                'Baremul acceptă: împarte slujbe, demnități, daruri și onoruri; de el depind pacea și războiul, impozitele, administrația; niciun consiliu nu-i poate limita puterea.'),

            self::ident(self::doc('r_sb25', 'II.1'), 'epoca-moderna',
                'Identifică o dorință națională a țării formulată în hotărârea Adunării ad-hoc.',
                "„1. Respectarea drepturilor principatelor și îndeosebi a autonomiei lor […];\n2. Unirea principatelor într-un singur stat cu numele România;\n3. Prinț străin, cu moștenirea tronului […];\n4. Neatârnarea pământului principatelor;\n5. Putere legiuitoare încredințată unei Adunări Obștești […].”\n— Hotărârea Adunării ad-hoc a Moldovei, 1857",
                ['unirea principatelor într-un singur stat — România', 'respectarea drepturilor și a autonomiei principatelor', 'prinț străin, cu moștenirea tronului'],
                ['păstrarea celor două principate separate', 'alegerea unui domn pământean pe viață'],
                [
                    'Hotărârea are dorințele numerotate de la 1 la 5.',
                    'Al doilea punct numește chiar statul care urma să se formeze.',
                    'Al treilea punct cere un prinț străin, nu unul pământean.',
                ],
                'Baremul acceptă dorințele enumerate în hotărâre: respectarea drepturilor principatelor, unirea lor într-un stat numit România, prinț străin cu moștenirea tronului, neatârnarea pământului, adunare legiuitoare.'),

            self::ident(self::doc('r_ss25', 'I.2'), 'antichitate',
                'Identifică în sursa B o cauză a cuceririi regatului dacilor de Imperiul Roman.',
                "„Cucerirea regatului dacilor de pe malul stâng al Dunării a avut drept scop regularizarea graniței prin suprimarea intrândului pe care îl forma fluviul în imperiu; dar se gândea mai ales la minele de aur din Carpați și la tezaurul dacilor…”\n— Larousse, Istoria universală, 2009",
                ['regularizarea graniței (cauză strategică)', 'minele de aur și tezaurul dacilor (cauză economică)'],
                ['răspândirea creștinismului', 'apărarea Romei de huni'],
                [
                    'Sursa vorbește despre un „dublu imperativ".',
                    'Unul privește granița, celălalt bogățiile.',
                    'Creștinismul și hunii nu apar în fragment.',
                ],
                'Baremul acceptă: cauza strategică — regularizarea graniței; cauza economică — minele de aur din Carpați și tezaurul dacilor.'),

            self::ident(self::doc('r_ss25', 'II.1'), 'ev-mediu',
                'Identifică un factor care a favorizat crearea primului imperiu al Europei medievale.',
                "„Formarea și recunoașterea primului imperiu al Europei medievale se datorează unui șir de factori: susținerea lui Carol cel Mare de către Biserică, nobilime și cavaleri, slăbirea influenței papei de la Roma, lărgirea substanțială a teritoriului Regatului Francilor și, bineînțeles, personalitatea lui Carol cel Mare…”\n— Pavel Cocârlă, De la Clovis la Richelieu, 2007",
                ['susținerea Bisericii, a nobilimii și a cavalerilor', 'lărgirea teritoriului Regatului Francilor', 'personalitatea lui Carol cel Mare'],
                ['alianța cu Imperiul Bizantin', 'cucerirea Angliei'],
                [
                    'Factorii sunt enumerați imediat după cele două puncte.',
                    'Unul privește sprijinul intern, altul teritoriul, altul pe Carol însuși.',
                    'Bizanțul și Anglia nu apar în fragment.',
                ],
                'Baremul acceptă: susținerea Bisericii, nobilimii și cavalerilor; slăbirea influenței papei; lărgirea teritoriului; personalitatea lui Carol cel Mare; concentrarea pământului în mâinile dinastiei.'),

            self::ident(self::doc('r_sb26', 'I.2'), 'ev-mediu',
                'Identifică în sursa B o formă de manifestare a regimului de suzeranitate otomană în Țările Române.',
                "„…pentru prima dată otomanii au impus în scaunul Moldovei candidatul lor. […] La Tighina a fost construită o cetate din piatră […], formându-se și o raia otomană […]. Dacă până la 1538 [haraciul] era un preț al răscumpărării păcii […], de acum înainte devine un semn al supunerii în fapt. Au fost limitate considerabil funcțiile domniei pe arena externă.”\n— Ion Eremia, Istoria românilor. Epoca medievală, 2003",
                ['impunerea domnului de către otomani', 'raiaua otomană de la Tighina', 'haraciul — semn al supunerii în fapt', 'limitarea funcțiilor domniei pe arena externă'],
                ['transformarea Moldovei în pașalâc', 'interzicerea credinței ortodoxe'],
                [
                    'Suzeranitatea limitează independența țării fără s-o desființeze.',
                    'Caută ce au impus, ce au construit și ce au schimbat otomanii.',
                    'Moldova nu a devenit pașalâc, iar fragmentul nu vorbește despre religie.',
                ],
                'Baremul acceptă: impunerea domnului; cetatea și raiaua de la Tighina; haraciul ca semn al supunerii în fapt; limitarea funcțiilor domniei pe arena externă.'),

            self::ident(self::doc('r_sb26', 'II.1'), 'epoca-moderna',
                'Identifică o acțiune întreprinsă de Napoleon în plan cultural și educațional.',
                "„El a stabilit o putere fermă, a rezolvat problemele financiare, a organizat armata; a dat un impuls dezvoltării comerțului, producției, artei și științelor; a ridicat din nou altare; a instaurat o nouă ordine în școli…”\n— Discursul deputatului François Joubert, 2 mai 1804",
                ['a dat impuls dezvoltării artei și științelor', 'a ridicat din nou altare', 'a instaurat o nouă ordine în școli'],
                ['a organizat armata', 'a rezolvat problemele financiare'],
                [
                    'Fragmentul enumeră acțiuni din domenii diferite.',
                    'Alege doar ce ține de cultură, religie și educație.',
                    'Armata și finanțele apar în sursă, dar țin de alte domenii.',
                ],
                'Baremul acceptă: impulsul dat artei și științelor; ridicarea din nou a altarelor; noua ordine în școli. Armata și finanțele sunt acțiuni din alte domenii.'),

            self::ident(self::doc('r_ss26', 'I.2'), 'antichitate',
                'Identifică în sursa A un grup etnic apărut în rezultatul romanizării.',
                "„Asimilarea elementului autohton este rezultatul, pretutindeni, al colonizării și romanizării, apărând astfel sinteze etnice romanice: daco-romanii, galo-romanii, ibero-romanii.”\n— M. Bărbulescu, D. Deletant, K. Hitchins ș.a., Istoria României",
                ['daco-romanii', 'galo-romanii', 'ibero-romanii'],
                ['slavii', 'traco-geții'],
                [
                    'Grupurile sunt „sinteze etnice romanice".',
                    'Fiecare nume combină populația locală cu romanii.',
                    'Slavii sunt migratori, iar traco-geții sunt populația dinainte de romanizare.',
                ],
                'Baremul acceptă: daco-romanii, galo-romanii, ibero-romanii.'),

            self::ident(self::doc('r_ss26', 'II.1'), 'epoca-moderna',
                'Identifică în sursă legea considerată de Ludovic al XIV-lea universală pentru a guverna statul.',
                "„Voinţa sa este ca acela care s-a născut supus să asculte fără discernământ şi această lege, aşa de directă şi de universală: Oricât de rău va fi un prinţ, infinit mai criminală este revolta supuşilor săi.”\n— Ludovic al XIV-lea, Memorii, 1661",
                ['„Oricât de rău va fi un prinț, infinit mai criminală este revolta supușilor săi"'],
                ['„Nu vă lăsați guvernați; fiți stăpânul"', '„Ascultați, consultați Consiliul, dar decideți"'],
                [
                    'Caută în text cuvântul „lege".',
                    'Legea e citată imediat după două puncte.',
                    'Celelalte două sunt sfaturi din aceeași lucrare, nu „legea universală".',
                ],
                'Baremul: „Oricât de rău va fi un prinț, infinit mai criminală este revolta supușilor săi."'),
        ];
    }

    private static function umanist(): array
    {
        return [
            self::ident(self::doc('u_sb24', 'II.1'), 'epoca-moderna',
                'Identifică în sursă un domeniu de activitate al lui Dimitrie Cantemir.',
                "„Lui îi aparţine pionieratul în scrierea unor lucrări fundamentale de istorie politică, socială şi spirituală a Principatului Moldav şi Imperiului Otoman […]. Cele menţionate ne permit să-l considerăm pe Dimitrie Cantemir unul dintre fondatorii ştiinţei etnografice europene. […] savantul-enciclopedist a adus concomitent o contribuţie inestimabilă la dezvoltarea culturii muzicale a Imperiului Otoman.”\n— Victor Țvircun, revista „Akademos”, nr. 4, 2008",
                ['istoria Moldovei și a Imperiului Otoman', 'etnografia', 'cultura muzicală'],
                ['fondarea primei universități din Moldova', 'reformarea armatei moldovenești'],
                [
                    'Fragmentul descrie trei contribuții ale savantului.',
                    'Una ține de istorie, una de popoare și obiceiuri, una de muzică.',
                    'Universitatea și armata nu apar în fragment.',
                ],
                'Baremul acceptă: scrierea lucrărilor de istorie a Moldovei și a Imperiului Otoman; fondarea științei etnografice europene; contribuția la cultura muzicală a Imperiului Otoman.'),

            self::ident(self::doc('u_ss24', 'II.1'), 'antichitate',
                'Identifică în sursă un popor cu care a luptat Burebista.',
                "„…i-a învins pe celți/gali la fel ca marele general și politician roman. Burebista a apărat frontierele tuturor dacilor, oprind marea migrație celtică/galică, luptând cu bastarnii și supunând coloniile grecești de la Marea Neagră…”\n— Mădălina Strechie, Buletinul Universității Naționale de Apărare „Carol I”, 2022",
                ['celții (galii)', 'bastarnii', 'grecii din coloniile de la Marea Neagră'],
                ['romanii lui Traian', 'slavii'],
                [
                    'Caută verbele de luptă: „a învins", „luptând", „supunând".',
                    'După fiecare verb e numit un popor.',
                    'Traian a cucerit Dacia peste un secol și jumătate; slavii au venit mult mai târziu.',
                ],
                'Baremul acceptă: celții/galii, bastarnii, coloniile grecești.'),

            self::ident(self::doc('u_sb25', 'II.1'), 'antichitate',
                'Identifică în sursă o funcție deținută de Pericle.',
                "„…atenienii nu s-au plictisit să-l tot aleagă […] din 467 până în 429, în cele mai înalte funcţii (strateg, lider politic); şi i-au recunoscut, în calitatea sa de strategos autokrator, mai multă putere decât îi acorda Constituţia.”\n— Pericle — omul care a dat numele unei epoci, historia.ro",
                ['strateg', 'lider politic', 'strategos autokrator'],
                ['rege al Atenei', 'consul'],
                [
                    'Funcțiile sunt scrise în paranteză și după „în calitatea sa de".',
                    'Atena era o democrație, nu o monarhie.',
                    'Consulul e o funcție romană.',
                ],
                'Baremul acceptă: strateg, lider politic, strategos autokrator.'),

            self::ident(self::doc('u_sb25', 'II.2'), 'antichitate',
                'Selectează din sursă aportul lui Pericle în domeniul militar sau judiciar.',
                "„Pericle a introdus în armată «solda», pentru ca chemarea sub arme să nu însemne pentru cei săraci ruinarea familiei; şi un mic stipendiu pentru juraţii din tribunale, tocmai pentru ca această funcţie delicată să nu rămână un monopol al bogaţilor.”\n— Pericle — omul care a dat numele unei epoci, historia.ro",
                ['a introdus solda în armată', 'a introdus un stipendiu pentru jurații din tribunale'],
                ['a desființat tribunalele populare', 'a creat armata permanentă a Spartei'],
                [
                    'Fragmentul are o măsură pentru armată și una pentru tribunale.',
                    'Ambele sunt plăți.',
                    'Sparta era rivala Atenei, iar tribunalele nu au fost desființate.',
                ],
                'Baremul: solda introdusă în armată; stipendiul pentru jurații din tribunale.'),

            self::ident(self::doc('u_ss25', 'II.1'), 'epoca-moderna',
                'Identifică în sursă o universitate fondată de Alexandru Ioan Cuza.',
                "„Înfiinţarea celor două Universităţi, la Iaşi în 1860 şi la Bucureşti în 1864, au avut un rol fundamental…”\n— Marius Diaconescu, Alexandru Ioan Cuza, părinte fondator al României moderne, historia.ro",
                ['Universitatea din Iași (1860)', 'Universitatea din București (1864)'],
                ['Academia Mihăileană din Iași', 'Universitatea din Cernăuți'],
                [
                    'Fragmentul dă și orașele, și anii.',
                    'Două orașe, câte unul în fiecare principat.',
                    'Academia Mihăileană e mai veche (1835), iar Cernăuțiul era în Imperiul Austriac.',
                ],
                'Baremul: universitățile de la Iași (1860) și București (1864).'),

            self::ident(self::doc('u_sb26', 'I.2'), 'epoca-moderna',
                'Identifică, în baza sursei A, un grup de coloniști veniți în Basarabia după anul 1812.',
                "„Guvernul rusesc a început să-l populeze (sudul Basarabiei) cu nemți, bulgari, ucraineni, francezi, elvețieni. Alte naționalități au fost admise să se așeze în orașe […]. Ele sunt următoarele: greci, armeni și evrei.”\n— Alexandru V. Boldur, Istoria Basarabiei, vol. II, Chișinău, 2016",
                ['nemții', 'bulgarii', 'grecii', 'armenii'],
                ['moldovenii', 'românii din Transilvania'],
                [
                    'Coloniștii sunt cei aduși de guvernul rus.',
                    'Fragmentul îi enumeră în două liste.',
                    'Moldovenii erau populația băștinașă, nu coloniști.',
                ],
                'Baremul acceptă: nemți, bulgari, ucraineni, francezi, elvețieni, greci, armeni, evrei.'),

            self::ident(self::doc('u_sb26', 'II.1'), 'antichitate',
                'Identifică, în sursă, o funcție deținută de Iulius Caesar.',
                "„În anul 73 î.e.n. a devenit membru al Colegiului Pontifilor, iar din anul următor a servit ca tribun militar în armata lui Crassus (…). În anul 69 î.e.n. (…) a obţinut funcţia de chestor în Hispania […] a câştigat mandatul pentru 5 ani de guvernator în trei provincii…”\n— Alexandru Bogdan, Caesar — omul poporului",
                ['membru al Colegiului Pontifilor', 'tribun militar', 'chestor în Hispania', 'guvernator'],
                ['împărat al Romei', 'rege al Romei'],
                [
                    'Fragmentul urmărește cariera lui Caesar an de an.',
                    'Fiecare an aduce o nouă funcție.',
                    'Caesar a fost dictator, nu împărat; Roma nu mai avea regi.',
                ],
                'Baremul acceptă: membru al Colegiului Pontifilor, tribun militar, chestor în Hispania, guvernator în trei provincii.'),

            self::ident(self::doc('u_sb26', 'II.2'), 'antichitate',
                'Stabilește, în baza sursei, o realizare în domeniul militar a lui Iulius Caesar.',
                "„…şi-a demonstrat calităţile militare în luptele cu galicienii şi lusitanii din Hispania. (…) Caeser a cucerit Gallia […]. A revizuit calendarul roman […]. S-a preocupat de achiziţionarea grânelor şi a distribuit pământuri pentru veteranii săi.”\n— Alexandru Bogdan, Caesar — omul poporului",
                ['luptele cu galicienii și lusitanii din Hispania', 'cucerirea Galliei'],
                ['revizuirea calendarului roman', 'distribuirea de pământuri veteranilor'],
                [
                    'Fragmentul amestecă realizări din mai multe domenii.',
                    'Alege doar ce ține de război.',
                    'Calendarul e o reformă culturală, iar pământurile — o măsură socială.',
                ],
                'Baremul acceptă: calitățile militare din luptele din Hispania; cucerirea Galliei; serviciul ca tribun militar.'),

            self::ident(self::doc('u_ss26', 'I.2'), 'antichitate',
                'Identifică, în baza sursei A, o reformă realizată de regele Burebista.',
                "„Regatul lui Burebista a fost […] prin realizările în plan intern (reforma administrativă, militară, monetară, religioasă, legislativă etc.) și în plan extern, cel mai puternic din Europa rămasă în afara granițelor romane.”\n— Ion Horațiu Crișan, SCIVA, t. 31, nr. 2, 1980",
                ['reforma administrativă', 'reforma militară', 'reforma monetară', 'reforma religioasă'],
                ['reforma agrară', 'reforma electorală'],
                [
                    'Reformele sunt enumerate în paranteză.',
                    'Toate sunt „realizări în plan intern".',
                    'Reforma agrară și cea electorală țin de epoca modernă.',
                ],
                'Baremul acceptă: reforma administrativă, militară, monetară, religioasă, legislativă.'),

            self::ident(self::doc('u_ss26', 'II.1'), 'ev-mediu',
                'Identifică, în sursă, o mănăstire ctitorită de Ștefan cel Mare.',
                "„…a ctitorit biserici și mănăstiri (Putna, Voroneț, Neamț, Căpriana etc.)…”\n— Ioan-Aurel Pop, Ioan Bolovan, Istoria României. Compendiu, 2007",
                ['Putna', 'Voroneț', 'Neamț', 'Căpriana'],
                ['Curtea de Argeș', 'Cozia'],
                [
                    'Mănăstirile sunt enumerate în paranteză.',
                    'Toate sunt în Moldova.',
                    'Curtea de Argeș și Cozia sunt ctitorii ale domnilor munteni.',
                ],
                'Baremul acceptă: Putna, Voroneț, Neamț (și celelalte din enumerare).'),

            self::ident(self::doc('u_ss26', 'II.2'), 'ev-mediu',
                'Numește, în baza sursei, o realizare în domeniul militar a lui Ștefan cel Mare.',
                "„…a ctitorit biserici și mănăstiri […], crearea trupelor de oșteni proveniți din țărani liberi, reorganizarea instituției «vitejilor» […]. A întărit, refăcut și extins ca nimeni altul sistemul de cetăți, mai ales pe cele de pe Nistru, și a adaptat tehnica militară progreselor făcute de artilerie.”\n— Ioan-Aurel Pop, Ioan Bolovan, Istoria României. Compendiu, 2007",
                ['oștenii proveniți din țărani liberi', 'reorganizarea instituției „vitejilor"', 'întărirea sistemului de cetăți', 'adaptarea tehnicii militare la artilerie'],
                ['ctitorirea de biserici și mănăstiri', 'limitarea privilegiilor marii boierimi'],
                [
                    'Fragmentul pornește de la politica internă și trece la armată.',
                    'Caută oștenii, vitejii, cetățile, artileria.',
                    'Mănăstirile și boierimea țin de alte domenii.',
                ],
                'Baremul acceptă: trupele de oșteni din țărani liberi; reorganizarea „vitejilor"; întărirea cetăților; adaptarea tehnicii militare la artilerie.'),
        ];
    }
}
