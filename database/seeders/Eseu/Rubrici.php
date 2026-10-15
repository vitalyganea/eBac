<?php

namespace Database\Seeders\Eseu;

/**
 * Bucățile comune ale grilelor de evaluare, transcrise din baremele ANCE.
 *
 * O grilă are trei feluri de criterii:
 *  - `criteria`: bifate de elev, pe nivelurile din barem (0 / 2 / 4…);
 *  - `errors`: punctajul pornește de la un plafon care depinde de numărul de
 *    cuvinte (`caps`) și scade cu 1 la fiecare `per` greșeli;
 *  - `volume`: calculat din numărul de cuvinte, pe benzile din barem.
 *
 * Baremul s-a schimbat de la an la an, deci fiecare variantă de mai jos
 * poartă anii în care s-a aplicat. Nimic nu e „unificat" între ani.
 */
final class Rubrici
{
    // ==================== LIMBA ENGLEZĂ ====================

    /** Semantica la eseu (Subiectul II), identică 2022–2026. */
    public static function enSemanticaEseu(): array
    {
        return [
            ['key' => 'coerenta', 'group' => 'Componenta semantică', 'label' => 'Ideile sunt expuse clar și logic, cu conectori',
                'options' => [
                    [0, 'text incoerent'],
                    [1, 'text parțial coerent, cu puțini conectori'],
                    [2, 'text în general coerent, cu 3–4 conectori de bază'],
                    [3, 'text coerent, cu o varietate de conectori logici și introducere, cuprins, concluzie distincte'],
                ]],
            ['key' => 'paragrafe', 'group' => 'Componenta semantică', 'label' => 'Textul e structurat în paragrafe',
                'options' => [[0, 'nu'], [1, 'da']]],
        ];
    }

    /** Semantica la articol (Subiectul III), 2022–2026. */
    public static function enSemanticaArticol(): array
    {
        return [
            ['key' => 'coerenta', 'group' => 'Componenta semantică', 'label' => 'Text coerent, cu structura unui articol: titlu, introducere, cuprins, încheiere',
                'options' => [
                    [0, 'nu'],
                    [1, 'text parțial coerent'],
                    [2, 'text coerent, cu toate părțile articolului'],
                ]],
        ];
    }

    /**
     * Eseu, 2022–2023: lexic −1 la 3 greșeli, gramatică −1 la 4.
     * Baremul cere punctare „proporțională cu volumul", fără tabel — deci
     * plafonul rămâne punctajul maxim; volumul se punctează separat.
     */
    public static function enEseu2223(): array
    {
        return [
            'errors' => [
                ['key' => 'lexic', 'label' => 'Greșeli lexicale și ortografice', 'max' => 6, 'per' => 3, 'caps' => null],
                ['key' => 'gramatica', 'label' => 'Greșeli gramaticale și ortografice', 'max' => 6, 'per' => 4, 'caps' => null],
            ],
            'volume' => ['max' => 4, 'over_max' => 0, 'bands' => [[180, 200, 4], [160, 179, 3], [140, 159, 2], [120, 139, 1]]],
        ];
    }

    /** Eseu, 2024–2025: lexic −1 la 4, gramatică −1 la 5, plafon după volum. */
    public static function enEseu2425(): array
    {
        $caps = [[180, 200, 6], [160, 179, 5], [150, 159, 4], [140, 149, 3], [130, 139, 2], [120, 129, 1]];

        return [
            'errors' => [
                ['key' => 'lexic', 'label' => 'Greșeli lexicale și ortografice', 'max' => 6, 'per' => 4, 'caps' => $caps],
                ['key' => 'gramatica', 'label' => 'Greșeli gramaticale și ortografice', 'max' => 6, 'per' => 5, 'caps' => $caps],
            ],
            'volume' => ['max' => 4, 'over_max' => 0, 'bands' => [[180, 200, 4], [160, 179, 3], [140, 159, 2], [120, 139, 1]]],
        ];
    }

    /** Eseu, 2026: lexic, gramatică și ortografie împreună, 12 p., −1 la 5 greșeli. */
    public static function enEseu26(): array
    {
        return [
            'errors' => [
                ['key' => 'limba', 'label' => 'Greșeli lexicale, gramaticale și ortografice', 'max' => 12, 'per' => 5, 'caps' => [
                    [180, 200, 12], [170, 179, 11], [160, 169, 10], [150, 159, 9], [140, 149, 8], [130, 139, 7],
                    [120, 129, 6], [110, 119, 5], [100, 109, 4], [90, 99, 3], [80, 89, 2], [70, 79, 1],
                ]],
            ],
            // „În cazul depășirii volumului maxim de cuvinte puncte nu se adaugă."
            'volume' => ['max' => 4, 'over_max' => 'max', 'bands' => [[180, 200, 4], [150, 179, 3], [110, 149, 2], [70, 109, 1]]],
        ];
    }

