<?php

namespace Database\Seeders\Matematica;

/**
 * Testele ANCE de matematică din 2023 (12_mat_test_{r,u}_ro_{sb,ss}23.pdf și
 * baremele cu același nume).
 */
final class Ance2023
{
    use AnceItems;

    /** @return list<array<string, mixed>> */
    public static function items(): array
    {
        return [...self::realBaza(), ...self::realSuplimentara(), ...self::umanistBaza(), ...self::umanistSuplimentara()];
    }

    /** Profil real, sesiunea de bază. */
    private static function realBaza(): array
    {
        $d = fn (int|string $n) => self::doc('r_sb23', $n);
        $f = 'Fie funcția $f:\mathbb{R}\setminus\{1\}\to\mathbb{R}$, $f(x) = \frac{x^2 + 3}{x - 1}$. ';

        return [
            self::num($d(1), 'numere-reale', 5,
                'Calculați valoarea expresiei: $\sqrt{9^{1{,}5} - 2}$.',
                5,
                [
                    '$9^{1{,}5} = (3^2)^{1{,}5} = 3^3$.',
                    '$9^{1{,}5} = 27$.',
                    '$\sqrt{27 - 2} = \sqrt{25}$.',
                ],
                '$9^{1{,}5} = 27$, deci $\sqrt{27 - 2} = 5$.'),

            self::num($d(2), 'numere-complexe', 5,
                'Calculați determinantul matricei $A = \begin{pmatrix} 1 + 3i & -6 \\\\ i^3 & 1 + 3i \end{pmatrix}$, unde $i^2 = -1$.',
                -8,
                [
                    '$\det A = (1 + 3i)^2 - (-6) \cdot i^3 = (1 + 3i)^2 + 6i^3$.',
                    '$(1 + 3i)^2 = 1 + 6i - 9 = -8 + 6i$, iar $i^3 = -i$.',
                    'Adună: $-8 + 6i - 6i$.',
                ],
                '$\det A = (1 + 3i)^2 + 6i^3 = -8 + 6i - 6i = -8$.'),

            self::choice($d(3), 'ecuatii-inecuatii', 8,
                'Rezolvați în $\mathbb{R}$ inecuația $0{,}25^{x + 3} \leq 8 \cdot 2^x$.',
                ['$S = [-3; +\infty)$', '$S = (-\infty; -3]$', '$S = [3; +\infty)$', '$S = (-3; +\infty)$'],
                [
                    'Scrie totul ca putere a lui 2: $0{,}25 = 2^{-2}$, $8 = 2^3$.',
                    '$2^{-2x - 6} \leq 2^{x + 3}$; baza 2 e supraunitară.',
                    '$-2x - 6 \leq x + 3$.',
                ],
                '$2^{-2x - 6} \leq 2^{x + 3}$ dă $-2x - 6 \leq x + 3$, adică $x \geq -3$: $S = [-3; +\infty)$. Inegalitatea e nestrictă, deci $-3$ e inclus.'),

            self::num($d(4), 'identitati-trigonometrice', 8,
                'Calculați $\sin\left(\alpha + \frac{\pi}{3}\right)$, dacă se cunoaște că $\cos\alpha = -\frac{3}{5}$ și $\alpha \in \left[\pi; \frac{3\pi}{2}\right]$.',
                (-4 - 3 * sqrt(3)) / 10,
                [
                    'Din $\sin^2\alpha + \cos^2\alpha = 1$: $\sin\alpha = \pm\frac{4}{5}$.',
                    'În cadranul III sinusul e negativ: $\sin\alpha = -\frac{4}{5}$.',
                    '$\sin\left(\alpha + \frac{\pi}{3}\right) = \sin\alpha \cdot \frac{1}{2} + \cos\alpha \cdot \frac{\sqrt{3}}{2}$.',
                ],
                '$\sin\alpha = -\frac{4}{5}$, deci $\sin\left(\alpha + \frac{\pi}{3}\right) = -\frac{4}{10} - \frac{3\sqrt{3}}{10} = \frac{-4 - 3\sqrt{3}}{10}$.'),

            self::choice($d(5), 'logaritmi', 8,
                'Rezolvați în $\mathbb{R}$ ecuația $\log_3^2(-x) - 2\log_3\frac{x^2}{27} - 6 = 0$.',
                ['$S = \{-81; -1\}$', '$S = \{1; 81\}$', '$S = \{-1\}$', '$S = \{-81; 81\}$'],
                [
                    'Domeniul: $-x > 0$, deci $x < 0$.',
                    'Pentru $x < 0$: $\log_3 x^2 = 2\log_3(-x)$ și $\log_3 27 = 3$. Notează $t = \log_3(-x)$.',
                    '$t^2 - 2(2t - 3) - 6 = 0$, adică $t^2 - 4t = 0$.',
                ],
                'Cu $t = \log_3(-x)$: $t^2 - 4t = 0$, deci $t = 0$ sau $t = 4$, adică $-x = 1$ sau $-x = 81$. $S = \{-81; -1\}$; soluțiile pozitive nu sunt în domeniu.'),

            self::num($d(6), 'triunghiuri', 5,
                'Fie triunghiul $ABC$, în care $MN \parallel AC$, $M \in (AB)$, $N \in (BC)$. Determinați lungimea segmentului $BN$, dacă $MN = 4$ cm, $NC = 5$ cm, $AC = 14$ cm.',
                2,
                [
                    'Triunghiurile $MBN$ și $ABC$ sunt asemenea.',
                    '$\frac{BN}{BC} = \frac{MN}{AC}$, cu $BC = BN + 5$.',
                    '$\frac{BN}{BN + 5} = \frac{4}{14}$.',
                ],
                '$14 \cdot BN = 4(BN + 5)$, deci $BN = 2$ cm.',
                'cm'),

            self::num($d(7), 'corpuri', 8,
                'Într-un trunchi de con circular drept, ariile bazelor sunt egale cu $\pi$ cm² și $16\pi$ cm², iar volumul este egal cu $28\pi$ cm³. Determinați aria laterală a trunchiului de con.',
                25 * M_PI,
                [
                    'Razele bazelor: $r = 1$ cm și $R = 4$ cm.',
                    '$\mathcal{V} = \frac{\pi H}{3}(R^2 + Rr + r^2) = 7\pi H = 28\pi$, deci $H = 4$ cm.',
                    'Generatoarea: $G = \sqrt{H^2 + (R - r)^2}$; $\mathcal{A}_{lat} = \pi G(R + r)$.',
                ],
                '$r = 1$, $R = 4$, $H = 4$, $G = 5$ cm, deci $\mathcal{A}_{lat} = \pi \cdot 5 \cdot 5 = 25\pi$ cm².',
                'cm²'),

            self::num($d(8), 'triunghiuri', 8,
                'În triunghiul $ABC$, $AB = 26$ cm, iar medianele $AN$ și $BM$ se intersectează în punctul $O$, astfel încât $m(\angle AOB) = 120^\circ$. Determinați lungimea medianei $AN$, dacă $BM = 24$ cm.',
                21,
                [
                    'Centrul de greutate: $BO = \frac{2}{3} BM = 16$ cm.',
                    'Teorema cosinusului în $AOB$: $26^2 = 16^2 + AO^2 - 2 \cdot 16 \cdot AO \cos 120^\circ$.',
                    '$676 = 256 + AO^2 + 16 AO$; apoi $AN = \frac{3}{2} AO$.',
                ],
                '$AO^2 + 16AO - 420 = 0$ dă $AO = 14$ cm, deci $AN = \frac{3}{2} \cdot 14 = 21$ cm.',
                'cm'),

            self::num($d(9), 'progresii', 5,
                'Determinați rația progresiei geometrice $(b_n)_{n \geq 1}$, dacă $b_3 = 20$ și $b_6 = 160$.',
                2,
                [
                    '$b_6 = b_3 \cdot q^3$.',
                    '$q^3 = 8$.',
                    'Rădăcina cubică.',
                ],
                '$q^3 = \frac{160}{20} = 8$, deci $q = 2$.'),

            self::blanks($d('10a'), 'derivate', 8,
                $f.'Determinați punctele de extrem local ale funcției $f$.',
                ['max' => ['maxim: $x =$', '-1'], 'min' => ['minim: $x =$', '3']],
                [
                    '$f\'(x) = \frac{2x(x - 1) - (x^2 + 3)}{(x - 1)^2} = \frac{x^2 - 2x - 3}{(x - 1)^2}$.',
                    'Zerourile derivatei: $-1$ și $3$.',
                    'Semnul derivatei: +, −, −, + (cu $x \neq 1$).',
                ],
                '$f\'(x) = \frac{x^2 - 2x - 3}{(x - 1)^2}$: $x = -1$ e punct de maxim local, $x = 3$ — de minim local.'),

            self::blanks($d('10b'), 'limite', 8,
                $f.'Determinați asimptota oblică la $+\infty$ a graficului funcției $f$. Scrie-o sub forma $y = mx + n$.',
                ['m' => ['$m$', '1'], 'n' => ['$n$', '1']],
                [
                    '$m = \lim\limits_{x \to +\infty} \frac{x^2 + 3}{x^2 - x}$.',
                    '$n = \lim\limits_{x \to +\infty} (f(x) - x) = \lim\limits_{x \to +\infty} \frac{x + 3}{x - 1}$.',
                    'Ambele limite sunt 1.',
                ],
                '$m = 1$ și $n = 1$, deci asimptota oblică este $y = x + 1$.'),

            self::num($d('10c'), 'integrale', 8,
                $f.'Calculați: $\int_2^3 f(x)\,dx$.',
                3.5 + 4 * log(2),
                [
                    'Împarte: $f(x) = x + 1 + \frac{4}{x - 1}$.',
                    'O primitivă: $\frac{x^2}{2} + x + 4\ln(x - 1)$.',
                    'Newton–Leibniz între 2 și 3.',
                ],
                '$\left[\frac{x^2}{2} + x + 4\ln(x - 1)\right]_2^3 = (4{,}5 + 3 + 4\ln 2) - (2 + 2) = \frac{7}{2} + 4\ln 2$. Se scrie „7/2 + 4ln2".'),

            self::num($d(11), 'probabilitati', 8,
                'Cu cifrele 1, 2, 3, 4, 5, 6 se formează la întâmplare un număr de patru cifre. Determinați probabilitatea că cifrele numărului format nu se repetă și primele trei cifre sunt impare.',
                1 / 72,
                [
                    'Cifrele se pot repeta, deci cazurile posibile sunt $n = 6^4$.',
                    'Primele trei cifre: o permutare a cifrelor impare 1, 3, 5 — $3!$ moduri.',
                    'A patra cifră e una dintre cele 3 pare rămase.',
                ],
                '$n = 6^4 = 1296$, $m = 3! \cdot 3 = 18$, deci $p = \frac{18}{1296} = \frac{1}{72}$.'),

            self::blanks($d(12), 'combinatorica', 8,
                'Suma coeficienților binomiali ai dezvoltării la putere a binomului $\left(x + \frac{1}{\sqrt[3]{x}}\right)^n$ este egală cu 4096. Determinați termenul de mijloc al dezvoltării. Scrie-l sub forma $a \cdot x^b$.',
                ['a' => ['$a =$', '924'], 'b' => ['$b =$', '4']],
                [
                    'Suma coeficienților binomiali este $2^n = 4096$.',
                    '$n = 12$, deci termenul de mijloc e $T_7$.',
                    '$T_7 = C_{12}^6 x^6 \left(x^{-\frac{1}{3}}\right)^6$.',
                ],
                '$n = 12$, iar $T_7 = C_{12}^6 \cdot x^6 \cdot x^{-2} = 924x^4$.'),
        ];
    }

