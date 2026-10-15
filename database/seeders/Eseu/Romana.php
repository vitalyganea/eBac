<?php

namespace Database\Seeders\Eseu;

/**
 * Eseul din testele ANCE de limba și literatura română, 2024–2026
 * (12_llro_test_{profil}_{sesiune}.pdf și baremele cu același nume).
 *
 * Redactarea — organizarea, corectitudinea stilistică, ortografia și
 * punctuația, 20 de puncte — se notează pe TOATĂ lucrarea, nu doar pe eseu,
 * deci nu intră în grila de aici; o spune nota.
 */
final class Romana
{
    /** Reperul d) e formulat diferit de la un test la altul; restul e identic. */
    private const D_REAL = 'referința la două texte din literatura română, studiate sau citite independent, care confirmă fiecare teză enunțată';

    private const D_UMANIST_24 = 'referința la două texte din literatura română, studiate sau citite independent, care confirmă tezele enunțate';

    private const D_UMANIST_25 = 'referința la două opere din literatura română, care confirmă tezele enunțate';

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        $a2425 = Rubrici::roArgumentativ2425();
        $a26 = Rubrici::roArgumentativ26();

        return [
            self::argumentativ('r_ss26', 24, $a26, 'Omul se definește prin legăturile pe care le construiește cu ceilalți.',
                'Redactează un eseu argumentativ de 1-1,5 pagini, în raport cu afirmația lui Antoine de Saint-Exupéry: Omul se definește prin legăturile pe care le construiește cu ceilalți.'),
            self::argumentativ('r_sb26', 24, $a26, 'Unde există voință, există și o cale.',
                'Redactează un eseu argumentativ de 1-1,5 pagini, în raport cu afirmația lui George Herbert: Unde există voință, există și o cale.'),

            [
                'session' => 'u_ss26',
                'kind' => 'eseu',
                'title' => 'Literatura română – univers al valorilor și al experiențelor umane',
                'task' => "Redactează un eseu structurat, de 1-1,5 pagini, cu tema: „Literatura română – univers al valorilor și al experiențelor umane”, pornind de la afirmația lui Mihai Cimpoi: Cugetul nostru caută să descifreze, în frenezia cunoașterii, ceva din misterul vieții. Omul caută totuși să înțeleagă esența lumii. Și literatura îi facilitează această cale.\n\nÎn eseul tău, te vei referi la două texte din literatura română, potrivite temei.\na) explicarea modului propriu de înțelegere a noțiunii de experiență umană;\nb) elucidarea raportului: literatura – univers al valorilor;\nc) alegerea argumentată a două texte din literatura română, potrivite temei;\nd) evidențierea aspectelor care conturează universul valorilor și al experiențelor umane (câte un aspect pentru fiecare text), prin comentarea unor particularități semnificative;\ne) ilustrarea adecvată a aspectelor comentate prin câte un exemplu convingător din textele selectate;\nf) dezvoltarea motivată a unei idei din afirmația propusă;\ng) exprimarea opiniei proprii în raport cu tema abordată.",
                'total' => 24,
                'criteria' => Rubrici::roStructurat26([
                    ['key' => 'notiune', 'group' => 'Eseul structurat', 'label' => 'Noțiunea de experiență umană: explicație logică (1 p.) și coerentă (1 p.)',
                        'options' => [[0, 'niciuna'], [1, 'una dintre ele'], [2, 'amândouă']]],
                    ['key' => 'raport', 'group' => 'Eseul structurat', 'label' => 'Raportul literatură – univers al valorilor, elucidat fundamentat',
                        'options' => [[0, 'lipsește'], [1, 'parțial'], [2, 'fundamentat']]],
                ]),
            ],

            [
                'session' => 'u_sb26',
                'kind' => 'eseu',
                'title' => 'Demnitatea, iubirea, autocunoașterea, binele',
                'task' => "Redactează un eseu structurat, de 1-1,5 pagini, cu tema: „Demnitatea, iubirea, autocunoașterea, binele – repere esențiale în universul valoric al literaturii române”, pornind de la aserțiunea: Literatura, ca orice artă, descoperă viața. (Ion Agârbiceanu)\n\nÎn eseul tău, te vei referi la două texte din literatura română, potrivite temei.\na) explicarea modului propriu de înțelegere a două concepte dintre cele subliniate în temă;\nb) raportarea conceptelor explicate la propria experiență de lectură;\nc) alegerea argumentată a două texte din literatura română, potrivite temei;\nd) evidențierea aspectelor care conturează universul valoric al textelor selectate (câte un aspect pentru fiecare text), prin comentarea unor particularități semnificative;\ne) ilustrarea adecvată a aspectelor comentate prin câte un exemplu convingător din cele două texte selectate;\nf) dezvoltarea motivată a unei idei din aserțiunea propusă;\ng) exprimarea opiniei proprii în raport cu tema abordată.",
                'total' => 24,
                'criteria' => Rubrici::roStructurat26([
                    ['key' => 'concepte', 'group' => 'Eseul structurat', 'label' => 'Modul propriu de înțelegere a două concepte din temă (câte 1 p. pe concept)',
                        'options' => [[0, 'niciunul'], [1, 'unul'], [2, 'două']]],
                    ['key' => 'lectura', 'group' => 'Eseul structurat', 'label' => 'Fiecare concept raportat la experiența de lectură: text concret, titlu, autor (câte 1 p.)',
                        'options' => [[0, 'niciunul'], [1, 'unul'], [2, 'două']]],
                ]),
            ],

            self::argumentativ('r_ss25', 20, $a2425, 'Cea mai mare răsplată pentru strădaniile unui om…',
                'Redactează un eseu argumentativ de 1-1,5 pagini, în raport cu afirmația: Cea mai mare răsplată pentru strădaniile unui om nu este ceea ce câștigă prin munca sa, ci ceea ce devine prin ea.'),
            self::argumentativ('r_sb25', 20, $a2425, 'Valoarea unui om nu constă în ceea ce are, ci în ceea ce este.',
                'Redactează un eseu argumentativ de 1-1,5 pagini, în raport cu aserțiunea: Valoarea unui om nu constă în ceea ce are, ci în ceea ce este. (Oscar Wilde)'),
            self::argumentativ('u_ss25', 20, $a2425, 'Tăria caracterului se formează din tăcerea cu care îți duci luptele interioare.',
                'Redactează un eseu argumentativ de 1-1,5 pagini, în raport cu aserțiunea: Tăria caracterului se formează din tăcerea cu care îți duci luptele interioare. (Lucian Blaga)', self::D_UMANIST_25),
            self::argumentativ('u_sb25', 20, $a2425, 'Nu poți deschide o carte și să nu înveți ceva.',
                'Redactează un eseu argumentativ de 1-1,5 pagini, în raport cu aserțiunea: Nu poți deschide o carte și să nu înveți ceva. (Confucius)', self::D_UMANIST_25),

            self::argumentativ('r_ss24', 20, $a2425, 'Dragostea adaugă atâtea calități imaginare îndrăgitului.',
                'Redactează un eseu argumentativ de 1–1,5 pagini, în raport cu ideea din textul dat: Dragostea adaugă atâtea calități imaginare îndrăgitului.'),
            self::argumentativ('r_sb24', 20, $a2425, 'Demnitatea constă nu din a poseda onoruri…',
                'Redactează un eseu argumentativ de 1–1,5 pagini, în raport cu aserțiunea lui Aristotel: Demnitatea constă nu din a poseda onoruri, ci din conștiința că le merităm.'),
            self::argumentativ('u_ss24', 20, $a2425, 'Numai patriotul nu cunoaște moarte.',
                'Redactează un eseu argumentativ de 1-1,5 pagini, în raport cu ideea din text: Numai patriotul nu cunoaște moarte.', self::D_UMANIST_24),
            self::argumentativ('u_sb24', 20, $a2425, 'Dragostea-i văzduhul fără de care nici om, nici pasăre nu pot trăi…',
                'Redactează un eseu argumentativ de 1-1,5 pagini, în raport cu ideea din text: Dragostea-i văzduhul fără de care nici om, nici pasăre nu pot trăi…', self::D_UMANIST_24),
        ];
    }

    /** @param  list<array<string, mixed>>  $criteria */
    private static function argumentativ(string $session, int $total, array $criteria, string $title, string $task,
        string $d = self::D_REAL): array
    {
        $repere = "\n\nÎn elaborarea eseului, vei avea în vedere următoarele repere:\n"
            ."a) exprimarea clară a opiniei;\nb) formularea a două teze distincte;\n"
            ."c) susținerea fiecărei teze cu câte un argument;\nd) {$d};\n"
            ."e) respectarea structurii eseului argumentativ;\nf) respectarea limitei de întindere.";

        return [
            'session' => $session,
            'kind' => 'eseu',
            'title' => $title,
            'task' => $task.$repere,
            'total' => $total,
            'criteria' => $criteria,
        ];
    }
}
