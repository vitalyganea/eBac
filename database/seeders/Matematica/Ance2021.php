<?php

namespace Database\Seeders\Matematica;

/**
 * Testele ANCE de matematică din 2021 (12_mat_test_{r,u}_ro_{sb,ss}21.pdf și
 * baremele cu același nume).
 *
 * Lipsește itemul 2 de la real, sesiunea de bază: se rezolvă citind un grafic
 * desenat, pe care nu-l avem decât ca imagine.
 */
final class Ance2021
{
    use AnceItems;

    /** @return list<array<string, mixed>> */
    public static function items(): array
    {
        return [
            ...self::realBaza(),
            ...self::realSuplimentara(),
            ...self::umanistBaza(),
            ...self::umanistSuplimentara(),
        ];
    }

    /** Profil real, sesiunea de bază. */
    private static function realBaza(): array
    {
        $d = fn (int|string $n) => self::doc('r_sb21', $n);

        return [
            self::choice($d(1), 'numere-reale', 2,
                'Alegeți unul dintre semnele „<", „>" sau „=", astfel încât propoziția obținută să fie adevărată: $\sqrt[3]{5} \;\square\; \sqrt[4]{5}$.',
                ['>', '<', '='],
                [
                    'Scrie ambii radicali ca puteri ale lui 5.',
                    '$\sqrt[3]{5} = 5^{\frac{1}{3}}$, $\sqrt[4]{5} = 5^{\frac{1}{4}}$.',
                    'Baza $5 > 1$, deci puterea cu exponentul mai mare e mai mare.',
                ],
                '$\frac{1}{3} > \frac{1}{4}$ și $5 > 1$, deci $\sqrt[3]{5} > \sqrt[4]{5}$.'),

            self::num($d(3), 'cerc', 2,
                'Dreptele $BA$ și $BC$ sunt tangente la cercul de centru $O$ în punctele $A$ și $C$ respectiv. Determinați măsura în grade a unghiului $ABC$, dacă se cunoaște că $m(\angle AOC) = 120^\circ$.',
                60,
                [
                    'Raza dusă în punctul de tangență e perpendiculară pe tangentă.',
                    'În patrulaterul $OABC$, unghiurile din $A$ și $C$ sunt drepte.',
                    'Suma unghiurilor patrulaterului este $360^\circ$.',
                ],
                '$m(\angle ABC) = 360^\circ - 90^\circ - 90^\circ - 120^\circ = 60^\circ$.',
                '°'),

            self::num($d(4), 'logaritmi', 4,
                'Calculați valoarea expresiei $7^{\log_{49} 3} \cdot 3^{-\frac{1}{2}}$.',
                1,
                [
                    '$\log_{49} 3 = \frac{1}{2}\log_7 3 = \log_7 3^{\frac{1}{2}}$.',
                    '$7^{\log_7 3^{1/2}} = 3^{\frac{1}{2}}$.',
                    '$3^{\frac{1}{2}} \cdot 3^{-\frac{1}{2}}$.',
                ],
                '$7^{\log_{49} 3} = \sqrt{3}$, iar $\sqrt{3} \cdot 3^{-\frac{1}{2}} = 1$.'),

            self::num($d(5), 'numere-complexe', 5,
                'Fie $z = \frac{5 + 3i}{1 + i} - 2i$, unde $i^2 = -1$. Determinați modulul numărului complex $z$.',
                5,
                [
                    'Amplifică fracția cu $1 - i$.',
                    '$\frac{5 + 3i}{1 + i} = 4 - i$.',
                    '$z = 4 - 3i$, iar $|z| = \sqrt{a^2 + b^2}$.',
                ],
                '$z = 4 - i - 2i = 4 - 3i$, deci $|z| = \sqrt{16 + 9} = 5$.'),

            self::choice($d(6), 'ecuatii-inecuatii', 5,
                'Rezolvați în $\mathbb{R}$ inecuația $\sqrt{1 - x} < \sqrt{2x + 4}$.',
                ['$S = (-1; 1]$', '$S = (-1; +\infty)$', '$S = [-2; 1]$', '$S = (-\infty; -1)$'],
                [
                    'Ambii radicali trebuie să existe, iar cel din stânga e cel mai mic.',
                    'E suficient sistemul $1 - x \geq 0$, $1 - x < 2x + 4$.',
                    'A doua condiție dă $x > -1$.',
                ],
                'Sistemul $1 - x \geq 0$, $1 - x < 2x + 4$ dă $-1 < x \leq 1$. Fără condiția de existență ($x \leq 1$) se obține $(-1; +\infty)$ — greșeala pe care baremul o depunctează.'),

            self::num($d(7), 'patrulatere', 6,
                'Fie paralelogramul $ABCD$, în care $m(\angle A) = 60^\circ$, $AB = 4$ cm, $BD = 2\sqrt{7}$ cm. Determinați aria paralelogramului $ABCD$.',
                12 * sqrt(3),
                [
                    'Proiecția laturii $AB$ pe $AD$ are $4 \cos 60^\circ = 2$ cm, iar înălțimea $4 \sin 60^\circ = 2\sqrt{3}$ cm.',
                    'Proiecția diagonalei $BD$ pe $AD$: $\sqrt{28 - 12} = 4$ cm.',
                    '$AD = 2 + 4 = 6$ cm; aria = $AD \cdot h$.',
                ],
                '$AD = 6$ cm și $h = 2\sqrt{3}$ cm, deci aria este $12\sqrt{3}$ cm².',
                'cm²'),

            self::choice($d(8), 'integrale', 6,
                'Fie funcția $f:\left(\frac{2}{3}; +\infty\right)\to\mathbb{R}$, $f(x) = \frac{1}{3x - 2} - 1$. Determinați primitiva $F$ a funcției $f$, astfel încât graficele funcțiilor $f$ și $F$ se intersectează în punctul de abscisă $x = 1$.',
                [
                    '$F(x) = \frac{1}{3}\ln(3x - 2) - x + 1$',
                    '$F(x) = \ln(3x - 2) - x + 1$',
                    '$F(x) = \frac{1}{3}\ln(3x - 2) - x$',
                    '$F(x) = \frac{1}{3}\ln(3x - 2) - x + 2$',
                ],
                [
                    '$F(x) = \frac{1}{3}\ln(3x - 2) - x + C$ — atenție la factorul $\frac{1}{3}$.',
                    'Graficele se intersectează în $x = 1$: $F(1) = f(1)$.',
                    '$f(1) = 0$, iar $F(1) = -1 + C$.',
                ],
                '$-1 + C = 0$, deci $C = 1$ și $F(x) = \frac{1}{3}\ln(3x - 2) - x + 1$.'),

            self::num($d(9), 'probabilitati', 5,
                'Se aruncă simultan 4 zaruri. Determinați probabilitatea că suma punctelor apărute este egală cu 22.',
                5 / 648,
                [
                    'Cazurile posibile: $n = 6^4 = 1296$.',
                    'Suma 22 se obține doar din $6, 6, 6, 4$ sau $6, 6, 5, 5$.',
                    'Așezările: $C_4^1 = 4$, respectiv $C_4^2 = 6$.',
                ],
                '$m = 4 + 6 = 10$, deci $p = \frac{10}{1296} = \frac{5}{648}$.'),

            self::num($d(10), 'corpuri', 6,
                'Baza prismei drepte $ABCA_1B_1C_1$ este triunghiul $ABC$, în care $m(\angle A) = 90^\circ$, $AB = 15$ cm, $AC = 20$ cm. Determinați distanța de la vârful $A_1$ la muchia $BC$, dacă volumul prismei este egal cu 750 cm³.',
                13,
                [
                    'Aria bazei: $\frac{15 \cdot 20}{2} = 150$ cm², deci înălțimea prismei este 5 cm.',
                    '$BC = 25$ cm; distanța de la $A$ la $BC$ este $\frac{15 \cdot 20}{25} = 12$ cm.',
                    'Teorema celor trei perpendiculare și Pitagora: $\sqrt{12^2 + 5^2}$.',
                ],
                'Distanța de la $A$ la $BC$ e 12 cm, înălțimea prismei 5 cm, deci distanța cerută este 13 cm.',
                'cm'),

            self::choice($d(11), 'identitati-trigonometrice', 6,
                'Fie expresia $E(x) = 2\sin(2x)\operatorname{tg} x$. Determinați valorile reale ale lui $x$ pentru care $E(x) \neq 1$.',
                [
                    '$x \in \mathbb{R} \setminus \left(\left\{\frac{\pi}{2} + k\pi, k \in \mathbb{Z}\right\} \cup \left\{\pm\frac{\pi}{6} + m\pi, m \in \mathbb{Z}\right\}\right)$',
                    '$x \in \mathbb{R} \setminus \left\{\pm\frac{\pi}{6} + m\pi, m \in \mathbb{Z}\right\}$',
                    '$x \in \mathbb{R} \setminus \left(\left\{\frac{\pi}{2} + k\pi, k \in \mathbb{Z}\right\} \cup \left\{\pm\frac{\pi}{3} + m\pi, m \in \mathbb{Z}\right\}\right)$',
                    '$x \in \mathbb{R} \setminus \left(\left\{\frac{\pi}{2} + k\pi, k \in \mathbb{Z}\right\} \cup \left\{\pm\frac{\pi}{6} + 2m\pi, m \in \mathbb{Z}\right\}\right)$',
                ],
                [
                    '$\operatorname{tg} x$ există doar pentru $\cos x \neq 0$.',
                    '$2\sin(2x)\operatorname{tg} x = 4\sin^2 x$, deci condiția e $4\sin^2 x - 1 \neq 0$.',
                    '$\sin^2 x = \frac{1 - \cos(2x)}{2}$, deci $\cos(2x) \neq \frac{1}{2}$.',
                ],
                '$\cos(2x) \neq \frac{1}{2}$ dă $x \neq \pm\frac{\pi}{6} + m\pi$; la acestea se adaugă $x \neq \frac{\pi}{2} + k\pi$, unde expresia nu există.'),

            self::choice($d(12), 'derivate', 6,
                'Fie funcția $f:\mathbb{R}\to\mathbb{R}$, $f(x) = m(x - 1)e^x - \frac{x^2}{2}$. Determinați valorile reale ale lui $m$, pentru care funcția $f$ are un singur punct critic.',
                ['$m \in (-\infty; 0] \cup \{1\}$', '$m \in (-\infty; 0]$', '$m \in \{1\}$', '$m \in (-\infty; 0) \cup \{1\}$'],
                [
                    '$f\'(x) = mxe^x - x = x(me^x - 1)$.',
                    '$x = 0$ e mereu punct critic; al doilea vine din $me^x = 1$.',
                    '$me^x = 1$ nu are soluții dacă $m \leq 0$, iar pentru $m = 1$ soluția e tot $x = 0$.',
                ],
                'Pentru $m \leq 0$ singurul punct critic e $x = 0$; pentru $m = 1$ ecuația $e^x = 1$ dă din nou $x = 0$. Deci $m \in (-\infty; 0] \cup \{1\}$.'),
        ];
    }

