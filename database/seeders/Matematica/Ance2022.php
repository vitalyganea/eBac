<?php

namespace Database\Seeders\Matematica;

/**
 * Testele ANCE de matematică din 2022 (12_mat_test_{r,u}_ro_{sb,ss}22.pdf și
 * baremele cu același nume). Testul de la umanist, sesiunea de bază, nu e
 * publicat pe site-ul ANCE.
 */
final class Ance2022
{
    use AnceItems;

    /** @return list<array<string, mixed>> */
    public static function items(): array
    {
        return [...self::realBaza(), ...self::realSuplimentara(), ...self::umanistSuplimentara()];
    }

    /** Profil real, sesiunea de bază. */
    private static function realBaza(): array
    {
        $d = fn (int|string $n) => self::doc('r_sb22', $n);
        $f = 'Fie funcția $f:(0; +\infty)\to\mathbb{R}$, $f(x) = 4\ln x - x$. ';

        return [
            self::num($d(1), 'numere-reale', 5,
                'Calculați valoarea expresiei: $32^{\frac{3}{5}} - 8$.',
                0,
                [
                    '$32 = 2^5$.',
                    '$32^{\frac{3}{5}} = 2^3 = 8$.',
                    '$8 - 8$.',
                ],
                '$32^{\frac{3}{5}} = (2^5)^{\frac{3}{5}} = 8$, deci valoarea este $0$.'),

            self::num($d(2), 'polinoame', 5,
                'Determinați restul împărțirii polinomului $P(X) = 2X^3 + X^2 - 5X + 1$ la binomul $X - 2$.',
                11,
                [
                    'Teorema lui Bézout: restul împărțirii la $X - 2$ este $P(2)$.',
                    '$P(2) = 16 + 4 - 10 + 1$.',
                    'Calculează suma.',
                ],
                'Restul este $P(2) = 16 + 4 - 10 + 1 = 11$.'),

            self::choice($d(3), 'ecuatii-inecuatii', 8,
                'Rezolvați în $\mathbb{R}$ ecuația $\sqrt{4 - x} = x - 2$.',
                ['$S = \{3\}$', '$S = \{0; 3\}$', '$S = \{0\}$', '$S = \varnothing$'],
                [
                    'Membrul drept trebuie să fie nenegativ: $x - 2 \geq 0$.',
                    'Ridică la pătrat: $4 - x = (x - 2)^2$, adică $x^2 - 3x = 0$.',
                    'Rădăcinile sunt 0 și 3. Care respectă $x \geq 2$?',
                ],
                'Sistemul $x - 2 \geq 0$, $4 - x = (x - 2)^2$ dă $x \in \{0; 3\}$, iar condiția păstrează doar $x = 3$.'),

            self::blanks($d(4), 'numere-complexe', 8,
                'Determinați numerele complexe $z = a + bi$, $a, b \in \mathbb{R}$, $i^2 = -1$, pentru care $\begin{vmatrix} 2z + 6i & \bar{z} \\\\ 3 + i & 1 \end{vmatrix} = 0$.',
                ['a' => ['$a$', '1'], 'b' => ['$b$', '-1']],
                [
                    'Determinantul: $2z + 6i - \bar{z}(3 + i) = 0$.',
                    'Înlocuiește $z = a + bi$ și separă părțile: $-a - b = 0$ și $-a + 5b + 6 = 0$.',
                    'Rezolvă sistemul.',
                ],
                'Sistemul $-a - b = 0$, $-a + 5b + 6 = 0$ dă $a = 1$, $b = -1$, deci $z = 1 - i$.'),

            self::choice($d(5), 'identitati-trigonometrice', 8,
                'Într-un triunghi, $\alpha$ este măsura în grade a unui unghi. Determinați $\alpha$, dacă se cunoaște că $\cos(2\alpha) + \sin\alpha - 1 = 0$.',
                ['$\alpha \in \{30^\circ; 150^\circ\}$', '$\alpha = 30^\circ$', '$\alpha \in \{0^\circ; 30^\circ; 150^\circ\}$', '$\alpha = 90^\circ$'],
                [
                    '$\cos(2\alpha) = 1 - 2\sin^2\alpha$, deci ecuația devine $\sin\alpha - 2\sin^2\alpha = 0$.',
                    '$\sin\alpha = 0$ sau $\sin\alpha = \frac{1}{2}$.',
                    'Unghiul unui triunghi e în $(0^\circ; 180^\circ)$: $\sin\alpha = 0$ nu dă soluții.',
                ],
                '$\sin\alpha(1 - 2\sin\alpha) = 0$; în $(0^\circ; 180^\circ)$, $\sin\alpha = \frac{1}{2}$ dă $30^\circ$ și $150^\circ$.'),

            self::num($d(6), 'corpuri', 5,
                'Determinați aria totală a unui cub, dacă se cunoaște că volumul lui este egal cu 8 cm³.',
                24,
                [
                    '$a^3 = 8$.',
                    'Muchia $a = 2$ cm.',
                    'Aria totală: $6a^2$.',
                ],
                'Muchia are 2 cm, deci aria totală este $6 \cdot 4 = 24$ cm².',
                'cm²'),

            self::num($d(7), 'triunghiuri', 8,
                'Fie $ABC$ un triunghi dreptunghic, în care $m(\angle ABC) = 90^\circ$, iar $BC = 36$ cm. Pe laturile $AB$, $AC$ și $BC$ se consideră respectiv punctele $P$, $Q$ și $R$, astfel încât $PQCR$ este un romb cu latura de 20 cm. Determinați aria triunghiului $APQ$.',
                150,
                [
                    '$PQ \parallel BC$, deci triunghiurile $APQ$ și $ABC$ sunt asemenea: $\frac{AC}{AQ} = \frac{BC}{PQ}$.',
                    'Cu $AC = AQ + 20$: $\frac{AQ + 20}{AQ} = \frac{36}{20}$, deci $AQ = 25$ cm.',
                    'Triunghiul $APQ$ e dreptunghic în $P$: $PA = \sqrt{25^2 - 20^2} = 15$ cm.',
                ],
                '$AQ = 25$ cm, $PA = 15$ cm, iar aria triunghiului $APQ$ este $\frac{1}{2} \cdot 15 \cdot 20 = 150$ cm².',
                'cm²'),

            self::num($d(8), 'corpuri', 8,
                'Baza unei piramide este un trapez isoscel circumscriptibil cu bazele de 4 cm și 16 cm. Toate unghiurile diedre de la baza piramidei sunt de $60^\circ$. Determinați lungimea înălțimii piramidei.',
                4 * sqrt(3),
                [
                    'Circumscriptibil: suma laturilor = suma bazelor, deci fiecare latură are 10 cm.',
                    'Înălțimea trapezului: $\sqrt{10^2 - 6^2} = 8$ cm; raza cercului înscris e jumătate: 4 cm.',
                    'Fețele egal înclinate: $H = r \operatorname{tg} 60^\circ$.',
                ],
                'Raza cercului înscris în trapez este 4 cm, deci $H = 4\sqrt{3}$ cm.',
                'cm'),

            self::num($d(9), 'progresii', 5,
                'Fie șirul $(b_n)_{n \geq 1}$, $b_{n+1} = 3b_n$, $b_1 = 2$. Determinați termenul al treilea al șirului.',
                18,
                [
                    '$b_2 = 3b_1$.',
                    '$b_2 = 6$.',
                    '$b_3 = 3b_2$.',
                ],
                '$b_2 = 6$, $b_3 = 18$.'),

            self::choice($d('10a'), 'derivate', 8,
                $f.'Determinați intervalele de monotonie ale funcției $f$.',
                [
                    '$f$ crescătoare pe $(0; 4]$, descrescătoare pe $[4; +\infty)$',
                    '$f$ descrescătoare pe $(0; 4]$, crescătoare pe $[4; +\infty)$',
                    '$f$ crescătoare pe $(0; +\infty)$',
                    '$f$ crescătoare pe $(0; e]$, descrescătoare pe $[e; +\infty)$',
                ],
                [
                    '$f\'(x) = \frac{4}{x} - 1 = \frac{4 - x}{x}$.',
                    'Derivata se anulează în $x = 4$.',
                    'Pe $(0; 4)$ derivata e pozitivă, pe $(4; +\infty)$ negativă.',
                ],
                '$f\'(x) = \frac{4 - x}{x}$: $f$ e crescătoare pe $(0; 4]$ și descrescătoare pe $[4; +\infty)$.'),

            self::choice($d('10b'), 'limite', 8,
                $f.'Comparați $\lim\limits_{x \to +\infty}\left(x - \frac{x^2 - 2x + 3}{x}\right)$ și $f(e)$.',
                [
                    'limita este 2, iar $2 > f(e)$',
                    'limita este 2, iar $2 < f(e)$',
                    'limita este 0, iar $0 < f(e)$',
                    'limita este $+\infty$, deci e mai mare',
                ],
                [
                    'Adu la același numitor: $x - \frac{x^2 - 2x + 3}{x} = \frac{2x - 3}{x}$.',
                    'Limita este 2.',
                    '$f(e) = 4\ln e - e = 4 - e$, iar $e > 2$.',
                ],
                'Limita este 2, iar $f(e) = 4 - e < 2$, deci limita e mai mare.'),

            self::num($d('10c'), 'integrale', 8,
                $f.'Calculați: $\int_1^e \frac{f(x)}{x}\,dx$.',
                3 - M_E,
                [
                    '$\frac{f(x)}{x} = 4\frac{\ln x}{x} - 1$.',
                    'Cu $t = \ln x$, $dt = \frac{1}{x}dx$: o primitivă a lui $4\frac{\ln x}{x}$ e $2\ln^2 x$.',
                    'Primitiva: $2\ln^2 x - x$.',
                ],
                '$\left[2\ln^2 x - x\right]_1^e = (2 - e) - (0 - 1) = 3 - e$. Se scrie „3 - e".'),

            self::num($d(11), 'probabilitati', 8,
                'Se aruncă simultan 4 zaruri. Determinați probabilitatea că produsul numerelor de puncte apărute este egal cu 15.',
                1 / 108,
                [
                    'Cazurile posibile: $n = 6^4$.',
                    '$15 = 1 \cdot 1 \cdot 3 \cdot 5$ — singura descompunere în fețe de zar.',
                    'Alege zarurile cu 3 și cu 5: $m = A_4^2$.',
                ],
                '$n = 1296$, $m = A_4^2 = 12$, deci $p = \frac{12}{1296} = \frac{1}{108}$.'),

            self::num($d(12), 'combinatorica', 8,
                'Determinați termenul care îl conține pe $a^{10}$ în dezvoltarea la putere a binomului $\left(\sqrt{a} + \frac{1}{\sqrt[3]{a}}\right)^{25}$. Scrie coeficientul lui.',
                2300,
                [
                    '$T_{k+1} = C_{25}^k a^{\frac{25 - k}{2}} a^{-\frac{k}{3}} = C_{25}^k a^{\frac{75 - 5k}{6}}$.',
                    '$\frac{75 - 5k}{6} = 10$ dă $k = 3$.',
                    '$T_4 = C_{25}^3 a^{10}$.',
                ],
                '$k = 3$, deci termenul este $T_4 = C_{25}^3 a^{10} = 2300a^{10}$.'),
        ];
    }

