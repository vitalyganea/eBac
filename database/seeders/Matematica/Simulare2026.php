<?php

namespace Database\Seeders\Matematica;

/**
 * Itemii rezervați simulărilor (§8): variante cu alte date ale problemelor din
 * testele ANCE 2026. Formularea și pașii sunt cei din test și barem; datele și
 * răspunsul sunt ale noastre, verificate numeric.
 *
 * Nu apar niciodată în antrenament sau pe paginile publice (§14). Banca
 * acoperă două simulări complete fără repetare, la ambele profiluri.
 */
final class Simulare2026
{
    use AnceItems;

    /** @return list<array<string, mixed>> */
    public static function items(): array
    {
        return [...self::algebra(), ...self::geometrie(), ...self::analiza(), ...self::combinatorica()];
    }

    private static function algebra(): array
    {
        return [
            self::num(self::variant(self::doc('r_sb26', 1)), 'numere-reale', 5,
                'Calculați valoarea expresiei $0{,}6 + \sqrt[3]{\frac{186}{125} - 2}$.',
                -0.2,
                [
                    'Începe cu expresia de sub radical: adu $\frac{186}{125} - 2$ la același numitor.',
                    '$\frac{186}{125} - 2 = -\frac{64}{125}$, iar $64 = 4^3$ și $125 = 5^3$.',
                    '$\sqrt[3]{-\frac{64}{125}} = -\frac{4}{5}$ și $0{,}6 = \frac{3}{5}$.',
                ],
                '$\sqrt[3]{\frac{186}{125} - 2} = \sqrt[3]{-\frac{64}{125}} = -\frac{4}{5}$, deci valoarea este $\frac{3}{5} - \frac{4}{5} = -\frac{1}{5}$.'),

            self::num(self::variant(self::doc('u_sb26', 1)), 'numere-reale', 5,
                'Calculați $\sqrt[3]{-\frac{1}{27}} + \left(\frac{3}{4}\right)^{-2}$.',
                13 / 9,
                [
                    '$27 = 3^3$, deci radicalul de ordinul 3 se extrage exact.',
                    '$\sqrt[3]{-\frac{1}{27}} = -\frac{1}{3}$.',
                    '$\left(\frac{3}{4}\right)^{-2} = \left(\frac{4}{3}\right)^2 = \frac{16}{9}$.',
                ],
                '$-\frac{1}{3} + \frac{16}{9} = \frac{13}{9}$.'),

            self::num(self::variant(self::doc('r_ss26', 1)), 'logaritmi', 5,
                'Calculați valoarea expresiei: $27^{\frac{2}{3}} + \log_3 \frac{1}{81}$.',
                5,
                [
                    'Scrie 27 ca putere a lui 3.',
                    '$27^{\frac{2}{3}} = (3^3)^{\frac{2}{3}} = 3^2$.',
                    '$\frac{1}{81} = 3^{-4}$.',
                ],
                '$27^{\frac{2}{3}} = 9$ și $\log_3 \frac{1}{81} = -4$, deci valoarea este $5$.'),

            self::num(self::variant(self::doc('u_ss26', 3)), 'logaritmi', 8,
                'Determinați valoarea expresiei: $3^{\log_{\sqrt{3}} 2} + \log_5 4 - 2\log_5 10$.',
                2,
                [
                    '$\log_{\sqrt{3}} 2 = 2\log_3 2$, deci $3^{2\log_3 2} = (3^{\log_3 2})^2$.',
                    'Primul termen este $2^2 = 4$.',
                    '$\log_5 4 - 2\log_5 10 = \log_5 \frac{4}{100}$.',
                ],
                '$3^{\log_{\sqrt{3}} 2} = 4$ și $\log_5 4 - \log_5 100 = \log_5 \frac{1}{25} = -2$, deci valoarea este $2$.'),

            self::choice(self::variant(self::doc('r_sb26', 2)), 'ecuatii-inecuatii', 5,
                'Rezolvați în $\mathbb{R}$ inecuația $8 \cdot 0{,}5^t \geq 32$.',
                ['$S = (-\infty; -2]$', '$S = [-2; +\infty)$', '$S = (-\infty; 2]$', '$S = [2; +\infty)$'],
                [
                    'Împarte la 8 și scrie ambii membri ca puteri ale lui $0{,}5$.',
                    '$0{,}5^t \geq 4 = 0{,}5^{-2}$.',
                    'Baza e subunitară, deci sensul inegalității se schimbă.',
                ],
                '$0{,}5^t \geq 0{,}5^{-2}$ și baza e subunitară, deci $t \leq -2$.'),

            self::choice(self::variant(self::doc('r_ss26', 3)), 'ecuatii-inecuatii', 8,
                'Rezolvați în $\mathbb{R}$ ecuația $3\left(\sqrt{x + 10} - 1\right) = x - 3$.',
                ['$S = \{15\}$', '$S = \{-6; 15\}$', '$S = \{-6\}$', '$S = \varnothing$'],
                [
                    'Izolează radicalul: $3\sqrt{x + 10} = x$.',
                    'Membrul stâng e nenegativ, deci $x \geq 0$. Ridică la pătrat: $9(x + 10) = x^2$.',
                    '$x^2 - 9x - 90 = 0$ are rădăcinile $-6$ și $15$.',
                ],
                '$3\sqrt{x + 10} = x$ cere $x \geq 0$; $x^2 - 9x - 90 = 0$ dă $-6$ și $15$, iar condiția o păstrează doar pe $15$.'),

            self::blanks(self::variant(self::doc('r_sb26', 4)), 'numere-complexe', 8,
                'Determinați valorile reale ale lui $p$ și $q$, pentru care numărul complex $z = 2 + i$ este soluție a ecuației $z^2 + pz + q - 5i = 0$, unde $i^2 = -1$.',
                ['p' => ['$p$', '1'], 'q' => ['$q$', '-5']],
                [
                    'Înlocuiește $z = 2 + i$ în ecuație.',
                    '$(2 + i)^2 = 3 + 4i$; grupează: $(3 + 2p + q) + i(p - 1) = 0$.',
                    'Ambele părți trebuie să fie zero.',
                ],
                '$(3 + 2p + q) + i(p - 1) = 0$ dă $p = 1$ și $q = -5$.'),

            self::num(self::variant(self::doc('u_ss26', 4)), 'numere-complexe', 8,
                'Determinați modulul numărului complex $z = a + bi$, $i^2 = -1$, știind că $2iz - 4\bar{z} = -8 + 10i$, unde $\bar{z}$ este conjugatul numărului complex $z$.',
                sqrt(5),
                [
                    'Înlocuiește $z = a + bi$ și $\bar{z} = a - bi$.',
                    'Obții $(-2b - 4a) + (2a + 4b)i = -8 + 10i$.',
                    'Sistemul $-2b - 4a = -8$, $2a + 4b = 10$ dă $z = 1 + 2i$.',
                ],
                'Sistemul $-4a - 2b = -8$, $2a + 4b = 10$ are soluția $a = 1$, $b = 2$, deci $|z| = \sqrt{5}$.'),

            self::num(self::variant(self::doc('u_ss26', 2)), 'matrici', 8,
                'Calculați determinantul matricei $X = A^2 + B$, unde $A = \begin{pmatrix} 2 & 1 \\\\ 0 & -1 \end{pmatrix}$, $B = \begin{pmatrix} 1 & 0 \\\\ 2 & 3 \end{pmatrix}$.',
                18,
                [
                    'Calculează întâi $A^2 = A \cdot A$.',
                    '$A^2 = \begin{pmatrix} 4 & 1 \\\\ 0 & 1 \end{pmatrix}$.',
                    '$X = \begin{pmatrix} 5 & 1 \\\\ 2 & 4 \end{pmatrix}$.',
                ],
                '$X = \begin{pmatrix} 5 & 1 \\\\ 2 & 4 \end{pmatrix}$ și $\det X = 20 - 2 = 18$.'),

            self::num(self::variant(self::doc('r_sb26', 5)), 'identitati-trigonometrice', 8,
                'Fie $\alpha \in \left(\frac{\pi}{2}, \pi\right)$ și $\beta \in \left(0, \frac{\pi}{2}\right)$, care verifică relațiile: $\frac{\alpha}{2} + \beta = \frac{\pi}{2}$ și $125\sin^2\alpha - 10\cos\alpha - 118 = 0$. Determinați valoarea lui $\sin\beta$.',
                0.6,
                [
                    'Scrie $\sin^2\alpha = 1 - \cos^2\alpha$: obții $125\cos^2\alpha + 10\cos\alpha - 7 = 0$.',
                    'Soluțiile sunt $\frac{1}{5}$ și $-\frac{7}{25}$; în cadranul II, $\cos\alpha < 0$.',
                    '$\sin\beta = \cos\frac{\alpha}{2} = \sqrt{\frac{1 + \cos\alpha}{2}}$.',
                ],
                '$\cos\alpha = -\frac{7}{25}$, deci $\sin\beta = \cos\frac{\alpha}{2} = \sqrt{\frac{18/25}{2}} = \frac{3}{5}$.'),
        ];
    }

