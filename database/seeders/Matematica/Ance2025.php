<?php

namespace Database\Seeders\Matematica;

/**
 * Testele ANCE de matematică din 2025 (12_mat_test_{r,u}_ro_{sb,ss}25.pdf și
 * baremele cu același nume).
 */
final class Ance2025
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
        $d = fn (int|string $n) => self::doc('r_sb25', $n);
        $f = 'Fie funcția $f:\mathbb{R}\to\mathbb{R}$, $f(x) = xe^x - 2x$. ';

        return [
            self::num($d(1), 'numere-reale', 5,
                'Calculați valoarea expresiei: $\left(\frac{4}{25}\right)^{1{,}5} \cdot (-5)^3$.',
                -8,
                [
                    'Scrie $\frac{4}{25}$ ca pătrat: $\left(\frac{2}{5}\right)^2$.',
                    '$\left(\left(\frac{2}{5}\right)^2\right)^{1{,}5} = \left(\frac{2}{5}\right)^3 = \frac{8}{125}$.',
                    '$(-5)^3 = -125$.',
                ],
                '$\left(\frac{4}{25}\right)^{1{,}5} = \frac{8}{125}$ și $(-5)^3 = -125$, deci produsul este $-8$.'),

            self::blanks($d(2), 'numere-complexe', 5,
                'Fie $z = \frac{3 - i}{1 + 3i}$, unde $i^2 = -1$. Arătați că $z$ este un număr complex pur imaginar. Scrie $z$ sub forma $a + bi$.',
                ['a' => ['$a$', '0'], 'b' => ['$b$', '-1']],
                [
                    'Amplifică fracția cu conjugatul numitorului, $1 - 3i$.',
                    '$(1 + 3i)(1 - 3i) = 10$.',
                    '$(3 - i)(1 - 3i) = 3 - 9i - i + 3i^2 = -10i$.',
                ],
                '$z = \frac{(3 - i)(1 - 3i)}{10} = \frac{-10i}{10} = -i$: partea reală e 0, deci $z$ e pur imaginar.'),

            self::choice($d(3), 'ecuatii-inecuatii', 8,
                'Rezolvați în $\mathbb{R}$ ecuația $2\sqrt{1 - 2x^2} = -x$.',
                ['$S = \left\{-\frac{2}{3}\right\}$', '$S = \left\{-\frac{2}{3}; \frac{2}{3}\right\}$', '$S = \left\{\frac{2}{3}\right\}$', '$S = \varnothing$'],
                [
                    'Membrul stâng e nenegativ, deci și $-x \geq 0$.',
                    'Ridică la pătrat: $4(1 - 2x^2) = x^2$.',
                    '$9x^2 = 4$ dă $x = \pm\frac{2}{3}$; care respectă $x \leq 0$?',
                ],
                'Sistemul $-x \geq 0$, $4(1 - 2x^2) = x^2$ dă $x^2 = \frac{4}{9}$; condiția $x \leq 0$ păstrează doar $-\frac{2}{3}$. Baremul dă câte 2 puncte pentru fiecare condiție a sistemului.'),

            self::choice($d(4), 'logaritmi', 8,
                'Rezolvați în $\mathbb{R}$ inecuația $\log_5(4 - 3x) \leq \log_{0{,}2} x$.',
                [
                    '$S = \left(0; \frac{1}{3}\right] \cup \left[1; \frac{4}{3}\right)$',
                    '$S = \left[\frac{1}{3}; 1\right]$',
                    '$S = \left(0; \frac{4}{3}\right)$',
                    '$S = \left(-\infty; \frac{1}{3}\right] \cup \left[1; \frac{4}{3}\right)$',
                ],
                [
                    'Adu ambii logaritmi la baza 5: $\log_{0{,}2} x = -\log_5 x = \log_5 \frac{1}{x}$.',
                    'Baza 5 e supraunitară: $4 - 3x > 0$ și $4 - 3x \leq \frac{1}{x}$, cu $x > 0$.',
                    '$4 - 3x \leq \frac{1}{x}$ devine $\frac{-3x^2 + 4x - 1}{x} \leq 0$; zerourile sunt $\frac{1}{3}$, $1$ și (la numitor) $0$.',
                ],
                'Inecuația devine $4 - 3x \leq \frac{1}{x}$, cu $0 < x < \frac{4}{3}$. Din $\frac{-3x^2 + 4x - 1}{x} \leq 0$ și curba semnelor rezultă $S = \left(0; \frac{1}{3}\right] \cup \left[1; \frac{4}{3}\right)$. Varianta cu $(-\infty; \ldots$ uită domeniul logaritmului.'),

            self::choice($d(5), 'matrici', 8,
                'Determinați valorile reale ale lui $\alpha \in \left(\pi; \frac{7\pi}{4}\right)$, pentru care matricea $A = \begin{pmatrix} 1 & -\sin\alpha \\\\ 2 & \operatorname{tg}\alpha \end{pmatrix}$ este inversabilă.',
                [
                    '$\alpha \in \left(\pi; \frac{7\pi}{4}\right) \setminus \left\{\frac{4\pi}{3}; \frac{3\pi}{2}\right\}$',
                    '$\alpha \in \left(\pi; \frac{7\pi}{4}\right) \setminus \left\{\frac{4\pi}{3}\right\}$',
                    '$\alpha \in \left(\pi; \frac{7\pi}{4}\right) \setminus \left\{\frac{3\pi}{2}\right\}$',
                    '$\alpha \in \left(\pi; \frac{7\pi}{4}\right) \setminus \left\{\frac{3\pi}{2}; \frac{5\pi}{3}\right\}$',
                ],
                [
                    'Pentru ce $\alpha$ din interval nu există $\operatorname{tg}\alpha$?',
                    '$\det A = \operatorname{tg}\alpha + 2\sin\alpha = \sin\alpha\left(\frac{1}{\cos\alpha} + 2\right)$.',
                    '$\sin\alpha \neq 0$ pe interval; rezolvă $\frac{1}{\cos\alpha} + 2 = 0$, adică $\cos\alpha = -\frac{1}{2}$.',
                ],
                'Matricea există doar pentru $\alpha \neq \frac{3\pi}{2}$. $\det A = \sin\alpha\left(\frac{1}{\cos\alpha} + 2\right)$, iar pe $\left(\pi; \frac{7\pi}{4}\right)$ se anulează doar pentru $\cos\alpha = -\frac{1}{2}$, adică $\alpha = \frac{4\pi}{3}$. Ambele valori se exclud.'),

            self::num($d(6), 'corpuri', 5,
                'Într-un cilindru circular drept înălțimea este congruentă cu diametrul bazei. Aria bazei cilindrului este egală cu $4\pi$ cm². Determinați volumul cilindrului.',
                16 * M_PI,
                [
                    'Din aria bazei $\pi R^2 = 4\pi$ află raza.',
                    '$R = 2$ cm, deci înălțimea (= diametrul) are 4 cm.',
                    '$\mathcal{V} = \pi R^2 H$.',
                ],
                '$R = 2$ cm, $H = 4$ cm, deci $\mathcal{V} = 4\pi \cdot 4 = 16\pi$ cm³.',
                'cm³'),

            self::num($d(7), 'triunghiuri', 8,
                'Fie triunghiul isoscel $ABC$, în care $AB = BC = 26$ cm, $AC = 20$ cm. Dreapta $MN$, $M \in (AB)$, $N \in (BC)$, $MN \parallel AC$, este situată la distanța de 18 cm de la vârful $B$. Determinați aria trapezului $AMNC$.',
                105,
                [
                    'Înălțimea din $B$ pe $AC$: $\sqrt{26^2 - 10^2} = 24$ cm.',
                    'Triunghiurile $MBN$ și $ABC$ sunt asemenea, cu raportul $\frac{18}{24} = \frac{3}{4}$.',
                    '$MN = \frac{3}{4} \cdot 20 = 15$ cm, iar înălțimea trapezului $= 24 - 18 = 6$ cm.',
                ],
                'Înălțimea triunghiului e 24 cm; din asemănare, $MN = 15$ cm. Trapezul $AMNC$ are înălțimea 6 cm, deci aria $= \frac{20 + 15}{2} \cdot 6 = 105$ cm².',
                'cm²'),

            self::num($d(8), 'corpuri', 8,
                'Baza unei piramide este un romb cu diagonalele de 30 cm și 40 cm. Toate fețele laterale ale piramidei formează cu planul bazei unghiuri de 60°. Determinați lungimea înălțimii piramidei.',
                12 * sqrt(3),
                [
                    'Latura rombului din semidiagonale: $\sqrt{15^2 + 20^2} = 25$ cm.',
                    'Fețele egal înclinate: piciorul înălțimii e centrul cercului înscris în romb. Raza = înălțimea triunghiului cu catetele 15 și 20 pe ipotenuza 25.',
                    '$r = \frac{15 \cdot 20}{25} = 12$ cm, iar $H = r \operatorname{tg} 60^\circ$.',
                ],
                'Latura rombului e 25 cm, raza cercului înscris $r = 12$ cm, deci $H = 12\sqrt{3}$ cm.',
                'cm'),

            self::choice($d(9), 'progresii', 5,
                'Studiați monotonia șirului $(a_n)_{n \geq 1}$, $a_n = \frac{2n + 1}{n + 1}$.',
                ['șirul este strict crescător', 'șirul este strict descrescător', 'șirul este constant', 'șirul nu este monoton'],
                [
                    'Calculează diferența $a_{n+1} - a_n$.',
                    '$a_{n+1} - a_n = \frac{2n + 3}{n + 2} - \frac{2n + 1}{n + 1}$.',
                    'Diferența se simplifică la $\frac{1}{(n + 2)(n + 1)}$. Ce semn are?',
                ],
                '$a_{n+1} - a_n = \frac{1}{(n + 2)(n + 1)} > 0$ pentru orice $n$, deci șirul este monoton crescător.'),

            self::num($d('10a'), 'limite', 8,
                $f.'Calculați: $\lim\limits_{x \to 0} \frac{(x^2 - 2x)^2}{x f(x)}$.',
                -4,
                [
                    'Înlocuiește $f(x)$: numitorul devine $x(xe^x - 2x)$.',
                    'Scoate factorul $x^2$ la numărător și la numitor: $\frac{x^2(x - 2)^2}{x^2(e^x - 2)}$.',
                    'Simplifică și înlocuiește $x = 0$ în $\frac{(x - 2)^2}{e^x - 2}$.',
                ],
                'După simplificarea cu $x^2$, limita este $\lim\limits_{x \to 0} \frac{(x - 2)^2}{e^x - 2} = \frac{4}{-1} = -4$.'),

            self::blanks($d('10b'), 'derivate', 8,
                $f.'Scrieți ecuația tangentei la graficul funcției $f$ în punctul de intersecție a graficului cu axa ordonatelor. Scrie-o sub forma $y = mx + n$.',
                ['m' => ['$m$', '-1'], 'n' => ['$n$', '0']],
                [
                    'Graficul intersectează axa ordonatelor în $x_0 = 0$.',
                    '$f\'(x) = e^x + xe^x - 2$, iar $f(0) = 0$.',
                    '$f\'(0) = -1$; ecuația tangentei: $y = f(0) + f\'(0)(x - 0)$.',
                ],
                '$f(0) = 0$, $f\'(x) = e^x + xe^x - 2$, $f\'(0) = -1$, deci tangenta este $y = -x$.'),

            self::num($d('10c'), 'integrale', 8,
                $f.'Determinați valoarea numerică a ariei figurii mărginite de axa absciselor și de graficul funcției $f$.',
                log(2) ** 2 - 2 * log(2) + 1,
                [
                    'Zerourile lui $f$: $x(e^x - 2) = 0$, deci $x = 0$ și $x = \ln 2$. Între ele, $f < 0$.',
                    'Aria $= \int_0^{\ln 2} (2x - xe^x)\,dx$.',
                    'O primitivă a lui $xe^x$ (prin părți): $xe^x - e^x$.',
                ],
                'Aria $= \int_0^{\ln 2} (2x - xe^x)\,dx = \left[x^2 - xe^x + e^x\right]_0^{\ln 2} = \ln^2 2 - 2\ln 2 + 2 - 1 = \ln^2 2 - 2\ln 2 + 1$. Se scrie „ln2^2 - 2ln2 + 1".'),

            self::num($d(11), 'probabilitati', 8,
                'Cu cifrele 1, 2, 3, 4, 5, 6, 7, 8, 9 se formează un număr de 3 cifre. Determinați probabilitatea că suma cifrelor numărului format este egală cu 5.',
                2 / 243,
                [
                    'Cifrele se pot repeta, deci cazurile posibile sunt $n = 9^3$.',
                    'Cum se scrie 5 ca sumă de trei cifre nenule? $1 + 1 + 3$ și $1 + 2 + 2$, în orice ordine.',
                    'Fiecare combinație are 3 așezări: $m = 6$.',
                ],
                '$n = 9^3 = 729$; numerele favorabile sunt 113, 131, 311, 122, 212, 221, deci $m = 6$ și $p = \frac{6}{729} = \frac{2}{243}$.'),

            self::num($d(12), 'combinatorica', 8,
                'Suma coeficienților binomiali de rang par în dezvoltarea la putere a binomului $\left(\sqrt[3]{x} + \frac{1}{\sqrt{x}}\right)^n$ este egală cu 256. Determinați coeficientul lui $\frac{1}{x^2}$ din dezvoltare.',
                84,
                [
                    'Suma coeficienților binomiali de rang par este $2^{n-1}$.',
                    '$2^{n-1} = 256$, deci $n = 9$. $T_{k+1} = C_9^k x^{\frac{9-k}{3} - \frac{k}{2}}$.',
                    'Exponentul $-2$: $\frac{9 - k}{3} - \frac{k}{2} = -2$.',
                ],
                '$2^{n-1} = 256$ dă $n = 9$; din $\frac{9 - k}{3} - \frac{k}{2} = -2$ rezultă $k = 6$, iar coeficientul este $C_9^6 = 84$.'),
        ];
    }

    /** Profil real, sesiunea suplimentară. */
    private static function realSuplimentara(): array
    {
        $d = fn (int|string $n) => self::doc('r_ss25', $n);
        $f = 'Fie funcția $f:\mathbb{R}\setminus\{-1\}\to\mathbb{R}$, $f(x) = \frac{x^2 + 3}{x + 1}$. ';

        return [
            self::num($d(1), 'numere-reale', 5,
                'Calculați valoarea expresiei: $\sqrt[3]{-2\frac{10}{27}} + 9^{-0{,}5}$.',
                -1,
                [
                    'Transformă numărul mixt în fracție: $-2\frac{10}{27} = -\frac{64}{27}$.',
                    '$\sqrt[3]{-\frac{64}{27}} = -\frac{4}{3}$.',
                    '$9^{-0{,}5} = \frac{1}{\sqrt{9}} = \frac{1}{3}$.',
                ],
                '$\sqrt[3]{-\frac{64}{27}} = -\frac{4}{3}$ și $9^{-0{,}5} = \frac{1}{3}$, deci suma este $-1$.'),

            self::num($d(2), 'ecuatii-inecuatii', 5,
                'Rezolvați în $\mathbb{R}$ ecuația $25^{x - 3} = \left(\frac{1}{5}\right)^{-x}$.',
                6,
                [
                    'Scrie ambii membri ca puteri ale lui 5.',
                    '$25^{x-3} = 5^{2x - 6}$ și $\left(\frac{1}{5}\right)^{-x} = 5^x$.',
                    'Egalează exponenții: $2x - 6 = x$.',
                ],
                '$5^{2x - 6} = 5^x$, deci $2x - 6 = x$ și $x = 6$.'),

            self::blanks($d(3), 'numere-complexe', 8,
                'Determinați numerele complexe $z$, pentru care $\begin{vmatrix} 1 & i^3 \\\\ z & z - 4 \end{vmatrix} = 0$, unde $i^2 = -1$. Scrie răspunsul sub forma $z = a + bi$.',
                ['a' => ['$a$', '2'], 'b' => ['$b$', '-2']],
                [
                    '$i^3 = -i$.',
                    'Determinantul: $z - 4 + iz = 0$, deci $z = \frac{4}{1 + i}$.',
                    'Amplifică cu $1 - i$: $(1 - i)(1 + i) = 2$.',
                ],
                '$z - 4 + iz = 0$ dă $z = \frac{4}{1 + i} = \frac{4(1 - i)}{2} = 2 - 2i$.'),

            self::num($d(4), 'identitati-trigonometrice', 8,
                'Fie $E(x) = \frac{\sin x + \cos x}{\sin x - \cos x} + \frac{\sin x - \cos x}{\sin x + \cos x}$. Determinați $E\left(\frac{5\pi}{8}\right)$.',
                2 * sqrt(2),
                [
                    'Adu la același numitor: $\frac{(\sin x + \cos x)^2 + (\sin x - \cos x)^2}{\sin^2 x - \cos^2 x}$.',
                    'Numărătorul e 2, iar numitorul este $-\cos(2x)$.',
                    '$E(x) = -\frac{2}{\cos(2x)}$, iar $\cos\frac{5\pi}{4} = -\frac{\sqrt{2}}{2}$.',
                ],
                '$E(x) = -\frac{2}{\cos(2x)}$, deci $E\left(\frac{5\pi}{8}\right) = -\frac{2}{\cos\frac{5\pi}{4}} = \frac{2}{\frac{\sqrt{2}}{2}} = 2\sqrt{2}$.'),

            self::choice($d(5), 'logaritmi', 8,
                'Rezolvați în $\mathbb{R}$ inecuația $\frac{|x - 1|}{\log_3^2 x - 2\log_3 x - 3} < 0$.',
                [
                    '$S = \left(\frac{1}{3}; 27\right) \setminus \{1\}$',
                    '$S = \left(\frac{1}{3}; 27\right)$',
                    '$S = (-1; 3) \setminus \{1\}$',
                    '$S = \left(0; \frac{1}{3}\right) \cup (27; +\infty)$',
                ],
                [
                    'Numărătorul e nenegativ; fracția e negativă doar dacă $|x - 1| \neq 0$ și numitorul e negativ.',
                    'Cu $t = \log_3 x$: $t^2 - 2t - 3 < 0$, deci $-1 < t < 3$.',
                    '$-1 < \log_3 x < 3$ înseamnă $\frac{1}{3} < x < 27$; nu uita $x \neq 1$.',
                ],
                'Fracția e negativă când $x \neq 1$ și $\log_3^2 x - 2\log_3 x - 3 < 0$, adică $-1 < \log_3 x < 3$, $\frac{1}{3} < x < 27$. Deci $S = \left(\frac{1}{3}; 27\right) \setminus \{1\}$; varianta fără $\setminus\{1\}$ uită că pentru $x = 1$ fracția e 0, nu negativă.'),

            self::num($d(6), 'corpuri', 5,
                'O sferă metalică cu volumul de $36\pi$ cm³ este topită și turnată într-un cilindru circular drept cu raza bazei congruentă cu raza sferei. Determinați lungimea înălțimii cilindrului.',
                4,
                [
                    'Din $\frac{4}{3}\pi R^3 = 36\pi$ află raza sferei.',
                    '$R = 3$ cm. Volumul se păstrează: $\pi \cdot 3^2 \cdot H = 36\pi$.',
                    '$9\pi H = 36\pi$.',
                ],
                '$R^3 = 27$, deci $R = 3$ cm, iar $9\pi H = 36\pi$ dă $H = 4$ cm.',
                'cm'),

            self::num($d(7), 'corpuri', 8,
                'Din vârful $A$ al triunghiului echilateral $ABC$, se construiește segmentul $AM = 2$ cm, perpendicular planului triunghiului. Determinați distanța de la punctul $M$ la dreapta $BC$, dacă se cunoaște că aria triunghiului $ABC$ este egală cu $4\sqrt{3}$ cm².',
                4,
                [
                    'Din $\frac{a^2\sqrt{3}}{4} = 4\sqrt{3}$ află latura triunghiului.',
                    'Latura are 4 cm, iar înălțimea $AK$ a triunghiului e $2\sqrt{3}$ cm.',
                    'După teorema celor trei perpendiculare, $MK \perp BC$; $MK = \sqrt{AM^2 + AK^2}$.',
                ],
                'Latura triunghiului e 4 cm, înălțimea $AK = 2\sqrt{3}$ cm, iar distanța cerută este $MK = \sqrt{4 + 12} = 4$ cm.',
                'cm'),

            self::num($d(8), 'patrulatere', 8,
                'Fie paralelogramul $ABCD$, în care $m(\angle B) = 120^\circ$, iar bisectoarea unghiului $A$ împarte diagonala $BD$ în segmentele $BK = 1$ cm și $KD = 2$ cm. Determinați măsura în grade a unghiului $AKB$.',
                60,
                [
                    'Teorema bisectoarei în triunghiul $ABD$: $\frac{AB}{AD} = \frac{BK}{KD}$, deci $AD = 2AB$.',
                    'Teorema cosinusului în $ABD$, cu $m(\angle A) = 60^\circ$ și $BD = 3$ cm, dă $AB = \sqrt{3}$ cm.',
                    'În triunghiul $ABK$ aplică teorema sinusurilor (știi $AB$, $BK$ și $\angle BAK = 30^\circ$).',
                ],
                '$AD = 2AB$ și $BD = 3$ cm dau $AB = \sqrt{3}$ cm. În triunghiul $ABK$: $\frac{BK}{\sin 30^\circ} = \frac{AB}{\sin \angle AKB}$, deci $\sin \angle AKB = \frac{\sqrt{3}}{2}$ și unghiul are $60^\circ$.',
                '°'),

            self::num($d(9), 'progresii', 5,
                'Fie progresia geometrică $(b_n)_{n \geq 1}$, în care $b_6 = 5$ și $b_9 = -40$. Determinați rația $q$ a progresiei.',
                -2,
                [
                    '$b_9 = b_6 \cdot q^3$.',
                    '$q^3 = \frac{-40}{5} = -8$.',
                    'Rădăcina cubică a lui $-8$.',
                ],
                '$q^3 = -8$, deci $q = -2$.'),

            self::blanks($d('10a'), 'derivate', 8,
                $f.'Determinați punctele de extrem local ale funcției $f$.',
                ['max' => ['maxim: $x =$', '-3'], 'min' => ['minim: $x =$', '1']],
                [
                    'Derivează cu regula câtului.',
                    '$f\'(x) = \frac{x^2 + 2x - 3}{(x + 1)^2}$. Unde se anulează?',
                    'Zerourile sunt $-3$ și $1$; studiază semnul derivatei, cu $x \neq -1$.',
                ],
                '$f\'(x) = \frac{x^2 + 2x - 3}{(x + 1)^2}$, cu zerourile $-3$ și $1$: derivata e pozitivă, negativă, apoi pozitivă, deci $x = -3$ e punct de maxim local, $x = 1$ — de minim local.'),

            self::blanks($d('10b'), 'limite', 8,
                $f.'Determinați asimptota oblică la $+\infty$ a graficului funcției $f$. Scrie-o sub forma $y = mx + n$.',
                ['m' => ['$m$', '1'], 'n' => ['$n$', '-1']],
                [
                    '$m = \lim\limits_{x \to +\infty} \frac{f(x)}{x} = \lim\limits_{x \to +\infty} \frac{x^2 + 3}{x(x + 1)}$.',
                    '$n = \lim\limits_{x \to +\infty} (f(x) - mx) = \lim\limits_{x \to +\infty} \frac{-x + 3}{x + 1}$.',
                    'Ambele limite sunt rapoarte de polinoame de același grad.',
                ],
                '$m = 1$, $n = \lim\limits_{x \to +\infty} \frac{-x + 3}{x + 1} = -1$, deci asimptota oblică este $y = x - 1$.'),

            self::num($d('10c'), 'integrale', 8,
                $f.'Calculați $\int_0^1 f(x)\,dx$.',
                4 * log(2) - 0.5,
                [
                    'Împarte polinoamele: $f(x) = x - 1 + \frac{4}{x + 1}$.',
                    'O primitivă: $\frac{x^2}{2} - x + 4\ln(x + 1)$.',
                    'Aplică Newton–Leibniz între 0 și 1.',
                ],
                '$\int_0^1 \left(x - 1 + \frac{4}{x + 1}\right)dx = \left[\frac{x^2}{2} - x + 4\ln(x + 1)\right]_0^1 = 4\ln 2 - \frac{1}{2}$. Se scrie „4ln2 - 1/2".'),

            self::num($d(11), 'probabilitati', 8,
                'La un eveniment participă 3 voluntari dintr-un liceu, 5 voluntari dintr-un colegiu și 7 voluntari de la o universitate. Se formează în mod aleator o echipă din 10 voluntari. Determinați probabilitatea că în echipă vor nimeri cel puțin câte 3 voluntari de la fiecare tip de instituție.',
                25 / 143,
                [
                    'Cazurile posibile: $n = C_{15}^{10}$.',
                    'Toți 3 de la liceu trebuie să fie în echipă; restul de 7 se împart între colegiu și universitate, fiecare cu cel puțin 3: $4 + 3$ sau $3 + 4$.',
                    '$m = C_5^4 \cdot C_7^3 + C_5^3 \cdot C_7^4$.',
                ],
                '$n = C_{15}^{10} = 3003$, $m = 5 \cdot 35 + 10 \cdot 35 = 525$, deci $p = \frac{525}{3003} = \frac{25}{143}$.'),

            self::num($d(12), 'combinatorica', 8,
                'Primul termen în dezvoltarea la putere a binomului $\left(\sqrt[3]{2} + 3\right)^n$ este egal cu 1024. Determinați numărul de termeni raționali din dezvoltare.',
                11,
                [
                    'Primul termen este $(\sqrt[3]{2})^n = 2^{\frac{n}{3}} = 1024 = 2^{10}$.',
                    '$n = 30$; $T_{k+1} = C_{30}^k \cdot 2^{\frac{30 - k}{3}} \cdot 3^k$.',
                    'Termenul e rațional când $\frac{30 - k}{3}$ e întreg, adică $k$ e multiplu de 3.',
                ],
                '$n = 30$, iar $k \in \{0, 3, 6, \ldots, 30\}$ dă 11 termeni raționali.'),
        ];
    }

    /** Profil umanist, sesiunea de bază. */
    private static function umanistBaza(): array
    {
        $d = fn (int|string $n) => self::doc('u_sb25', $n);

        return [
            self::num($d(1), 'numere-reale', 5,
                'Calculați: $\left(\frac{4}{49}\right)^{-0{,}5} - 0{,}5$.',
                3,
                [
                    'Scrie $\frac{4}{49}$ ca pătrat: $\left(\frac{2}{7}\right)^2$.',
                    '$\left(\left(\frac{2}{7}\right)^2\right)^{-0{,}5} = \left(\frac{2}{7}\right)^{-1}$.',
                    '$\left(\frac{2}{7}\right)^{-1} = \frac{7}{2}$.',
                ],
                '$\left(\frac{4}{49}\right)^{-0{,}5} = \frac{7}{2}$, deci $\frac{7}{2} - \frac{1}{2} = 3$.'),

            self::num($d(2), 'numere-complexe', 8,
                'Fie numărul complex $z = \frac{10i}{1 - 2i} + 4 - 4i$, unde $i^2 = -1$. Determinați $z^4$.',
                16,
                [
                    'Amplifică fracția cu $1 + 2i$: $(1 - 2i)(1 + 2i) = 5$.',
                    '$\frac{10i(1 + 2i)}{5} = -4 + 2i$.',
                    '$z = -4 + 2i + 4 - 4i = -2i$; ridică la puterea a patra.',
                ],
                '$\frac{10i}{1 - 2i} = -4 + 2i$, deci $z = -2i$ și $z^4 = 16i^4 = 16$.'),

            self::num($d(3), 'logaritmi', 8,
                'Determinați valoarea expresiei $\log_{0{,}2} 15 + \log_5 375 + \log_7 1$.',
                2,
                [
                    '$\log_{0{,}2} 15 = -\log_5 15$ (baza $0{,}2 = 5^{-1}$).',
                    '$\log_5 375 - \log_5 15 = \log_5 25$.',
                    '$\log_7 1 = 0$.',
                ],
                '$-\log_5 15 + \log_5 375 = \log_5 25 = 2$ și $\log_7 1 = 0$, deci valoarea este $2$.'),

            self::num($d(4), 'ecuatii-inecuatii', 8,
                'Petru vrea să transporte de la depozit 600 kg de făină în saci. El are saci de două tipuri. Într-un sac de primul tip încap cu 10 kilograme de făină mai mult decât într-un sac de al doilea tip. Dacă Petru ar pune făina în saci de al doilea tip, atunci el ar avea nevoie de 3 ori mai mulți saci, decât dacă ar pune făina în saci de primul tip. Determinați numărul necesar de saci de primul tip pentru a transporta făina.',
                40,
                [
                    'Notează $x$ capacitatea unui sac de primul tip și $n$ numărul acestor saci.',
                    'Sistemul: $nx = 600$ și $3n(x - 10) = 600$.',
                    'Împarte ecuațiile: $3(x - 10) = x$, deci $x = 15$.',
                ],
                'Din $nx = 600$ și $3n(x - 10) = 600$ rezultă $x = 15$ kg, deci $n = 40$ de saci de primul tip.',
                'saci'),

            self::num($d(5), 'ecuatii-inecuatii', 8,
                'Determinați valorile reale ale lui $a$, astfel încât ecuația $x^2 + (2a + 2)x + a^2 - 2 = 0$ are două soluții reale distincte, modulele cărora sunt egale.',
                -1,
                [
                    'Două soluții distincte cu module egale sunt opuse: $x_1 = -x_2$.',
                    'Atunci $x_1 + x_2 = 0$, deci $-(2a + 2) = 0$.',
                    'Verifică discriminantul: $\Delta = 8a + 12$ trebuie să fie pozitiv.',
                ],
                'Din $x_1 = -x_2$ rezultă $2a + 2 = 0$, adică $a = -1$; atunci $\Delta = 8a + 12 = 4 > 0$, deci soluțiile sunt reale și distincte.'),

            self::num($d(6), 'patrulatere', 5,
                'Fie $ABCD$ un paralelogram, în care $AK$, $K \in (BC)$, este bisectoare a unghiului $A$, $AB = 5$ cm și $KC = 1$ cm. Determinați perimetrul paralelogramului $ABCD$.',
                22,
                [
                    '$AD \parallel BC$, deci $\angle DAK = \angle AKB$. Ce fel de triunghi e $ABK$?',
                    'Triunghiul $ABK$ e isoscel: $BK = AB = 5$ cm.',
                    '$BC = BK + KC = 6$ cm.',
                ],
                '$ABK$ e isoscel, deci $BK = 5$ cm și $BC = 6$ cm. Perimetrul: $2(5 + 6) = 22$ cm.',
                'cm'),

            self::num($d(7), 'corpuri', 5,
                'Aria laterală a unui cilindru circular drept este egală cu $8\pi$ cm². Determinați volumul cilindrului, dacă se cunoaște că suma ariilor bazelor este egală cu aria laterală.',
                8 * M_PI,
                [
                    '$2\pi R^2 = 2\pi R H$ înseamnă $R = H$.',
                    '$2\pi R^2 = 8\pi$, deci $R = 2$ cm.',
                    '$\mathcal{V} = \pi R^2 H$.',
                ],
                '$R = H = 2$ cm, deci $\mathcal{V} = \pi \cdot 4 \cdot 2 = 8\pi$ cm³.',
                'cm³'),

            self::num($d(8), 'triunghiuri', 8,
                'Aria unui triunghi isoscel este egală cu 48 cm². Determinați lungimea razei cercului înscris în triunghi, dacă se cunoaște că înălțimea corespunzătoare bazei este de 6 cm.',
                8 / 3,
                [
                    'Din aria $\frac{1}{2} \cdot b \cdot 6 = 48$ află baza.',
                    'Baza are 16 cm; latura congruentă $= \sqrt{8^2 + 6^2} = 10$ cm.',
                    'Semiperimetrul $p = 18$ cm, iar $\mathcal{A} = p \cdot r$.',
                ],
                'Baza are 16 cm, laturile congruente câte 10 cm, $p = 18$ cm, deci $r = \frac{48}{18} = \frac{8}{3}$ cm.',
                'cm'),

            self::num($d(9), 'corpuri', 8,
                'Punctul $M$ se află la distanța de 6 cm de la planul $\alpha$. Oblicele $MA$ și $MB$ ($A, B \in \alpha$) formează cu planul $\alpha$ unghiuri de $60^\circ$ și $45^\circ$ respectiv. Determinați lungimea segmentului $AB$, dacă se cunoaște că proiecțiile pe planul $\alpha$ ale segmentelor $MA$ și $MB$ sunt reciproc perpendiculare.',
                4 * sqrt(3),
                [
                    'Fie $O$ piciorul perpendicularei din $M$: $MO = 6$ cm. Proiecțiile sunt $OA$ și $OB$.',
                    '$OA = \frac{6}{\operatorname{tg} 60^\circ} = 2\sqrt{3}$ cm și $OB = \frac{6}{\operatorname{tg} 45^\circ} = 6$ cm.',
                    '$OA \perp OB$, deci $AB = \sqrt{OA^2 + OB^2}$.',
                ],
                '$OA = 2\sqrt{3}$ cm, $OB = 6$ cm, deci $AB = \sqrt{12 + 36} = 4\sqrt{3}$ cm.',
                'cm'),

            self::choice($d(10), 'functii', 5,
                'Stabiliți monotonia funcției $f:\mathbb{R}\to\mathbb{R}$, $f(x) = \left(\sqrt{3}\right)^{-x}$.',
                ['$f$ este strict descrescătoare', '$f$ este strict crescătoare', '$f$ este constantă'],
                [
                    'Scrie funcția ca exponențială cu exponent $x$: $(\sqrt{3})^{-x} = \left(\frac{1}{\sqrt{3}}\right)^x$.',
                    'Monotonia exponențialei depinde de bază: e mai mare sau mai mică decât 1?',
                    '$\frac{1}{\sqrt{3}} < 1$.',
                ],
                '$f(x) = \left(\frac{1}{\sqrt{3}}\right)^x$, iar baza $\frac{1}{\sqrt{3}} < 1$, deci funcția este strict descrescătoare.'),

            self::choice($d(11), 'functii', 8,
                'Fie funcțiile $f:\mathbb{R}\to\mathbb{R}$, $f(x) = -x^2 + 4x - 3$, $g:D\to\mathbb{R}$, $g(x) = \sqrt{x - 2}$, iar $A$ — mulțimea valorilor reale ale lui $x$, pentru care $f(x) > 0$. Determinați intersecția mulțimii $A$ cu domeniul de definiție $D$ al funcției $g$.',
                ['$[2; 3)$', '$(1; 3)$', '$(2; 3)$', '$[2; +\infty)$'],
                [
                    'Zerourile lui $f$: $-x^2 + 4x - 3 = 0$ dă $x = 1$ și $x = 3$.',
                    'Coeficientul lui $x^2$ e negativ, deci $f > 0$ între rădăcini: $A = (1; 3)$.',
                    '$D$: $x - 2 \geq 0$, deci $D = [2; +\infty)$.',
                ],
                '$A = (1; 3)$ și $D = [2; +\infty)$, deci $A \cap D = [2; 3)$. Capătul 2 e inclus: $g(2) = 0$ există.'),

            self::num($d(12), 'progresii', 8,
                'Fie progresia geometrică $(b_n)_{n \geq 1}$, în care $b_3 = 16$, $b_6 = -2$. Determinați suma primilor 6 termeni ai progresiei.',
                42,
                [
                    'Sistemul: $b_1 q^2 = 16$ și $b_1 q^5 = -2$.',
                    'Împarte: $q^3 = -\frac{1}{8}$, deci $q = -\frac{1}{2}$ și $b_1 = 64$.',
                    '$S_6 = \frac{b_1(1 - q^6)}{1 - q}$.',
                ],
                '$q = -\frac{1}{2}$, $b_1 = 64$, deci $S_6 = \frac{64\left(1 - \frac{1}{64}\right)}{\frac{3}{2}} = 42$.'),

            self::num($d(13), 'probabilitati', 8,
                'Cu cifrele 2, 3, 4, 5, 6, 7 se formează un număr de 6 cifre care nu se repetă. Determinați probabilitatea că numărul format este divizibil prin 25.',
                1 / 15,
                [
                    'Cazurile posibile: toate permutările celor 6 cifre, $n = 6!$.',
                    'Un număr e divizibil prin 25 dacă se termină în 00, 25, 50 sau 75. Care terminații sunt posibile aici?',
                    'Doar 25 și 75; celelalte 4 cifre se așază în $4!$ moduri: $m = 2 \cdot 4!$.',
                ],
                '$n = 6! = 720$, $m = 2 \cdot 4! = 48$, deci $p = \frac{48}{720} = \frac{1}{15}$.'),

            self::num($d(14), 'probabilitati', 8,
                'Salariul net al unui angajat reprezintă suma rămasă după reținerea a 9% din salariul brut și apoi a 12% din suma rămasă. Determinați salariul brut al domnului Popescu, dacă salariul net este de 12012 lei.',
                15000,
                [
                    'După prima reținere rămâne $0{,}91x$ din salariul brut $x$.',
                    'După a doua reținere rămâne $0{,}88 \cdot 0{,}91x$.',
                    '$0{,}88 \cdot 0{,}91x = 12012$.',
                ],
                '$0{,}88 \cdot 0{,}91x = 0{,}8008x = 12012$, deci $x = 15000$ de lei.',
                'lei'),
        ];
    }

    /** Profil umanist, sesiunea suplimentară. */
    private static function umanistSuplimentara(): array
    {
        $d = fn (int|string $n) => self::doc('u_ss25', $n);

        return [
            self::num($d(1), 'logaritmi', 5,
                'Calculați: $\log_{27} 3 - \frac{10}{3}$.',
                -3,
                [
                    '$27 = 3^3$.',
                    '$\log_{27} 3 = \log_{3^3} 3 = \frac{1}{3}\log_3 3$.',
                    '$\frac{1}{3} - \frac{10}{3}$.',
                ],
                '$\log_{27} 3 = \frac{1}{3}$, deci $\frac{1}{3} - \frac{10}{3} = -3$.'),

            self::num($d(2), 'matrici', 8,
                'Arătați că valoarea lui $d = \begin{vmatrix} 0{,}25^{-18} & 2025^0 \\\\ \left(-\frac{1}{7}\right)^{-1} & 32^{-7} \end{vmatrix}$ este un pătrat perfect. Ce valoare are $d$?',
                9,
                [
                    'Scrie puterile în baza 2: $0{,}25^{-18} = (2^{-2})^{-18} = 2^{36}$ și $32^{-7} = 2^{-35}$.',
                    '$\left(-\frac{1}{7}\right)^{-1} = -7$ și $2025^0 = 1$.',
                    '$d = 2^{36} \cdot 2^{-35} - 1 \cdot (-7)$.',
                ],
                '$d = 2^{36} \cdot 2^{-35} - (-7) = 2 + 7 = 9 = 3^2$, un pătrat perfect.'),

            self::blanks($d(3), 'ecuatii-inecuatii', 8,
                'În două vase sunt în total 90 litri de lapte. Dacă din primul vas se iau 10% din cantitatea de lapte și se toarnă în al doilea vas, cantitățile de lapte din cele două vase devin egale. Determinați cât lapte era inițial în fiecare vas.',
                ['v1' => ['primul vas (l)', '50'], 'v2' => ['al doilea vas (l)', '40']],
                [
                    'Notează $x$ litri în primul vas; în al doilea sunt $90 - x$.',
                    'După turnare: în primul rămân $0{,}9x$, în al doilea ajung $90 - x + 0{,}1x$.',
                    '$0{,}9x = 90 - x + 0{,}1x$.',
                ],
                'Ecuația $0{,}9x = 90 - 0{,}9x$ dă $x = 50$: inițial 50 de litri în primul vas și 40 în al doilea.'),

            self::num($d(4), 'numere-complexe', 8,
                'Determinați modulul numărului complex $z = a + bi$, $a, b \in \mathbb{R}$, $i^2 = -1$, pentru care $iz - 2\bar{z} = 4 + 10i$, unde $\bar{z}$ este conjugatul numărului complex $z$.',
                10,
                [
                    'Înlocuiește $z = a + bi$ și $\bar{z} = a - bi$.',
                    'Obții $-2a - b + i(a + 2b) = 4 + 10i$.',
                    'Sistemul $-2a - b = 4$, $a + 2b = 10$ dă $a = -6$, $b = 8$.',
                ],
                '$z = -6 + 8i$, deci $|z| = \sqrt{36 + 64} = 10$.'),

            self::choice($d(5), 'ecuatii-inecuatii', 8,
                'Determinați valorile reale ale lui $a$, pentru care ecuația $x^2 + 2x + a^2 = 0$ are două soluții distincte negative.',
                ['$a \in (-1; 1) \setminus \{0\}$', '$a \in (-1; 1)$', '$a \in (0; 1)$', '$a \in (-\infty; -1) \cup (1; +\infty)$'],
                [
                    'Două soluții distincte: $\Delta = 4 - 4a^2 > 0$.',
                    'Ambele negative: produsul pozitiv și suma negativă, $x_1x_2 = a^2 > 0$ și $x_1 + x_2 = -2 < 0$.',
                    '$a^2 > 0$ exclude $a = 0$ — atunci o soluție ar fi 0, care nu e negativă.',
                ],
                '$\Delta > 0$ dă $a \in (-1; 1)$, iar $x_1x_2 = a^2 > 0$ cere $a \neq 0$. Deci $a \in (-1; 1) \setminus \{0\}$.'),

            self::num($d(6), 'triunghiuri', 5,
                'Fie triunghiul $ABC$, în care $M \in (AB)$, $N \in (BC)$, astfel încât $MN \parallel AC$. Se știe că $BM = x$, $MA = x + 6$, $BN = 4$ și $NC = 12$. Determinați valoarea lui $x$.',
                3,
                [
                    '$MN \parallel AC$, deci triunghiurile $MBN$ și $ABC$ sunt asemenea.',
                    'Raportul: $\frac{BM}{BA} = \frac{BN}{BC}$, adică $\frac{x}{2x + 6} = \frac{4}{16}$.',
                    '$16x = 4(2x + 6)$.',
                ],
                'Din $\frac{x}{2x + 6} = \frac{1}{4}$ rezultă $4x = 2x + 6$, deci $x = 3$.'),

            self::num($d(7), 'corpuri', 5,
                'Volumul unui con circular drept este egal cu $96\pi$ cm³. Determinați aria laterală a conului, dacă se cunoaște că înălțimea lui este de 8 cm.',
                60 * M_PI,
                [
                    '$\frac{1}{3}\pi R^2 \cdot 8 = 96\pi$.',
                    '$R^2 = 36$, deci $R = 6$ cm.',
                    'Generatoarea $G = \sqrt{6^2 + 8^2}$, iar $\mathcal{A}_{lat} = \pi R G$.',
                ],
                '$R = 6$ cm, $G = 10$ cm, deci $\mathcal{A}_{lat} = 60\pi$ cm².',
                'cm²'),

            self::num($d(8), 'triunghiuri', 8,
                'Fie triunghiul $ABC$, în care înălțimea $BK = 12$ cm, $AB = 13$ cm, $AC = 14$ cm. Determinați lungimea înălțimii corespunzătoare laturii $BC$.',
                56 / 5,
                [
                    'În triunghiul dreptunghic $ABK$: $AK = \sqrt{13^2 - 12^2} = 5$ cm.',
                    '$KC = 14 - 5 = 9$ cm, deci $BC = \sqrt{12^2 + 9^2} = 15$ cm.',
                    'Aria în două feluri: $15h = 12 \cdot 14$.',
                ],
                '$AK = 5$ cm, $KC = 9$ cm, $BC = 15$ cm. Din $15h = 12 \cdot 14$ rezultă $h = \frac{56}{5}$ cm.',
                'cm'),

            self::num($d(9), 'corpuri', 8,
                'Într-o prismă patrulateră regulată, diagonala formează cu planul bazei un unghi de $60^\circ$, iar înălțimea este de $6\sqrt{3}$ cm. Determinați volumul prismei.',
                108 * sqrt(3),
                [
                    'Proiecția diagonalei prismei pe bază e diagonala pătratului: $d = \frac{6\sqrt{3}}{\operatorname{tg} 60^\circ} = 6$ cm.',
                    'Aria pătratului cu diagonala $d$: $\frac{d^2}{2} = 18$ cm².',
                    '$\mathcal{V} = \mathcal{A}_b \cdot H$.',
                ],
                'Diagonala bazei are 6 cm, aria bazei 18 cm², deci $\mathcal{V} = 18 \cdot 6\sqrt{3} = 108\sqrt{3}$ cm³.',
                'cm³'),

            self::num($d(10), 'functii', 5,
                'Fie funcția $f:\mathbb{R}\to\mathbb{R}$, $f(x) = \frac{x}{2 - \sqrt{5}} + \sqrt{20}$. Determinați dacă punctul $M(\sqrt{5}; -5)$ aparține graficului funcției $f$. Cât este $f(\sqrt{5})$?',
                -5,
                [
                    'Punctul aparține graficului dacă $f(\sqrt{5}) = -5$.',
                    'Amplifică $\frac{\sqrt{5}}{2 - \sqrt{5}}$ cu $2 + \sqrt{5}$: obții $-2\sqrt{5} - 5$.',
                    '$\sqrt{20} = 2\sqrt{5}$.',
                ],
                '$f(\sqrt{5}) = -2\sqrt{5} - 5 + 2\sqrt{5} = -5$, deci $M$ aparține graficului.'),

            self::choice($d(11), 'functii', 8,
                'Fie funcțiile $f:\mathbb{R}\to\mathbb{R}$, $f(x) = 2x - 3$, $g:\mathbb{R}^*\to\mathbb{R}$, $g(x) = \frac{2}{x}$. Determinați valorile negative ale lui $x$, pentru care $f(x) > g(x)$.',
                ['$x \in \left(-\frac{1}{2}; 0\right)$', '$x \in \left(-\infty; -\frac{1}{2}\right)$', '$x \in \left(-\frac{1}{2}; 2\right)$', '$x \in (-\infty; 0)$'],
                [
                    'Condițiile: $2x - 3 > \frac{2}{x}$ și $x < 0$.',
                    'Înmulțind cu $x < 0$, sensul inegalității se schimbă: $2x^2 - 3x - 2 < 0$.',
                    'Rădăcinile sunt $-\frac{1}{2}$ și $2$; intersectează cu $x < 0$.',
                ],
                'Pentru $x < 0$, inecuația devine $2x^2 - 3x - 2 < 0$, adică $x \in \left(-\frac{1}{2}; 2\right)$; cu $x < 0$ rămâne $\left(-\frac{1}{2}; 0\right)$. Varianta $\left(-\infty; -\frac{1}{2}\right)$ apare dacă uiți să schimbi sensul la înmulțirea cu un număr negativ.'),

            self::num($d(12), 'progresii', 8,
                'Fie progresia aritmetică $(a_n)_{n \geq 1}$, în care $a_{23} = 45$, $a_{45} = 1$. Determinați numărul de termeni pozitivi ai progresiei.',
                45,
                [
                    'Sistemul: $a_1 + 22r = 45$ și $a_1 + 44r = 1$.',
                    '$r = -2$, $a_1 = 89$, deci $a_n = 91 - 2n$.',
                    'Rezolvă în $\mathbb{N}^*$: $91 - 2n > 0$.',
                ],
                '$a_n = 91 - 2n > 0$ pentru $n \leq 45$, deci progresia are 45 de termeni pozitivi.'),

            self::num($d(13), 'probabilitati', 8,
                'Într-un concurs de matematică participă 7 țări, printre care România, Republica Moldova și Ucraina. Determinați probabilitatea că aceste trei țări se vor plasa pe primele trei locuri, dacă se cunoaște că toate 7 țări se plasează pe locuri distincte.',
                1 / 35,
                [
                    'Cazurile posibile: toate clasamentele, $n = 7!$.',
                    'Favorabile: cele trei țări își împart primele trei locuri ($3!$), celelalte patru — restul ($4!$).',
                    '$m = 3! \cdot 4!$.',
                ],
                '$n = 7! = 5040$, $m = 3! \cdot 4! = 144$, deci $p = \frac{144}{5040} = \frac{1}{35}$.'),

            self::blanks($d(14), 'probabilitati', 8,
                'Opt elevi au înregistrat numărul în ore de utilizare a telefonului mobil într-o zi. Rezultatele înregistrate de primii șapte elevi sunt următoarele: 6, 7, 5, 1, 2, 5, 4. Determinați rezultatul înregistrat de cel de-al optulea elev, dacă se cunoaște că media aritmetică a numărului de ore a celor opt elevi este egală cu 4. Determinați mediana seriei statistice corespunzătoare.',
                ['x' => ['al optulea elev (ore)', '2'], 'me' => ['mediana', '4.5']],
                [
                    'Suma primelor șapte valori este 30: $\frac{30 + x}{8} = 4$.',
                    '$x = 2$.',
                    'Seria ordonată: 1, 2, 2, 4, 5, 5, 6, 7 — mediana e media celor doi termeni din mijloc.',
                ],
                '$x = 2$, iar mediana seriei 1, 2, 2, 4, 5, 5, 6, 7 este $\frac{4 + 5}{2} = 4{,}5$.'),
        ];
    }
}