    /** Profil real, sesiunea suplimentară. */
    private static function realSuplimentara(): array
    {
        $d = fn (int|string $n) => self::doc('r_ss22', $n);
        $f = 'Fie funcția $f:[-2; 6]\to\mathbb{R}$, $f(x) = \sqrt{x + 3} + 2$. ';

        return [
            self::num($d(1), 'logaritmi', 5,
                'Calculați valoarea expresiei $1{,}5 + \log_2 \sqrt{8}$.',
                3,
                [
                    '$\sqrt{8} = 8^{\frac{1}{2}}$.',
                    '$\log_2 \sqrt{8} = \frac{1}{2}\log_2 8 = \frac{3}{2}$.',
                    '$1{,}5 + 1{,}5$.',
                ],
                '$\log_2 \sqrt{8} = \frac{3}{2}$, deci valoarea este 3.'),

            self::blanks($d(2), 'numere-complexe', 5,
                'Rezolvați în $\mathbb{C}$ ecuația $(2 + i)z = 5$. Scrie soluția sub forma $z = a + bi$.',
                ['a' => ['$a$', '2'], 'b' => ['$b$', '-1']],
                [
                    '$z = \frac{5}{2 + i}$.',
                    'Amplifică cu $2 - i$.',
                    '$(2 + i)(2 - i) = 5$.',
                ],
                '$z = \frac{5(2 - i)}{5} = 2 - i$.'),

            self::num($d(3), 'ecuatii-inecuatii', 8,
                'Fie $D(x) = \begin{vmatrix} 2^{x-1} & 4 \\\\ 8 & 4^x \end{vmatrix}$. Rezolvați în $\mathbb{R}$ ecuația $D(x) = 0$.',
                2,
                [
                    '$D(x) = 2^{x-1} \cdot 4^x - 32$.',
                    '$2^{x-1} \cdot 2^{2x} = 2^5$, adică $2^{3x - 1} = 2^5$.',
                    '$3x - 1 = 5$.',
                ],
                '$2^{3x - 1} = 2^5$ dă $x = 2$.'),

            self::num($d(4), 'identitati-trigonometrice', 8,
                'Fie expresia $E(\alpha) = (\cos\alpha + 1)^2 + (\cos\alpha - 1)^2 - 3$. Arătați că valoarea expresiei $2\sqrt{3} \cdot E(15^\circ)$ este un număr natural. Ce valoare are?',
                3,
                [
                    'Dezvoltă pătratele: $E(\alpha) = 2\cos^2\alpha - 1$.',
                    '$2\cos^2\alpha - 1 = \cos(2\alpha)$.',
                    '$E(15^\circ) = \cos 30^\circ = \frac{\sqrt{3}}{2}$.',
                ],
                '$E(\alpha) = \cos(2\alpha)$, deci $2\sqrt{3} \cdot \cos 30^\circ = 2\sqrt{3} \cdot \frac{\sqrt{3}}{2} = 3$.'),

            self::choice($d(5), 'logaritmi', 8,
                'Rezolvați în $\mathbb{R}$ inecuația $\frac{|x|}{\log_{0{,}2}(2x + 3)} \geq 0$.',
                [
                    '$S = \left(-\frac{3}{2}; -1\right) \cup \{0\}$',
                    '$S = \left(-\frac{3}{2}; -1\right)$',
                    '$S = (-1; +\infty)$',
                    '$S = \left(-\frac{3}{2}; -1\right] \cup \{0\}$',
                ],
                [
                    '$x = 0$ face numărătorul 0 — e soluție, dacă numitorul există și e nenul.',
                    'Pentru $x \neq 0$, numărătorul e pozitiv: trebuie $\log_{0{,}2}(2x + 3) > 0$.',
                    'Baza e subunitară: $0 < 2x + 3 < 1$.',
                ],
                '$x = 0$ e soluție ($\log_{0{,}2} 3 \neq 0$); altfel $0 < 2x + 3 < 1$, adică $-\frac{3}{2} < x < -1$. $S = \left(-\frac{3}{2}; -1\right) \cup \{0\}$; $x = -1$ anulează numitorul.'),

            self::num($d(6), 'corpuri', 5,
                'Secțiunea axială a unui con circular drept este un triunghi dreptunghic cu catetele de $\sqrt{2}$ cm. Determinați aria laterală a conului.',
                M_PI * sqrt(2),
                [
                    'Catetele sunt generatoarele: $G = \sqrt{2}$ cm.',
                    'Ipotenuza (diametrul bazei) are 2 cm, deci $R = 1$ cm.',
                    '$\mathcal{A}_{lat} = \pi R G$.',
                ],
                '$R = 1$ cm, $G = \sqrt{2}$ cm, deci $\mathcal{A}_{lat} = \pi\sqrt{2}$ cm².',
                'cm²'),

            self::num($d(7), 'corpuri', 8,
                'Baza piramidei $VABC$ este triunghiul dreptunghic $ABC$, în care $m(\angle A) = 90^\circ$, $AB = 6$ cm, $AC = 8$ cm. Muchia $VA$ este perpendiculară pe planul bazei piramidei și este congruentă cu mediana triunghiului $ABC$ dusă din vârful $A$. Determinați volumul piramidei $VABC$.',
                40,
                [
                    'Ipotenuza $BC = 10$ cm; mediana din unghiul drept e jumătate din ipotenuză.',
                    '$VA = 5$ cm, iar aria bazei $= \frac{6 \cdot 8}{2} = 24$ cm².',
                    '$\mathcal{V} = \frac{1}{3}\mathcal{A}_b \cdot VA$.',
                ],
                '$\mathcal{V} = \frac{1}{3} \cdot 24 \cdot 5 = 40$ cm³.',
                'cm³'),

            self::num($d(8), 'triunghiuri', 8,
                'În triunghiul $ABC$, $m(\angle A) = 60^\circ$, iar bisectoarea $AD$ determină pe latura $BC$ segmentele $BD = 2$ cm și $CD = 4$ cm. Determinați măsura unghiului $C$.',
                30,
                [
                    'Teorema bisectoarei: $\frac{AB}{AC} = \frac{2}{4}$, deci $AC = 2AB$.',
                    'Teorema cosinusului: $36 = AB^2 + 4AB^2 - 2AB^2 = 3AB^2$, deci $AB = 2\sqrt{3}$ cm.',
                    '$AC^2 = 48 = AB^2 + BC^2$ — triunghiul e dreptunghic în $B$.',
                ],
                'Unghiul $B$ are $90^\circ$, deci $m(\angle C) = 180^\circ - 90^\circ - 60^\circ = 30^\circ$.',
                '°'),

            self::choice($d(9), 'functii', 5,
                'Fie funcția $f:\mathbb{R}\to\mathbb{R}$, $f(x) = \left(\frac{e}{3}\right)^x$. Stabiliți monotonia funcției $f$.',
                ['$f$ este strict descrescătoare', '$f$ este strict crescătoare', '$f$ este constantă'],
                [
                    'Monotonia exponențialei depinde de bază.',
                    '$e \approx 2{,}718$.',
                    '$\frac{e}{3} < 1$.',
                ],
                'Baza $\frac{e}{3} < 1$, deci funcția este strict descrescătoare.'),

            self::choice($d('10a'), 'derivate', 8,
                $f.'Comparați: $f\'(5)$ și $\frac{\sqrt{2}}{3} - \frac{\sqrt{2}}{4}$.',
                ['$f\'(5) > \frac{\sqrt{2}}{3} - \frac{\sqrt{2}}{4}$', '$f\'(5) < \frac{\sqrt{2}}{3} - \frac{\sqrt{2}}{4}$', '$f\'(5) = \frac{\sqrt{2}}{3} - \frac{\sqrt{2}}{4}$'],
                [
                    '$f\'(x) = \frac{1}{2\sqrt{x + 3}}$, deci $f\'(5) = \frac{1}{4\sqrt{2}} = \frac{\sqrt{2}}{8}$.',
                    '$\frac{\sqrt{2}}{3} - \frac{\sqrt{2}}{4} = \frac{\sqrt{2}}{12}$.',
                    'Compară $\frac{1}{8}$ cu $\frac{1}{12}$.',
                ],
                '$f\'(5) = \frac{\sqrt{2}}{8} > \frac{\sqrt{2}}{12}$.'),

            self::num($d('10b'), 'limite', 8,
                $f.'Calculați $\lim\limits_{x \to 1} \frac{f(x)\left(\sqrt{x + 3} - 2\right)}{1 - x^2}$.',
                -0.5,
                [
                    '$f(x)(\sqrt{x + 3} - 2) = (\sqrt{x + 3} + 2)(\sqrt{x + 3} - 2) = x - 1$.',
                    'Limita devine $\lim\limits_{x \to 1} \frac{x - 1}{(1 - x)(1 + x)}$.',
                    'Simplifică: $\frac{-1}{1 + x}$.',
                ],
                'Limita este $\lim\limits_{x \to 1} \frac{-1}{1 + x} = -\frac{1}{2}$.'),

            self::num($d('10c'), 'integrale', 8,
                $f.'Determinați valoarea numerică a volumului corpului obținut prin rotirea subgraficului funcției $f$ în jurul axei $Ox$.',
                424 * M_PI / 3,
                [
                    '$V = \pi\int_{-2}^{6} f^2(x)\,dx$.',
                    '$f^2(x) = x + 7 + 4\sqrt{x + 3}$.',
                    'O primitivă: $\frac{x^2}{2} + 7x + \frac{8}{3}(x + 3)^{\frac{3}{2}}$.',
                ],
                '$V = \pi\left[\frac{x^2}{2} + 7x + \frac{8}{3}(x + 3)^{\frac{3}{2}}\right]_{-2}^{6} = \frac{424}{3}\pi$.'),

            self::num($d(11), 'probabilitati', 8,
                'Literele A, P, O, L, L, O sunt scrise câte una pe șase fișe identice. Patru fișe se extrag aleatoriu consecutiv. Determinați probabilitatea că, în ordinea extragerii, se obține cuvântul POLO.',
                1 / 90,
                [
                    'Cazurile posibile: extrageri ordonate de 4 fișe din 6, $n = A_6^4$.',
                    'Favorabile: P (1 fișă), O (2 fișe), L (2 fișe), O (fișa rămasă).',
                    '$m = 1 \cdot 2 \cdot 2 \cdot 1$.',
                ],
                '$n = 360$, $m = 4$, deci $p = \frac{4}{360} = \frac{1}{90}$.'),

            self::num($d(12), 'combinatorica', 8,
                'Determinați termenul care nu-l conține pe $x$ în dezvoltarea la putere a binomului $\left(x\sqrt{x} + \frac{1}{x}\right)^{10}$.',
                210,
                [
                    '$T_{k+1} = C_{10}^k (x\sqrt{x})^{10 - k} x^{-k} = C_{10}^k x^{\frac{30 - 5k}{2}}$.',
                    'Exponentul 0: $30 - 5k = 0$.',
                    '$k = 6$.',
                ],
                '$k = 6$, deci $T_7 = C_{10}^6 = 210$.'),
        ];
    }