    private static function geometrie(): array
    {
        return [
            self::num(self::variant(self::doc('r_sb26', 6)), 'corpuri', 5,
                'Secțiunea axială a unui con circular drept este un triunghi dreptunghic. Determinați aria laterală a conului, dacă înălțimea conului este de 5 cm.',
                25 * sqrt(2) * M_PI,
                [
                    'Triunghiul axial e dreptunghic isoscel.',
                    'Raza bazei $R = H = 5$ cm.',
                    '$G = 5\sqrt{2}$ cm, iar $\mathcal{A}_{lat} = \pi R G$.',
                ],
                '$R = 5$ cm, $G = 5\sqrt{2}$ cm, deci $\mathcal{A}_{lat} = 25\sqrt{2}\pi$ cm².',
                'cm²'),

            self::num(self::variant(self::doc('r_sb26', 8)), 'corpuri', 8,
                'Aria laterală a unei piramide patrulatere regulate este egală cu 400 cm², iar unghiurile diedre de la baza piramidei sunt de 60°. Determinați volumul piramidei.',
                1000 * sqrt(6) / 3,
                [
                    'Cu $x$ proiecția apotemei pe bază: latura bazei $= 2x$, apotema $= 2x$.',
                    '$8x^2 = 400$, deci $x = 5\sqrt{2}$.',
                    'Înălțimea $H = x\sqrt{3} = 5\sqrt{6}$, aria bazei $= (10\sqrt{2})^2$.',
                ],
                'Aria bazei $= 200$ cm², $H = 5\sqrt{6}$ cm, deci $\mathcal{V} = \frac{1}{3} \cdot 200 \cdot 5\sqrt{6} = \frac{1000\sqrt{6}}{3}$ cm³.',
                'cm³'),

            self::num(self::variant(self::doc('u_ss26', 7)), 'corpuri', 5,
                'Într-un cilindru circular drept suma ariilor bazelor este egală cu aria laterală. Determinați volumul cilindrului, dacă diagonala secțiunii axiale are lungimea de $2\sqrt{5}$ cm.',
                8 * M_PI,
                [
                    '$2\pi R^2 = 2\pi R H$ înseamnă $R = H$.',
                    'Diagonala secțiunii axiale: $(2R)^2 + H^2 = 20$.',
                    '$5R^2 = 20$, deci $R = H = 2$ cm.',
                ],
                '$R = H = 2$ cm, deci $\mathcal{V} = \pi \cdot 4 \cdot 2 = 8\pi$ cm³.',
                'cm³'),

            self::num(self::variant(self::doc('r_sb26', 7)), 'patrulatere', 8,
                'Într-un trapez isoscel diagonala este perpendiculară pe latura laterală și are lungimea de 20 cm. Determinați perimetrul trapezului, dacă înălțimea trapezului este de 12 cm.',
                62,
                [
                    'Proiecția diagonalei pe baza mare: $\sqrt{20^2 - 12^2}$.',
                    'Înălțimea e medie proporțională: $12^2 = 16 \cdot p$.',
                    'Proiecția laturii $p = 9$ cm, latura $= 15$ cm, baza mare $= 25$ cm.',
                ],
                'Proiecțiile: 16 cm și 9 cm; latura 15 cm; bazele 25 cm și $25 - 18 = 7$ cm. Perimetrul: $25 + 7 + 30 = 62$ cm.',
                'cm'),

            self::num(self::variant(self::doc('u_sb26', 6)), 'patrulatere', 5,
                'Într-un trapez baza mare este de 10 cm, iar linia mijlocie este de 7 cm. Determinați aria trapezului, dacă se cunoaște că înălțimea trapezului este congruentă cu baza mică.',
                28,
                [
                    'Linia mijlocie e media bazelor.',
                    'Baza mică are 4 cm, deci și înălțimea.',
                    'Aria = linia mijlocie × înălțimea.',
                ],
                'Baza mică are 4 cm, la fel înălțimea; aria $= 7 \cdot 4 = 28$ cm².',
                'cm²'),

            self::num(self::variant(self::doc('u_ss26', 8)), 'patrulatere', 8,
                'Fie trapezul isoscel $ABCD$, $BC \parallel AD$, în care $AB = CD = 6$ cm, $BC = 3\sqrt{2}$ cm, iar unghiul de la baza mare $AD$ este de $45^\circ$. Determinați lungimea diagonalei $AC$.',
                3 * sqrt(10),
                [
                    'Cu unghiul de $45^\circ$, înălțimea și proiecția laturii laterale sunt congruente.',
                    'Fiecare are $6 \cos 45^\circ = 3\sqrt{2}$ cm.',
                    'Proiecția diagonalei pe baza mare: $3\sqrt{2} + 3\sqrt{2}$.',
                ],
                'Înălțimea are $3\sqrt{2}$ cm, proiecția diagonalei $6\sqrt{2}$ cm, deci $AC = \sqrt{72 + 18} = 3\sqrt{10}$ cm.',
                'cm'),

            self::num(self::variant(self::doc('u_ss26', 6)), 'triunghiuri', 5,
                'În dreptunghiul $ABCD$ lungimea laturii $AD$ este egală cu 4 cm. Punctul $F$ aparține laturii $DC$, astfel încât $DF = 3$ cm. Fie $E$ punctul de intersecție a dreptelor $AF$ și $BC$, iar $CE = 8$ cm. Determinați lungimea segmentului $EF$.',
                10,
                [
                    'Triunghiurile $ADF$ și $ECF$ sunt asemenea.',
                    '$\frac{FC}{DF} = \frac{CE}{AD} = 2$, deci $FC = 6$ cm.',
                    '$EF$ e ipotenuza triunghiului $ECF$.',
                ],
                '$FC = 6$ cm, deci $EF = \sqrt{36 + 64} = 10$ cm.',
                'cm'),

            self::num(self::variant(self::doc('r_ss26', 6)), 'cerc', 5,
                'Punctele $A$, $B$ și $C$ aparțin unui cerc, astfel încât $m(\angle ABC) = 30^\circ$. Determinați lungimea cercului, dacă se cunoaște că lungimea arcului mic $AC$ este egală cu $3\pi$ cm.',
                18 * M_PI,
                [
                    'Unghiul înscris e jumătate din arcul subîntins.',
                    'Arcul mic $AC$ are $60^\circ$ — a șasea parte din cerc.',
                    'Lungimea cercului e de 6 ori lungimea arcului.',
                ],
                'Arcul mic $AC$ are $60^\circ$, deci cercul are $6 \cdot 3\pi = 18\pi$ cm.',
                'cm'),
        ];
    }

