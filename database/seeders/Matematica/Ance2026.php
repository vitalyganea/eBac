<?php

namespace Database\Seeders\Matematica;

/**
 * Testele ANCE de matematică din 2026.
 *
 * https://ance.gov.md/sites/default/files/12_mat_test_r_ro_sb26.pdf și baremul
 * cu același nume (`_barem_`); la fel pentru `u_` (umanist) și `ss` (sesiunea
 * suplimentară).
 */
final class Ance2026
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
        $d = fn (int|string $n) => self::doc('r_sb26', $n);
        $f = 'Fie funcția $f:\mathbb{R}\to\mathbb{R}$, $f(x) = \frac{x}{x^2+4}$. ';

        return [
            self::num($d(1), 'numere-reale', 5,
                'Calculați valoarea expresiei $0{,}2 + \sqrt[3]{\frac{34}{125} - 2}$.',
                -1,
                [
                    'Începe cu expresia de sub radical: adu $\frac{34}{125} - 2$ la același numitor.',
                    '$\frac{34}{125} - 2 = -\frac{216}{125}$, iar $216 = 6^3$ și $125 = 5^3$.',
                    '$\sqrt[3]{-\frac{216}{125}} = -\frac{6}{5}$ și $0{,}2 = \frac{1}{5}$.',
                ],
                '$\sqrt[3]{\frac{34}{125} - 2} = \sqrt[3]{-\frac{216}{125}} = -\frac{6}{5}$, iar $0{,}2 = \frac{1}{5}$, deci valoarea expresiei este $\frac{1}{5} - \frac{6}{5} = -1$.'),

            self::choice($d(2), 'ecuatii-inecuatii', 5,
                'Rezolvați în $\mathbb{R}$ inecuația $64 \cdot 0{,}25^t < 1$.',
                ['$S = (3; +\infty)$', '$S = (-\infty; 3)$', '$S = (-3; +\infty)$', '$S = (-\infty; -3)$'],
                [
                    'Împarte la 64 și scrie ambii membri ca puteri ale aceleiași baze.',
                    '$0{,}25^t < \frac{1}{64}$, adică $\left(\frac{1}{4}\right)^t < \left(\frac{1}{4}\right)^3$.',
                    'Baza $\frac{1}{4}$ e subunitară: funcția e descrescătoare, deci sensul inegalității se schimbă.',
                ],
                '$0{,}25^t < \frac{1}{64} \iff \left(\frac{1}{4}\right)^t < \left(\frac{1}{4}\right)^3$. Baza e subunitară, deci $t > 3$ și $S = (3; +\infty)$. Varianta $(-\infty; 3)$ e greșeala de a păstra sensul inegalității.'),

            self::choice($d(3), 'matrici', 8,
                'Determinați valorile reale ale lui $x$, pentru care matricea $A = \begin{pmatrix} \frac{1}{x} & 4 + x \\\\ 2 & x^3 \end{pmatrix}$ este inversabilă.',
                [
                    '$x \in \mathbb{R} \setminus \{-2; 0; 4\}$',
                    '$x \in \mathbb{R} \setminus \{-2; 4\}$',
                    '$x \in \{-2; 0; 4\}$',
                    '$x \in \mathbb{R} \setminus \{0\}$',
                ],
                [
                    'Înainte de orice calcul: pentru ce valoare a lui $x$ matricea nici nu există?',
                    'O matrice e inversabilă dacă și numai dacă $\det A \neq 0$. Calculează determinantul pentru $x \neq 0$.',
                    '$\det A = x^2 - 2x - 8$. Rezolvă $x^2 - 2x - 8 = 0$ și exclude rădăcinile, împreună cu $x = 0$.',
                ],
                'Elementul $\frac{1}{x}$ cere $x \neq 0$. Pentru $x \neq 0$, $\det A = \frac{1}{x} \cdot x^3 - 2(4 + x) = x^2 - 2x - 8$, cu rădăcinile $-2$ și $4$. Deci $x \in \mathbb{R} \setminus \{-2; 0; 4\}$. Baremul dă un punct separat pentru condiția $x \neq 0$ — varianta fără ea e cea mai frecventă greșeală.'),

            self::blanks($d(4), 'numere-complexe', 8,
                'Determinați valorile reale ale lui $p$ și $q$, pentru care numărul complex $z = 3 - i$ este soluție a ecuației $z^2 + pz + q + 8i = 0$, unde $i^2 = -1$.',
                ['p' => ['$p$', '2'], 'q' => ['$q$', '-14']],
                [
                    'Înlocuiește $z = 3 - i$ în ecuație.',
                    'Grupează partea reală și partea imaginară: $(8 + 3p + q) + i(2 - p) = 0$.',
                    'Un număr complex e zero când ambele părți sunt zero: $8 + 3p + q = 0$ și $2 - p = 0$.',
                ],
                '$(3 - i)^2 + p(3 - i) + q + 8i = 8 - 6i + 3p - pi + q + 8i = (8 + 3p + q) + i(2 - p) = 0$. Din $2 - p = 0$ rezultă $p = 2$, apoi $q = -8 - 3p = -14$.'),

            self::num($d(5), 'identitati-trigonometrice', 8,
                'Fie $\alpha \in \left(\frac{\pi}{2}, \pi\right)$ și $\beta \in \left(0, \frac{\pi}{2}\right)$, care verifică relațiile: $\frac{\alpha}{2} + \beta = \frac{\pi}{2}$ și $27\sin^2\alpha + 6\cos\alpha - 26 = 0$. Determinați valoarea lui $\sin\beta$.',
                2 / 3,
                [
                    'Scrie $\sin^2\alpha = 1 - \cos^2\alpha$ și obții o ecuație de gradul II în $\cos\alpha$.',
                    '$27\cos^2\alpha - 6\cos\alpha - 1 = 0$ dă $\cos\alpha = \frac{1}{3}$ sau $\cos\alpha = -\frac{1}{9}$. Care e posibilă pentru $\alpha$ din cadranul II?',
                    'Din $\beta = \frac{\pi}{2} - \frac{\alpha}{2}$ rezultă $\sin\beta = \cos\frac{\alpha}{2}$; folosește $\cos\alpha = 2\cos^2\frac{\alpha}{2} - 1$.',
                ],
                'Ecuația devine $27\cos^2\alpha - 6\cos\alpha - 1 = 0$, cu soluțiile $\frac{1}{3}$ și $-\frac{1}{9}$. Pentru $\alpha$ din cadranul II, $\cos\alpha < 0$, deci $\cos\alpha = -\frac{1}{9}$. Apoi $\sin\beta = \cos\frac{\alpha}{2} = \sqrt{\frac{1 + \cos\alpha}{2}} = \sqrt{\frac{4}{9}} = \frac{2}{3}$.'),

            self::num($d(6), 'corpuri', 5,
                'Secțiunea axială a unui con circular drept este un triunghi dreptunghic. Determinați aria laterală a conului, dacă înălțimea conului este de 3 cm.',
                9 * sqrt(2) * M_PI,
                [
                    'Secțiunea axială e un triunghi isoscel (două generatoare). Dacă e și dreptunghic, ce unghiuri are?',
                    'Înălțimea împarte triunghiul în două triunghiuri dreptunghice isoscele, deci raza bazei $R = H = 3$ cm.',
                    'Generatoarea $G = 3\sqrt{2}$ cm, iar $\mathcal{A}_{lat} = \pi R G$.',
                ],
                'Triunghiul axial e dreptunghic isoscel, deci $R = H = 3$ cm și $G = 3\sqrt{2}$ cm. $\mathcal{A}_{lat} = \pi R G = \pi \cdot 3 \cdot 3\sqrt{2} = 9\sqrt{2}\pi$ cm².',
                'cm²'),

            self::num($d(7), 'patrulatere', 8,
                'Într-un trapez isoscel diagonala este perpendiculară pe latura laterală și are lungimea de 100 cm. Determinați perimetrul trapezului, dacă înălțimea trapezului este de 60 cm.',
                310,
                [
                    'Coboară înălțimea din vârful bazei mici în care se întâlnesc diagonala și latura laterală. Ce triunghiuri dreptunghice apar?',
                    'Proiecția diagonalei pe baza mare: $\sqrt{100^2 - 60^2} = 80$ cm. În triunghiul dreptunghic format de diagonală și latură, înălțimea e medie proporțională: $60^2 = 80 \cdot p$.',
                    'Proiecția laturii $p = 45$ cm, latura $= 75$ cm, baza mare $= 125$ cm, baza mică $= 125 - 2 \cdot 45$.',
                ],
                'Proiecția diagonalei pe baza mare este $\sqrt{100^2 - 60^2} = 80$ cm. Cum diagonala e perpendiculară pe latură, $60^2 = 80 \cdot p$, deci proiecția laturii e $p = 45$ cm și latura are $\sqrt{60^2 + 45^2} = 75$ cm. Baza mare $= 80 + 45 = 125$ cm, baza mică $= 125 - 90 = 35$ cm. Perimetrul: $125 + 35 + 2 \cdot 75 = 310$ cm.',
                'cm'),

            self::num($d(8), 'corpuri', 8,
                'Aria laterală a unei piramide patrulatere regulate este egală cu 200 cm², iar unghiurile diedre de la baza piramidei sunt de 60°. Determinați volumul piramidei.',
                500 * sqrt(3) / 3,
                [
                    'Construiește unghiul diedru de la bază: apotema piramidei și proiecția ei pe bază (jumătate din latura pătratului) formează $60^\circ$.',
                    'Notează cu $x$ proiecția apotemei. Atunci latura bazei e $2x$, iar apotema e $2x$ (din $\cos 60^\circ = \frac{1}{2}$).',
                    '$\mathcal{A}_{lat} = 4 \cdot \frac{1}{2} \cdot 2x \cdot 2x = 8x^2 = 200$, deci $x = 5$; înălțimea $H = x\sqrt{3}$.',
                ],
                'Cu $x$ proiecția apotemei pe bază: latura bazei $= 2x$, apotema $= 2x$, iar $8x^2 = 200$ dă $x = 5$ cm. Înălțimea $H = 5\sqrt{3}$ cm, aria bazei $= 10^2 = 100$ cm², deci $\mathcal{V} = \frac{1}{3} \cdot 100 \cdot 5\sqrt{3} = \frac{500\sqrt{3}}{3}$ cm³.',
                'cm³'),

            self::num($d(9), 'progresii', 5,
                'Numerele $30$, $k$, $-2$ sunt termeni consecutivi ai unei progresii aritmetice. Determinați valoarea lui $k$.',
                14,
                [
                    'Într-o progresie aritmetică, fiecare termen e media aritmetică a vecinilor săi.',
                    '$k = \frac{30 + (-2)}{2}$.',
                    'Verifică: rația e $k - 30 = -2 - k$.',
                ],
                '$k = \frac{30 - 2}{2} = 14$; rația progresiei este $-16$.'),

            self::blanks($d('10a'), 'derivate', 8,
                $f.'Determinați punctele de extrem local ale funcției $f$.',
                ['min' => ['minim: $x =$', '-2'], 'max' => ['maxim: $x =$', '2']],
                [
                    'Derivează cu regula câtului.',
                    '$f\'(x) = \frac{4 - x^2}{(x^2 + 4)^2}$. Unde se anulează?',
                    'Studiază semnul lui $4 - x^2$: negativ, pozitiv, negativ.',
                ],
                '$f\'(x) = \frac{(x^2 + 4) - 2x^2}{(x^2 + 4)^2} = \frac{4 - x^2}{(x^2 + 4)^2}$, cu zerourile $\pm 2$. Derivata e negativă pe $(-\infty; -2)$, pozitivă pe $(-2; 2)$ și negativă pe $(2; +\infty)$: $x = -2$ e punct de minim local, $x = 2$ — de maxim local.'),

            self::num($d('10b'), 'limite', 8,
                $f.'Calculați: $\lim\limits_{x \to 0} \frac{\sqrt{x^2 + x + 1} - 1}{f(x)}$.',
                2,
                [
                    'Înlocuiește $f(x)$: limita devine $\lim\limits_{x \to 0} \frac{(x^2 + 4)(\sqrt{x^2 + x + 1} - 1)}{x}$ — o nedeterminare $\frac{0}{0}$.',
                    'Amplifică cu conjugata $\sqrt{x^2 + x + 1} + 1$.',
                    'La numărător rămâne $x^2 + x = x(x + 1)$; simplifică prin $x$.',
                ],
                'După amplificarea cu conjugata, limita devine $\lim\limits_{x \to 0} \frac{(x^2 + 4)(x + 1)}{\sqrt{x^2 + x + 1} + 1} = \frac{4 \cdot 1}{2} = 2$.'),

            self::num($d('10c'), 'integrale', 8,
                $f.'Determinați valoarea reală a lui $m > 0$, pentru care dreapta $x = m$ împarte figura, mărginită de graficul funcției $f$, de axele de coordonate și de dreapta $x = 2\sqrt{3}$, în două figuri de arii egale.',
                2,
                [
                    'Condiția: $\int_0^m f(x)\,dx = \int_m^{2\sqrt{3}} f(x)\,dx$.',
                    'O primitivă a lui $f$ este $\frac{1}{2}\ln(x^2 + 4)$.',
                    'Newton–Leibniz dă $\ln(m^2 + 4) - \ln 4 = \ln 16 - \ln(m^2 + 4)$.',
                ],
                'Cu primitiva $\frac{1}{2}\ln(x^2 + 4)$, condiția devine $\ln(m^2 + 4) - \ln 4 = \ln 16 - \ln(m^2 + 4)$, adică $(m^2 + 4)^2 = 64$, $m^2 + 4 = 8$ și $m = 2$ (cu $m > 0$).'),

            self::num($d(11), 'probabilitati', 8,
                'Cu cifrele 1, 2, 3, 4, 5, 6 se formează în mod aleator un număr de 4 cifre care nu se repetă. Determinați probabilitatea că numărul format conține cifra 5.',
                2 / 3,
                [
                    'Probabilitatea = cazuri favorabile / cazuri posibile. Câte numere de 4 cifre distincte se pot forma din 6 cifre?',
                    'Cazurile posibile: $n = A_6^4$, pentru că ordinea cifrelor contează.',
                    'Favorabile: cifra 5 ocupă una din 4 poziții, restul de 3 poziții se completează cu celelalte 5 cifre: $m = 4 \cdot A_5^3$.',
                ],
                '$n = A_6^4 = 360$, $m = 4 \cdot A_5^3 = 4 \cdot 60 = 240$, deci $p = \frac{240}{360} = \frac{2}{3}$.'),

            self::blanks($d(12), 'combinatorica', 8,
                'Suma coeficienților primilor trei termeni și a coeficienților ultimilor trei termeni din dezvoltarea la putere a binomului $\left(\sqrt{x} + \frac{1}{\sqrt[3]{x}}\right)^n$ este egală cu 112. Determinați termenul de mijloc al dezvoltării. Scrie-l sub forma $a \cdot x^b$.',
                ['a' => ['$a =$', '252'], 'b' => ['$b =$', '5/6']],
                [
                    'Coeficienții sunt $C_n^0, C_n^1, C_n^2$ și $C_n^{n-2}, C_n^{n-1}, C_n^n$ — iar $C_n^k = C_n^{n-k}$.',
                    '$2(1 + n + \frac{n(n-1)}{2}) = 112$ dă $n^2 + n - 110 = 0$, deci $n = 10$.',
                    'Cu $n = 10$ termenul de mijloc e $T_6 = C_{10}^5 (\sqrt{x})^5 \left(\frac{1}{\sqrt[3]{x}}\right)^5$.',
                ],
                'Din $C_n^0 + C_n^1 + C_n^2 + C_n^{n-2} + C_n^{n-1} + C_n^n = 112$ rezultă $n^2 + n - 110 = 0$, $n = 10$. Termenul de mijloc: $T_6 = C_{10}^5 \cdot x^{\frac{5}{2}} \cdot x^{-\frac{5}{3}} = 252\,x^{\frac{5}{6}}$.'),
        ];
    }

    /** Profil real, sesiunea suplimentară. */
    private static function realSuplimentara(): array
    {
        $d = fn (int|string $n) => self::doc('r_ss26', $n);
        $fg = 'Fie funcțiile $f, g:(0; +\infty)\to\mathbb{R}$, $f(x) = \ln x + \frac{1}{x}$, $g(x) = \frac{1}{x}$. ';

        return [
            self::num($d(1), 'logaritmi', 5,
                'Calculați valoarea expresiei: $16^{0{,}75} + \log_2 \frac{1}{32}$.',
                3,
                [
                    'Scrie 16 ca putere a lui 2.',
                    '$16^{0{,}75} = (2^4)^{0{,}75} = 2^3$.',
                    '$\frac{1}{32} = 2^{-5}$, deci $\log_2 \frac{1}{32} = -5$.',
                ],
                '$16^{0{,}75} = (2^4)^{0{,}75} = 2^3 = 8$ și $\log_2 \frac{1}{32} = -5$, deci valoarea expresiei este $8 - 5 = 3$.'),

            self::num($d(2), 'polinoame', 5,
                'Fie polinomul $P(X) = -X^3 + aX^2 + 3X + 2a + 1$. Determinați valorile reale ale lui $a$, pentru care restul împărțirii lui $P(X)$ la binomul $X - 1$ este egal cu 6.',
                1,
                [
                    'Teorema lui Bézout: restul împărțirii la $X - 1$ este $P(1)$.',
                    'Condiția devine $P(1) = 6$.',
                    '$P(1) = -1 + a + 3 + 2a + 1 = 3a + 3$.',
                ],
                'Restul împărțirii la $X - 1$ este $P(1) = 3a + 3$. Din $3a + 3 = 6$ rezultă $a = 1$.'),

            self::choice($d(3), 'ecuatii-inecuatii', 8,
                'Rezolvați în $\mathbb{R}$ ecuația $3\left(\sqrt{4 - x} - 1\right) = x - 3$.',
                ['$S = \{3\}$', '$S = \{-12; 3\}$', '$S = \{-12\}$', '$S = \varnothing$'],
                [
                    'Desfă paranteza și izolează radicalul: $3\sqrt{4 - x} = x$.',
                    'Membrul stâng e nenegativ, deci și $x \geq 0$. Abia apoi ridici la pătrat: $9(4 - x) = x^2$.',
                    '$x^2 + 9x - 36 = 0$ are rădăcinile $-12$ și $3$. Care respectă $x \geq 0$?',
                ],
                'Ecuația devine $3\sqrt{4 - x} = x$, cu condiția $x \geq 0$. Prin ridicare la pătrat, $9(4 - x) = x^2$, adică $x^2 + 9x - 36 = 0$, cu rădăcinile $-12$ și $3$. Condiția $x \geq 0$ o elimină pe $-12$, deci $S = \{3\}$. Baremul punctează separat condiția și selectarea soluției.'),

            self::blanks($d(4), 'numere-complexe', 8,
                'Determinați numerele complexe $z = a + bi$, $i^2 = -1$, care verifică relația $\begin{vmatrix} 1 & z & 2i \\\\ -i & \bar{z} & 1 \\\\ 1 & 1 + i & 0 \end{vmatrix} = -4 + 5i$, unde $\bar{z}$ este conjugatul numărului complex $z$.',
                ['a' => ['$a$', '-1'], 'b' => ['$b$', '2']],
                [
                    'Dezvoltă determinantul (regula triunghiurilor sau după o linie).',
                    'Obții $-i(1 + i)2i + z - 2i\bar{z} - (1 + i) = -4 + 5i$; înlocuiește $z = a + bi$ și $\bar{z} = a - bi$.',
                    'Egalează părțile reale și imaginare: $a - 2b + 1 = -4$ și $-2a + b + 1 = 5$.',
                ],
                'Dezvoltarea determinantului dă $a - 2b + 1 + (-2a + b + 1)i = -4 + 5i$. Sistemul $a - 2b + 1 = -4$, $-2a + b + 1 = 5$ are soluția $a = -1$, $b = 2$, deci $z = -1 + 2i$.'),

            self::choice($d(5), 'ecuatii-inecuatii', 8,
                'Rezolvați în $\mathbb{R}$ inecuația $5^{2 - |x|} + 5^{|x|} - 26 \geq 0$.',
                [
                    '$S = (-\infty; -2] \cup \{0\} \cup [2; +\infty)$',
                    '$S = (-\infty; -2] \cup [2; +\infty)$',
                    '$S = [-2; 2]$',
                    '$S = \{0\} \cup [2; +\infty)$',
                ],
                [
                    'Notează $t = 5^{|x|}$, cu $t > 0$, și înmulțește inecuația cu $t$.',
                    '$t^2 - 26t + 25 \geq 0$ dă $t \leq 1$ sau $t \geq 25$.',
                    '$5^{|x|} \leq 1$ înseamnă $|x| \leq 0$ — nu uita această ramură.',
                ],
                'Cu $t = 5^{|x|}$: $t^2 - 26t + 25 \geq 0$, deci $5^{|x|} \leq 1$ sau $5^{|x|} \geq 25$, adică $|x| \leq 0$ sau $|x| \geq 2$. Prima ramură dă $x = 0$, a doua $x \in (-\infty; -2] \cup [2; +\infty)$. Varianta fără $\{0\}$ pierde tocmai ramura $|x| \leq 0$.'),

            self::num($d(6), 'cerc', 5,
                'Punctele $A$, $B$ și $C$ aparțin unui cerc, astfel încât $m(\angle ABC) = 45^\circ$. Determinați lungimea cercului, dacă se cunoaște că lungimea arcului mic $AC$ este egală cu $2\pi$ cm.',
                8 * M_PI,
                [
                    'Unghiul $ABC$ e înscris în cerc. Cât măsoară arcul pe care îl subîntinde?',
                    'Unghiul înscris e jumătate din arc: arcul mic $AC$ are $90^\circ$.',
                    '$90^\circ$ e un sfert din cerc, deci cercul e de 4 ori mai lung decât arcul.',
                ],
                'Unghiul înscris $ABC$ de $45^\circ$ subîntinde un arc de $90^\circ$, adică un sfert din cerc. Lungimea cercului: $4 \cdot 2\pi = 8\pi$ cm.',
                'cm'),

            self::num($d(7), 'corpuri', 8,
                'Baza prismei drepte $ABCA_1B_1C_1$ este triunghiul dreptunghic isoscel $ABC$, în care $m(\angle BAC) = 90^\circ$. Se știe că $B_1C = 8$ cm, iar $m(\angle B_1CB) = 60^\circ$. Determinați volumul prismei.',
                16 * sqrt(3),
                [
                    'Triunghiul $B_1BC$ e dreptunghic în $B$ (muchia laterală e perpendiculară pe bază).',
                    '$BC = B_1C \cos 60^\circ = 4$ cm și $BB_1 = B_1C \sin 60^\circ = 4\sqrt{3}$ cm.',
                    'În triunghiul dreptunghic isoscel cu ipotenuza $BC = 4$, catetele sunt $AB = AC = 2\sqrt{2}$ cm.',
                ],
                '$BC = 4$ cm, înălțimea $CC_1 = BB_1 = 4\sqrt{3}$ cm, catetele bazei $AB = AC = 2\sqrt{2}$ cm. Aria bazei $= \frac{1}{2}(2\sqrt{2})^2 = 4$ cm², deci $\mathcal{V} = 4 \cdot 4\sqrt{3} = 16\sqrt{3}$ cm³.',
                'cm³'),

            self::num($d(8), 'patrulatere', 8,
                'În paralelogramul $ABCD$, punctul $E$ aparține laturii $BC$, astfel încât $BE = 5$ cm, iar $AE = 8$ cm. Determinați lungimea înălțimii paralelogramului corespunzătoare laturii $AD$, dacă unghiurile $BAE$ și $DAE$ sunt congruente.',
                4.8,
                [
                    '$AD \parallel BC$, deci $\angle DAE = \angle AEB$ (alterne interne). Ce fel de triunghi este $ABE$?',
                    'Triunghiul $ABE$ e isoscel: $AB = BE = 5$ cm. Teorema cosinusului în $ABE$ dă $\cos(\angle ABC) = -\frac{7}{25}$.',
                    'Unghiurile $ABC$ și $BAD$ sunt suplementare, deci $\sin(\angle BAD) = \frac{24}{25}$; înălțimea $h = AB \sin(\angle BAD)$.',
                ],
                'Din $\angle BAE = \angle DAE = \angle AEB$, triunghiul $ABE$ e isoscel și $AB = 5$ cm. Teorema cosinusului: $64 = 25 + 25 - 50\cos(\angle ABC)$, deci $\cos(\angle ABC) = -\frac{7}{25}$ și $\sin(\angle BAD) = \frac{24}{25}$. Înălțimea: $h = 5 \cdot \frac{24}{25} = 4{,}8$ cm.',
                'cm'),

            self::num($d(9), 'progresii', 5,
                'Fie progresia aritmetică $(a_n)_{n \geq 1}$, în care $a_1 = -1$ și $a_3 = 3$. Determinați valoarea lui $a_{2026}$.',
                4049,
                [
                    '$a_3 = a_1 + 2r$.',
                    'Rația: $r = \frac{3 - (-1)}{2} = 2$.',
                    '$a_{2026} = a_1 + 2025r$.',
                ],
                'Din $a_3 = a_1 + 2r$ rezultă $r = 2$, deci $a_{2026} = -1 + 2025 \cdot 2 = 4049$.'),

            self::choice($d('10a'), 'derivate', 8,
                $fg.'Determinați intervalele de monotonie a funcției $f$.',
                [
                    '$f$ descrescătoare pe $(0; 1]$, crescătoare pe $[1; +\infty)$',
                    '$f$ crescătoare pe $(0; 1]$, descrescătoare pe $[1; +\infty)$',
                    '$f$ crescătoare pe $(0; +\infty)$',
                    '$f$ descrescătoare pe $(0; e]$, crescătoare pe $[e; +\infty)$',
                ],
                [
                    'Calculează $f\'(x)$.',
                    '$f\'(x) = \frac{1}{x} - \frac{1}{x^2} = \frac{x - 1}{x^2}$. Unde se anulează?',
                    'Pe $(0; 1)$, $x - 1 < 0$; pe $(1; +\infty)$, $x - 1 > 0$.',
                ],
                '$f\'(x) = \frac{x - 1}{x^2}$ se anulează în $x = 1$, e negativă pe $(0; 1)$ și pozitivă pe $(1; +\infty)$. Deci $f$ e descrescătoare pe $(0; 1]$ și crescătoare pe $[1; +\infty)$.'),

            self::choice($d('10b'), 'limite', 8,
                $fg.'Comparați valoarea limitei $\lim\limits_{x \to +\infty} \left(\frac{x^2 + 7}{x} - \frac{x^2 - 1}{x + 2}\right)$ cu valoarea funcției $f\left(\frac{1}{3}\right)$.',
                [
                    'limita este 2, iar $f\left(\frac{1}{3}\right) < 2$',
                    'limita este 2, iar $f\left(\frac{1}{3}\right) > 2$',
                    'limita este 0, iar $f\left(\frac{1}{3}\right) > 0$',
                    'limita este $+\infty$, deci e mai mare decât $f\left(\frac{1}{3}\right)$',
                ],
                [
                    'Adu la același numitor înainte de a trece la limită: $\infty - \infty$ e o nedeterminare.',
                    'Diferența devine $\frac{2x^2 + 8x + 14}{x(x + 2)}$, iar limita ei este 2.',
                    '$f\left(\frac{1}{3}\right) = \ln\frac{1}{3} + 3 = 3 - \ln 3$. Compară $\ln 3$ cu 1.',
                ],
                'Limita este $\lim\limits_{x \to +\infty} \frac{2x^2 + 8x + 14}{x(x + 2)} = 2$. Cum $\ln 3 > 1$, $f\left(\frac{1}{3}\right) = 3 - \ln 3 < 2$.'),

            self::num($d('10c'), 'integrale', 8,
                $fg.'Determinați valoarea numerică a ariei figurii mărginite de graficele funcțiilor $f$ și $g$, de dreptele $x = \frac{1}{e}$ și $x = 1$.',
                1 - 2 / M_E,
                [
                    'Aria este $\int_{1/e}^{1} |f(x) - g(x)|\,dx$, iar $f(x) - g(x) = \ln x$.',
                    'Pe $\left[\frac{1}{e}; 1\right]$, $\ln x \leq 0$, deci aria $= -\int_{1/e}^{1} \ln x\,dx$.',
                    'O primitivă a lui $\ln x$ (prin părți): $x\ln x - x$.',
                ],
                'Aria $= -\int_{1/e}^{1} \ln x\,dx = -\left[x\ln x - x\right]_{1/e}^{1} = -\left(-1 - \left(-\frac{1}{e} - \frac{1}{e}\right)\right) = 1 - \frac{2}{e}$.'),

            self::num($d(11), 'probabilitati', 8,
                'Într-o clasă sunt 13 elevi care au pregătit cadouri pentru copiii de la un orfelinat. Cinci elevi au pregătit câte 3 cadouri, șapte elevi au pregătit câte 2 cadouri, un elev a pregătit un cadou. Determinați probabilitatea că trei elevi luați la întâmplare au pregătit împreună 7 cadouri.',
                115 / 286,
                [
                    'Cazurile posibile: alegi 3 elevi din 13, fără ordine — $n = C_{13}^3$.',
                    'Cum se poate obține 7 din trei numere luate din $\{3, 2, 1\}$? Sunt două combinații.',
                    '$7 = 3 + 2 + 2$ sau $7 = 3 + 3 + 1$: $m = C_5^1 \cdot C_7^2 + C_5^2 \cdot C_1^1$.',
                ],
                '$n = C_{13}^3 = 286$. Șapte cadouri se obțin ca $3 + 2 + 2$ ($C_5^1 \cdot C_7^2 = 105$ moduri) sau $3 + 3 + 1$ ($C_5^2 \cdot C_1^1 = 10$ moduri), deci $m = 115$ și $p = \frac{115}{286}$.'),

            self::num($d(12), 'combinatorica', 8,
                'Diferența dintre coeficienții binomiali ai termenului al treilea și termenului al doilea în dezvoltarea la putere a binomului $\left(\sqrt[3]{x} + \frac{1}{x}\right)^n$ este egală cu 35. Determinați coeficientul lui $\frac{1}{x^2}$ din dezvoltare.',
                210,
                [
                    'Coeficienții binomiali ai termenilor 3 și 2 sunt $C_n^2$ și $C_n^1$.',
                    '$\frac{n(n-1)}{2} - n = 35$ dă $n = 10$.',
                    '$T_{k+1} = C_{10}^k x^{\frac{10-k}{3}} \cdot x^{-k}$; cere exponentul $-2$: $\frac{10 - k}{3} - k = -2$.',
                ],
                'Din $\frac{n(n-1)}{2} - n = 35$ rezultă $n = 10$. Exponentul lui $x$ în $T_{k+1}$ este $\frac{10 - k}{3} - k = -2$, deci $k = 4$ și coeficientul este $C_{10}^4 = 210$.'),
        ];
    }

    /** Profil umanist, sesiunea de bază. */
    private static function umanistBaza(): array
    {
        $d = fn (int|string $n) => self::doc('u_sb26', $n);

        return [
            self::num($d(1), 'numere-reale', 5,
                'Calculați $\sqrt[3]{-\frac{1}{64}} + \left(\frac{2}{3}\right)^{-2}$.',
                2,
                [
                    '$64 = 4^3$, deci radicalul de ordinul 3 se poate extrage exact.',
                    '$\sqrt[3]{-\frac{1}{64}} = -\frac{1}{4}$.',
                    'Exponentul negativ inversează fracția: $\left(\frac{2}{3}\right)^{-2} = \left(\frac{3}{2}\right)^2 = \frac{9}{4}$.',
                ],
                '$\sqrt[3]{-\frac{1}{64}} = -\frac{1}{4}$ și $\left(\frac{2}{3}\right)^{-2} = \frac{9}{4}$, deci suma este $\frac{8}{4} = 2$.'),

            self::num($d(2), 'logaritmi', 8,
                'Arătați că valoarea expresiei $\log_{\sqrt{2}} 20 + \log_{0{,}5} 25$ este un pătrat perfect. Ce valoare are expresia?',
                4,
                [
                    'Adu ambii logaritmi la baza 2: $\sqrt{2} = 2^{\frac{1}{2}}$ și $0{,}5 = 2^{-1}$.',
                    '$\log_{\sqrt{2}} 20 = 2\log_2 20 = \log_2 400$ și $\log_{0{,}5} 25 = -\log_2 25$.',
                    '$\log_2 400 - \log_2 25 = \log_2 16$.',
                ],
                '$\log_{\sqrt{2}} 20 = 2\log_2 20 = \log_2 400$, iar $\log_{0{,}5} 25 = -\log_2 25$. Suma este $\log_2 \frac{400}{25} = \log_2 16 = 4 = 2^2$ — un pătrat perfect.'),

            self::blanks($d(3), 'numere-complexe', 8,
                'Determinați numerele complexe $z$, pentru care $\begin{vmatrix} z & 2iz \\\\ 1 & 3 \end{vmatrix} = 2 + 3i$, unde $i^2 = -1$. Scrie răspunsul sub forma $z = a + bi$.',
                ['a' => ['$a$', '0'], 'b' => ['$b$', '1']],
                [
                    'Calculează determinantul: $3z - 2iz$.',
                    'Din $3z - 2iz = 2 + 3i$ rezultă $z = \frac{2 + 3i}{3 - 2i}$.',
                    'Amplifică fracția cu conjugata numitorului, $3 + 2i$: $(3 - 2i)(3 + 2i) = 13$.',
                ],
                '$3z - 2iz = 2 + 3i$, deci $z = \frac{2 + 3i}{3 - 2i} = \frac{(2 + 3i)(3 + 2i)}{13} = \frac{13i}{13} = i$, adică $a = 0$, $b = 1$.'),

            self::blanks($d(4), 'ecuatii-inecuatii', 8,
                'Într-un oraș, tarifele pentru călătoriile cu autobuzul și cu troleibuzul sunt fixe, la fel și costul cursei cu taxiul. Două călătorii cu autobuzul și două cu troleibuzul costă 32 de lei; trei călătorii cu troleibuzul și o cursă cu taxiul costă 78 de lei; o călătorie cu autobuzul, două cu troleibuzul și o cursă cu taxiul costă 82 de lei. Determinați tariful unei călătorii cu autobuzul, tariful unei călătorii cu troleibuzul și costul unei curse cu taxiul.',
                ['x' => ['autobuz (lei)', '10'], 'y' => ['troleibuz (lei)', '6'], 'z' => ['taxi (lei)', '60']],
                [
                    'Notează $x$, $y$, $z$ prețurile și scrie câte o ecuație pentru fiecare propoziție.',
                    '$2x + 2y = 32$, $3y + z = 78$, $x + 2y + z = 82$. Exprimă $x$ și $z$ prin $y$.',
                    '$x = 16 - y$ și $z = 78 - 3y$; înlocuiește în a treia ecuație.',
                ],
                'Sistemul $2x + 2y = 32$, $3y + z = 78$, $x + 2y + z = 82$ dă $x = 16 - y$, $z = 78 - 3y$, iar din a treia ecuație $94 - 2y = 82$, deci $y = 6$, $x = 10$, $z = 60$.'),

            self::choice($d(5), 'ecuatii-inecuatii', 8,
                'Determinați valorile reale ale lui $m$, pentru care soluțiile reale ale ecuației $x^2 + x - m^2 + m = 0$ sunt mai mici decât 2.',
                ['$m \in (-2; 3)$', '$m \in (-\infty; 3)$', '$m \in (-2; +\infty)$', '$m \in (-3; 2)$'],
                [
                    'Calculează discriminantul: $\Delta = 1 + 4m^2 - 4m$. E un pătrat perfect?',
                    '$\Delta = (2m - 1)^2$, deci soluțiile sunt $x_1 = m - 1$ și $x_2 = -m$.',
                    'Ambele soluții trebuie să fie mai mici decât 2: $m - 1 < 2$ și $-m < 2$.',
                ],
                '$\Delta = (2m - 1)^2$, iar soluțiile sunt $x_1 = m - 1$ și $x_2 = -m$. Condițiile $m - 1 < 2$ și $-m < 2$ dau $m \in (-2; 3)$. Variantele cu o singură margine pun condiția doar pe una dintre soluții.'),

            self::num($d(6), 'patrulatere', 5,
                'Într-un trapez baza mare este de 6 cm, iar linia mijlocie este de 5 cm. Determinați aria trapezului, dacă se cunoaște că înălțimea trapezului este congruentă cu baza mică.',
                20,
                [
                    'Linia mijlocie e media bazelor: $\frac{6 + b}{2} = 5$.',
                    'Baza mică $b = 4$ cm, deci și înălțimea are 4 cm.',
                    'Aria trapezului = linia mijlocie × înălțimea.',
                ],
                'Din $\frac{6 + b}{2} = 5$, baza mică are 4 cm, la fel și înălțimea. Aria $= \frac{6 + 4}{2} \cdot 4 = 20$ cm².',
                'cm²'),

            self::num($d(7), 'corpuri', 5,
                'Un con circular drept are generatoarea de 1 m și aria laterală de $6000\pi$ cm². Determinați volumul conului.',
                96000 * M_PI,
                [
                    'Atenție la unități: generatoarea de 1 m = 100 cm.',
                    '$\mathcal{A}_{lat} = \pi R G$: $6000\pi = 100\pi R$, deci $R = 60$ cm.',
                    'Înălțimea din triunghiul dreptunghic: $H = \sqrt{100^2 - 60^2}$.',
                ],
                '$6000\pi = 100\pi R$ dă $R = 60$ cm, iar $H = \sqrt{100^2 - 60^2} = 80$ cm. $\mathcal{V} = \frac{1}{3}\pi \cdot 60^2 \cdot 80 = 96000\pi$ cm³.',
                'cm³'),

            self::num($d(8), 'patrulatere', 8,
                'În dreptunghiul $ABCD$, punctul $M$ aparține laturii $AB$, astfel încât $m(\angle AMD) = m(\angle BCM) = 60^\circ$. Determinați perimetrul dreptunghiului $ABCD$ dacă $AM = 2$ cm.',
                16 + 4 * sqrt(3),
                [
                    'În triunghiul dreptunghic $AMD$, unghiul $ADM$ are $30^\circ$: cateta $AM$ e jumătate din ipotenuza $DM$.',
                    '$DM = 4$ cm și $AD = 2\sqrt{3}$ cm. Cât măsoară unghiul $MCD$?',
                    '$m(\angle MCD) = 30^\circ$, iar în triunghiul $DMC$ se obține $DC = 8$ cm.',
                ],
                'Din triunghiul $AMD$: $DM = 4$ cm, $AD = 2\sqrt{3}$ cm. Cum $m(\angle MCD) = 30^\circ$, se obține $DC = 8$ cm. Perimetrul: $2(8 + 2\sqrt{3}) = 16 + 4\sqrt{3}$ cm.',
                'cm'),

            self::num($d(9), 'corpuri', 8,
                'Baza prismei drepte $ABCA_1B_1C_1$ este triunghiul isoscel $ABC$, în care $AB = BC = 13$ cm, iar $AC = 10$ cm. Determinați aria laterală a prismei $ABCA_1B_1C_1$, dacă se cunoaște că distanța de la vârful $B_1$ la muchia $AC$ este egală cu 20 cm.',
                576,
                [
                    'Distanța de la $B_1$ la $AC$ se măsoară pe perpendiculara dusă din $B_1$; piciorul ei e mijlocul lui $AC$ (teorema celor trei perpendiculare).',
                    'Înălțimea bazei din $B$: $\sqrt{13^2 - 5^2} = 12$ cm.',
                    'Muchia laterală: $BB_1 = \sqrt{20^2 - 12^2} = 16$ cm; aria laterală = perimetrul bazei × muchia.',
                ],
                'Înălțimea triunghiului $ABC$ corespunzătoare bazei $AC$ este 12 cm, deci $BB_1 = \sqrt{20^2 - 12^2} = 16$ cm. Aria laterală $= (13 + 13 + 10) \cdot 16 = 576$ cm².',
                'cm²'),

            self::num($d(10), 'functii', 5,
                'Fie funcția $f:\mathbb{R}\to\mathbb{R}$, $f(x) = \frac{x}{2 - \sqrt{3}} + \sqrt{48}$. Determinați dacă punctul $M(-2\sqrt{3}; -6)$ aparține graficului funcției $f$. Cât este $f(-2\sqrt{3})$?',
                -6,
                [
                    'Punctul aparține graficului dacă $f(-2\sqrt{3}) = -6$.',
                    'Raționalizează: amplifică $\frac{-2\sqrt{3}}{2 - \sqrt{3}}$ cu $2 + \sqrt{3}$.',
                    '$\frac{-2\sqrt{3}}{2 - \sqrt{3}} = -4\sqrt{3} - 6$, iar $\sqrt{48} = 4\sqrt{3}$.',
                ],
                '$f(-2\sqrt{3}) = \frac{-2\sqrt{3}(2 + \sqrt{3})}{(2 - \sqrt{3})(2 + \sqrt{3})} + 4\sqrt{3} = -4\sqrt{3} - 6 + 4\sqrt{3} = -6$, deci punctul $M$ aparține graficului.'),

            self::choice($d(11), 'functii', 8,
                'Fie funcția $f:\mathbb{R}\to\mathbb{R}$, $f(x) = (-3a^2 - 11a + 4)x + 8$. Determinați valorile reale ale lui $a$, pentru care funcția $f$ este strict crescătoare pe $\mathbb{R}$.',
                [
                    '$a \in \left(-4; \frac{1}{3}\right)$',
                    '$a \in (-\infty; -4) \cup \left(\frac{1}{3}; +\infty\right)$',
                    '$a \in \left(-\frac{1}{3}; 4\right)$',
                    '$a \in \left[-4; \frac{1}{3}\right]$',
                ],
                [
                    'O funcție de gradul întâi e strict crescătoare când panta e pozitivă.',
                    'Condiția: $-3a^2 - 11a + 4 > 0$. Rezolvă ecuația asociată.',
                    'Rădăcinile sunt $-4$ și $\frac{1}{3}$; coeficientul lui $a^2$ e negativ, deci trinomul e pozitiv între rădăcini.',
                ],
                'Panta trebuie să fie pozitivă: $-3a^2 - 11a + 4 > 0$. Rădăcinile sunt $-4$ și $\frac{1}{3}$, iar trinomul (cu coeficient dominant negativ) e pozitiv între ele: $a \in \left(-4; \frac{1}{3}\right)$. Capetele sunt excluse — acolo panta e 0 și funcția e constantă.'),

            self::num($d(12), 'progresii', 8,
                'Într-un amfiteatru în primul rând sunt 10 locuri, iar în fiecare rând începând cu al doilea sunt cu 2 locuri mai mult decât în rândul precedent. În total în amfiteatru sunt 630 de locuri. Determinați câte locuri sunt în ultimul rând.',
                50,
                [
                    'Numărul de locuri pe rânduri formează o progresie aritmetică cu $a_1 = 10$ și $r = 2$.',
                    'Suma primilor $n$ termeni: $630 = \frac{20 + 2(n - 1)}{2} \cdot n$.',
                    'Ecuația dă $n = 21$ rânduri; calculează $a_{21}$.',
                ],
                'Din $630 = \frac{20 + 2(n - 1)}{2} \cdot n$ rezultă $n = 21$, iar ultimul rând are $a_{21} = 10 + 20 \cdot 2 = 50$ de locuri.'),

            self::num($d(13), 'probabilitati', 8,
                'Într-o vază sunt 6 trandafiri roșii și 7 trandafiri albi. Se iau la întâmplare 5 trandafiri. Determinați probabilitatea că printre trandafirii luați sunt cel puțin câte 2 de fiecare culoare.',
                105 / 143,
                [
                    'Cazurile posibile: $n = C_{13}^5$.',
                    'Cu 5 flori și cel puțin 2 din fiecare culoare, sunt doar două posibilități: 2 roșii + 3 albe sau 3 roșii + 2 albe.',
                    '$m = C_6^2 \cdot C_7^3 + C_6^3 \cdot C_7^2$.',
                ],
                '$n = C_{13}^5 = 1287$, $m = C_6^2 \cdot C_7^3 + C_6^3 \cdot C_7^2 = 525 + 420 = 945$, deci $p = \frac{945}{1287} = \frac{105}{143}$.'),

            self::blanks($d(14), 'probabilitati', 8,
                'Numărul de pași (exprimat în mii) realizați de un adolescent timp de 8 zile a fost: 6, 9, 8, 12, 13, $x$, 9, 7. Valoarea medie a numărului de pași realizați de adolescent în cele 8 zile este egală cu 9 (mii). Determinați valoarea lui $x$ și mediana seriei statistice corespunzătoare.',
                ['x' => ['$x$', '8'], 'me' => ['mediana', '8.5']],
                [
                    'Media: suma valorilor împărțită la 8 este 9.',
                    '$\frac{64 + x}{8} = 9$, deci $x = 8$.',
                    'Ordonează cele 8 valori; mediana unei serii cu număr par de termeni e media celor doi din mijloc.',
                ],
                'Din $\frac{64 + x}{8} = 9$ rezultă $x = 8$. Seria ordonată: 6, 7, 8, 8, 9, 9, 12, 13; mediana este $\frac{8 + 9}{2} = 8{,}5$.'),
        ];
    }

    /** Profil umanist, sesiunea suplimentară. */
    private static function umanistSuplimentara(): array
    {
        $d = fn (int|string $n) => self::doc('u_ss26', $n);

        return [
            self::num($d(1), 'logaritmi', 5,
                'Calculați: $64^{\frac{2}{3}} \cdot 0{,}125$.',
                2,
                [
                    'Scrie 64 ca putere a lui 2.',
                    '$64^{\frac{2}{3}} = (2^6)^{\frac{2}{3}} = 2^4$.',
                    '$0{,}125 = \frac{1}{8}$.',
                ],
                '$64^{\frac{2}{3}} = 2^4 = 16$ și $0{,}125 = \frac{1}{8}$, deci produsul este $2$.'),

            self::num($d(2), 'matrici', 8,
                'Calculați determinantul matricei $X = A^2 + B$, unde $A = \begin{pmatrix} 1 & -1 \\\\ 0 & 2 \end{pmatrix}$, $B = \begin{pmatrix} 1 & 2 \\\\ 3 & -1 \end{pmatrix}$.',
                9,
                [
                    'Calculează întâi $A^2 = A \cdot A$ (linie × coloană).',
                    '$A^2 = \begin{pmatrix} 1 & -3 \\\\ 0 & 4 \end{pmatrix}$.',
                    '$X = \begin{pmatrix} 2 & -1 \\\\ 3 & 3 \end{pmatrix}$; determinantul: $2 \cdot 3 - (-1) \cdot 3$.',
                ],
                '$A^2 = \begin{pmatrix} 1 & -3 \\\\ 0 & 4 \end{pmatrix}$, $X = \begin{pmatrix} 2 & -1 \\\\ 3 & 3 \end{pmatrix}$, iar $\det X = 6 + 3 = 9$.'),

            self::num($d(3), 'logaritmi', 8,
                'Determinați valoarea expresiei: $5^{\log_{\sqrt{5}} 3} + \log_2 9 - 2\log_2 6$.',
                7,
                [
                    '$\log_{\sqrt{5}} 3 = 2\log_5 3$, iar $5^{2\log_5 3} = (5^{\log_5 3})^2$.',
                    'Primul termen este $3^2 = 9$.',
                    '$\log_2 9 - 2\log_2 6 = \log_2 \frac{9}{36} = \log_2 \frac{1}{4}$.',
                ],
                '$5^{\log_{\sqrt{5}} 3} = 5^{2\log_5 3} = 9$ și $\log_2 9 - \log_2 36 = \log_2 \frac{1}{4} = -2$, deci valoarea expresiei este $9 - 2 = 7$.'),

            self::num($d(4), 'numere-complexe', 8,
                'Determinați modulul numărului complex $z = a + bi$, $i^2 = -1$, știind că $2iz - 6\bar{z} = -16$, unde $\bar{z}$ este conjugatul numărului complex $z$.',
                sqrt(10),
                [
                    'Înlocuiește $z = a + bi$ și $\bar{z} = a - bi$.',
                    'Obții $(-2b - 6a) + (2a + 6b)i = -16$, deci $-2b - 6a = -16$ și $2a + 6b = 0$.',
                    'Sistemul dă $z = 3 - i$; modulul este $\sqrt{a^2 + b^2}$.',
                ],
                'Din $2ai - 2b - 6a + 6bi = -16$ rezultă sistemul $-2b - 6a = -16$, $2a + 6b = 0$, cu soluția $a = 3$, $b = -1$. Deci $z = 3 - i$ și $|z| = \sqrt{9 + 1} = \sqrt{10}$.'),

            self::num($d(5), 'ecuatii-inecuatii', 8,
                'Determinați valoarea maximă a expresiei $\frac{1}{x_1} + \frac{1}{x_2}$, unde $x_1$ și $x_2$ sunt soluțiile ecuației $2x^2 - m(m + 6)x - 1 = 0$.',
                9,
                [
                    'Relațiile lui Viète: $x_1 + x_2 = \frac{m(m + 6)}{2}$, $x_1 x_2 = -\frac{1}{2}$.',
                    '$\frac{1}{x_1} + \frac{1}{x_2} = \frac{x_1 + x_2}{x_1 x_2} = -m^2 - 6m$.',
                    'Valoarea maximă a trinomului $-m^2 - 6m$ este $-\frac{\Delta}{4a}$.',
                ],
                'Cu relațiile lui Viète, $\frac{1}{x_1} + \frac{1}{x_2} = -m^2 - 6m$. Trinomul are maximul $-\frac{\Delta}{4a} = -\frac{36}{-4} = 9$, atins pentru $m = -3$.'),

            self::num($d(6), 'triunghiuri', 5,
                'În dreptunghiul $ABCD$ lungimea laturii $AD$ este egală cu 3 cm. Punctul $F$ aparține laturii $DC$, astfel încât $DF = 4$ cm. Fie $E$ punctul de intersecție a dreptelor $AF$ și $BC$, iar $CE = 6$ cm. Determinați lungimea segmentului $EF$.',
                10,
                [
                    'Triunghiurile $ADF$ și $ECF$ sunt asemenea (unghiuri opuse la vârf și unghiuri drepte).',
                    'Raportul de asemănare: $\frac{FC}{DF} = \frac{CE}{AD} = \frac{6}{3}$, deci $FC = 8$ cm.',
                    '$EF$ e ipotenuza triunghiului dreptunghic $ECF$.',
                ],
                'Din asemănarea triunghiurilor $ADF$ și $ECF$, $FC = 2 \cdot DF = 8$ cm. Atunci $EF = \sqrt{8^2 + 6^2} = 10$ cm.',
                'cm'),

            self::num($d(7), 'corpuri', 5,
                'Într-un cilindru circular drept suma ariilor bazelor este egală cu aria laterală. Determinați volumul cilindrului, dacă diagonala secțiunii axiale are lungimea de $\sqrt{5}$ cm.',
                M_PI,
                [
                    '$2\pi R^2 = 2\pi R H$ înseamnă $R = H$.',
                    'Secțiunea axială e un dreptunghi cu laturile $2R$ și $H$: $(2R)^2 + H^2 = 5$.',
                    'Cu $H = R$: $5R^2 = 5$, deci $R = H = 1$ cm.',
                ],
                'Din $2\pi R^2 = 2\pi RH$ rezultă $R = H$, iar $(2R)^2 + R^2 = 5$ dă $R = H = 1$ cm. $\mathcal{V} = \pi R^2 H = \pi$ cm³.',
                'cm³'),

            self::num($d(8), 'patrulatere', 8,
                'Fie trapezul isoscel $ABCD$, $BC \parallel AD$, în care $AB = CD = 4$ cm, $BC = 2\sqrt{2}$ cm, iar unghiul de la baza mare $AD$ este de $45^\circ$. Determinați lungimea diagonalei $AC$.',
                2 * sqrt(10),
                [
                    'Cu unghiul de $45^\circ$, înălțimea și proiecția laturii laterale pe baza mare sunt congruente.',
                    'Proiecția laturii: $4 \cdot \cos 45^\circ = 2\sqrt{2}$ cm, la fel înălțimea.',
                    'Proiecția diagonalei pe baza mare: $BC + 2\sqrt{2} = 4\sqrt{2}$ cm.',
                ],
                'Înălțimea și proiecția laturii laterale au fiecare $2\sqrt{2}$ cm. Proiecția diagonalei $AC$ pe baza mare este $2\sqrt{2} + 2\sqrt{2} = 4\sqrt{2}$ cm, deci $AC = \sqrt{32 + 8} = 2\sqrt{10}$ cm.',
                'cm'),

            self::num($d(9), 'corpuri', 8,
                'Într-o piramidă triunghiulară regulată înălțimea este de $\sqrt{6}$ cm, iar muchia bazei este de 6 cm. Determinați aria laterală a piramidei.',
                27,
                [
                    'Înălțimea triunghiului echilateral din bază: $\frac{6\sqrt{3}}{2} = 3\sqrt{3}$ cm.',
                    'Raza cercului înscris în bază este o treime din înălțime: $\sqrt{3}$ cm.',
                    'Apotema piramidei: $\sqrt{(\sqrt{6})^2 + (\sqrt{3})^2} = 3$ cm.',
                ],
                'Raza cercului înscris în bază este $\sqrt{3}$ cm, deci apotema piramidei are $\sqrt{6 + 3} = 3$ cm. Aria laterală $= 3 \cdot \frac{1}{2} \cdot 6 \cdot 3 = 27$ cm².',
                'cm²'),

            self::choice($d(10), 'functii', 5,
                'Fie funcția $f:\mathbb{R}\to\mathbb{R}$, $f(x) = (4\sqrt{3} - 7)x + 1$. Stabiliți monotonia funcției $f$.',
                ['$f$ este strict descrescătoare', '$f$ este strict crescătoare', '$f$ este constantă'],
                [
                    'Monotonia unei funcții de gradul întâi depinde doar de semnul pantei.',
                    'Compară $4\sqrt{3}$ cu 7: ridică ambele la pătrat.',
                    '$(4\sqrt{3})^2 = 48 < 49 = 7^2$.',
                ],
                'Cum $48 < 49$, $4\sqrt{3} < 7$, deci panta $4\sqrt{3} - 7$ e negativă și funcția este strict descrescătoare.'),

            self::choice($d(11), 'functii', 8,
                'Fie funcțiile $f:\mathbb{R}\to\mathbb{R}$, $f(x) = 2x - 1$ și $g:D\to\mathbb{R}$, $g(x) = \sqrt{1 - x}$. Determinați valorile reale ale lui $x \in D$, pentru care pătratul valorilor funcției $f$ sunt mai mici decât 9.',
                ['$x \in (-1; 1]$', '$x \in (-1; 2)$', '$x \in [-1; 1]$', '$x \in (-\infty; 1]$'],
                [
                    'Mai întâi domeniul lui $g$: $1 - x \geq 0$.',
                    'Inecuația $(2x - 1)^2 < 9$ are soluțiile $x \in (-1; 2)$.',
                    'Intersectează cu domeniul $D = (-\infty; 1]$.',
                ],
                '$D = (-\infty; 1]$ (din $1 - x \geq 0$), iar $(2x - 1)^2 < 9$ dă $x \in (-1; 2)$. Intersecția: $x \in (-1; 1]$. Varianta $(-1; 2)$ uită condiția $x \in D$, punctată separat în barem.'),

            self::num($d(12), 'progresii', 8,
                'Într-o progresie aritmetică termenul al 10-lea este de două ori mai mare decât termenul al 3-lea, iar suma primilor șapte termeni este egală cu 280. Determinați primul termen al progresiei.',
                25,
                [
                    'Scrie termenii prin $a_1$ și $r$: $a_{10} = a_1 + 9r$, $a_3 = a_1 + 2r$.',
                    'Sistemul: $a_1 + 9r = 2(a_1 + 2r)$ și $\frac{2a_1 + 6r}{2} \cdot 7 = 280$.',
                    'Prima ecuație dă $a_1 = 5r$.',
                ],
                'Din $a_1 + 9r = 2(a_1 + 2r)$ rezultă $a_1 = 5r$, iar $(a_1 + 3r) \cdot 7 = 280$ dă $8r = 40$, $r = 5$ și $a_1 = 25$.'),

            self::num($d(13), 'probabilitati', 8,
                'Echipa Republicii Moldova la un concurs de Informatică este formată din 7 elevi, dintre care 4 programează doar în C++, iar 3 programează doar în Python. Pentru o probă au fost luați la întâmplare 4 elevi. Determinați probabilitatea că cel puțin un elev dintre ei programează în Python.',
                34 / 35,
                [
                    '„Cel puțin unul" se calculează cel mai ușor prin evenimentul contrar: niciunul nu programează în Python.',
                    '$n = C_7^4$; evenimentul contrar înseamnă toți 4 din C++: $m = C_4^4 \cdot C_3^0$.',
                    '$p = 1 - \frac{m}{n}$.',
                ],
                '$n = C_7^4 = 35$, iar „niciun elev de Python" are $m = C_4^4 \cdot C_3^0 = 1$ caz. Deci $p = 1 - \frac{1}{35} = \frac{34}{35}$.'),

            self::num($d(14), 'probabilitati', 8,
                'Suma de 100 de mii de lei a fost divizată în două părți și depusă pe două conturi bancare. Dobânda anuală de la primul cont este de 8%, iar de la al doilea cont – de 11%. Dobânda totală obținută pentru un an de la ambele conturi a fost de 9,8 mii de lei. Determinați ce sumă a fost depusă pe primul cont.',
                40,
                [
                    'Notează cu $x$ suma (în mii de lei) depusă pe primul cont; pe al doilea rămâne $100 - x$.',
                    'Dobânzile: $0{,}08x$ și $0{,}11(100 - x)$.',
                    '$0{,}08x + 0{,}11(100 - x) = 9{,}8$.',
                ],
                'Ecuația $0{,}08x + (100 - x) \cdot 0{,}11 = 9{,}8$ dă $11 - 0{,}03x = 9{,}8$, deci $x = 40$ de mii de lei.',
                'mii de lei'),
        ];
    }
}