    /** Profil real, sesiunea suplimentară. */
    private static function realSuplimentara(): array
    {
        $d = fn (int|string $n) => self::doc('r_ss23', $n);
        $f = 'Fie funcția $f:(0; +\infty)\to\mathbb{R}$, $f(x) = 8\ln x - x^2$. ';

        return [
            self::num($d(1), 'logaritmi', 5,
                'Calculați valoarea expresiei $\log_3 36 - 2\log_3 2$.',
                2,
                [
                    '$2\log_3 2 = \log_3 4$.',
                    '$\log_3 36 - \log_3 4 = \log_3 9$.',
                    '$9 = 3^2$.',
                ],
                '$\log_3 36 - \log_3 4 = \log_3 9 = 2$.'),

            self::choice($d(2), 'ecuatii-inecuatii', 5,
                'Rezolvați în $\mathbb{R}$ ecuația $\sqrt{4x + 12} = x$.',
                ['$S = \{6\}$', '$S = \{-2; 6\}$', '$S = \{-2\}$', '$S = \varnothing$'],
                [
                    'Membrul drept trebuie să fie nenegativ: $x \geq 0$.',
                    'Ridică la pătrat: $x^2 = 4x + 12$.',
                    'Rădăcinile sunt $-2$ și $6$. Care respectă $x \geq 0$?',
                ],
                'Sistemul $x \geq 0$, $x^2 = 4x + 12$ dă doar $x = 6$; $-2$ e soluție străină.'),

            self::choice($d(3), 'matrici', 8,
                'Fie matricea $A = \begin{pmatrix} \frac{1}{x - 1} & 9 \\\\ x & x^3(x - 1) \end{pmatrix}$. Determinați valorile reale ale lui $x$, pentru care matricea $A$ este inversabilă.',
                ['$x \in \mathbb{R} \setminus \{-3; 0; 1; 3\}$', '$x \in \mathbb{R} \setminus \{-3; 0; 3\}$', '$x \in \mathbb{R} \setminus \{1\}$', '$x \in \mathbb{R} \setminus \{-3; 3\}$'],
                [
                    'Pentru $x = 1$ matricea nici nu există.',
                    '$\det A = \frac{1}{x - 1} \cdot x^3(x - 1) - 9x = x^3 - 9x$.',
                    '$x^3 - 9x = x(x - 3)(x + 3)$.',
                ],
                'Condițiile: $x \neq 1$ și $\det A = x^3 - 9x \neq 0$, adică $x \notin \{-3; 0; 3\}$. Deci $x \in \mathbb{R} \setminus \{-3; 0; 1; 3\}$.'),

            self::num($d(4), 'numere-complexe', 8,
                'Fie expresia $E(z) = pz^2 + p^2 z + 2 - 6i$. Determinați valorile reale ale lui $p$, pentru care $E(1 + 2i)$ este un număr real nenul.',
                -3,
                [
                    '$(1 + 2i)^2 = -3 + 4i$.',
                    '$E(1 + 2i) = (p^2 - 3p + 2) + i(2p^2 + 4p - 6)$.',
                    'Partea imaginară zero, partea reală nenulă.',
                ],
                '$2p^2 + 4p - 6 = 0$ dă $p = 1$ sau $p = -3$; pentru $p = 1$ partea reală $p^2 - 3p + 2$ e 0, deci rămâne $p = -3$.'),

            self::choice($d(5), 'logaritmi', 8,
                'Rezolvați în $\mathbb{R}$ inecuația $\log_{3 - x} 0{,}25 \leq -2$.',
                ['$S = [1; 2)$', '$S = (2; 3)$', '$S = [1; 3)$', '$S = (-\infty; 1]$'],
                [
                    'Baza $3 - x$ trebuie să fie pozitivă și diferită de 1. Discută după bază: subunitară sau supraunitară.',
                    '$-2 = \log_{3 - x} \frac{1}{(3 - x)^2}$.',
                    'Bază subunitară: $\frac{1}{4} \geq \frac{1}{(3 - x)^2}$; supraunitară: $\frac{1}{4} \leq \frac{1}{(3 - x)^2}$.',
                ],
                'Cazul $0 < 3 - x < 1$ nu are soluții; cazul $3 - x > 1$ dă $(3 - x)^2 \leq 4$, adică $1 \leq x < 2$. $S = [1; 2)$.'),

            self::num($d(6), 'cerc', 5,
                'Punctele $M$ și $B$ aparțin cercului de centru $O$ astfel, încât $AM$ este tangentă la cerc, iar segmentul $AB$ conține punctul $O$. Determinați măsura în grade a unghiului $OAM$, dacă se cunoaște că $AB = 3$ cm, iar raza cercului este de 1 cm.',
                30,
                [
                    '$OB$ e rază, deci $AO = AB - OB = 2$ cm.',
                    'Tangenta e perpendiculară pe rază: triunghiul $OMA$ e dreptunghic în $M$.',
                    '$\sin(\angle OAM) = \frac{OM}{AO} = \frac{1}{2}$.',
                ],
                '$AO = 2$ cm, $OM = 1$ cm și $OM \perp AM$, deci $\sin(\angle OAM) = \frac{1}{2}$ și unghiul are $30^\circ$.',
                '°'),

            self::num($d(7), 'corpuri', 8,
                'Determinați volumul unei piramide patrulatere regulate cu toate muchiile de 6 cm.',
                36 * sqrt(2),
                [
                    'Diagonala bazei: $6\sqrt{2}$ cm; jumătatea ei $3\sqrt{2}$ cm.',
                    'Înălțimea: $H = \sqrt{6^2 - (3\sqrt{2})^2} = 3\sqrt{2}$ cm.',
                    '$\mathcal{V} = \frac{1}{3} \cdot 36 \cdot H$.',
                ],
                '$H = 3\sqrt{2}$ cm, deci $\mathcal{V} = \frac{1}{3} \cdot 36 \cdot 3\sqrt{2} = 36\sqrt{2}$ cm³.',
                'cm³'),

            self::num($d(8), 'patrulatere', 8,
                'Într-un trapez isoscel poate fi înscris un cerc. Baza mică a trapezului este de 3 cm, iar unghiul de la baza mare este de $60^\circ$. Determinați lungimea razei cercului circumscris trapezului.',
                sqrt(21),
                [
                    'Cu proiecția laturii $p$: latura $= 2p$, înălțimea $= p\sqrt{3}$, baza mare $= 3 + 2p$.',
                    'Cerc înscris: suma bazelor = suma laturilor: $6 + 2p = 4p$, deci $p = 3$.',
                    'Diagonala: $\sqrt{6^2 + 27} = \sqrt{63}$; raza $R = \frac{d}{2\sin 60^\circ}$.',
                ],
                '$p = 3$, latura 6 cm, baza mare 9 cm, diagonala $\sqrt{63}$ cm, deci $R = \frac{\sqrt{63}}{\sqrt{3}} = \sqrt{21}$ cm.',
                'cm'),

            self::choice($d(9), 'progresii', 5,
                'Stabiliți monotonia șirului $(a_n)_{n \geq 1}$, $a_n = \frac{2n}{n + 1}$.',
                ['șirul este strict crescător', 'șirul este strict descrescător', 'șirul este constant', 'șirul nu este monoton'],
                [
                    'Calculează $a_{n+1} - a_n$.',
                    '$\frac{2(n + 1)}{n + 2} - \frac{2n}{n + 1}$.',
                    'Diferența este $\frac{2}{(n + 1)(n + 2)}$.',
                ],
                '$a_{n+1} - a_n = \frac{2}{(n + 1)(n + 2)} > 0$, deci șirul e monoton crescător.'),

            self::blanks($d('10a'), 'derivate', 8,
                $f.'Scrieți ecuația tangentei la graficul funcției $f$ în punctul de abscisă $x_0 = 1$. Scrie-o sub forma $y = mx + n$.',
                ['m' => ['$m$', '6'], 'n' => ['$n$', '-7']],
                [
                    '$f\'(x) = \frac{8}{x} - 2x$.',
                    '$f\'(1) = 6$ și $f(1) = -1$.',
                    '$y = f(1) + f\'(1)(x - 1)$.',
                ],
                '$y = -1 + 6(x - 1) = 6x - 7$.'),

            self::num($d('10b'), 'limite', 8,
                $f.'Calculați: $\lim\limits_{x \to 0} \frac{f(x + 1) + x^2 + 2x + 1}{2x}$.',
                4,
                [
                    '$f(x + 1) = 8\ln(x + 1) - (x + 1)^2$.',
                    'Numărătorul se reduce la $8\ln(x + 1)$.',
                    '$\lim\limits_{x \to 0} \frac{\ln(x + 1)}{x} = 1$.',
                ],
                'Limita devine $\lim\limits_{x \to 0} \frac{8\ln(x + 1)}{2x} = 4$.'),

            self::num($d('10c'), 'integrale', 8,
                $f.'Calculați: $\int_1^e f(x)\,dx$.',
                (25 - M_E ** 3) / 3,
                [
                    'O primitivă a lui $8\ln x$ (prin părți): $8x\ln x - 8x$.',
                    'Primitiva lui $f$: $8x\ln x - 8x - \frac{x^3}{3}$.',
                    'Newton–Leibniz între 1 și $e$.',
                ],
                '$\left[8x\ln x - 8x - \frac{x^3}{3}\right]_1^e = -\frac{e^3}{3} + 8 + \frac{1}{3} = \frac{25 - e^3}{3}$. Se scrie „(25 - e^3)/3".'),

            self::num($d(11), 'probabilitati', 8,
                'Într-un magazin activează secțiile A, B și C. Pe parcursul unei ore în magazin intră 7 persoane. Fiecare persoană poate intra aleator în una dintre secții. Determinați probabilitatea că 3 persoane intră în secția A, 2 persoane intră în secția B și 2 persoane intră în secția C.',
                70 / 729,
                [
                    'Fiecare din 7 persoane alege una din 3 secții: $n = 3^7$.',
                    'Alegi cele 3 pentru A, apoi 2 din cele rămase pentru B.',
                    '$m = C_7^3 \cdot C_4^2 \cdot C_2^2$.',
                ],
                '$n = 2187$, $m = 35 \cdot 6 \cdot 1 = 210$, deci $p = \frac{210}{2187} = \frac{70}{729}$.'),

            self::num($d(12), 'combinatorica', 8,
                'Determinați numărul de termeni raționali în dezvoltarea la putere a binomului $\left(\sqrt{5} + \sqrt[3]{5}\right)^{100}$.',
                17,
                [
                    '$T_{k+1} = C_{100}^k \cdot 5^{\frac{100 - k}{2}} \cdot 5^{\frac{k}{3}} = C_{100}^k \cdot 5^{\frac{300 - k}{6}}$.',
                    'Termenul e rațional când $300 - k$ se divide cu 6, adică $k$ e multiplu de 6.',
                    'Câți multipli de 6 sunt între 0 și 100?',
                ],
                '$k \in \{0, 6, 12, \ldots, 96\}$ — 17 valori, deci 17 termeni raționali.'),
        ];
    }