    private static function analiza(): array
    {
        return [
            self::num(self::variant(self::doc('r_sb26', 9)), 'progresii', 5,
                'Numerele $25$, $k$, $-3$ sunt termeni consecutivi ai unei progresii aritmetice. Determinați valoarea lui $k$.',
                11,
                [
                    'Termenul din mijloc e media aritmetică a vecinilor.',
                    '$k = \frac{25 + (-3)}{2}$.',
                    'Verifică rația: $k - 25 = -3 - k$.',
                ],
                '$k = \frac{25 - 3}{2} = 11$.'),

            self::num(self::variant(self::doc('r_ss26', 9)), 'progresii', 5,
                'Fie progresia aritmetică $(a_n)_{n \geq 1}$, în care $a_1 = 2$ și $a_4 = 11$. Determinați valoarea lui $a_{2026}$.',
                6077,
                [
                    '$a_4 = a_1 + 3r$.',
                    'Rația este $r = 3$.',
                    '$a_{2026} = a_1 + 2025r$.',
                ],
                '$r = 3$, deci $a_{2026} = 2 + 2025 \cdot 3 = 6077$.'),

            self::num(self::variant(self::doc('u_sb26', 10)), 'functii', 5,
                'Fie funcția $f:\mathbb{R}\to\mathbb{R}$, $f(x) = \frac{x}{\sqrt{5} - 2} + \sqrt{80}$. Determinați dacă punctul $M(-2\sqrt{5}; -10)$ aparține graficului funcției $f$. Cât este $f(-2\sqrt{5})$?',
                -10,
                [
                    'Punctul aparține graficului dacă $f(-2\sqrt{5}) = -10$.',
                    'Amplifică fracția cu $\sqrt{5} + 2$: numitorul devine 1.',
                    '$\sqrt{80} = 4\sqrt{5}$.',
                ],
                '$f(-2\sqrt{5}) = -2\sqrt{5}(\sqrt{5} + 2) + 4\sqrt{5} = -10$, deci $M$ aparține graficului.'),

            self::choice(self::variant(self::doc('u_ss26', 10)), 'functii', 5,
                'Fie funcția $f:\mathbb{R}\to\mathbb{R}$, $f(x) = (3\sqrt{2} - 4)x + 2$. Stabiliți monotonia funcției $f$.',
                ['$f$ este strict crescătoare', '$f$ este strict descrescătoare', '$f$ este constantă'],
                [
                    'Monotonia depinde de semnul pantei.',
                    'Compară $3\sqrt{2}$ cu 4: ridică la pătrat.',
                    '$18 > 16$.',
                ],
                '$3\sqrt{2} > 4$, deci panta e pozitivă și funcția e strict crescătoare.'),

            self::choice(self::variant(self::doc('r_ss26', '10a')), 'derivate', 8,
                'Fie funcția $f:(0; +\infty)\to\mathbb{R}$, $f(x) = \ln x + \frac{4}{x}$. Determinați intervalele de monotonie a funcției $f$.',
                [
                    '$f$ descrescătoare pe $(0; 4]$, crescătoare pe $[4; +\infty)$',
                    '$f$ crescătoare pe $(0; 4]$, descrescătoare pe $[4; +\infty)$',
                    '$f$ descrescătoare pe $(0; 2]$, crescătoare pe $[2; +\infty)$',
                    '$f$ crescătoare pe $(0; +\infty)$',
                ],
                [
                    'Calculează $f\'(x)$.',
                    '$f\'(x) = \frac{1}{x} - \frac{4}{x^2} = \frac{x - 4}{x^2}$.',
                    'Semnul lui $x - 4$ pe $(0; +\infty)$.',
                ],
                '$f\'(x) = \frac{x - 4}{x^2}$: negativă pe $(0; 4)$, pozitivă pe $(4; +\infty)$.'),

            self::num(self::variant(self::doc('r_sb26', '10c')), 'integrale', 8,
                'Fie funcția $f:\mathbb{R}\to\mathbb{R}$, $f(x) = \frac{x}{x^2 + 1}$. Determinați valoarea reală a lui $m > 0$, pentru care dreapta $x = m$ împarte figura, mărginită de graficul funcției $f$, de axele de coordonate și de dreapta $x = 4\sqrt{5}$, în două figuri de arii egale.',
                2 * sqrt(2),
                [
                    'O primitivă a lui $f$ este $\frac{1}{2}\ln(x^2 + 1)$.',
                    'Condiția: $\ln(m^2 + 1) - \ln 1 = \ln 81 - \ln(m^2 + 1)$.',
                    '$(m^2 + 1)^2 = 81$.',
                ],
                '$(m^2 + 1)^2 = 81$, deci $m^2 = 8$ și $m = 2\sqrt{2}$.'),
        ];
    }

