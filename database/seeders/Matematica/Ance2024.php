<?php

namespace Database\Seeders\Matematica;

/**
 * Testele ANCE de matematică din 2024 (12_mat_test_{r,u}_ro_{sb,ss}24.pdf și
 * baremele cu același nume).
 */
final class Ance2024
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
        $d = fn (int|string $n) => self::doc('r_sb24', $n);
        $f = 'Fie funcția $f:\left[-\frac{\pi}{2}; \frac{\pi}{4}\right]\to\mathbb{R}$, $f(x) = \sin(2x) - x$. ';

        return [
            self::num($d(1), 'numere-reale', 5,
                'Calculați valoarea expresiei: $-\frac{5}{3} + \left(\frac{16}{81}\right)^{0{,}25}$.',
                -1,
                [
                    'Scrie numărătorul și numitorul ca puteri: $16 = 2^4$, $81 = 3^4$.',
                    '$\left(\frac{2^4}{3^4}\right)^{0{,}25} = \frac{2}{3}$.',
                    '$-\frac{5}{3} + \frac{2}{3}$.',
                ],
                '$\left(\frac{16}{81}\right)^{0{,}25} = \frac{2}{3}$, deci valoarea expresiei este $-\frac{5}{3} + \frac{2}{3} = -1$.'),

            self::blanks($d(2), 'polinoame', 5,
                'Fie polinomul $P(X) = \begin{vmatrix} X & 3 & 0 \\\\ -1 & X & 3 \\\\ 2X & 6 & X - 2 \end{vmatrix}$. Arătați că $P(X)$ este divizibil cu $X - 2$. Scrie mai întâi $P(X)$ sub forma $X^3 + aX^2 + bX + c$.',
                ['a' => ['$a$', '-2'], 'b' => ['$b$', '3'], 'c' => ['$c$', '-6']],
                [
                    'Dezvoltă determinantul după prima linie (sau cu regula triunghiurilor).',
                    'Obții $P(X) = X^3 - 2X^2 + 3X - 6$.',
                    'Verifică divizibilitatea cu teorema lui Bézout: $P(2) = 8 - 8 + 6 - 6$.',
                ],
                '$P(X) = X^3 - 2X^2 + 3X - 6$, iar $P(2) = 0$, deci $P(X)$ e divizibil cu $X - 2$ (teorema lui Bézout).'),

            self::choice($d(3), 'logaritmi', 8,
                'Rezolvați în $\mathbb{R}$ ecuația $\log_2(x - 1) + \log_2(x - 2) = 1$.',
                ['$S = \{3\}$', '$S = \{0; 3\}$', '$S = \{0\}$', '$S = \varnothing$'],
                [
                    'Domeniul: $x - 1 > 0$ și $x - 2 > 0$, deci $x > 2$.',
                    'Suma logaritmilor: $\log_2(x^2 - 3x + 2) = 1$, deci $x^2 - 3x + 2 = 2$.',
                    '$x^2 - 3x = 0$ dă $x = 0$ și $x = 3$. Care e în domeniu?',
                ],
                'Pe domeniul $x > 2$, ecuația devine $x^2 - 3x + 2 = 2$, cu soluțiile 0 și 3; doar $x = 3$ e în domeniu. Baremul dă 2 puncte pentru domeniu.'),

            self::num($d(4), 'numere-complexe', 8,
                'Determinați valorile reale ale lui $m$, pentru care numărul complex nenul $z = a + ai$, $a \in \mathbb{R}$, $i^2 = -1$, este soluție a ecuației $z^2 - 6z + m = 0$.',
                18,
                [
                    'Înlocuiește: $(a + ai)^2 - 6(a + ai) + m = 0$. Cât face $(a + ai)^2$?',
                    '$(a + ai)^2 = 2a^2 i$. Egalează părțile: $-6a + m = 0$ și $2a^2 - 6a = 0$.',
                    '$z$ e nenul, deci $a \neq 0$: din a doua ecuație $a = 3$.',
                ],
                'Sistemul $-6a + m = 0$, $2a^2 - 6a = 0$ cu $a \neq 0$ dă $a = 3$ și $m = 18$.'),

            self::choice($d(5), 'ecuatii-inecuatii', 8,
                'Rezolvați în $\mathbb{R}$ inecuația $\left(3 \cdot 9^x - 4 \cdot 3^x + 1\right)\sqrt{1 - x} \leq 0$.',
                ['$S = [-1; 0] \cup \{1\}$', '$S = [-1; 0]$', '$S = [-1; 1]$', '$S = (-\infty; -1] \cup [0; 1]$'],
                [
                    'Domeniul: $x \leq 1$. Pentru $x = 1$, radicalul e 0 și inecuația e adevărată.',
                    'Pentru $x < 1$, radicalul e pozitiv, deci $3 \cdot 9^x - 4 \cdot 3^x + 1 \leq 0$.',
                    'Cu $t = 3^x$: $3t^2 - 4t + 1 \leq 0$ dă $\frac{1}{3} \leq 3^x \leq 1$.',
                ],
                '$x = 1$ e soluție (radicalul se anulează). Pentru $x < 1$: $\frac{1}{3} \leq 3^x \leq 1$, adică $-1 \leq x \leq 0$. Deci $S = [-1; 0] \cup \{1\}$.'),

            self::num($d(6), 'cerc', 5,
                'Punctele $A$, $B$, $C$ aparțin unui cerc, iar punctul $D$ aparține dreptei $AB$, astfel încât $B \in (AD)$ și $m(\angle CBD) = 100^\circ$. Determinați măsura în grade a arcului mic $AC$.',
                160,
                [
                    'Unghiurile $ABC$ și $CBD$ sunt adiacente suplementare.',
                    '$m(\angle ABC) = 80^\circ$.',
                    'Unghiul înscris $ABC$ e jumătate din arcul mic $AC$.',
                ],
                '$m(\angle ABC) = 180^\circ - 100^\circ = 80^\circ$, iar arcul mic $AC$ măsoară $2 \cdot 80^\circ = 160^\circ$.',
                '°'),

            self::num($d(7), 'triunghiuri', 8,
                'În triunghiul isoscel $ABC$ cu baza $BC$, bisectoarea $BD$ determină pe latura $AC$ segmentele $AD = 8$ cm și $DC = 12$ cm. Determinați lungimea înălțimii triunghiului $ABC$, corespunzătoare laturii $BC$.',
                5 * sqrt(7),
                [
                    '$AB = AC = 8 + 12 = 20$ cm.',
                    'Teorema bisectoarei: $\frac{AB}{BC} = \frac{AD}{DC}$, deci $\frac{20}{BC} = \frac{8}{12}$.',
                    '$BC = 30$ cm; înălțimea $AK$ cade în mijlocul bazei, $BK = 15$ cm.',
                ],
                '$BC = 30$ cm, $BK = 15$ cm, deci $AK = \sqrt{20^2 - 15^2} = 5\sqrt{7}$ cm.',
                'cm'),

            self::num($d(8), 'corpuri', 8,
                'Baza piramidei $VABCD$ este trapezul isoscel $ABCD$, în care baza mică este de 6 cm, laturile congruente sunt de $\sqrt{2}$ cm, iar unghiurile de la baza mare sunt de $45^\circ$. Determinați lungimea înălțimii piramidei, dacă se cunoaște că toate muchiile laterale sunt de 13 cm.',
                12,
                [
                    'Muchii laterale congruente: piciorul înălțimii e centrul cercului circumscris bazei.',
                    'Înălțimea trapezului și proiecțiile laturilor au câte 1 cm; baza mare are 8 cm, iar diagonala $BD = 5\sqrt{2}$ cm.',
                    'Raza cercului circumscris trapezului (al triunghiului $ABD$) iese 5 cm; $H = \sqrt{13^2 - R^2}$.',
                ],
                'Raza cercului circumscris bazei este 5 cm, deci înălțimea piramidei este $\sqrt{169 - 25} = 12$ cm.',
                'cm'),

            self::choice($d(9), 'functii', 5,
                'Studiați paritatea funcției $f:\mathbb{R}^*\to\mathbb{R}$, $f(x) = x^3 + \frac{1}{x}$.',
                ['$f$ este impară', '$f$ este pară', '$f$ nu este nici pară, nici impară'],
                [
                    'Calculează $f(-x)$ și compară cu $f(x)$.',
                    '$f(-x) = (-x)^3 + \frac{1}{-x}$.',
                    '$f(-x) = -x^3 - \frac{1}{x} = -f(x)$.',
                ],
                'Domeniul $\mathbb{R}^*$ e simetric, iar $f(-x) = -x^3 - \frac{1}{x} = -f(x)$, deci funcția este impară.'),

            self::num($d('10a'), 'limite', 8,
                $f.'Calculați: $\lim\limits_{x \to 0} \frac{f(x)}{x^2 + x}$.',
                1,
                [
                    'Separă: $\frac{\sin(2x)}{x^2 + x} - \frac{x}{x^2 + x}$.',
                    '$\frac{\sin(2x)}{x(x + 1)} = \frac{\sin(2x)}{2x} \cdot \frac{2}{x + 1} \to 2$.',
                    '$\frac{x}{x^2 + x} = \frac{1}{x + 1} \to 1$.',
                ],
                'Limita este $2 - 1 = 1$ (cu $\lim\limits_{x \to 0} \frac{\sin(2x)}{2x} = 1$).'),

            self::choice($d('10b'), 'integrale', 8,
                $f.'Determinați primitiva $F$ a funcției $f$, graficul căreia trece prin originea sistemului de coordonate.',
                [
                    '$F(x) = -\frac{1}{2}\cos(2x) - \frac{x^2}{2} + \frac{1}{2}$',
                    '$F(x) = -\frac{1}{2}\cos(2x) - \frac{x^2}{2}$',
                    '$F(x) = \frac{1}{2}\cos(2x) - \frac{x^2}{2} - \frac{1}{2}$',
                    '$F(x) = -\frac{1}{2}\cos(2x) - \frac{x^2}{2} - \frac{1}{2}$',
                ],
                [
                    'Toate primitivele: $F(x) = -\frac{1}{2}\cos(2x) - \frac{x^2}{2} + C$.',
                    'Graficul trece prin origine: $F(0) = 0$.',
                    '$F(0) = -\frac{1}{2} + C = 0$.',
                ],
                '$F(x) = -\frac{1}{2}\cos(2x) - \frac{x^2}{2} + C$, iar $F(0) = -\frac{1}{2} + C = 0$ dă $C = \frac{1}{2}$. Varianta fără constantă nu trece prin origine: $F(0) = -\frac{1}{2}$.'),

            self::blanks($d('10c'), 'derivate', 8,
                $f.'Determinați extremele globale ale funcției $f$ pe segmentul $\left[-\frac{\pi}{2}; 0\right]$.',
                ['min' => ['minimul', '-√3/2 + π/6'], 'max' => ['maximul', 'π/2']],
                [
                    '$f\'(x) = 2\cos(2x) - 1$; rezolvă $f\'(x) = 0$ pe segment.',
                    'Pe $\left[-\frac{\pi}{2}; 0\right]$, $\cos(2x) = \frac{1}{2}$ dă $x = -\frac{\pi}{6}$.',
                    'Compară $f\left(-\frac{\pi}{2}\right)$, $f\left(-\frac{\pi}{6}\right)$ și $f(0)$.',
                ],
                '$f\left(-\frac{\pi}{2}\right) = \frac{\pi}{2}$, $f\left(-\frac{\pi}{6}\right) = -\frac{\sqrt{3}}{2} + \frac{\pi}{6}$, $f(0) = 0$. Minimul global este $-\frac{\sqrt{3}}{2} + \frac{\pi}{6}$, maximul global $\frac{\pi}{2}$.'),

            self::num($d(11), 'probabilitati', 8,
                'Șapte copii, printre care Ana și Maria, participă la un maraton. Copiii ajung la linia de finiș în diferite momente de timp. Determinați probabilitatea că Ana va ajunge la linia de finiș următoarea după Maria.',
                1 / 7,
                [
                    'Cazurile posibile: toate ordinile de sosire, $n = 7!$.',
                    'Lipește „Maria, apoi Ana" într-un singur bloc: rămân 6 obiecte de ordonat.',
                    '$m = 6!$.',
                ],
                '$n = 7!$, $m = 6!$, deci $p = \frac{6!}{7!} = \frac{1}{7}$.'),

            self::num($d(12), 'combinatorica', 8,
                'Suma coeficienților binomiali ai termenului al doilea și al penultimului termen ai dezvoltării la putere a binomului $\left(x^6 + \frac{1}{\sqrt[3]{x^2}}\right)^n$ este egală cu 40. Determinați termenul care nu-l conține pe $x$.',
                190,
                [
                    '$C_n^1 + C_n^{n-1} = 2n = 40$, deci $n = 20$.',
                    '$T_{k+1} = C_{20}^k x^{120 - 6k - \frac{2}{3}k}$.',
                    'Exponentul 0: $120 - 6k - \frac{2}{3}k = 0$.',
                ],
                '$n = 20$, iar exponentul se anulează pentru $k = 18$; termenul liber este $T_{19} = C_{20}^{18} = 190$.'),
        ];
    }

    /** Profil real, sesiunea suplimentară. */
    private static function realSuplimentara(): array
    {
        $d = fn (int|string $n) => self::doc('r_ss24', $n);
        $f = 'Fie funcția $f:\mathbb{R}\setminus\{-2\}\to\mathbb{R}$, $f(x) = \frac{x^2 - 1}{x + 2}$. ';

        return [
            self::num($d(1), 'numere-reale', 5,
                'Calculați valoarea expresiei: $\sqrt[3]{32^{0{,}4} - 12}$.',
                -2,
                [
                    '$32 = 2^5$.',
                    '$32^{0{,}4} = (2^5)^{0{,}4} = 2^2 = 4$.',
                    '$\sqrt[3]{4 - 12} = \sqrt[3]{-8}$.',
                ],
                '$32^{0{,}4} = 4$, deci $\sqrt[3]{4 - 12} = \sqrt[3]{-8} = -2$.'),

            self::num($d(2), 'numere-complexe', 5,
                'Fie numărul complex $z = (1 - 3i)^2 + 12i$, unde $i^2 = -1$. Determinați modulul numărului $z$.',
                10,
                [
                    '$(1 - 3i)^2 = 1 - 6i + 9i^2 = -8 - 6i$.',
                    '$z = -8 - 6i + 12i = -8 + 6i$.',
                    '$|z| = \sqrt{(-8)^2 + 6^2}$.',
                ],
                '$z = -8 + 6i$, deci $|z| = \sqrt{64 + 36} = 10$.'),

            self::num($d(3), 'ecuatii-inecuatii', 8,
                'Determinați soluțiile întregi ale ecuației $125^{x^2} = 25^{x + 4}$.',
                2,
                [
                    'Scrie ambii membri ca puteri ale lui 5: $5^{3x^2} = 5^{2x + 8}$.',
                    'Egalează exponenții: $3x^2 = 2x + 8$.',
                    'Rădăcinile sunt $2$ și $-\frac{4}{3}$. Care e întreagă?',
                ],
                '$3x^2 - 2x - 8 = 0$ are soluțiile $2$ și $-\frac{4}{3}$; singura soluție întreagă este $x = 2$.'),

            self::choice($d(4), 'logaritmi', 8,
                'Rezolvați în $\mathbb{R}$ inecuația $\log_{\frac{1}{2}} |2 - x| \geq -2$.',
                ['$S = [-2; 6] \setminus \{2\}$', '$S = [-2; 6]$', '$S = (-\infty; -2] \cup [6; +\infty)$', '$S = [-2; 2)$'],
                [
                    'Domeniul: $|2 - x| > 0$, adică $x \neq 2$.',
                    '$-2 = \log_{\frac{1}{2}} 4$, iar baza e subunitară, deci $|2 - x| \leq 4$.',
                    '$|2 - x| \leq 4 \iff -2 \leq x \leq 6$.',
                ],
                'Baza $\frac{1}{2}$ e subunitară, deci inecuația devine $|2 - x| \leq 4$, adică $-2 \leq x \leq 6$; cu domeniul $x \neq 2$, $S = [-2; 6] \setminus \{2\}$.'),

            self::blanks($d(5), 'matrici', 8,
                'Fie matricea $A = \begin{pmatrix} \sin(2x) & 1 & \cos x \\\\ 1 & 4 & 1 \\\\ \sin x & 1 & 1 \end{pmatrix}$, unde $\sin x + \cos x = m$, $m \in \left[-\frac{1}{2}; \frac{1}{2}\right]$. Arătați că matricea $A$ este inversabilă. Scrie mai întâi $\det A$ în funcție de $m$, sub forma $m^2 + am + b$.',
                ['a' => ['$a$', '1'], 'b' => ['$b$', '-2']],
                [
                    'Dezvoltă determinantul: $\det A = \sin(2x) + \cos x + \sin x - 1$.',
                    'Ridică la pătrat $\sin x + \cos x = m$: $\sin(2x) = m^2 - 1$.',
                    '$\det A = m^2 - 1 + m - 1$; are rădăcini în $\left[-\frac{1}{2}; \frac{1}{2}\right]$?',
                ],
                '$\det A = m^2 + m - 2 = (m - 1)(m + 2)$, cu rădăcinile 1 și $-2$, ambele în afara intervalului $\left[-\frac{1}{2}; \frac{1}{2}\right]$. Deci $\det A \neq 0$ și $A$ e inversabilă.'),

            self::num($d(6), 'triunghiuri', 5,
                'În triunghiul $ABC$ medianele $AM$ și $BN$ sunt perpendiculare și au lungimile de 9 cm și 12 cm respectiv. Determinați lungimea laturii $AB$.',
                10,
                [
                    'Centrul de greutate $O$ împarte fiecare mediană în raportul $2 : 1$ de la vârf.',
                    '$AO = \frac{2}{3} \cdot 9 = 6$ cm și $BO = \frac{2}{3} \cdot 12 = 8$ cm.',
                    'Triunghiul $AOB$ e dreptunghic în $O$.',
                ],
                '$AO = 6$ cm, $BO = 8$ cm, iar $AB = \sqrt{36 + 64} = 10$ cm.',
                'cm'),

            self::num($d(7), 'corpuri', 8,
                'Aria secțiunii axiale a unui con circular drept este de 60 cm². Determinați aria laterală a conului, dacă se cunoaște că diametrul bazei este de 10 cm.',
                65 * M_PI,
                [
                    'Secțiunea axială e un triunghi cu baza 10 cm: $\frac{1}{2} \cdot 10 \cdot H = 60$.',
                    '$H = 12$ cm și $R = 5$ cm.',
                    '$G = \sqrt{12^2 + 5^2} = 13$ cm; $\mathcal{A}_{lat} = \pi R G$.',
                ],
                '$H = 12$ cm, $R = 5$ cm, $G = 13$ cm, deci $\mathcal{A}_{lat} = 65\pi$ cm².',
                'cm²'),

            self::num($d(8), 'patrulatere', 8,
                'În paralelogramul $ABCD$, $O$ este punctul de intersecție a diagonalelor astfel încât $m(\angle AOB) = 60^\circ$. Determinați lungimea înălțimii paralelogramului, corespunzătoare laturii $AB$, dacă $AC = 16$ cm și $BD = 10$ cm.',
                40 * sqrt(3) / 7,
                [
                    'Diagonalele se înjumătățesc: $AO = 8$ cm, $BO = 5$ cm.',
                    'Teorema cosinusului în $AOB$: $AB^2 = 64 + 25 - 80\cos 60^\circ = 49$.',
                    'Aria: $\frac{1}{2} d_1 d_2 \sin 60^\circ = 40\sqrt{3}$; apoi $40\sqrt{3} = 7h$.',
                ],
                '$AB = 7$ cm, aria $= 40\sqrt{3}$ cm², deci $h = \frac{40\sqrt{3}}{7}$ cm.',
                'cm'),

            self::num($d(9), 'functii', 5,
                'Determinați valoarea maximă a funcției $f:[0; +\infty)\to\mathbb{R}$, $f(x) = 2 - \sqrt{x}$.',
                2,
                [
                    '$\sqrt{x} \geq 0$ pentru orice $x \geq 0$.',
                    'Deci $2 - \sqrt{x} \leq 2$.',
                    'Egalitatea se atinge pentru $x = 0$.',
                ],
                '$\sqrt{x} \geq 0$, deci $f(x) = 2 - \sqrt{x} \leq 2$, cu egalitate în $x = 0$: valoarea maximă este 2.'),

            self::blanks($d('10a'), 'derivate', 8,
                $f.'Scrieți ecuația tangentei la graficul funcției $f$ în punctul de abscisă $x_0 = -1$. Scrie-o sub forma $y = mx + n$.',
                ['m' => ['$m$', '-2'], 'n' => ['$n$', '-2']],
                [
                    '$f(-1) = 0$.',
                    '$f\'(x) = \frac{x^2 + 4x + 1}{(x + 2)^2}$.',
                    '$f\'(-1) = -2$; tangenta: $y = f(-1) + f\'(-1)(x + 1)$.',
                ],
                '$f(-1) = 0$, $f\'(-1) = -2$, deci tangenta este $y = -2(x + 1) = -2x - 2$.'),

            self::blanks($d('10b'), 'limite', 8,
                $f.'Determinați asimptota oblică la $+\infty$ a graficului funcției $f$. Scrie-o sub forma $y = mx + n$.',
                ['m' => ['$m$', '1'], 'n' => ['$n$', '-2']],
                [
                    '$m = \lim\limits_{x \to +\infty} \frac{x^2 - 1}{x(x + 2)}$.',
                    '$n = \lim\limits_{x \to +\infty} \left(\frac{x^2 - 1}{x + 2} - x\right) = \lim\limits_{x \to +\infty} \frac{-2x - 1}{x + 2}$.',
                    'Ambele sunt rapoarte de polinoame de același grad.',
                ],
                '$m = 1$, $n = -2$, deci asimptota oblică este $y = x - 2$.'),

            self::num($d('10c'), 'integrale', 8,
                $f.'Calculați: $\int_0^2 |f(x)|\,dx$.',
                1 + 3 * log(8 / 9),
                [
                    '$f$ își schimbă semnul în $x = 1$: $\int_0^2 |f| = -\int_0^1 f + \int_1^2 f$.',
                    'Împarte polinoamele: $f(x) = x - 2 + \frac{3}{x + 2}$.',
                    'O primitivă: $\frac{x^2}{2} - 2x + 3\ln(x + 2)$.',
                ],
                'Cu primitiva $\frac{x^2}{2} - 2x + 3\ln(x + 2)$ și semnul lui $f$ pe $[0; 1]$ și $[1; 2]$, integrala este $1 + 3\ln\frac{8}{9}$. Se scrie „1 + 3ln(8/9)".'),

            self::num($d(11), 'probabilitati', 8,
                'Un zar se aruncă de 5 ori. Determinați probabilitatea că doar la două aruncări va cădea un număr de puncte divizibil prin 3.',
                80 / 243,
                [
                    'Cazurile posibile: $n = 6^5$.',
                    'Divizibile prin 3: fețele 3 și 6 (2 fețe); celelalte 4 fețe nu sunt.',
                    'Alegi cele două aruncări ($C_5^2$), apoi fețele: $m = C_5^2 \cdot 2^2 \cdot 4^3$.',
                ],
                '$n = 6^5 = 7776$, $m = C_5^2 \cdot 2^2 \cdot 4^3 = 2560$, deci $p = \frac{2560}{7776} = \frac{80}{243}$.'),

            self::num($d(12), 'combinatorica', 8,
                'Coeficientul binomial al termenului al treilea în dezvoltarea la putere a binomului $\left(\sqrt[3]{x^5} + \frac{1}{\sqrt[3]{x^2}}\right)^n$ este egal cu 105. Determinați coeficientul binomial al lui $x^{-3}$.',
                455,
                [
                    '$C_n^2 = \frac{n(n - 1)}{2} = 105$, deci $n = 15$.',
                    '$T_{k+1} = C_{15}^k x^{\frac{75 - 7k}{3}}$.',
                    'Exponentul $-3$: $\frac{75 - 7k}{3} = -3$.',
                ],
                '$n = 15$, iar $\frac{75 - 7k}{3} = -3$ dă $k = 12$; coeficientul binomial este $C_{15}^{12} = 455$.'),
        ];
    }

    /** Profil umanist, sesiunea de bază. */
    private static function umanistBaza(): array
    {
        $d = fn (int|string $n) => self::doc('u_sb24', $n);

        return [
            self::num($d(1), 'numere-reale', 5,
                'Calculați: $36^{0{,}25} \cdot 6^{0{,}5} - 6$.',
                0,
                [
                    '$36 = 6^2$, deci $36^{0{,}25} = 6^{0{,}5}$.',
                    '$6^{0{,}5} \cdot 6^{0{,}5} = 6$.',
                    '$6 - 6$.',
                ],
                '$36^{0{,}25} = 6^{0{,}5}$, iar $6^{0{,}5} \cdot 6^{0{,}5} = 6$, deci valoarea este $0$.'),

            self::num($d(2), 'logaritmi', 8,
                'Arătați că valoarea expresiei $\frac{\log_7 12 - 2\log_7 2}{\log_{49} \frac{1}{9}}$ este un număr întreg. Ce valoare are expresia?',
                -1,
                [
                    'Numărătorul: $\log_7 12 - \log_7 4 = \log_7 3$.',
                    'Numitorul: $\log_{49} \frac{1}{9} = \log_{7^2} 3^{-2}$.',
                    '$\log_{7^2} 3^{-2} = \frac{-2}{2}\log_7 3 = -\log_7 3$.',
                ],
                'Numărătorul este $\log_7 3$, numitorul $-\log_7 3$, deci expresia este $-1$, un număr întreg.'),

            self::blanks($d(3), 'numere-complexe', 8,
                'Determinați numerele complexe $z$, pentru care $\begin{vmatrix} z & 2 \\\\ 2i & i \end{vmatrix} = 3 - 2z$, unde $i^2 = -1$. Scrie răspunsul sub forma $z = a + bi$.',
                ['a' => ['$a$', '2'], 'b' => ['$b$', '1']],
                [
                    'Determinantul: $zi - 4i$. Ecuația: $zi - 4i = 3 - 2z$.',
                    '$z(2 + i) = 3 + 4i$, deci $z = \frac{3 + 4i}{2 + i}$.',
                    'Amplifică cu $2 - i$: $(2 - i)(2 + i) = 5$, iar $(3 + 4i)(2 - i) = 10 + 5i$.',
                ],
                '$z = \frac{3 + 4i}{2 + i} = \frac{10 + 5i}{5} = 2 + i$.'),

            self::num($d(4), 'ecuatii-inecuatii', 8,
                'Într-o clasă 40% din numărul total de elevi erau băieți. După ce în clasă au venit încă 6 băieți, numărul de băieți a devenit egal cu numărul de fete. Determinați numărul de fete din clasă.',
                18,
                [
                    'Notează cu $x$ numărul inițial de elevi: băieți $0{,}4x$, fete $0{,}6x$.',
                    'După sosirea celor 6 băieți: $0{,}4x + 6 = 0{,}6x$.',
                    '$x = 30$; fetele sunt $0{,}6 \cdot 30$.',
                ],
                'Din $0{,}4x + 6 = 0{,}6x$ rezultă $x = 30$, deci în clasă sunt $18$ fete.'),

            self::num($d(5), 'ecuatii-inecuatii', 8,
                'Determinați cea mai mică valoare întreagă a lui $a$, pentru care una dintre soluțiile ecuației $x^2 - (2a - 6)x + 9 - 6a = 0$ aparține intervalului $(1; +\infty)$.',
                3,
                [
                    'Calculează discriminantul: $\Delta = 4a^2$.',
                    'Soluțiile sunt $x_1 = -3$ și $x_2 = 2a - 3$. Care poate fi mai mare decât 1?',
                    '$2a - 3 > 1$ dă $a > 2$.',
                ],
                '$\Delta = 4a^2$, soluțiile sunt $-3$ și $2a - 3$; condiția $2a - 3 > 1$ dă $a > 2$, deci cea mai mică valoare întreagă este $a = 3$.'),

            self::num($d(6), 'triunghiuri', 5,
                'Fie $AD \parallel BC$, $AD = 20$ cm, $BC = 8$ cm, iar $O$ este punctul de intersecție a dreptelor $AC$ și $BD$. Determinați lungimea segmentului $OC$, dacă se cunoaște că ea este cu 6 cm mai mică decât lungimea segmentului $OA$.',
                4,
                [
                    'Triunghiurile $BOC$ și $DOA$ sunt asemenea ($BC \parallel AD$).',
                    'Raportul de asemănare: $\frac{BC}{AD} = \frac{OC}{OA}$, cu $OA = OC + 6$.',
                    '$\frac{8}{20} = \frac{OC}{OC + 6}$.',
                ],
                'Din $\frac{4}{10} = \frac{OC}{OC + 6}$ rezultă $4OC + 24 = 10OC$, deci $OC = 4$ cm.',
                'cm'),

            self::num($d(7), 'corpuri', 5,
                'Secțiunea axială a unui con circular drept este un triunghi cu laturile de 13 cm, 13 cm și 10 cm. Determinați volumul conului.',
                100 * M_PI,
                [
                    'Baza triunghiului axial e diametrul: $R = 5$ cm; laturile egale sunt generatoarele.',
                    'Înălțimea conului: $H = \sqrt{13^2 - 5^2} = 12$ cm.',
                    '$\mathcal{V} = \frac{1}{3}\pi R^2 H$.',
                ],
                '$R = 5$ cm, $H = 12$ cm, deci $\mathcal{V} = \frac{1}{3}\pi \cdot 25 \cdot 12 = 100\pi$ cm³.',
                'cm³'),

            self::num($d(8), 'patrulatere', 8,
                'Într-un trapez isoscel, unghiul de la baza mare este de $60^\circ$, iar înălțimea de 3 cm. Determinați aria trapezului, dacă se cunoaște că diagonala lui este de 6 cm.',
                9 * sqrt(3),
                [
                    'Proiecția laturii laterale pe baza mare: $\frac{3}{\operatorname{tg} 60^\circ} = \sqrt{3}$ cm.',
                    'Proiecția diagonalei pe baza mare: $\sqrt{6^2 - 3^2} = 3\sqrt{3}$ cm — ea e egală cu linia mijlocie.',
                    'Aria = linia mijlocie × înălțimea.',
                ],
                'Proiecția diagonalei pe baza mare, $3\sqrt{3}$ cm, este egală cu semisuma bazelor (baza mică $2\sqrt{3}$ cm, baza mare $4\sqrt{3}$ cm). Aria $= 3\sqrt{3} \cdot 3 = 9\sqrt{3}$ cm².',
                'cm²'),

            self::num($d(9), 'corpuri', 8,
                'Baza unei prisme drepte este un romb cu latura de 5 cm și o diagonală de 6 cm. Determinați aria laterală a prismei, dacă se cunoaște că înălțimea prismei este congruentă cu înălțimea rombului din bază.',
                96,
                [
                    'Diagonalele rombului se înjumătățesc perpendicular: jumătatea celeilalte diagonale e $\sqrt{25 - 9} = 4$ cm.',
                    'Aria rombului $= \frac{6 \cdot 8}{2} = 24$ cm², deci $5h = 24$.',
                    'Înălțimea rombului (și a prismei) $h = 4{,}8$ cm; aria laterală = perimetrul bazei × înălțimea.',
                ],
                'A doua diagonală are 8 cm, aria rombului 24 cm², înălțimea $4{,}8$ cm. Aria laterală $= 20 \cdot 4{,}8 = 96$ cm².',
                'cm²'),

            self::choice($d(10), 'functii', 5,
                'Studiați monotonia funcției $f:\mathbb{R}\to\mathbb{R}$, $f(x) = \left(\frac{1}{\sqrt{2} - 1}\right)^x$.',
                ['$f$ este strict crescătoare', '$f$ este strict descrescătoare', '$f$ este constantă'],
                [
                    'Monotonia exponențialei depinde de bază.',
                    'Raționalizează baza: $\frac{1}{\sqrt{2} - 1} = \sqrt{2} + 1$.',
                    '$\sqrt{2} + 1 > 1$.',
                ],
                'Baza $\frac{1}{\sqrt{2} - 1} = \sqrt{2} + 1 > 1$, deci funcția este strict crescătoare.'),

            self::choice($d(11), 'functii', 8,
                'Fie funcțiile $f:[0; +\infty)\to\mathbb{R}$, $f(x) = \sqrt{x} + 1$, $g:\mathbb{R}\to\mathbb{R}$, $g(x) = -2x^2 + 8x + 5$. Determinați intersecția mulțimilor de valori $E(f)$ și $E(g)$ ale funcțiilor $f$ și $g$.',
                ['$[1; 13]$', '$[1; +\infty)$', '$(-\infty; 13]$', '$[0; 13]$'],
                [
                    '$\sqrt{x} \geq 0$, deci $E(f) = [1; +\infty)$.',
                    'Parabola $g$ are ramurile în jos; maximul e în vârf, $x = 2$.',
                    '$g(2) = 13$, deci $E(g) = (-\infty; 13]$.',
                ],
                '$E(f) = [1; +\infty)$, $E(g) = (-\infty; 13]$, deci $E(f) \cap E(g) = [1; 13]$.'),

            self::num($d(12), 'progresii', 8,
                'Fie progresia aritmetică $(a_n)_{n \geq 1}$, în care $a_1 = 102$, $r = -3$. Determinați suma termenilor pozitivi ai progresiei.',
                1785,
                [
                    'Termenul general: $a_n = 102 - 3(n - 1)$. Când e pozitiv?',
                    '$102 - 3(n - 1) > 0$ dă $n < 35$: sunt 34 de termeni pozitivi ($a_{35} = 0$).',
                    '$S_{34} = \frac{a_1 + a_{34}}{2} \cdot 34$, cu $a_{34} = 3$.',
                ],
                'Termenii pozitivi sunt $a_1, \ldots, a_{34}$ (cu $a_{34} = 3$), iar suma lor este $\frac{102 + 3}{2} \cdot 34 = 1785$.'),

            self::num($d(13), 'probabilitati', 8,
                'Într-un pachet sunt 6 drajeuri de culoare roșie, 8 de culoare galbenă și 1 de culoare verde. Petru a luat la întâmplare 4 drajeuri din pachet. Determinați probabilitatea că Petru a luat drajeuri de toate culorile.',
                96 / 455,
                [
                    'Cazurile posibile: $n = C_{15}^4$.',
                    'Drajeul verde e unul singur și trebuie luat; celelalte 3 sunt roșii și galbene, cel puțin unul din fiecare.',
                    '$m = C_6^2 \cdot C_8^1 + C_6^1 \cdot C_8^2$.',
                ],
                '$n = C_{15}^4 = 1365$, $m = 15 \cdot 8 + 6 \cdot 28 = 288$, deci $p = \frac{288}{1365} = \frac{96}{455}$.'),

            self::num($d(14), 'probabilitati', 8,
                'Într-o competiție 10 sportivi au acumulat următoarele punctaje: 100, 80, 50, 60, 80, 90, 50, 70, 50, 110. Sportivii, ale căror rezultate sunt mai mari decât media aritmetică și mai mari decât mediana seriei statistice corespunzătoare, sunt promovați în etapa următoare. Determinați câți sportivi au fost promovați în etapa următoare.',
                5,
                [
                    'Media aritmetică: suma punctajelor împărțită la 10.',
                    'Media este 74. Ordonează seria pentru mediană.',
                    'Seria ordonată: 50, 50, 50, 60, 70, 80, 80, 90, 100, 110; mediana e media termenilor 5 și 6.',
                ],
                'Media este 74, mediana $\frac{70 + 80}{2} = 75$. Peste ambele sunt punctajele 80, 80, 90, 100, 110 — deci 5 sportivi.'),
        ];
    }

    /** Profil umanist, sesiunea suplimentară. */
    private static function umanistSuplimentara(): array
    {
        $d = fn (int|string $n) => self::doc('u_ss24', $n);

        return [
            self::num($d(1), 'numere-reale', 5,
                'Calculați: $0{,}25 \cdot 64^{\frac{2}{3}}$.',
                4,
                [
                    '$64 = 4^3$.',
                    '$64^{\frac{2}{3}} = 4^2 = 16$.',
                    '$0{,}25 = \frac{1}{4}$.',
                ],
                '$64^{\frac{2}{3}} = 16$, deci $0{,}25 \cdot 16 = 4$.'),

            self::num($d(2), 'logaritmi', 8,
                'Calculați valoarea expresiei $25^{\log_5 2} + \log_3 2 - \log_3 18$.',
                2,
                [
                    '$25^{\log_5 2} = 5^{2\log_5 2} = 5^{\log_5 4}$.',
                    '$5^{\log_5 4} = 4$.',
                    '$\log_3 2 - \log_3 18 = \log_3 \frac{1}{9} = -2$.',
                ],
                '$25^{\log_5 2} = 4$ și $\log_3 \frac{2}{18} = -2$, deci valoarea este $2$.'),

            self::num($d(3), 'numere-complexe', 8,
                'Rezolvați în $\mathbb{C}$ ecuația $z^2 + 6z + 25 = 0$ și determinați modulul soluțiilor.',
                5,
                [
                    '$\Delta = 36 - 100 = -64 = 64i^2$.',
                    '$z = \frac{-6 \pm 8i}{2} = -3 \pm 4i$.',
                    '$|z| = \sqrt{9 + 16}$.',
                ],
                'Soluțiile sunt $-3 \pm 4i$, ambele cu modulul $\sqrt{9 + 16} = 5$.'),

            self::num($d(4), 'ecuatii-inecuatii', 8,
                'Ion vrea să comande un buchet din 3 lalele, 2 narciși și 4 iriși. Pe pagina web a magazinului, un buchet din 5 lalele, 2 narciși și 2 iriși costă 260 lei, un buchet din 5 lalele și 2 narciși costă 190 lei, iar un buchet din 3 narciși și 2 iriși costă 130 de lei. Determinați prețul buchetului pe care vrea să-l comande Ion.',
                270,
                [
                    'Notează $x$, $y$, $z$ prețurile unei lalele, unui narcis și unui iris.',
                    '$5x + 2y + 2z = 260$ și $5x + 2y = 190$ dau imediat $z = 35$.',
                    'Apoi $3y + 70 = 130$, deci $y = 20$, și $5x + 40 = 190$.',
                ],
                '$z = 35$, $y = 20$, $x = 30$ lei, deci buchetul lui Ion costă $3 \cdot 30 + 2 \cdot 20 + 4 \cdot 35 = 270$ de lei.',
                'lei'),

            self::num($d(5), 'ecuatii-inecuatii', 8,
                'Determinați valoarea minimă a sumei pătratelor soluțiilor reale ale ecuației $x^2 + 3ax + a^2 - 2 = 0$, $a \in \mathbb{R}$.',
                4,
                [
                    '$\Delta = 5a^2 + 8 > 0$ pentru orice $a$: soluțiile sunt mereu reale.',
                    'Viète: $x_1 + x_2 = -3a$, $x_1x_2 = a^2 - 2$.',
                    '$x_1^2 + x_2^2 = (x_1 + x_2)^2 - 2x_1x_2 = 7a^2 + 4$.',
                ],
                '$x_1^2 + x_2^2 = 9a^2 - 2(a^2 - 2) = 7a^2 + 4$, cu minimul 4, atins pentru $a = 0$.'),

            self::num($d(6), 'cerc', 5,
                'Punctele $A$, $B$ și $C$ aparțin unui cerc, astfel încât $AB$ este diametru și $m(\angle BAC) = 20^\circ$. Determinați măsura în grade a arcului mic $AC$.',
                140,
                [
                    'Unghiul înscris în semicerc e drept: $m(\angle ACB) = 90^\circ$.',
                    'Atunci $m(\angle ABC) = 70^\circ$.',
                    'Unghiul înscris $ABC$ subîntinde arcul mic $AC$, de două ori mai mare.',
                ],
                '$m(\angle ABC) = 180^\circ - 90^\circ - 20^\circ = 70^\circ$, deci arcul mic $AC$ măsoară $140^\circ$.',
                '°'),

            self::num($d(7), 'corpuri', 5,
                'Volumul unei prisme patrulatere regulate este egal cu 36 cm³. Determinați lungimea diagonalei feței laterale a prismei, dacă se cunoaște că aria bazei este egală cu 9 cm².',
                5,
                [
                    'Înălțimea: $H = \frac{\mathcal{V}}{\mathcal{A}_b} = 4$ cm.',
                    'Baza e un pătrat cu aria 9 cm², deci latura are 3 cm.',
                    'Fața laterală e un dreptunghi $3 \times 4$.',
                ],
                '$H = 4$ cm, latura bazei 3 cm, deci diagonala feței laterale este $\sqrt{9 + 16} = 5$ cm.',
                'cm'),

            self::num($d(8), 'triunghiuri', 8,
                'În triunghiul isoscel $ABC$ înălțimea $AK$ împarte latura $BC$ în segmentele $BK = 12$ cm și $KC = 8$ cm. Determinați lungimea înălțimii corespunzătoare bazei $AC$.',
                8 * sqrt(5),
                [
                    'Baza e $AC$, deci $AB = BC = 20$ cm.',
                    '$AK = \sqrt{20^2 - 12^2} = 16$ cm și $AC = \sqrt{16^2 + 8^2} = 8\sqrt{5}$ cm.',
                    'Înălțimea din $B$ cade în mijlocul lui $AC$: jumătatea bazei este $4\sqrt{5}$ cm.',
                ],
                '$AB = 20$ cm, $AK = 16$ cm, $AC = 8\sqrt{5}$ cm, iar înălțimea din $B$ este $\sqrt{400 - 80} = 8\sqrt{5}$ cm.',
                'cm'),

            self::num($d(9), 'corpuri', 8,
                'Într-un trunchi de con circular drept, razele bazelor sunt de $\sqrt{3}$ cm și $3\sqrt{3}$ cm, iar unghiul format de generatoarea trunchiului de con cu baza mare este de $30^\circ$. Determinați aria laterală a trunchiului de con.',
                16 * M_PI * sqrt(3),
                [
                    'Proiecția generatoarei pe baza mare: $3\sqrt{3} - \sqrt{3} = 2\sqrt{3}$ cm.',
                    'Generatoarea: $G = \frac{2\sqrt{3}}{\cos 30^\circ} = 4$ cm.',
                    '$\mathcal{A}_{lat} = \pi G(R + r)$.',
                ],
                '$G = 4$ cm, deci $\mathcal{A}_{lat} = \pi \cdot 4 \cdot 4\sqrt{3} = 16\pi\sqrt{3}$ cm².',
                'cm²'),

            self::num($d(10), 'functii', 5,
                'Fie funcția $f:\mathbb{R}\setminus\{\sqrt{3}\}\to\mathbb{R}$, $f(x) = \frac{3}{\sqrt{3} - x}$. Determinați abscisa punctului de pe graficul funcției $f$, ordonata căruia este egală cu $\sqrt{3}$.',
                0,
                [
                    'Rezolvă $f(x) = \sqrt{3}$.',
                    '$\frac{3}{\sqrt{3} - x} = \sqrt{3}$, deci $\sqrt{3} - x = \sqrt{3}$.',
                    'Rezultă $x$ imediat.',
                ],
                '$\frac{3}{\sqrt{3} - x} = \sqrt{3}$ dă $\sqrt{3} - x = \frac{3}{\sqrt{3}} = \sqrt{3}$, deci $x = 0$.'),

            self::num($d(11), 'functii', 8,
                'Fie funcția $f:\mathbb{R}\to\mathbb{R}$, $f(x) = -2x^2 + 5x + 12$. Arătați că suma valorilor întregi ale lui $x$, pentru care valorile respective ale funcției $f$ sunt nenegative, este un pătrat perfect. Ce valoare are suma?',
                9,
                [
                    'Inecuația: $-2x^2 + 5x + 12 \geq 0$. Rădăcinile ecuației asociate sunt $-\frac{3}{2}$ și 4.',
                    'Trinomul (cu coeficient dominant negativ) e nenegativ între rădăcini: $x \in \left[-\frac{3}{2}; 4\right]$.',
                    'Valorile întregi: $-1, 0, 1, 2, 3, 4$.',
                ],
                'Soluțiile întregi ale inecuației $-2x^2 + 5x + 12 \geq 0$ sunt $-1, 0, 1, 2, 3, 4$, cu suma $9 = 3^2$.'),

            self::num($d(12), 'progresii', 8,
                'Determinați primul termen al progresiei geometrice cu termeni pozitivi, dacă se cunoaște că produsul dintre primul termen și al treilea termen este egal cu 4, iar al patrulea termen al progresiei este egal cu 50.',
                0.4,
                [
                    'Sistemul: $b_1 \cdot b_1q^2 = 4$ și $b_1q^3 = 50$.',
                    'Termenii sunt pozitivi: din $b_1^2q^2 = 4$ rezultă $b_1q = 2$.',
                    'Împarte: $q^2 = \frac{50}{2} = 25$, deci $q = 5$.',
                ],
                '$b_1q = 2$ și $b_1q^3 = 50$ dau $q = 5$, deci $b_1 = \frac{2}{5}$.'),

            self::num($d(13), 'probabilitati', 8,
                'Un zar este aruncat de 4 ori. Determinați probabilitatea că produsul punctelor apărute este egal cu 250.',
                1 / 324,
                [
                    'Cazurile posibile: $n = 6^4$.',
                    'Descompune: $250 = 2 \cdot 5 \cdot 5 \cdot 5$ — singurul mod cu fețe de zar.',
                    'Fața 2 poate apărea la oricare din cele 4 aruncări: $m = 4$.',
                ],
                '$n = 1296$, $m = 4$, deci $p = \frac{4}{1296} = \frac{1}{324}$.'),

            self::num($d(14), 'probabilitati', 8,
                'După o scumpire cu 20%, apoi o ieftinire cu 15%, un calculator costă 9180 de lei. Determinați prețul inițial al calculatorului.',
                9000,
                [
                    'După scumpire, prețul e $1{,}2x$.',
                    'După ieftinire, $0{,}85 \cdot 1{,}2x = 1{,}02x$.',
                    '$1{,}02x = 9180$.',
                ],
                '$0{,}85 \cdot 1{,}2x = 1{,}02x = 9180$, deci prețul inițial a fost $9000$ de lei.',
                'lei'),
        ];
    }
}