    /** Profil real, sesiunea suplimentară. */
    private static function realSuplimentara(): array
    {
        $d = fn (int|string $n) => self::doc('r_ss21', $n);

        return [
            self::blanks($d(1), 'numere-reale', 2,
                'Completați cu două numere întregi consecutive, astfel încât propoziția obținută să fie adevărată: $\square < \sqrt[3]{-17} < \square$.',
                ['st' => ['numărul din stânga', '-3'], 'dr' => ['numărul din dreapta', '-2']],
                [
                    'Caută cuburile perfecte din jurul lui $-17$.',
                    '$(-3)^3 = -27$, $(-2)^3 = -8$.',
                    '$-27 < -17 < -8$.',
                ],
                '$-27 < -17 < -8$, deci $-3 < \sqrt[3]{-17} < -2$.'),

            self::choice($d(2), 'functii', 2,
                'Fie funcția $f:\left[0; \frac{\pi}{2}\right]\to\mathbb{R}$, $f(x) = \cos x$. Completați propoziția: „Funcția $f$ este …"',
                ['monoton descrescătoare', 'monoton crescătoare'],
                [
                    '$f(0) = 1$.',
                    '$f\left(\frac{\pi}{2}\right) = 0$.',
                    'Pe cercul trigonometric, abscisa scade când unghiul crește de la 0 la $\frac{\pi}{2}$.',
                ],
                'Pe $\left[0; \frac{\pi}{2}\right]$ cosinusul scade de la 1 la 0.'),

            self::num($d(3), 'corpuri', 2,
                'Un con circular drept are înălțimea $VO = 6$ cm și raza bazei de 15 cm. Conul e secționat de un plan paralel bazei, la distanța de 2 cm de la vârful $V$. Determinați lungimea razei cercului din secțiune.',
                5,
                [
                    'Secțiunea și baza formează triunghiuri asemenea.',
                    'Raportul de asemănare: $\frac{2}{6} = \frac{1}{3}$.',
                    '$O_1B_1 = \frac{1}{3} \cdot 15$.',
                ],
                'Raza secțiunii este $15 \cdot \frac{2}{6} = 5$ cm.',
                'cm'),

            self::num($d(4), 'logaritmi', 4,
                'Calculați valoarea expresiei $\log_2 5 + 2\log_{\frac{1}{4}} 20 + 32^{\frac{1}{5}}$.',
                0,
                [
                    '$2\log_{\frac{1}{4}} 20 = -\log_2 20$.',
                    '$\log_2 5 - \log_2 20 = \log_2 \frac{1}{4} = -2$.',
                    '$32^{\frac{1}{5}} = 2$.',
                ],
                '$-2 + 2 = 0$.'),

            self::blanks($d(5), 'numere-complexe', 5,
                'Fie $z = \frac{(3 + i)^2}{2i}$, unde $i^2 = -1$. Determinați $\bar{z}$. Scrie rezultatul sub forma $\bar{z} = a + bi$.',
                ['a' => ['$a$', '3'], 'b' => ['$b$', '4']],
                [
                    '$(3 + i)^2 = 8 + 6i$.',
                    'Amplifică $\frac{8 + 6i}{2i}$ cu $-i$.',
                    '$z = 3 - 4i$; conjugatul schimbă semnul părții imaginare.',
                ],
                '$z = \frac{(8 + 6i)(-i)}{2} = 3 - 4i$, deci $\bar{z} = 3 + 4i$.'),

            self::choice($d(6), 'ecuatii-inecuatii', 5,
                'Rezolvați în $\mathbb{R}$ inecuația $\left(\frac{64}{27}\right)^{x - 4} \geq \left(\frac{9}{16}\right)^{6 + x}$.',
                ['$S = [0; +\infty)$', '$S = (-\infty; 0]$', '$S = (0; +\infty)$', '$S = [-12; +\infty)$'],
                [
                    '$\left(\frac{64}{27}\right)^{x - 4} = \left(\frac{4}{3}\right)^{3x - 12}$.',
                    '$\left(\frac{9}{16}\right)^{6 + x} = \left(\frac{4}{3}\right)^{-12 - 2x}$.',
                    'Baza $\frac{4}{3} > 1$, deci sensul inegalității se păstrează.',
                ],
                '$3x - 12 \geq -12 - 2x$ dă $x \geq 0$. Sensul se inversează doar la o bază subunitară.'),

            self::num($d(7), 'corpuri', 6,
                'Fie piramida patrulateră regulată $VABCD$, în care $VAC$ este un triunghi dreptunghic cu catetele de 6 cm. Determinați volumul piramidei $VABCD$.',
                36 * sqrt(2),
                [
                    'Diagonala bazei $AC$ e ipotenuza: $6\sqrt{2}$ cm, deci latura bazei e 6 cm.',
                    'Înălțimea piramidei e mediana din unghiul drept: $\frac{AC}{2} = 3\sqrt{2}$ cm.',
                    '$\mathcal{V} = \frac{1}{3}\mathcal{A}_b \cdot h$.',
                ],
                '$\mathcal{V} = \frac{1}{3} \cdot 36 \cdot 3\sqrt{2} = 36\sqrt{2}$ cm³.',
                'cm³'),

            self::choice($d(8), 'derivate', 6,
                'Fie funcția $f:(0; +\infty)\to\mathbb{R}$, $f(x) = \ln x + \frac{1}{x}$. Determinați intervalele de monotonie ale funcției $f$.',
                [
                    'pe $(0; 1]$ $f$ este descrescătoare, pe $[1; +\infty)$ este crescătoare',
                    'pe $(0; 1]$ $f$ este crescătoare, pe $[1; +\infty)$ este descrescătoare',
                    '$f$ este crescătoare pe $(0; +\infty)$',
                    'pe $(0; e]$ $f$ este descrescătoare, pe $[e; +\infty)$ este crescătoare',
                ],
                [
                    '$f\'(x) = \frac{1}{x} - \frac{1}{x^2} = \frac{x - 1}{x^2}$.',
                    '$f\'(x) = 0$ pentru $x = 1$.',
                    'Semnul derivatei e semnul lui $x - 1$.',
                ],
                '$f\'(x) < 0$ pe $(0; 1)$ și $f\'(x) > 0$ pe $(1; +\infty)$.'),

            self::num($d(9), 'probabilitati', 5,
                'Într-o urnă sunt 5 bile albe, 4 bile roșii și o bilă neagră. Se extrag la întâmplare 5 bile. Determinați probabilitatea că au fost extrase bile de toate trei culori.',
                10 / 21,
                [
                    '$n = C_{10}^5 = 252$.',
                    'Bila neagră trebuie extrasă; celelalte 4 sunt albe și roșii, cu cel puțin una din fiecare.',
                    '$m = C_5^3 C_4^1 + C_5^2 C_4^2 + C_5^1 C_4^3$.',
                ],
                '$m = 40 + 60 + 20 = 120$, deci $p = \frac{120}{252} = \frac{10}{21}$.'),

            self::num($d(10), 'patrulatere', 6,
                'Fie paralelogramul $ABCD$, în care $AB = 13$ cm, $BD = 16$ cm, iar $O$ este punctul de intersecție a diagonalelor. Determinați perimetrul paralelogramului $ABCD$, dacă $m(\angle AOB) = 60^\circ$.',
                26 + 2 * sqrt(409),
                [
                    'Teorema cosinusului în $\triangle AOB$, cu $BO = 8$: $169 = 64 + AO^2 - 16 \cdot AO \cos 60^\circ$.',
                    '$AO^2 - 8AO - 105 = 0$, deci $AO = 15$ cm.',
                    'În $\triangle AOD$, $m(\angle AOD) = 120^\circ$: $AD^2 = 225 + 64 + 120$.',
                ],
                '$AD = \sqrt{409}$ cm, deci perimetrul este $(26 + 2\sqrt{409})$ cm.',
                'cm'),

            self::num($d(11), 'integrale', 6,
                'Fie funcțiile $f, g:[0; +\infty)\to\mathbb{R}$, $f(x) = \sqrt{x}$, $g(x) = 2 - x$. Determinați valoarea numerică a ariei figurii mărginite de graficele funcțiilor $f$ și $g$ și de axa $Oy$.',
                5 / 6,
                [
                    'Graficele se intersectează unde $\sqrt{x} = 2 - x$, adică în $x = 1$.',
                    'Pe $[0; 1]$, $g \geq f$: aria $= \int_0^1 (2 - x - \sqrt{x})\,dx$.',
                    'O primitivă: $2x - \frac{x^2}{2} - \frac{2}{3}x^{\frac{3}{2}}$.',
                ],
                '$2 - \frac{1}{2} - \frac{2}{3} = \frac{5}{6}$.'),

            self::num($d(12), 'ecuatii-inecuatii', 6,
                'Determinați valorile reale ale lui $a$, pentru care ecuația $|x^2 + 3a - 2| = a$ are 3 soluții reale.',
                0.5,
                [
                    'Pentru $a \geq 0$: $x^2 = 2 - 2a$ sau $x^2 = 2 - 4a$.',
                    'Trei soluții: una din ecuații are o soluție ($x = 0$), cealaltă două.',
                    'Deci $2 - 2a = 0$ și $2 - 4a > 0$, sau $2 - 2a > 0$ și $2 - 4a = 0$.',
                ],
                'Primul caz ($a = 1$) cere $-2 > 0$ — imposibil. Al doilea dă $a = \frac{1}{2}$: $x^2 = 1$ și $x^2 = 0$, adică trei soluții.'),
        ];
    }