    private static function combinatorica(): array
    {
        return [
            self::num(self::variant(self::doc('r_sb26', 11)), 'probabilitati', 8,
                'Cu cifrele 1, 2, 3, 4, 5, 6, 7 se formează în mod aleator un număr de 3 cifre care nu se repetă. Determinați probabilitatea că numărul format conține cifra 5.',
                3 / 7,
                [
                    'Cazurile posibile: $n = A_7^3$.',
                    'Cifra 5 ocupă una din 3 poziții; celelalte două se completează din 6 cifre.',
                    '$m = 3 \cdot A_6^2$.',
                ],
                '$n = 210$, $m = 3 \cdot 30 = 90$, deci $p = \frac{90}{210} = \frac{3}{7}$.'),

            self::num(self::variant(self::doc('u_ss26', 13)), 'probabilitati', 8,
                'Echipa unui liceu la un concurs de Informatică este formată din 8 elevi, dintre care 5 programează doar în C++, iar 3 programează doar în Python. Pentru o probă au fost luați la întâmplare 4 elevi. Determinați probabilitatea că cel puțin un elev dintre ei programează în Python.',
                13 / 14,
                [
                    'Treci prin evenimentul contrar: niciun elev de Python.',
                    '$n = C_8^4$, iar contrarul are $m = C_5^4$ cazuri.',
                    '$p = 1 - \frac{m}{n}$.',
                ],
                '$n = 70$, $m = 5$, deci $p = 1 - \frac{5}{70} = \frac{13}{14}$.'),

            self::num(self::variant(self::doc('r_ss26', 12)), 'combinatorica', 8,
                'Diferența dintre coeficienții binomiali ai termenului al treilea și termenului al doilea în dezvoltarea la putere a binomului $\left(\sqrt{x} + \frac{1}{x}\right)^n$ este egală cu 44. Determinați coeficientul lui $\frac{1}{x^2}$ din dezvoltare.',
                462,
                [
                    '$C_n^2 - C_n^1 = 44$.',
                    '$n^2 - 3n - 88 = 0$, deci $n = 11$.',
                    '$T_{k+1} = C_{11}^k x^{\frac{11 - k}{2}} x^{-k}$; exponentul $-2$ dă $k = 5$.',
                ],
                '$n = 11$, iar $\frac{11 - k}{2} - k = -2$ dă $k = 5$; coeficientul este $C_{11}^5 = 462$.'),

            self::blanks(self::variant(self::doc('u_sb26', 14)), 'probabilitati', 8,
                'Numărul de pași (exprimat în mii) realizați de un adolescent timp de 8 zile a fost: 5, 8, 7, 11, 12, $x$, 8, 6. Valoarea medie a numărului de pași realizați de adolescent în cele 8 zile este egală cu 8 (mii). Determinați valoarea lui $x$ și mediana seriei statistice corespunzătoare.',
                ['x' => ['$x$', '7'], 'me' => ['mediana', '7.5']],
                [
                    '$\frac{57 + x}{8} = 8$.',
                    '$x = 7$.',
                    'Seria ordonată are 8 termeni; mediana e media celor doi din mijloc.',
                ],
                '$x = 7$; seria ordonată 5, 6, 7, 7, 8, 8, 11, 12 are mediana $\frac{7 + 8}{2} = 7{,}5$.'),
        ];
    }
}