    /** Profil umanist, sesiunea de bază. */
    private static function umanistBaza(): array
    {
        $d = fn (int|string $n) => self::doc('u_sb23', $n);

        return [
            self::num($d(1), 'logaritmi', 5,
                'Calculați: $\log_4 32 - 4{,}5$.',
                -2,
                [
                    '$\log_4 32 = \log_{2^2} 2^5$.',
                    '$\log_{2^2} 2^5 = \frac{5}{2}$.',
                    '$2{,}5 - 4{,}5$.',
                ],
                '$\log_4 32 = \frac{5}{2}$, deci $\frac{5}{2} - 4{,}5 = -2$.'),

            self::num($d(2), 'matrici', 8,
                'Fie matricea $A = \begin{pmatrix} 2 & 1 \\\\ -1 & 3 \end{pmatrix}$. Calculați determinantul matricei $X = A^2 - 3I_2$, unde $I_2 = \begin{pmatrix} 1 & 0 \\\\ 0 & 1 \end{pmatrix}$.',
                25,
                [
                    '$A^2 = \begin{pmatrix} 3 & 5 \\\\ -5 & 8 \end{pmatrix}$.',
                    '$X = \begin{pmatrix} 0 & 5 \\\\ -5 & 5 \end{pmatrix}$.',
                    '$\det X = 0 \cdot 5 - 5 \cdot (-5)$.',
                ],
                '$X = \begin{pmatrix} 0 & 5 \\\\ -5 & 5 \end{pmatrix}$, iar $\det X = 25$.'),

            self::num($d(3), 'numere-reale', 8,
                'Determinați valoarea expresiei: $\sqrt[3]{\left(6\frac{1}{4}\right)^{\frac{1}{2}} - \left(\frac{1}{81}\right)^{-0{,}25} + \frac{3}{8}}$.',
                -0.5,
                [
                    '$6\frac{1}{4} = \frac{25}{4}$, iar $\left(\frac{25}{4}\right)^{\frac{1}{2}} = \frac{5}{2}$.',
                    '$\left(\frac{1}{81}\right)^{-0{,}25} = (3^{-4})^{-0{,}25} = 3$.',
                    'Sub radical: $\frac{5}{2} - 3 + \frac{3}{8} = -\frac{1}{8}$.',
                ],
                '$\sqrt[3]{\frac{5}{2} - 3 + \frac{3}{8}} = \sqrt[3]{-\frac{1}{8}} = -\frac{1}{2}$.'),

            self::blanks($d(4), 'numere-complexe', 8,
                'Determinați numerele complexe $z = a + bi$, $a, b \in \mathbb{R}$, $i^2 = -1$, pentru care $i\bar{z} + 2z = 5 - 2i$, unde $\bar{z}$ este conjugatul numărului $z$.',
                ['a' => ['$a$', '4'], 'b' => ['$b$', '-3']],
                [
                    '$i\bar{z} = i(a - bi) = ai + b$.',
                    'Ecuația: $(2a + b) + (a + 2b)i = 5 - 2i$.',
                    'Sistemul $2a + b = 5$, $a + 2b = -2$.',
                ],
                'Sistemul $2a + b = 5$, $a + 2b = -2$ dă $a = 4$, $b = -3$, deci $z = 4 - 3i$.'),

            self::choice($d(5), 'ecuatii-inecuatii', 8,
                'Determinați valorile reale ale lui $a$, pentru care ecuația $x^2 + ax + 3 - a = 0$ are două soluții reale distincte și pozitive.',
                ['$a \in (-\infty; -6)$', '$a \in (-\infty; -6) \cup (2; +\infty)$', '$a \in (-6; 2)$', '$a \in (2; 3)$'],
                [
                    'Distincte: $\Delta = a^2 - 4(3 - a) = a^2 + 4a - 12 > 0$.',
                    'Pozitive: produsul $3 - a > 0$ și suma $-a > 0$.',
                    'Intersectează $(-\infty; -6) \cup (2; +\infty)$ cu $a < 0$.',
                ],
                '$\Delta > 0$ dă $a < -6$ sau $a > 2$; soluțiile pozitive cer $a < 3$ și $a < 0$. Rămâne $a \in (-\infty; -6)$.'),

            self::num($d(6), 'cerc', 5,
                'Punctele $A$, $B$ și $C$ aparțin cercului de centru $O$, astfel încât unghiul înscris $ABC$ are $50^\circ$, iar unghiul la centru $AOC$ are $(3x - 50)^\circ$. Determinați valoarea lui $x$.',
                50,
                [
                    'Ambele unghiuri subîntind același arc $AC$.',
                    'Unghiul la centru e dublul celui înscris: $3x - 50 = 100$.',
                    'Rezolvă ecuația.',
                ],
                '$3x - 50 = 2 \cdot 50 = 100$, deci $x = 50$.'),

            self::num($d(7), 'corpuri', 5,
                'Aria totală a unui cub este egală cu 12 cm². Determinați lungimea diagonalei unei fețe a cubului.',
                2,
                [
                    'Cubul are 6 fețe pătrate egale: $6a^2 = 12$.',
                    '$a = \sqrt{2}$ cm.',
                    'Diagonala pătratului: $a\sqrt{2}$.',
                ],
                '$a = \sqrt{2}$ cm, deci diagonala feței are $\sqrt{2} \cdot \sqrt{2} = 2$ cm.',
                'cm'),

            self::num($d(8), 'patrulatere', 8,
                'Unghiul obtuz al unui romb este de $120^\circ$. Determinați lungimea diagonalei mari a rombului, dacă perimetrul rombului este egal cu 40 cm.',
                10 * sqrt(3),
                [
                    'Latura rombului: 10 cm.',
                    'Unghiul ascuțit are $60^\circ$, deci diagonala mică formează un triunghi echilateral: 10 cm.',
                    'Jumătatea diagonalei mari: $\sqrt{10^2 - 5^2} = 5\sqrt{3}$ cm.',
                ],
                'Diagonala mică are 10 cm, iar diagonala mare $2 \cdot 5\sqrt{3} = 10\sqrt{3}$ cm.',
                'cm'),

            self::num($d(9), 'corpuri', 8,
                'Într-un con circular drept înălțimea este de 8 cm, iar generatoarea de 10 cm. Prin punctul ce împarte înălțimea conului în raportul 1:2 de la vârf este dus un plan paralel bazei. Determinați aria secțiunii obținute la intersecția conului cu planul secant.',
                4 * M_PI,
                [
                    'Raza bazei: $\sqrt{10^2 - 8^2} = 6$ cm.',
                    'Secțiunea e la o treime din înălțime, măsurat de la vârf: triunghiuri asemenea cu raportul $\frac{1}{3}$.',
                    'Raza secțiunii: 2 cm.',
                ],
                'Raza secțiunii este $\frac{1}{3} \cdot 6 = 2$ cm, deci aria este $4\pi$ cm².',
                'cm²'),

            self::num($d(10), 'functii', 5,
                'Fie funcția $f:\mathbb{R}^*\to\mathbb{R}$, $f(x) = \frac{2}{x}$. Determinați dacă punctul $M(\sqrt{3} - 1; \sqrt{3} + 1)$ aparține graficului funcției $f$. Cât este $f(\sqrt{3} - 1)$?',
                sqrt(3) + 1,
                [
                    'Calculează $f(\sqrt{3} - 1) = \frac{2}{\sqrt{3} - 1}$.',
                    'Raționalizează cu $\sqrt{3} + 1$.',
                    '$(\sqrt{3} - 1)(\sqrt{3} + 1) = 2$.',
                ],
                '$f(\sqrt{3} - 1) = \frac{2(\sqrt{3} + 1)}{2} = \sqrt{3} + 1$, deci $M$ aparține graficului.'),

            self::num($d(11), 'progresii', 8,
                'Determinați suma primilor 20 de termeni ai progresiei aritmetice $(a_n)_{n \geq 1}$, dacă $a_4 = 15$ și $a_7 = 9$.',
                40,
                [
                    'Sistemul: $a_1 + 3r = 15$, $a_1 + 6r = 9$.',
                    '$r = -2$, $a_1 = 21$.',
                    '$S_{20} = \frac{2a_1 + 19r}{2} \cdot 20$.',
                ],
                '$r = -2$, $a_1 = 21$, deci $S_{20} = (42 - 38) \cdot 10 = 40$.'),

            self::blanks($d(12), 'functii', 8,
                'Fie funcția $f:\mathbb{R}\to\mathbb{R}$, $f(x) = x^2 + (p - 1)x + q^2 + 2$. Determinați valorile reale ale lui $p$ și $q$, pentru care punctul $A\left(-\frac{3}{2}; 0\right)$ este vârful parabolei ce reprezintă graficul funcției $f$.',
                ['p' => ['$p$', '4'], 'q1' => ['valoarea negativă a lui $q$', '-1/2'], 'q2' => ['valoarea pozitivă a lui $q$', '1/2']],
                [
                    'Abscisa vârfului: $-\frac{p - 1}{2} = -\frac{3}{2}$.',
                    '$p = 4$; ordonata vârfului e 0: $f\left(-\frac{3}{2}\right) = 0$.',
                    '$\frac{9}{4} - \frac{9}{2} + q^2 + 2 = 0$.',
                ],
                '$p = 4$, iar $q^2 = \frac{1}{4}$, deci $q = -\frac{1}{2}$ sau $q = \frac{1}{2}$.'),

            self::num($d(13), 'probabilitati', 8,
                'Cu cifrele 1, 3, 4, 7 se formează în mod aleator un număr de trei cifre, care nu se repetă. Determinați probabilitatea că numărul format este divizibil prin 3.',
                0.25,
                [
                    'Cazurile posibile: $n = A_4^3$.',
                    'Divizibil prin 3: suma cifrelor se divide cu 3. Doar 1, 4, 7 au suma 12.',
                    '$m = 3!$.',
                ],
                '$n = 24$, $m = 6$, deci $p = \frac{1}{4}$.'),

            self::num($d(14), 'probabilitati', 8,
                'Un bilet pentru o călătorie în transportul public costă 6 lei, iar un abonament pentru elevi pe o perioadă de 6 luni costă 486 de lei. Un elev efectuează lunar 54 de călătorii. Determinați câte procente din costul biletului achită elevul prin abonament.',
                25,
                [
                    'În 6 luni elevul face $6 \cdot 54 = 324$ de călătorii.',
                    'Costul unei călătorii cu abonament: $486 : 324 = 1{,}5$ lei.',
                    'Ce procent din 6 lei reprezintă 1,5 lei?',
                ],
                'O călătorie cu abonament costă 1,5 lei, adică 25% din 6 lei.',
                '%'),
        ];
    }