    /** Profil umanist, sesiunea de bază. */
    private static function umanistBaza(): array
    {
        $d = fn (int|string $n) => self::doc('u_sb21', $n);

        return [
            self::num($d(1), 'numere-reale', 2,
                'Scrieți un număr întreg, astfel încât propoziția obținută să fie adevărată: $\left(\frac{2}{3}\right)^{\square} = \frac{81}{16}$.',
                -4,
                [
                    '$\frac{81}{16} = \left(\frac{3}{2}\right)^4$.',
                    '$\frac{3}{2}$ este inversul lui $\frac{2}{3}$.',
                    '$\left(\frac{3}{2}\right)^4 = \left(\frac{2}{3}\right)^{-4}$.',
                ],
                '$\left(\frac{2}{3}\right)^{-4} = \left(\frac{3}{2}\right)^4 = \frac{81}{16}$.'),

            self::choice($d(2), 'functii', 2,
                'Fie șirul numeric $(a_n)_{n\geq 1}$, $a_n = 1 + \frac{2}{n}$. Completați propoziția: „Șirul $(a_n)_{n\geq 1}$ este …"',
                ['monoton descrescător', 'monoton crescător'],
                [
                    'Calculează primii termeni: $a_1 = 3$, $a_2 = 2$.',
                    'Când $n$ crește, $\frac{2}{n}$ scade.',
                    '$a_{n+1} - a_n = \frac{2}{n+1} - \frac{2}{n} < 0$.',
                ],
                '$a_{n+1} < a_n$ pentru orice $n$, deci șirul este monoton descrescător.'),

            self::num($d(3), 'cerc', 2,
                'Dreptele $BA$ și $BC$ sunt tangente la cercul de centru $O$ în punctele $A$ și $C$ respectiv, iar $m(\angle ABC) = 60^\circ$. Determinați lungimea coardei $AC$, dacă se cunoaște că $AB = 7$ cm.',
                7,
                [
                    'Tangentele duse dintr-un punct exterior sunt egale: $BA = BC$.',
                    'Triunghiul $ABC$ e isoscel cu unghiul din vârf de $60^\circ$.',
                    'Un triunghi isoscel cu un unghi de $60^\circ$ e echilateral.',
                ],
                '$\triangle ABC$ e echilateral, deci $AC = AB = 7$ cm.',
                'cm'),

            self::num($d(4), 'probabilitati', 4,
                'Cu cifrele 1 și 2 se formează numere de trei cifre. Determinați probabilitatea că un număr format la întâmplare are prima și ultima cifră egale.',
                0.5,
                [
                    'Fiecare dintre cele trei poziții are 2 variante: $n = 2^3 = 8$.',
                    'Prima cifră se alege liber, ultima e aceeași, cea din mijloc e liberă.',
                    '$m = 2 \cdot 2 = 4$.',
                ],
                '$p = \frac{4}{8} = \frac{1}{2}$.'),

            self::num($d(5), 'logaritmi', 4,
                'Calculați valoarea expresiei: $\log_{\sqrt{3}} 6 - \log_3 4$.',
                2,
                [
                    '$\log_{\sqrt{3}} 6 = 2\log_3 6 = \log_3 36$.',
                    'Diferența de logaritmi în aceeași bază e logaritmul câtului.',
                    '$\log_3 \frac{36}{4} = \log_3 9$.',
                ],
                '$\log_3 36 - \log_3 4 = \log_3 9 = 2$.'),

            self::blanks($d(6), 'numere-complexe', 4,
                'Fie $z = (2 - i)(2 + i) - 3i^3$, unde $i^2 = -1$. Determinați conjugatul numărului complex $z$. Scrie rezultatul sub forma $\bar{z} = a + bi$.',
                ['a' => ['$a$', '5'], 'b' => ['$b$', '-3']],
                [
                    '$i^3 = -i$.',
                    '$(2 - i)(2 + i) = 4 - i^2 = 5$, deci $z = 5 + 3i$.',
                    'Conjugatul schimbă semnul părții imaginare.',
                ],
                '$z = 5 + 3i$, deci $\bar{z} = 5 - 3i$.'),

            self::num($d(7), 'corpuri', 5,
                'Într-un con circular drept înălțimea este de 24 cm, iar raza bazei este de 10 cm. La distanța de 6 cm de la vârf este dus un plan paralel cu baza. Determinați lungimea generatoarei conului mic obținut după secționare.',
                6.5,
                [
                    'Generatoarea conului inițial: $\sqrt{24^2 + 10^2} = 26$ cm.',
                    'Conul mic e asemenea cu cel inițial, cu raportul $\frac{6}{24} = \frac{1}{4}$.',
                    'Generatoarea mică $= \frac{26}{4}$.',
                ],
                'Generatoarea conului mic este $26 \cdot \frac{6}{24} = 6{,}5$ cm.',
                'cm'),

            self::blanks($d(8), 'derivate', 5,
                'Fie funcția $f:\mathbb{R}\to\mathbb{R}$, $f(x) = 2x^3 - x^2 - 4x$. Determinați punctele de extrem local ale funcției $f$.',
                ['max' => ['punctul de maxim local $x =$', '-2/3'], 'min' => ['punctul de minim local $x =$', '1']],
                [
                    '$f\'(x) = 6x^2 - 2x - 4$.',
                    '$f\'(x) = 0$ pentru $x = -\frac{2}{3}$ și $x = 1$.',
                    'Derivata e pozitivă în afara rădăcinilor și negativă între ele.',
                ],
                '$f\'$ trece din $+$ în $-$ în $x = -\frac{2}{3}$ (maxim local) și din $-$ în $+$ în $x = 1$ (minim local).'),

            self::num($d(9), 'patrulatere', 5,
                'Diagonalele unui romb sunt de 12 cm și 16 cm. Determinați lungimea înălțimii rombului.',
                9.6,
                [
                    'Diagonalele rombului se înjumătățesc perpendicular: 6 cm și 8 cm.',
                    'Latura: $\sqrt{6^2 + 8^2} = 10$ cm.',
                    'Aria $= \frac{12 \cdot 16}{2} = 96$ cm², iar aria $=$ latura $\cdot$ înălțimea.',
                ],
                '$h = \frac{96}{10} = 9{,}6$ cm.',
                'cm'),

            self::num($d(10), 'ecuatii-inecuatii', 6,
                'Fie mulțimea $M = \left\{x \in \mathbb{R} \;\middle|\; \sqrt{x + A_5^1} = -3x - 1\right\}$. Determinați $\operatorname{card} M$.',
                1,
                [
                    '$A_5^1 = 5$.',
                    'Sistemul: $-3x - 1 \geq 0$ și $x + 5 = (-3x - 1)^2$.',
                    '$9x^2 + 5x - 4 = 0$ are rădăcinile $-1$ și $\frac{4}{9}$; verifică prima condiție.',
                ],
                'Doar $x = -1$ satisface $-3x - 1 \geq 0$, deci $M = \{-1\}$ și $\operatorname{card} M = 1$.'),

            self::blanks($d(11), 'ecuatii-inecuatii', 5,
                'Rezolvați în $\mathbb{R} \times \mathbb{R}$ sistemul de ecuații $\begin{cases} 3^x + 2^y = 7 \\\\ 3^{x+1} + 2^{y-1} = 11 \end{cases}$.',
                ['x' => ['$x$', '1'], 'y' => ['$y$', '2']],
                [
                    'A doua ecuație: $3 \cdot 3^x + \frac{1}{2} \cdot 2^y = 11$.',
                    'Notează $u = 3^x$, $v = 2^y$ și rezolvă sistemul liniar.',
                    '$u = 3$, $v = 4$.',
                ],
                '$3^x = 3$ și $2^y = 4$, deci $S = \{(1; 2)\}$.'),

            self::choice($d(12), 'integrale', 6,
                'Fie funcția $f:[1; m]\to\mathbb{R}$, $m > 1$, $f(x) = 4x^3 - 2x$. Determinați valorile reale ale lui $m$, pentru care valoarea numerică a ariei subgraficului funcției $f$ este mai mică decât 12.',
                ['$m \in (1; 2)$', '$m \in (-2; 2)$', '$m \in (1; 3)$', '$m \in (1; \sqrt{3})$'],
                [
                    'Aria $= \int_1^m (4x^3 - 2x)\,dx$.',
                    'O primitivă: $x^4 - x^2$; integrala este $m^4 - m^2$.',
                    '$m^4 - m^2 < 12$ înseamnă $(m^2 - 4)(m^2 + 3) < 0$.',
                ],
                '$m^2 < 4$, iar cu $m > 1$ rămâne $m \in (1; 2)$.'),
        ];
    }