    /** Articol, 2022–2023: lexic −1 la 3, gramatică −1 la 4. */
    public static function enArticol2223(): array
    {
        return [
            'errors' => [
                ['key' => 'lexic', 'label' => 'Greșeli lexicale și ortografice', 'max' => 5, 'per' => 3, 'caps' => null],
                ['key' => 'gramatica', 'label' => 'Greșeli gramaticale și ortografice', 'max' => 4, 'per' => 4, 'caps' => null],
            ],
            'volume' => ['max' => 3, 'over_max' => 0, 'bands' => [[90, 100, 3], [80, 89, 2], [70, 79, 1]]],
        ];
    }

    /** Articol, 2024–2025: lexic −1 la 4, gramatică −1 la 5, plafon după volum. */
    public static function enArticol2425(): array
    {
        return [
            'errors' => [
                ['key' => 'lexic', 'label' => 'Greșeli lexicale și ortografice', 'max' => 5, 'per' => 4,
                    'caps' => [[90, 100, 5], [85, 89, 4], [80, 84, 3], [75, 79, 2], [70, 74, 1]]],
                ['key' => 'gramatica', 'label' => 'Greșeli gramaticale și ortografice', 'max' => 4, 'per' => 5,
                    'caps' => [[90, 100, 4], [80, 89, 3], [75, 79, 2], [70, 74, 1]]],
            ],
            'volume' => ['max' => 3, 'over_max' => 0, 'bands' => [[90, 100, 3], [80, 89, 2], [70, 79, 1]]],
        ];
    }

    /** Articol, 2026: 100–110 cuvinte; lexic, gramatică și ortografie împreună, 9 p. */
    public static function enArticol26(): array
    {
        return [
            'errors' => [
                ['key' => 'limba', 'label' => 'Greșeli lexicale, gramaticale și ortografice', 'max' => 9, 'per' => 5, 'caps' => [
                    [100, 110, 9], [85, 99, 8], [80, 84, 7], [75, 79, 6], [70, 74, 5], [65, 69, 4], [60, 64, 3], [55, 59, 2], [45, 54, 1],
                ]],
            ],
            'volume' => ['max' => 3, 'over_max' => 'max', 'bands' => [[100, 110, 3], [66, 99, 2], [45, 65, 1]]],
        ];
    }

    // ==================== LIMBA ȘI LITERATURA ROMÂNĂ ====================

    /** Eseu argumentativ, 2024–2025 (real și umanist): 2+4+4+6+3+1 = 20. */
    public static function roArgumentativ2425(): array
    {
        $g = 'Eseul argumentativ';

        return [
            ['key' => 'opinie', 'group' => $g, 'label' => 'Opinia e exprimată clar', 'options' => [
                [0, 'lipsește'], [1, 'superficială, echivocă'], [2, 'clară'],
            ]],
            ['key' => 'teza1', 'group' => $g, 'label' => 'Teza 1, distinctă și derivată din aserțiune', 'options' => [
                [0, 'lipsește'], [1, 'parțial formulată, cu premisă ambiguă'], [2, 'formulată clar'],
            ]],
            ['key' => 'teza2', 'group' => $g, 'label' => 'Teza 2, distinctă de prima', 'options' => [
                [0, 'lipsește'], [1, 'parțial formulată, cu premisă ambiguă'], [2, 'formulată clar'],
            ]],
            ['key' => 'argument1', 'group' => $g, 'label' => 'Argumentul pentru teza 1: valid, concordant, logic', 'options' => [
                [0, 'lipsește'], [1, 'parțial, cu justificare evazivă'], [2, 'valid și logic'],
            ]],
            ['key' => 'argument2', 'group' => $g, 'label' => 'Argumentul pentru teza 2: valid, concordant, logic', 'options' => [
                [0, 'lipsește'], [1, 'parțial, cu justificare evazivă'], [2, 'valid și logic'],
            ]],
            ['key' => 'text1', 'group' => $g, 'label' => 'Textul literar 1 confirmă teza 1', 'options' => [
                [0, 'lipsește'],
                [1, 'titlu, autor, o referință superficială, fără exemple'],
                [2, 'titlu, autor, referință parțială, exemple evazive'],
                [3, 'titlu, autor, referință exactă cu dovezi, exemple concrete sau citate'],
            ]],
            ['key' => 'text2', 'group' => $g, 'label' => 'Textul literar 2 confirmă teza 2', 'options' => [
                [0, 'lipsește'],
                [1, 'titlu, autor, o referință superficială, fără exemple'],
                [2, 'titlu, autor, referință parțială, exemple evazive'],
                [3, 'titlu, autor, referință exactă cu dovezi, exemple concrete sau citate'],
            ]],
            ['key' => 'structura', 'group' => $g, 'label' => 'Teză, justificare graduală și concluzie', 'options' => [[0, 'nu'], [1, 'da']]],
            ['key' => 'conectori', 'group' => $g, 'label' => 'Conectori specifici, logici', 'options' => [[0, 'nu'], [1, 'da']]],
            ['key' => 'grafic', 'group' => $g, 'label' => 'Aspect grafic îngrijit, lizibil', 'options' => [[0, 'nu'], [1, 'da']]],
            ['key' => 'intindere', 'group' => $g, 'label' => 'Limita de întindere (1–1,5 pagini)', 'options' => [[0, 'nu'], [1, 'da']]],
        ];
    }