    /** Profil umanist, sesiunea suplimentară. */
    private static function umanistSuplimentara(): array
    {
        $d = fn (int|string $n) => self::doc('u_ss23', $n);

        return [
            self::num($d(1), 'numere-reale', 5,
                'Calculați: $\sqrt[3]{16} \cdot 32^{\frac{1}{3}}$.',
                8,
                [
                    '$\sqrt[3]{16} = 2^{\frac{4}{3}}$.',
                    '$32^{\frac{1}{3}} = 2^{\frac{5}{3}}$.',
                    'La înmulțire exponenții se adună.',
                ],
                '$2^{\frac{4}{3}} \cdot 2^{\frac{5}{3}} = 2^3 = 8$.'),

            self::num($d(2), 'numere-complexe', 8,
                'Determinați modulul numărului complex $z$, pentru care $zi = (2 - i)(1 + 2i)$, unde $i^2 = -1$.',
                5,
                [
                    '$z = \frac{(2 - i)(1 + 2i)}{i}$.',
                    '$(2 - i)(1 + 2i) = 4 + 3i$; împarte la $i$ (amplifică cu $i$).',
                    '$z = 3 - 4i$.',
                ],
                '$z = 3 - 4i$, deci $|z| = 5$.'),

            self::blanks($d(3), 'matrici', 8,
                'Rezolvați în $\mathbb{R} \times \mathbb{R} \times \mathbb{R}$ sistemul de ecuații: $x_1 + 2x_2 + 3x_3 = 10$, $2x_1 - x_2 = 4$, $x_2 + 3x_3 = 5$.',
                ['x1' => ['$x_1$', '3'], 'x2' => ['$x_2$', '2'], 'x3' => ['$x_3$', '1']],
                [
                    'Poți folosi regula lui Cramer: $\Delta = -9$.',
                    '$\Delta_1 = -27$, $\Delta_2 = -18$, $\Delta_3 = -9$.',
                    '$x_i = \frac{\Delta_i}{\Delta}$.',
                ],
                'Cu regula lui Cramer: $x_1 = 3$, $x_2 = 2$, $x_3 = 1$.'),

            self::num($d(4), 'logaritmi', 8,
                'Calculați valoarea expresiei $7^{\frac{2}{\log_2 7}} + \log_7 196 + 2\log_{\frac{1}{7}} 2$.',
                6,
                [
                    '$\frac{1}{\log_2 7} = \log_7 2$, deci $7^{2\log_7 2} = 4$.',
                    '$2\log_{\frac{1}{7}} 2 = -\log_7 4$.',
                    '$\log_7 196 - \log_7 4 = \log_7 49 = 2$.',
                ],
                '$4 + 2 = 6$.'),

            self::num($d(5), 'ecuatii-inecuatii', 8,
                'Determinați valorile reale ale lui $a$, pentru care ecuația $x^2 - ax + a + 3 = 0$ are două soluții reale $x_1$ și $x_2$, care verifică relația $x_1x_2^2 + x_1^2x_2 = 10$.',
                -5,
                [
                    '$x_1x_2^2 + x_1^2x_2 = x_1x_2(x_1 + x_2)$.',
                    'Viète: $(a + 3) \cdot a = 10$, deci $a = 2$ sau $a = -5$.',
                    'Verifică $\Delta = a^2 - 4a - 12 > 0$.',
                ],
                '$a(a + 3) = 10$ dă 2 și $-5$; pentru $a = 2$, $\Delta < 0$, deci rămâne $a = -5$.'),

            self::num($d(6), 'patrulatere', 5,
                'Fie paralelogramul $ABCD$, în care $AK$, $K \in (BC)$, este bisectoare a unghiului $BAD$. Determinați măsura unghiului $AKC$, dacă $m(\angle ABC) = 120^\circ$.',
                150,
                [
                    '$m(\angle BAD) = 60^\circ$, deci $m(\angle BAK) = m(\angle KAD) = 30^\circ$.',
                    'Alterne interne: $m(\angle AKB) = m(\angle KAD) = 30^\circ$.',
                    '$\angle AKC$ e suplementul lui $\angle AKB$.',
                ],
                '$m(\angle AKB) = 30^\circ$, deci $m(\angle AKC) = 150^\circ$.',
                '°'),

            self::num($d(7), 'corpuri', 5,
                'Volumul unei prisme patrulatere regulate este egal cu 12 cm³. Determinați aria laterală a prismei, dacă se cunoaște că lungimea înălțimii este egală cu 3 cm.',
                24,
                [
                    'Aria bazei: $\frac{12}{3} = 4$ cm².',
                    'Baza e un pătrat: latura 2 cm.',
                    'Aria laterală = perimetrul bazei × înălțimea.',
                ],
                'Latura bazei 2 cm, deci aria laterală $= 8 \cdot 3 = 24$ cm².',
                'cm²'),

            self::num($d(8), 'cerc', 8,
                'Un punct $M$ este situat pe un cerc, la distanța de 3 cm de diametrul $AB$. Determinați lungimea cercului, dacă se cunoaște că $MB = \sqrt{10}$ cm.',
                10 * M_PI,
                [
                    'Proiecția lui $MB$ pe diametru: $\sqrt{10 - 9} = 1$ cm.',
                    'Cu raza $x$: $x^2 = (x - 1)^2 + 9$.',
                    '$x = 5$ cm.',
                ],
                'Raza este 5 cm, deci lungimea cercului este $10\pi$ cm.',
                'cm'),

            self::num($d(9), 'corpuri', 8,
                'În piramida patrulateră regulată $VABCD$, muchiile laterale sunt de 8 cm, iar măsura unghiului dintre muchiile $VB$ și $VD$ este egală cu $120^\circ$. Determinați lungimea muchiei bazei piramidei.',
                4 * sqrt(6),
                [
                    'Triunghiul $VBD$ e isoscel cu unghiul de $120^\circ$ la vârf.',
                    'Jumătatea diagonalei $BD$: $8 \sin 60^\circ = 4\sqrt{3}$ cm.',
                    'Diagonala $8\sqrt{3}$ cm; latura pătratului = diagonala / $\sqrt{2}$.',
                ],
                '$BD = 8\sqrt{3}$ cm, deci latura bazei $= \frac{8\sqrt{3}}{\sqrt{2}} = 4\sqrt{6}$ cm.',
                'cm'),

            self::choice($d(10), 'functii', 5,
                'Fie funcția $f:[0; +\infty)\to\mathbb{R}$, $f(x) = \sqrt{x} - 3$. Determinați mulțimea $E(f)$ a valorilor funcției $f$.',
                ['$[-3; +\infty)$', '$[0; +\infty)$', '$[3; +\infty)$', '$(-\infty; -3]$'],
                [
                    '$\sqrt{x} \geq 0$ pentru orice $x \geq 0$.',
                    'Deci $\sqrt{x} - 3 \geq -3$.',
                    'Valoarea $-3$ se atinge în $x = 0$, iar $\sqrt{x}$ crește nelimitat.',
                ],
                '$\sqrt{x} \geq 0$, deci $E(f) = [-3; +\infty)$.'),

            self::num($d(11), 'progresii', 8,
                'Determinați valorile întregi ale lui $p$, pentru care numerele $p + 2$, $3p + 4$, $p + 8$ sunt primii trei termeni ai unei progresii geometrice.',
                0,
                [
                    'Termenul din mijloc e medie geometrică: $(3p + 4)^2 = (p + 2)(p + 8)$.',
                    '$8p^2 + 14p = 0$.',
                    'Soluțiile sunt 0 și $-\frac{7}{4}$; care e întreagă?',
                ],
                '$(3p + 4)^2 = (p + 2)(p + 8)$ dă $p = 0$ sau $p = -\frac{7}{4}$; valoarea întreagă este $p = 0$.'),

            self::num($d(12), 'functii', 8,
                'Fie funcțiile $f:(0; +\infty)\to\mathbb{R}$, $f(x) = \log_2 x$, $g:\mathbb{R}\to\mathbb{R}$, $g(x) = ax^2 - (a^2 + 2a - 4)x + 5$. Determinați valorile reale nenule ale lui $a$, pentru care zeroul funcției $f$ este punct de maxim al funcției $g$.',
                -2,
                [
                    'Zeroul lui $f$ este $x = 1$.',
                    'Vârful parabolei: $\frac{a^2 + 2a - 4}{2a} = 1$, deci $a^2 = 4$.',
                    'Pentru maxim, ramurile parabolei trebuie să fie în jos: $a < 0$.',
                ],
                '$a = \pm 2$, iar maximul cere $a < 0$: $a = -2$.'),

            self::num($d(13), 'probabilitati', 8,
                'Într-o pușculiță sunt 6 monede de 5 lei și 4 monede de 10 lei. Maria scoate la întâmplare din pușculiță 4 monede. Determinați probabilitatea că Maria va putea achita cu monedele extrase un bilet de 35 de lei.',
                5 / 42,
                [
                    'Cazurile posibile: $n = C_{10}^4$.',
                    'Ajunge pentru 35 de lei: trei monede de 10 și una de 5, sau patru de 10.',
                    '$m = C_6^1 \cdot C_4^3 + C_4^4$.',
                ],
                '$n = 210$, $m = 24 + 1 = 25$, deci $p = \frac{25}{210} = \frac{5}{42}$.'),

            self::num($d(14), 'probabilitati', 8,
                'După două indexări consecutive a câte 20%, bursa unor studenți s-a majorat cu 660 de lei. Determinați bursa studenților după indexări.',
                2160,
                [
                    'Două creșteri de 20%: bursa devine $1{,}2 \cdot 1{,}2 \cdot S = 1{,}44S$.',
                    'Creșterea: $0{,}44S = 660$.',
                    '$S = 1500$ lei.',
                ],
                '$0{,}44S = 660$ dă $S = 1500$ de lei, deci bursa după indexări este 2160 de lei.',
                'lei'),
        ];
    }
}