    /** Profil umanist, sesiunea suplimentară. */
    private static function umanistSuplimentara(): array
    {
        $d = fn (int|string $n) => self::doc('u_ss22', $n);

        return [
            self::num($d(1), 'numere-reale', 5,
                'Calculați: $\sqrt{\left(\frac{1}{27}\right)^{-\frac{2}{3}}}$.',
                3,
                [
                    '$\frac{1}{27} = 3^{-3}$.',
                    '$(3^{-3})^{-\frac{2}{3}} = 3^2 = 9$.',
                    '$\sqrt{9}$.',
                ],
                '$\left(\frac{1}{27}\right)^{-\frac{2}{3}} = 9$, deci rezultatul este 3.'),

            self::num($d(2), 'matrici', 8,
                'Fie matricea $B = 2A - I_3$, unde $A = \begin{pmatrix} 0 & -1 & 2 \\\\ 3 & 1 & -1 \\\\ 1 & 2 & 0 \end{pmatrix}$, $I_3 = \begin{pmatrix} 1 & 0 & 0 \\\\ 0 & 1 & 0 \\\\ 0 & 0 & 1 \end{pmatrix}$. Determinați dacă matricea $B$ este inversabilă. Cât este $\det B$?',
                77,
                [
                    '$B = \begin{pmatrix} -1 & -2 & 4 \\\\ 6 & 1 & -2 \\\\ 2 & 4 & -1 \end{pmatrix}$.',
                    'Dezvoltă determinantul după prima linie.',
                    'O matrice e inversabilă dacă determinantul ei e nenul.',
                ],
                '$\det B = -1 \cdot 7 + 2 \cdot (-2) + 4 \cdot 22 = 77 \neq 0$, deci $B$ este inversabilă. Baremul punctează calculul determinantului și concluzia.'),

            self::num($d(3), 'logaritmi', 8,
                'Calculați: $\log_3 72 + \frac{1}{2}\log_{\frac{1}{3}} 64$.',
                2,
                [
                    '$\frac{1}{2}\log_{\frac{1}{3}} 64 = \log_{\frac{1}{3}} 8 = -\log_3 8$.',
                    '$\log_3 72 - \log_3 8 = \log_3 9$.',
                    '$\log_3 9 = 2$.',
                ],
                '$\log_3 72 - \log_3 8 = \log_3 9 = 2$.'),

            self::blanks($d(4), 'numere-complexe', 8,
                'Rezolvați în $\mathbb{C}$ ecuația $(2 - 3i)(1 + 2i) - iz = i - z$. Scrie soluția sub forma $z = a + bi$.',
                ['a' => ['$a$', '-4'], 'b' => ['$b$', '-4']],
                [
                    '$(2 - 3i)(1 + 2i) = 8 + i$.',
                    'Ecuația devine $(-1 + i)z = 8$.',
                    'Amplifică $\frac{8}{-1 + i}$ cu $-1 - i$.',
                ],
                '$z = \frac{8(-1 - i)}{2} = -4 - 4i$.'),

            self::blanks($d(5), 'ecuatii-inecuatii', 8,
                'Determinați valorile reale ale lui $a$, pentru care soluțiile reale $x_1$ și $x_2$ ale ecuației $x^2 - 4ax + a^2 - 5 = 0$ verifică relația $\frac{1}{x_1} + \frac{1}{x_2} = 1$.',
                ['a1' => ['valoarea mai mică', '-1'], 'a2' => ['valoarea mai mare', '5']],
                [
                    '$\frac{1}{x_1} + \frac{1}{x_2} = \frac{x_1 + x_2}{x_1 x_2}$.',
                    'Viète: $\frac{4a}{a^2 - 5} = 1$.',
                    '$a^2 - 4a - 5 = 0$.',
                ],
                '$a^2 - 4a - 5 = 0$ dă $a = -1$ sau $a = 5$; discriminantul $12a^2 + 20$ e pozitiv, deci soluțiile sunt reale.'),

            self::num($d(6), 'triunghiuri', 5,
                'Fie triunghiul $ABC$, în care $MN \parallel AC$, $M \in (AB)$, $N \in (BC)$, $AB = 25$ cm, $BN = 2$ cm, $NC = 3$ cm. Determinați lungimea segmentului $AM$.',
                15,
                [
                    'Teorema lui Thales: $\frac{BM}{AM} = \frac{BN}{NC}$.',
                    '$\frac{25 - AM}{AM} = \frac{2}{3}$.',
                    'Rezolvă ecuația.',
                ],
                '$3(25 - AM) = 2AM$, deci $AM = 15$ cm.',
                'cm'),

            self::num($d(7), 'corpuri', 5,
                'Determinați volumul corpului sferic, dacă aria suprafeței sferice este egală cu $36\pi$ cm².',
                36 * M_PI,
                [
                    '$4\pi R^2 = 36\pi$.',
                    '$R = 3$ cm.',
                    '$\mathcal{V} = \frac{4}{3}\pi R^3$.',
                ],
                '$R = 3$ cm, deci $\mathcal{V} = \frac{4}{3}\pi \cdot 27 = 36\pi$ cm³.',
                'cm³'),

            self::num($d(8), 'patrulatere', 8,
                'Fie trapezul $ABCD$, în care $AD \parallel BC$, $BC = 4$ cm, $m(\angle A) = 60^\circ$, $m(\angle D) = 45^\circ$. Determinați lungimea bazei $AD$, dacă se cunoaște că înălțimea trapezului este de $3\sqrt{3}$ cm.',
                7 + 3 * sqrt(3),
                [
                    'Proiecția laturii $AB$ pe baza mare: $\frac{3\sqrt{3}}{\operatorname{tg} 60^\circ} = 3$ cm.',
                    'Proiecția laturii $CD$: $\frac{3\sqrt{3}}{\operatorname{tg} 45^\circ} = 3\sqrt{3}$ cm.',
                    '$AD = 3 + 4 + 3\sqrt{3}$.',
                ],
                '$AD = 3 + 4 + 3\sqrt{3} = (7 + 3\sqrt{3})$ cm.',
                'cm'),

            self::num($d(9), 'corpuri', 8,
                'Aria bazei unei piramide patrulatere regulate este egală cu 32 cm². Determinați lungimea muchiei laterale a piramidei, dacă volumul ei este egal cu 32 cm³.',
                5,
                [
                    'Latura bazei: $\sqrt{32} = 4\sqrt{2}$ cm; diagonala: 8 cm.',
                    'Înălțimea: $\frac{3 \cdot 32}{32} = 3$ cm.',
                    'Muchia laterală: $\sqrt{4^2 + 3^2}$.',
                ],
                'Jumătatea diagonalei e 4 cm, înălțimea 3 cm, deci muchia laterală are 5 cm.',
                'cm'),

            self::choice($d(10), 'functii', 5,
                'Fie funcția $f:(0; +\infty)\to\mathbb{R}$, $f(x) = \log_{\frac{\sqrt{5}}{2}} x$. Stabiliți monotonia funcției $f$.',
                ['$f$ este strict crescătoare pe $(0; +\infty)$', '$f$ este strict descrescătoare pe $(0; +\infty)$', '$f$ este constantă'],
                [
                    'Monotonia logaritmului depinde de bază.',
                    '$\sqrt{5} \approx 2{,}24$.',
                    '$\frac{\sqrt{5}}{2} > 1$.',
                ],
                'Baza $\frac{\sqrt{5}}{2} > 1$, deci funcția este strict crescătoare.'),

            self::num($d(11), 'progresii', 8,
                'Într-o progresie geometrică, suma dintre primul termen și termenul al treilea este egală cu 15, iar suma dintre termenul al doilea și termenul al patrulea este egală cu 30. Determinați primul termen al progresiei.',
                3,
                [
                    'Sistemul: $b_1 + b_1 q^2 = 15$, $b_1 q + b_1 q^3 = 30$.',
                    'A doua ecuație e prima înmulțită cu $q$: $q = 2$.',
                    '$b_1(1 + 4) = 15$.',
                ],
                '$q = 2$, deci $b_1 = 3$.'),

            self::choice($d(12), 'functii', 8,
                'Fie funcția $f:\mathbb{R}\to\mathbb{R}$, $f(x) = (12 - m - m^2)x + 2$. Determinați valorile întregi ale lui $m$, pentru care funcția $f$ este strict crescătoare pe $\mathbb{R}$.',
                ['$m \in \{-3; -2; -1; 0; 1; 2\}$', '$m \in \{-4; -3; -2; -1; 0; 1; 2; 3\}$', '$m \in \{-2; -1; 0; 1; 2; 3\}$', '$m \in \{0; 1; 2\}$'],
                [
                    'Strict crescătoare: panta pozitivă, $12 - m - m^2 > 0$.',
                    'Rădăcinile lui $m^2 + m - 12$ sunt $-4$ și 3.',
                    'Trinomul $12 - m - m^2$ e pozitiv strict între ele.',
                ],
                '$-4 < m < 3$, iar valorile întregi sunt $-3, -2, -1, 0, 1, 2$. Capetele sunt excluse — acolo panta e 0.'),

            self::num($d(13), 'probabilitati', 8,
                'Într-o urnă sunt 5 bile roșii, 4 bile albastre și o bilă galbenă. Se extrag la întâmplare 4 bile. Determinați probabilitatea că printre bilele extrase sunt bile de toate culorile.',
                1 / 3,
                [
                    'Cazurile posibile: $n = C_{10}^4$.',
                    'Bila galbenă trebuie extrasă; restul de 3: 2 roșii + 1 albastră sau 1 roșie + 2 albastre.',
                    '$m = C_5^2 \cdot 4 + C_4^2 \cdot 5$.',
                ],
                '$n = 210$, $m = 40 + 30 = 70$, deci $p = \frac{1}{3}$.'),

            self::num($d(14), 'probabilitati', 8,
                'Un magazin a cumpărat de la producător căpșune la prețul de 20 de lei per kilogram și a aplicat un adaos de 30%. Peste 3 zile magazinul a micșorat prețul cu 10%. Determinați prețul unui kilogram de căpșune după ieftinire.',
                23.4,
                [
                    'După adaos: $20 \cdot 1{,}3 = 26$ de lei.',
                    'Ieftinirea cu 10% se aplică noului preț.',
                    '$26 \cdot 0{,}9$.',
                ],
                '$20 \cdot 1{,}3 \cdot 0{,}9 = 23{,}4$ lei.',
                'lei'),
        ];
    }
}