    /** Eseu argumentativ, profil real, 2026: 2+4+4+8+5+1 = 24. */
    public static function roArgumentativ26(): array
    {
        $g = 'Eseul argumentativ';
        // Baremul dă câte 1 punct pentru FIECARE element, independent: titlul,
        // autorul, referința exactă prin dovezi, exemplul concret sau citatul.
        $text = fn (int $n) => ['key' => "text{$n}", 'group' => $g,
            'label' => "Textul literar {$n} confirmă teza {$n}: câte dintre cele 4 elemente ai — titlul, numele autorului, referința exactă prin dovezi, exemplul concret sau citatul?",
            'options' => [[0, 'niciunul'], [1, 'unul'], [2, 'două'], [3, 'trei'], [4, 'toate patru']]];

        return [
            ['key' => 'opinie', 'group' => $g, 'label' => 'Opinia: exprimată clar (1 p.) și legată de aserțiune (1 p.)', 'options' => [
                [0, 'niciuna'], [1, 'una dintre ele'], [2, 'amândouă'],
            ]],
            ['key' => 'teza1', 'group' => $g, 'label' => 'Teza 1: formulată explicit (1 p.) și corelată cu aserțiunea (1 p.)', 'options' => [
                [0, 'niciuna'], [1, 'una dintre ele'], [2, 'amândouă'],
            ]],
            ['key' => 'teza2', 'group' => $g, 'label' => 'Teza 2: formulată explicit (1 p.) și corelată cu aserțiunea (1 p.)', 'options' => [
                [0, 'niciuna'], [1, 'una dintre ele'], [2, 'amândouă'],
            ]],
            ['key' => 'argument1', 'group' => $g, 'label' => 'Argumentul pentru teza 1: valid (1 p.), concordant și logic (1 p.)', 'options' => [
                [0, 'niciuna'], [1, 'una dintre ele'], [2, 'amândouă'],
            ]],
            ['key' => 'argument2', 'group' => $g, 'label' => 'Argumentul pentru teza 2: valid (1 p.), concordant și logic (1 p.)', 'options' => [
                [0, 'niciuna'], [1, 'una dintre ele'], [2, 'amândouă'],
            ]],
            $text(1),
            $text(2),
            ['key' => 'concluzie', 'group' => $g, 'label' => 'Concluzia rezultă din argumente', 'options' => [
                [0, 'lipsește'], [1, 'schematică, necorelată cu argumentele'], [2, 'rezultă din argumente și e corelată cu ideile'],
            ]],
            ['key' => 'alineate', 'group' => $g, 'label' => 'Alineate și justificare graduală', 'options' => [[0, 'nu'], [1, 'da']]],
            ['key' => 'conectori', 'group' => $g, 'label' => 'Conectori specifici, logici', 'options' => [[0, 'nu'], [1, 'da']]],
            ['key' => 'grafic', 'group' => $g, 'label' => 'Aspect grafic îngrijit, lizibil', 'options' => [[0, 'nu'], [1, 'da']]],
            ['key' => 'intindere', 'group' => $g, 'label' => 'Limita de întindere (1–1,5 pagini)', 'options' => [[0, 'nu'], [1, 'da']]],
        ];
    }

    /**
     * Eseu structurat, profil umanist, 2026: 2+2+6+6+4+2+2 = 24.
     * Primele două repere diferă de la o sesiune la alta și se dau separat.
     *
     * @param  list<array<string, mixed>>  $first
     */
    public static function roStructurat26(array $first): array
    {
        $g = 'Eseul structurat';
        $per = fn (string $key, string $label, array $options) => ['key' => $key, 'group' => $g, 'label' => $label, 'options' => $options];

        $alegere = [[0, 'lipsește'], [1, 'titlul și autorul'], [2, 'titlu, autor și o motivare insuficientă'], [3, 'titlu, autor și motivare pertinentă, în acord cu tema']];
        $comentariu = [[0, 'lipsește'], [1, 'aspect menționat sau comentariu evaziv'], [2, 'aspect relevant, comentariu parțial'], [3, 'aspect relevant și particularități comentate']];
        $exemplu = [[0, 'lipsește'], [1, 'incomplet sau slab corelat cu aspectul'], [2, 'convingător']];

        return [
            ...$first,
            $per('alegere1', 'Textul 1 e ales argumentat', $alegere),
            $per('alegere2', 'Textul 2 e ales argumentat', $alegere),
            $per('aspect1', 'Aspectul din textul 1 e comentat', $comentariu),
            $per('aspect2', 'Aspectul din textul 2 e comentat', $comentariu),
            $per('exemplu1', 'Exemplul din textul 1', $exemplu),
            $per('exemplu2', 'Exemplul din textul 2', $exemplu),
            $per('idee', 'O idee din aserțiune, formulată explicit și dezvoltată', [[0, 'lipsește'], [1, 'doar formulată'], [2, 'formulată și dezvoltată']]),
            $per('opinie', 'Opinia argumentată în raport cu tema', [[0, 'lipsește'], [1, 'generală, fără raportare la temă'], [2, 'argumentată și raportată la temă']]),
        ];
    }
}