    /** Profil umanist, sesiunea suplimentară. */
    private static function umanistSuplimentara(): array
    {
        $d = fn (int|string $n) => self::doc('u_ss21', $n);

        return [
            self::num($d(1), 'logaritmi', 2,
                'Scrieți un număr întreg, astfel încât propoziția obținută să fie adevărată: $\log_3 \frac{1}{9} = \square$.',
                -2,
                [
                    '$\frac{1}{9} = 3^{?}$',
                    '$9 = 3^2$.',
                    '$\frac{1}{9} = 3^{-2}$.',
                ],
                '$\log_3 3^{-2} = -2$.'),

            self::choice($d(2), 'functii', 2,
                'Fie funcția $f:\mathbb{R}\to\mathbb{R}$, $f(x) = \begin{cases} -x^2 + 1, & x \geq 1 \\\\ -x + 1, & x < 1 \end{cases}$. Alegeți unul dintre semnele „<" sau „>", astfel încât propoziția obținută să fie adevărată: $f(3) \;\square\; f(-3)$.',
                ['<', '>'],
                [
                    'Pentru $x = 3$ se folosește ramura $x \geq 1$.',
                    '$f(3) = -9 + 1 = -8$.',
                    'Pentru $x = -3$: $f(-3) = 3 + 1 = 4$.',
                ],
                '$f(3) = -8 < 4 = f(-3)$.'),

            self::num($d(3), 'patrulatere', 2,
                '$AM$, $M \in (BC)$, este bisectoarea unghiului $A$ al paralelogramului $ABCD$. Determinați lungimea segmentului $MC$, dacă se cunoaște că $AB = 3$ cm și $AD = 4$ cm.',
                1,
                [
                    '$\angle MAD \equiv \angle AMB$ (alterne interne, $BC \parallel AD$).',
                    'Deci $\angle BAM \equiv \angle BMA$: triunghiul $ABM$ e isoscel.',
                    '$BM = AB = 3$ cm, iar $BC = AD = 4$ cm.',
                ],
                '$MC = BC - BM = 4 - 3 = 1$ cm.',
                'cm'),

            self::num($d(4), 'probabilitati', 4,
                'Se aruncă simultan două zaruri. Determinați probabilitatea că numerele de puncte apărute pe ambele zaruri să fie pătrate perfecte.',
                1 / 9,
                [
                    '$n = 6 \cdot 6 = 36$.',
                    'Pe un zar, pătratele perfecte sunt 1 și 4.',
                    '$m = 2 \cdot 2 = 4$.',
                ],
                '$p = \frac{4}{36} = \frac{1}{9}$.'),

            self::num($d(5), 'numere-reale', 4,
                'Calculați valoarea expresiei: $27^{\frac{2}{3}} + \left(-\frac{1}{4}\right)^{-2}$.',
                25,
                [
                    '$27^{\frac{2}{3}} = (3^3)^{\frac{2}{3}} = 9$.',
                    'Exponentul negativ inversează baza: $\left(-\frac{1}{4}\right)^{-2} = (-4)^2$.',
                    '$(-4)^2 = 16$.',
                ],
                '$9 + 16 = 25$.'),

            self::order($d(6), 'numere-complexe', 4,
                'Arătați că $z = (2 - i)^2 + 4i^5$ este un număr real, unde $i^2 = -1$. Ordonează pașii demonstrației.',
                [
                    '$i^5 = i^4 \cdot i = i$',
                    '$(2 - i)^2 = 4 - 4i + i^2 = 3 - 4i$',
                    '$z = 3 - 4i + 4i$',
                    '$z = 3 \in \mathbb{R}$',
                ],
                [
                    'Începe cu puterea lui $i$: $i^4 = 1$.',
                    'Dezvoltă pătratul binomului.',
                    'Adună și observă că partea imaginară se anulează.',
                ],
                '$i^5 = i$ și $(2 - i)^2 = 3 - 4i$, deci $z = 3 - 4i + 4i = 3 \in \mathbb{R}$.'),

            self::num($d(7), 'triunghiuri', 5,
                'Fie triunghiul isoscel $ABC$, în care $AB = BC = 20$ cm, $AC = 24$ cm, $MN \parallel AC$, $M \in (AB)$, $N \in (BC)$, astfel încât $\frac{MA}{MB} = \frac{1}{3}$. Determinați distanța de la punctul $B$ la dreapta $MN$.',
                12,
                [
                    '$MB = \frac{3}{4} \cdot 20 = 15$ cm.',
                    '$\triangle MBN \sim \triangle ABC$, cu raportul $\frac{15}{20} = \frac{3}{4}$.',
                    'Înălțimea din $B$ în $\triangle ABC$: $\sqrt{20^2 - 12^2} = 16$ cm.',
                ],
                'Distanța de la $B$ la $MN$ e înălțimea triunghiului mic: $16 \cdot \frac{3}{4} = 12$ cm. (În enunțul tipărit apare $MN \parallel AB$; desenul și baremul — $MN = 18$ cm — arată că e vorba de $MN \parallel AC$.)',
                'cm'),

            self::num($d(8), 'integrale', 5,
                'Fie funcția $f:[-1; 1]\to\mathbb{R}$, $f(x) = (2x + 1)^2$. Determinați valoarea numerică a ariei subgraficului funcției $f$.',
                14 / 3,
                [
                    'Aria $= \int_{-1}^{1} (2x + 1)^2\,dx$.',
                    '$(2x + 1)^2 = 4x^2 + 4x + 1$.',
                    'O primitivă: $\frac{4x^3}{3} + 2x^2 + x$.',
                ],
                '$\left(\frac{4}{3} + 3\right) - \left(-\frac{4}{3} + 1\right) = \frac{14}{3}$.'),

            self::num($d(9), 'corpuri', 5,
                'Dreptunghiul $ABCD$ este baza prismei drepte $ABCDA_1B_1C_1D_1$. Determinați volumul prismei, dacă $DC_1 = 2\sqrt{21}$ cm, iar diagonala $AC_1 = 10$ cm și formează cu planul bazei un unghi de $60^\circ$.',
                60 * sqrt(3),
                [
                    'În $\triangle ACC_1$: $AC = 10\cos 60^\circ = 5$ cm, $CC_1 = 10\sin 60^\circ = 5\sqrt{3}$ cm.',
                    'În $\triangle DCC_1$: $DC = \sqrt{84 - 75} = 3$ cm.',
                    '$BC = \sqrt{AC^2 - DC^2} = 4$ cm.',
                ],
                '$\mathcal{V} = 3 \cdot 4 \cdot 5\sqrt{3} = 60\sqrt{3}$ cm³.',
                'cm³'),

            self::choice($d(10), 'ecuatii-inecuatii', 6,
                'Fie $A$ mulțimea soluțiilor reale ale inecuației $2 + \frac{1}{x - 4} - \frac{9}{x + 4} < 0$. Determinați mulțimea $A \cap \mathbb{Z}$.',
                [
                    '$\{-3, -2, -1, 0, 1, 3\}$',
                    '$\{-3, -2, -1, 0, 1, 2, 3\}$',
                    '$\{-4, -3, -2, -1, 0, 1, 3, 4\}$',
                    '$\{-3, -2, -1, 0, 1\}$',
                ],
                [
                    'Adu la același numitor: $\frac{2x^2 - 8x + 8}{(x - 4)(x + 4)} < 0$.',
                    'Numărătorul e $2(x - 2)^2$: nu e negativ niciodată și e zero în $x = 2$.',
                    'Inegalitatea e strictă, deci $x = 2$ se exclude.',
                ],
                '$A = (-4; 2) \cup (2; 4)$, deci $A \cap \mathbb{Z} = \{-3, -2, -1, 0, 1, 3\}$.'),

            self::num($d(11), 'logaritmi', 5,
                'Rezolvați în $\mathbb{R}$ ecuația $\log_2(x - 1) + \log_2(x^2 + x - 2) = 1$.',
                sqrt(3),
                [
                    'Domeniul: $x - 1 > 0$ și $x^2 + x - 2 > 0$, adică $x > 1$.',
                    '$(x - 1)(x^2 + x - 2) = 2$.',
                    'Ecuația devine $x^3 - 3x = 0$.',
                ],
                'Soluțiile lui $x(x^2 - 3) = 0$ sunt $0$ și $\pm\sqrt{3}$; doar $\sqrt{3}$ e în domeniu, deci $S = \{\sqrt{3}\}$.'),

            self::num($d(12), 'derivate', 6,
                'Fie funcția $f:\left(\frac{1}{2}; +\infty\right)\to\mathbb{R}$, $f(x) = \sqrt{2x - 1} - \ln x$. Scrieți ecuația tangentei la graficul funcției $f$, care este paralelă axei absciselor. Ecuația are forma $y = c$; scrie valoarea lui $c$.',
                1,
                [
                    '$f\'(x) = \frac{1}{\sqrt{2x - 1}} - \frac{1}{x}$.',
                    'Tangenta paralelă cu $Ox$ are panta 0: $f\'(x) = 0$, adică $x = \sqrt{2x - 1}$, deci $x = 1$.',
                    'Calculează $f(1)$.',
                ],
                '$f(1) = 1 - 0 = 1$, deci tangenta are ecuația $y = 1$.'),
        ];
    }
}
