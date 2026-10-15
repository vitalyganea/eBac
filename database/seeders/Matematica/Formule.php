<?php

namespace Database\Seeders\Matematica;

/**
 * Formulele de matematică pentru secțiunea „Formule".
 *
 * Două surse oficiale (§11b):
 *  - ANEXELE testelor ANCE 2021–2026 (23 de teste, real și umanist). Fiecare
 *    test își alege formulele după itemii lui, deci anexa diferă de la un an
 *    la altul; `annex` ține exact testele în care a apărut fiecare formulă,
 *    citite de pe ultima pagină a fiecărui PDF (la 2024 real, prima pagină).
 *  - PROGRAMA de examen, pentru formulele care n-au apărut în nicio anexă și
 *    trebuie știute pe de rost — cu codul unității de conținut.
 *
 * Coduri: `r_sb26` = profil real, sesiunea de bază 2026; `u_ss24` = umanist,
 * sesiunea suplimentară 2024. Testul de bază din 2022 de la umanist nu e
 * publicat de ANCE.
 *
 * Forma unui rând: [slug, competență, nume, latex, explicație, variabile,
 * greșeala tipică, teste cu anexa, unitatea din programă].
 */
final class Formule
{
    /** @return list<array{0: string, 1: string, 2: string, 3: string, 4: string, 5: ?array, 6: ?string, 7: list<string>, 8: string}> */
    public static function all(): array
    {
        $realAll22 = ['r_sb22', 'r_ss22', 'r_sb23', 'r_ss23', 'r_sb24', 'r_ss24', 'r_sb25', 'r_ss25', 'r_sb26', 'r_ss26'];

        return [
            // ==================== Numere reale ====================
            ['puteri', 'numere-reale', 'Proprietățile puterilor',
                'a^m \cdot a^n = a^{m+n},\quad \frac{a^m}{a^n} = a^{m-n},\quad (a^m)^n = a^{mn},\quad a^{-n} = \frac{1}{a^n},\quad a^{\frac{m}{n}} = \sqrt[n]{a^m}',
                'Regulile cu care se simplifică orice expresie cu puteri și radicali. Un radical e o putere cu exponent fracționar: $\sqrt{a} = a^{\frac{1}{2}}$, $\sqrt[3]{a} = a^{\frac{1}{3}}$.',
                [['a > 0', 'baza'], ['m, n', 'exponenții']],
                'La produs exponenții se ADUNĂ, nu se înmulțesc: $a^2 \cdot a^3 = a^5$, nu $a^6$.',
                [], 'X.2'],

            ['calcul-prescurtat', 'numere-reale', 'Formulele de calcul prescurtat',
                '(a \pm b)^2 = a^2 \pm 2ab + b^2,\qquad a^2 - b^2 = (a-b)(a+b)',
                'Pătratul sumei sau al diferenței și diferența de pătrate. Merg în ambele sensuri: la desfacerea parantezelor și la descompunerea în factori.',
                null,
                '$(a+b)^2 \neq a^2 + b^2$ — termenul din mijloc, $2ab$, se uită cel mai des.',
                [], 'X.3'],

            // ==================== Puteri, radicali, logaritmi ====================
            ['log-definitie', 'logaritmi', 'Definiția logaritmului',
                '\log_a b = c \iff a^c = b,\qquad a > 0,\ a \neq 1,\ b > 0',
                'Logaritmul răspunde la întrebarea „la ce putere trebuie ridicat $a$ ca să obțin $b$?". Din definiție: $\log_a 1 = 0$ și $\log_a a = 1$.',
                [['a', 'baza: pozitivă și diferită de 1'], ['b', 'argumentul: strict pozitiv']],
                'Condițiile de existență ($b > 0$, $a > 0$, $a \neq 1$) se scriu ÎNAINTE de a rezolva o ecuație logaritmică — altfel păstrezi soluții străine.',
                [], 'X.2'],

            ['log-identitate', 'logaritmi', 'Identitatea logaritmică fundamentală',
                'a^{\log_a b} = b,\qquad a \in \mathbb{R}_+^* \setminus \{1\},\ b \in \mathbb{R}_+^*',
                'Puterea și logaritmul în aceeași bază se anulează reciproc. Se folosește la calcule de tipul $7^{\log_{49} 3}$: întâi aduci baza logaritmului la 7.',
                null, null,
                ['r_sb21', 'u_ss23', 'u_ss24', 'u_ss26'], 'X.2'],

            ['log-produs', 'logaritmi', 'Logaritmul unui produs',
                '\log_a b + \log_a c = \log_a (bc),\qquad a \in \mathbb{R}_+^* \setminus \{1\},\ b, c \in \mathbb{R}_+^*',
                'Suma a doi logaritmi în ACEEAȘI bază e logaritmul produsului.',
                null,
                'Nu există formulă pentru $\log_a(b + c)$ — logaritmul unei sume nu se desface.',
                ['r_ss21', 'r_sb23', 'r_sb24', 'r_sb26', 'u_sb21', 'u_ss21', 'u_ss23', 'u_sb26'], 'X.2'],

            ['log-cat', 'logaritmi', 'Logaritmul unui cât',
                '\log_a b - \log_a c = \log_a \frac{b}{c},\qquad a \in \mathbb{R}_+^* \setminus \{1\},\ b, c \in \mathbb{R}_+^*',
                'Diferența a doi logaritmi în aceeași bază e logaritmul câtului.',
                null, null,
                ['r_ss21', 'r_sb23', 'r_ss23', 'r_sb24', 'u_sb21', 'u_ss21', 'u_ss23', 'u_sb24', 'u_ss24', 'u_sb25', 'u_sb26', 'u_ss26'], 'X.2'],

            ['log-putere', 'logaritmi', 'Logaritmul unei puteri',
                '\log_a b^c = c \log_a b,\qquad a \in \mathbb{R}_+^* \setminus \{1\},\ b \in \mathbb{R}_+^*,\ c \in \mathbb{R}',
                'Exponentul argumentului iese în fața logaritmului. Cea mai frecventă formulă din anexe.',
                null,
                'Exponentul trebuie să fie al ARGUMENTULUI: $(\log_a b)^2 \neq 2\log_a b$.',
                ['r_sb21', 'r_ss21', 'r_sb22', 'r_ss22', 'r_sb23', 'r_ss23', 'r_sb25', 'r_sb26', 'u_sb21', 'u_ss21', 'u_ss22', 'u_sb23', 'u_ss23', 'u_sb24', 'u_ss24', 'u_sb26', 'u_ss26'], 'X.2'],

            ['log-baza-putere', 'logaritmi', 'Logaritmul cu baza o putere',
                '\log_{a^c} b = \frac{1}{c} \log_a b,\qquad a \in \mathbb{R}_+^* \setminus \{1\},\ b \in \mathbb{R}_+^*,\ c \neq 0',
                'Exponentul bazei iese în față ca INVERS. Exemplu: $\log_{\sqrt{3}} 6 = \log_{3^{1/2}} 6 = 2\log_3 6$.',
                null,
                'Se confundă cu logaritmul unei puteri: exponentul bazei iese ca $\frac{1}{c}$, nu ca $c$.',
                ['r_sb21', 'r_ss21', 'r_sb22', 'r_ss22', 'r_sb25', 'u_sb21', 'u_ss21', 'u_ss22', 'u_sb23', 'u_ss23', 'u_sb24', 'u_sb25', 'u_ss25', 'u_sb26', 'u_ss26'], 'X.2'],

            ['log-schimbare-baza', 'logaritmi', 'Schimbarea bazei',
                '\log_a b = \frac{\log_c b}{\log_c a},\qquad \frac{1}{\log_a b} = \log_b a',
                'Aduce logaritmi cu baze diferite la aceeași bază, ca să poată fi adunați sau comparați.',
                null, null,
                ['u_ss23'], 'X.2'],

            // ==================== Ecuații ====================
            ['ecuatia-de-gradul-2', 'ecuatii-inecuatii', 'Rădăcinile ecuației de gradul al doilea',
                'ax^2 + bx + c = 0,\quad \Delta = b^2 - 4ac,\quad x_{1,2} = \frac{-b \pm \sqrt{\Delta}}{2a}',
                'Dacă $\Delta > 0$ — două rădăcini reale distincte; $\Delta = 0$ — una (dublă); $\Delta < 0$ — niciuna reală (două complexe conjugate).',
                [['a \neq 0', 'coeficientul lui $x^2$'], ['\Delta', 'discriminantul']],
                'Semnul lui $b$: la $x^2 - 5x + 6$, $b = -5$, deci $-b = 5$.',
                [], 'X.5'],

            ['viete', 'ecuatii-inecuatii', 'Relațiile lui Viète',
                'x_1 + x_2 = -\frac{b}{a},\qquad x_1 x_2 = \frac{c}{a}',
                'Suma și produsul rădăcinilor, fără a le calcula. Utile la expresii simetrice: $x_1^2 + x_2^2 = (x_1 + x_2)^2 - 2x_1x_2$, $\frac{1}{x_1} + \frac{1}{x_2} = \frac{x_1 + x_2}{x_1 x_2}$.',
                null,
                'Suma are semnul MINUS: $x_1 + x_2 = -\frac{b}{a}$.',
                [], 'X.5'],

            // ==================== Numere complexe ====================
            ['numere-complexe', 'numere-complexe', 'Forma algebrică, conjugatul și modulul',
                'i^2 = -1,\quad z = a + bi,\quad \bar{z} = a - bi,\quad |z| = \sqrt{a^2 + b^2}',
                'Partea reală $a$, partea imaginară $b$. Un cât se calculează amplificând cu conjugatul numitorului: $z \cdot \bar{z} = a^2 + b^2$ e real. Puterile lui $i$ se repetă din 4 în 4: $i^3 = -i$, $i^4 = 1$.',
                [['a = \operatorname{Re} z', 'partea reală'], ['b = \operatorname{Im} z', 'partea imaginară (fără $i$)']],
                'Partea imaginară a lui $3 - 4i$ e $-4$, nu $-4i$.',
                [], 'XI.4'],

            // ==================== Matrici ====================
            ['determinant', 'matrici', 'Determinantul de ordinul 2 și 3',
                '\begin{vmatrix} a & b \\\\ c & d \end{vmatrix} = ad - bc,\qquad \begin{vmatrix} a_1 & b_1 & c_1 \\\\ a_2 & b_2 & c_2 \\\\ a_3 & b_3 & c_3 \end{vmatrix} =,\quad a_1b_2c_3 + b_1c_2a_3 + c_1a_2b_3,\quad {} - c_1b_2a_3 - a_1c_2b_3 - b_1a_2c_3',
                'Ordinul 3 se calculează cu regula lui Sarrus sau dezvoltând după o linie. O matrice pătratică e inversabilă dacă și numai dacă determinantul ei e nenul.',
                null,
                'La Sarrus, cei trei produse de pe diagonalele secundare se SCAD.',
                [], 'XI.5'],

            // ==================== Progresii ====================
            ['progresie-aritmetica', 'progresii', 'Termenul general al progresiei aritmetice',
                'a_n = a_1 + (n - 1)r',
                'Fiecare termen se obține adunând rația $r$ la precedentul. Proprietatea caracteristică: $a_n = \frac{a_{n-1} + a_{n+1}}{2}$.',
                [['a_1', 'primul termen'], ['r', 'rația'], ['n', 'rangul termenului']],
                'Rația se înmulțește cu $n - 1$, nu cu $n$: de la $a_1$ la $a_n$ sunt $n - 1$ pași.',
                ['r_ss26', 'u_sb23', 'u_sb24', 'u_ss25', 'u_sb26', 'u_ss26'], 'XI.1'],

            ['suma-aritmetica', 'progresii', 'Suma primilor n termeni ai progresiei aritmetice',
                'S_n = \frac{a_1 + a_n}{2} \cdot n = \frac{2a_1 + (n-1)r}{2} \cdot n',
                'Media primului și ultimului termen, înmulțită cu numărul de termeni. A doua formă nu cere $a_n$.',
                null, null,
                ['u_sb23', 'u_sb24', 'u_sb26', 'u_ss26'], 'XI.1'],

            ['progresie-geometrica', 'progresii', 'Termenul general al progresiei geometrice',
                'b_n = b_1 q^{n-1}',
                'Fiecare termen se obține înmulțind precedentul cu rația $q$. Proprietatea caracteristică: $b_n^2 = b_{n-1} b_{n+1}$.',
                [['b_1', 'primul termen'], ['q', 'rația']],
                null,
                ['r_ss25', 'u_ss22', 'u_ss23', 'u_ss24', 'u_sb25'], 'XI.1'],

            ['suma-geometrica', 'progresii', 'Suma primilor n termeni ai progresiei geometrice',
                'S_n = \frac{b_1(1 - q^n)}{1 - q},\qquad q \neq 1',
                'Pentru $q = 1$ toți termenii sunt egali și $S_n = n b_1$.',
                null, null,
                ['u_sb25'], 'XI.1'],

            // ==================== Probabilități, statistică, calcul financiar ====================
            ['probabilitate', 'probabilitati', 'Probabilitatea clasică',
                'P(A) = \frac{m}{n}',
                'Raportul dintre numărul cazurilor favorabile și numărul cazurilor posibile, când toate sunt egal probabile. Rezultatul e între 0 și 1.',
                [['m', 'cazurile favorabile'], ['n', 'cazurile posibile']],
                'Ordinea contează sau nu? La „se extrag 4 bile" se numără cu combinări; la „se formează un număr" — cu aranjamente.',
                [], 'XII.4'],

            ['media-mediana', 'probabilitati', 'Media aritmetică și mediana',
                '\bar{x} = \frac{x_1 + x_2 + \dots + x_n}{n}',
                'Mediana e valoarea din mijloc după ce datele se ORDONEAZĂ crescător; la un număr par de valori, media celor două din mijloc.',
                null,
                'Mediana se ia din seria ordonată, nu din ordinea în care apar datele în enunț.',
                [], 'XII.4'],

            ['procente', 'probabilitati', 'Creșteri și scăderi procentuale',
                'x \cdot \left(1 + \frac{p}{100}\right),\qquad x \cdot \left(1 - \frac{p}{100}\right)',
                'O creștere cu $p\%$ înmulțește cu $1 + \frac{p}{100}$; o scădere — cu $1 - \frac{p}{100}$. Modificările succesive se înmulțesc: $+20\%$ apoi $-15\%$ înseamnă $\cdot 1{,}2 \cdot 0{,}85$.',
                [['x', 'valoarea inițială'], ['p', 'procentul']],
                'Procentele succesive nu se adună: $+20\%$ apoi $-20\%$ dă $0{,}96x$, nu $x$.',
                [], 'XII.4'],

            // ==================== Trigonometrie ====================
            ['valori-uzuale', 'valori-trigonometrice', 'Valorile funcțiilor trigonometrice uzuale',
                '\begin{array}{c|ccc} & 30^\circ & 45^\circ & 60^\circ \\\\ \hline \sin & \frac{1}{2} & \frac{\sqrt{2}}{2} & \frac{\sqrt{3}}{2} \\\\ \cos & \frac{\sqrt{3}}{2} & \frac{\sqrt{2}}{2} & \frac{1}{2} \\\\ \operatorname{tg} & \frac{\sqrt{3}}{3} & 1 & \sqrt{3} \end{array}',
                'Sinusul crește de la $30^\circ$ la $60^\circ$, cosinusul scade cu aceleași valori în ordine inversă. $\sin 0^\circ = 0$, $\sin 90^\circ = 1$, $\cos 0^\circ = 1$, $\cos 90^\circ = 0$.',
                null, null,
                [], 'X.6'],

            ['trig-fundamentala', 'identitati-trigonometrice', 'Identitatea fundamentală',
                '\sin^2 \alpha + \cos^2 \alpha = 1,\qquad \operatorname{tg} \alpha = \frac{\sin \alpha}{\cos \alpha}',
                'Din una se află cealaltă: dacă știi $\sin \alpha$ și cadranul, afli $\cos \alpha$. Semnul îl dă cadranul.',
                null,
                'După $\cos \alpha = \pm\sqrt{1 - \sin^2 \alpha}$ trebuie ales semnul după cadran.',
                [], 'X.6'],

            ['formule-reducere', 'valori-trigonometrice', 'Formulele de reducere',
                '\sin(\pi - \alpha) = \sin \alpha,\quad \cos(\pi - \alpha) = -\cos \alpha,\quad \sin\left(\frac{\pi}{2} - \alpha\right) = \cos \alpha,\quad \sin(-\alpha) = -\sin \alpha,\quad \cos(-\alpha) = \cos \alpha',
                'Aduc orice unghi la un unghi ascuțit. Cosinusul e par, sinusul și tangenta sunt impare.',
                null, null,
                [], 'X.6'],

            ['sin-suma', 'identitati-trigonometrice', 'Sinusul și cosinusul sumei',
                '\sin(\alpha \pm \beta) = \sin \alpha \cos \beta \pm \cos \alpha \sin \beta,\qquad \cos(\alpha \pm \beta) = \cos \alpha \cos \beta \mp \sin \alpha \sin \beta',
                'La sinus semnul se păstrează, la cosinus se schimbă.',
                null,
                'La cosinusul sumei apare MINUS: $\cos(\alpha + \beta) = \cos\alpha\cos\beta - \sin\alpha\sin\beta$.',
                ['r_sb23'], 'X.6'],

            ['sin-dublu', 'identitati-trigonometrice', 'Sinusul unghiului dublu',
                '\sin(2\alpha) = 2 \sin \alpha \cos \alpha',
                'Transformă un produs $\sin \alpha \cos \alpha$ într-un singur sinus și invers.',
                null, null,
                ['r_sb21', 'r_ss24'], 'X.6'],

            ['cos-dublu', 'identitati-trigonometrice', 'Cosinusul unghiului dublu',
                '\cos(2\alpha) = \cos^2 \alpha - \sin^2 \alpha = 2\cos^2 \alpha - 1 = 1 - 2\sin^2 \alpha',
                'Trei forme, după ce funcție vrei să păstrezi. Din ultimele două: $\sin^2 \alpha = \frac{1 - \cos 2\alpha}{2}$, $\cos^2 \alpha = \frac{1 + \cos 2\alpha}{2}$.',
                null, null,
                ['r_sb22', 'r_ss22', 'r_ss25', 'r_sb26'], 'X.6'],

            ['sin-patrat', 'identitati-trigonometrice', 'Formula de micșorare a gradului',
                '\sin^2 \alpha = \frac{1 - \cos(2\alpha)}{2}',
                'Coboară pătratul în schimbul unghiului dublu — util la integrale de tipul $\int \sin^2 x\,dx$.',
                null, null,
                ['r_sb21'], 'X.6'],

            // ==================== Triunghiuri ====================
            ['pitagora', 'triunghiuri', 'Teorema lui Pitagora',
                'c^2 = a^2 + b^2',
                'În triunghiul dreptunghic, pătratul ipotenuzei e suma pătratelor catetelor. Reciproca: dacă relația are loc, triunghiul e dreptunghic.',
                [['c', 'ipotenuza (opusă unghiului drept)'], ['a, b', 'catetele']],
                null,
                [], 'X.7'],

            ['rapoarte-triunghi-dreptunghic', 'triunghiuri', 'Rapoartele în triunghiul dreptunghic',
                '\sin \alpha = \frac{\text{cateta opusă}}{\text{ipotenuza}},\quad \cos \alpha = \frac{\text{cateta alăturată}}{\text{ipotenuza}},\quad \operatorname{tg} \alpha = \frac{\text{cateta opusă}}{\text{cateta alăturată}}',
                'Leagă unghiurile ascuțite de laturi. Cateta opusă unghiului de $30^\circ$ e jumătate din ipotenuză.',
                null, null,
                [], 'X.7'],

            ['teorema-cosinusului', 'triunghiuri', 'Teorema cosinusului',
                'c^2 = a^2 + b^2 - 2ab\cos\gamma',
                'Generalizează teorema lui Pitagora la orice triunghi. Se folosește când cunoști două laturi și unghiul dintre ele, sau toate trei laturile.',
                [['\gamma', 'unghiul opus laturii $c$, cuprins între $a$ și $b$']],
                'Unghiul e cel DINTRE laturile $a$ și $b$, opus lui $c$.',
                ['r_ss21', 'r_ss22', 'r_sb23', 'r_ss24', 'r_ss25', 'r_ss26'], 'X.7'],

            ['teorema-sinusurilor', 'triunghiuri', 'Teorema sinusurilor',
                '\frac{a}{\sin \alpha} = \frac{b}{\sin \beta} = \frac{c}{\sin \gamma} = 2R',
                'Fiecare latură, raportată la sinusul unghiului opus, dă același număr: diametrul cercului circumscris.',
                [['R', 'raza cercului circumscris']],
                null,
                ['r_ss22', 'r_ss23', 'r_sb24', 'r_ss25'], 'X.7'],

            ['aria-triunghi', 'triunghiuri', 'Aria triunghiului: baza și înălțimea',
                '\mathcal{A}_\Delta = \frac{1}{2} a h_a',
                'Jumătate din produsul unei laturi cu înălțimea dusă pe ea.',
                [['h_a', 'înălțimea pe latura $a$']],
                null,
                ['r_sb21', 'r_sb22', 'r_ss22', 'r_ss24', 'r_sb25', 'r_ss25', 'r_ss26', 'u_sb21', 'u_sb25', 'u_ss25', 'u_ss26'], 'X.7'],

            ['aria-triunghi-sinus', 'triunghiuri', 'Aria triunghiului: două laturi și unghiul dintre ele',
                '\mathcal{A}_\Delta = \frac{1}{2} ab \sin\gamma',
                'Când nu ai înălțimea, dar ai două laturi și unghiul cuprins între ele.',
                null, null,
                [], 'X.7'],

            ['heron', 'triunghiuri', 'Formula lui Heron',
                '\mathcal{A}_\Delta = \sqrt{p(p-a)(p-b)(p-c)},\qquad p = \frac{a + b + c}{2}',
                'Aria din cele trei laturi, fără unghiuri și fără înălțimi.',
                [['p', 'semiperimetrul']],
                'Se folosește SEMIperimetrul, nu perimetrul.',
                [], 'X.7'],

            ['aria-raza-inscrisa', 'triunghiuri', 'Aria triunghiului și raza cercului înscris',
                '\mathcal{A}_\Delta = p r,\qquad p = \frac{a + b + c}{2}',
                'Aria e semiperimetrul înmulțit cu raza cercului înscris.',
                [['r', 'raza cercului înscris']],
                null,
                ['u_sb25'], 'X.7'],

            // ==================== Patrulatere ====================
            ['aria-paralelogram', 'patrulatere', 'Aria paralelogramului',
                '\mathcal{A} = a h_a',
                'O latură înmulțită cu înălțimea dusă pe ea. Echivalent: $\mathcal{A} = ab\sin\alpha$.',
                null, null,
                ['r_sb21', 'r_ss24', 'u_sb24'], 'X.7'],

            ['aria-diagonale', 'patrulatere', 'Aria patrulaterului prin diagonale',
                '\mathcal{A} = \frac{1}{2} d_1 d_2 \sin\varphi',
                'Pentru orice patrulater convex, din diagonale și unghiul dintre ele.',
                [['\varphi', 'unghiul dintre diagonale']],
                null,
                ['r_ss24'], 'X.7'],

            ['aria-romb', 'patrulatere', 'Aria rombului',
                '\mathcal{A} = a h = \frac{1}{2} d_1 d_2',
                'Diagonalele rombului sunt perpendiculare, deci $\sin\varphi = 1$. Ele se înjumătățesc, iar jumătățile și latura formează un triunghi dreptunghic.',
                null, null,
                ['u_sb21', 'u_sb24'], 'X.7'],

            ['aria-trapez', 'patrulatere', 'Aria trapezului',
                '\mathcal{A} = \frac{a + b}{2} \cdot h',
                'Media bazelor (adică linia mijlocie), înmulțită cu înălțimea.',
                [['a, b', 'bazele'], ['h', 'înălțimea']],
                null,
                ['r_sb25', 'u_sb24', 'u_sb26'], 'X.7'],

            // ==================== Cercul ====================
            ['lungimea-cercului', 'cerc', 'Lungimea cercului',
                'L = 2\pi R',
                'Perimetrul cercului de rază $R$.',
                null, null,
                ['u_ss23'], 'X.7'],

            ['aria-disc', 'cerc', 'Aria discului',
                '\mathcal{A}_{\text{disc}} = \pi R^2',
                'Suprafața mărginită de cerc.',
                null,
                'Aria are $R^2$, lungimea cercului doar $R$.',
                ['r_sb23', 'r_sb25', 'u_sb23', 'u_sb25', 'u_ss26'], 'X.7'],

            // ==================== Corpuri ====================
            ['volum-prisma', 'corpuri', 'Volumul prismei',
                '\mathcal{V} = \mathcal{A}_b \cdot H',
                'Aria bazei înmulțită cu înălțimea. Paralelipipedul și cubul sunt cazuri particulare.',
                null, null,
                ['r_sb21', 'r_ss26', 'u_ss21', 'u_ss23', 'u_ss24', 'u_ss25'], 'XII.5'],

            ['volum-piramida', 'corpuri', 'Volumul piramidei',
                '\mathcal{V} = \frac{1}{3} \mathcal{A}_b \cdot H',
                'O treime din volumul prismei cu aceeași bază și aceeași înălțime.',
                null,
                'Factorul $\frac{1}{3}$ se uită ușor — fără el ai volumul prismei.',
                ['r_ss21', 'r_ss22', 'r_ss23', 'r_sb26', 'u_ss22'], 'XII.5'],

            ['cilindru-arie', 'corpuri', 'Aria laterală a cilindrului',
                '\mathcal{A}_{\text{lat}} = 2\pi R H',
                'Desfășurată, suprafața laterală e un dreptunghi: lungimea cercului bazei pe înălțime. Aria totală adaugă cele două baze: $2\pi R^2$.',
                null, null,
                ['u_sb25', 'u_ss26'], 'XII.6'],

            ['cilindru-volum', 'corpuri', 'Volumul cilindrului',
                '\mathcal{V} = \pi R^2 H',
                'Aria bazei înmulțită cu înălțimea, ca la prismă.',
                null, null,
                ['r_sb25', 'r_ss25', 'u_sb25', 'u_ss26'], 'XII.6'],

            ['con-arie', 'corpuri', 'Aria laterală a conului',
                '\mathcal{A}_{\text{lat}} = \pi R G',
                'Raza bazei înmulțită cu generatoarea. Generatoarea, raza și înălțimea formează un triunghi dreptunghic: $G^2 = R^2 + H^2$.',
                [['G', 'generatoarea'], ['R', 'raza bazei']],
                null,
                ['r_ss22', 'r_ss24', 'r_sb26', 'u_ss25', 'u_sb26'], 'XII.6'],

            ['con-volum', 'corpuri', 'Volumul conului',
                '\mathcal{V} = \frac{1}{3}\pi R^2 H',
                'O treime din volumul cilindrului cu aceeași bază și înălțime.',
                null, null,
                ['u_sb24', 'u_ss25', 'u_sb26'], 'XII.6'],

            ['trunchi-con-arie', 'corpuri', 'Aria laterală a trunchiului de con',
                '\mathcal{A}_{\text{lat}} = \pi G (R + r)',
                'Generatoarea înmulțită cu suma razelor bazelor, ori $\pi$.',
                null, null,
                ['r_sb23', 'u_ss24'], 'XII.6'],

            ['trunchi-con-volum', 'corpuri', 'Volumul trunchiului de con',
                '\mathcal{V} = \frac{\pi h}{3}(R^2 + r^2 + Rr)',
                'Pentru $r = 0$ redevine volumul conului.',
                null, null,
                ['r_sb23'], 'XII.6'],

            ['sfera-arie', 'corpuri', 'Aria sferei',
                '\mathcal{A} = 4\pi R^2',
                'Patru arii de disc de aceeași rază.',
                null, null,
                ['u_ss22'], 'XII.6'],

            ['sfera-volum', 'corpuri', 'Volumul corpului sferic',
                '\mathcal{V} = \frac{4}{3}\pi R^3',
                'Raza la puterea a treia — volumul crește cu cubul razei.',
                null, null,
                ['r_ss25', 'u_ss22'], 'XII.6'],

            // ==================== Limite ====================
            ['limita-sin', 'limite', 'Limita remarcabilă cu sinus',
                '\lim_{x \to 0} \frac{\sin x}{x} = 1',
                'Ridică nedeterminarea $\frac{0}{0}$ cu funcții trigonometrice. Variantă: $\lim_{x\to 0} \frac{\sin kx}{kx} = 1$.',
                null, null,
                ['r_sb24'], 'XI.2'],

            ['limita-ln', 'limite', 'Limita remarcabilă cu logaritm',
                '\lim_{x \to 0} \frac{\ln(1 + x)}{x} = 1',
                'Ridică nedeterminarea $\frac{0}{0}$ cu logaritmi.',
                null, null,
                ['r_ss23'], 'XI.2'],

            ['asimptota-oblica', 'limite', 'Asimptota oblică',
                'y = mx + n,\qquad m = \lim_{x \to +\infty} \frac{f(x)}{x},\qquad n = \lim_{x \to +\infty} (f(x) - mx)',
                'Dacă ambele limite sunt finite și $m \neq 0$, graficul se apropie de dreapta $y = mx + n$. Pentru $m = 0$ asimptota e orizontală.',
                null, null,
                ['r_sb23', 'r_ss24', 'r_ss25'], 'XI.2'],

            // ==================== Derivate ====================
            ['derivata-putere', 'derivate', 'Derivata puterii',
                '(x^\alpha)\' = \alpha x^{\alpha - 1},\qquad \alpha \in \mathbb{R}',
                'Merge și pentru radicali, scriși ca puteri: $(\sqrt{x})\' = \frac{1}{2\sqrt{x}}$. Derivata unei constante e 0.',
                null, null,
                ['r_sb21', 'r_ss21', 'r_sb22', 'r_ss22', 'r_sb23', 'r_ss23', 'r_sb24', 'r_ss24', 'r_sb25', 'r_ss25', 'r_sb26', 'r_ss26', 'u_sb21', 'u_ss21'], 'XI.3'],

            ['derivata-exp-ln', 'derivate', 'Derivatele exponențialei și logaritmului',
                '(e^x)\' = e^x,\qquad (\ln x)\' = \frac{1}{x}',
                '$e^x$ e singura funcție egală cu derivata ei.',
                null, null,
                ['r_sb21', 'r_ss21', 'r_sb22', 'r_ss23', 'r_sb25', 'r_ss26', 'u_ss21'], 'XI.3'],

            ['derivata-trig', 'derivate', 'Derivatele sinusului și cosinusului',
                '(\sin x)\' = \cos x,\qquad (\cos x)\' = -\sin x',
                'Semnul minus apare doar la cosinus.',
                null, null,
                ['r_sb24'], 'XI.3'],

            ['derivata-produs', 'derivate', 'Derivata produsului',
                '(f \cdot g)\' = f\' \cdot g + f \cdot g\'',
                'Derivezi pe rând câte un factor, păstrându-l pe celălalt.',
                null,
                '$(f \cdot g)\' \neq f\' \cdot g\'$.',
                ['r_sb21', 'r_sb25'], 'XI.3'],

            ['derivata-cat', 'derivate', 'Derivata câtului',
                '\left(\frac{f}{g}\right)\' = \frac{f\' \cdot g - f \cdot g\'}{g^2}',
                'Ca la produs, dar cu MINUS și împărțit la pătratul numitorului.',
                null,
                'Ordinea la numărător contează: întâi $f\'g$, apoi minus $fg\'$.',
                ['r_sb23', 'r_ss24', 'r_ss25', 'r_sb26', 'r_ss26'], 'XI.3'],

            ['derivata-compusa', 'derivate', 'Derivata funcției compuse',
                '(f(g(x)))\' = f\'(g(x)) \cdot g\'(x)',
                'Derivata funcției exterioare, calculată în funcția interioară, ori derivata funcției interioare. Exemplu: $(\ln(3x - 2))\' = \frac{3}{3x - 2}$.',
                null,
                'Se uită factorul $g\'(x)$ — derivata „din interior".',
                [], 'XI.3'],

            ['tangenta', 'derivate', 'Ecuația tangentei',
                'y = f(x_0) + f\'(x_0)(x - x_0)',
                'Tangenta la grafic în punctul de abscisă $x_0$. Panta ei e $f\'(x_0)$: o tangentă paralelă cu $Ox$ are $f\'(x_0) = 0$.',
                [['x_0', 'abscisa punctului de contact']],
                null,
                ['r_ss23', 'r_ss24', 'r_sb25', 'u_ss21'], 'XI.3'],

            ['monotonie', 'derivate', 'Monotonia și extremele',
                'f\'(x) > 0 \Rightarrow f \nearrow,\qquad f\'(x) < 0 \Rightarrow f \searrow',
                'Punctele critice sunt soluțiile ecuației $f\'(x) = 0$. Unde derivata trece din $+$ în $-$ e un maxim local, din $-$ în $+$ — un minim local.',
                null,
                'Un punct critic nu e automat extrem: derivata trebuie să-și SCHIMBE semnul.',
                [], 'XI.3'],

            // ==================== Integrale ====================
            ['primitiva-putere', 'integrale', 'Primitiva puterii',
                '\int x^\alpha\,dx = \frac{x^{\alpha + 1}}{\alpha + 1} + C,\qquad \alpha \in \mathbb{R} \setminus \{-1\}',
                'Inversul derivatei puterii: exponentul crește cu 1 și se împarte la noul exponent.',
                null,
                'Nu merge pentru $\alpha = -1$ — acolo primitiva e $\ln|x|$.',
                ['r_sb21', 'r_ss21', 'r_sb22', 'r_ss22', 'r_sb23', 'r_ss23', 'r_sb24', 'r_ss24', 'r_sb25', 'r_ss25', 'r_ss26', 'u_sb21', 'u_ss21'], 'XII.1'],

            ['primitiva-1-supra-x', 'integrale', 'Primitiva lui 1/x',
                '\int \frac{dx}{x} = \ln|x| + C',
                'Cazul exceptat de formula puterii. Modulul ține locul domeniului: funcția $\frac{1}{x}$ există și pentru $x < 0$.',
                null, null,
                ['r_sb21', 'r_ss24', 'r_ss25', 'r_sb26'], 'XII.1'],

            ['primitiva-exp-trig', 'integrale', 'Primitivele exponențialei, sinusului și cosinusului',
                '\int e^x\,dx = e^x + C,\quad \int \sin x\,dx = -\cos x + C,\quad \int \cos x\,dx = \sin x + C',
                'Se verifică derivând rezultatul.',
                null,
                'Minusul apare la primitiva SINUSULUI (invers decât la derivate).',
                ['r_sb24', 'r_sb25'], 'XII.1'],

            ['integrare-prin-parti', 'integrale', 'Integrarea prin părți',
                '\int f(x) g\'(x)\,dx = f(x) g(x) - \int f\'(x) g(x)\,dx',
                'Pentru produse de tipul $x e^x$, $x \ln x$, $x \sin x$: alegi ca $f$ factorul care se simplifică prin derivare. Formă scurtă: $\int u\,dv = uv - \int v\,du$.',
                null, null,
                ['r_ss23', 'r_sb25', 'r_ss26'], 'XII.1'],

            ['newton-leibniz', 'integrale', 'Formula Newton-Leibniz',
                '\int_a^b f(x)\,dx = F(b) - F(a)',
                'Integrala definită e diferența valorilor unei primitive la capete. Aria subgraficului unei funcții pozitive pe $[a; b]$ e chiar această integrală; aria dintre două grafice — $\int_a^b (g(x) - f(x))\,dx$, cu $g \geq f$.',
                [['F', 'o primitivă a lui $f$']],
                'Ordinea: valoarea la capătul de SUS minus cea de la capătul de jos.',
                [], 'XII.2'],

            ['volum-rotatie', 'integrale', 'Volumul corpului de rotație',
                '\mathcal{V} = \pi \int_a^b f^2(x)\,dx',
                'Volumul corpului obținut rotind subgraficul lui $f$ în jurul axei $Ox$.',
                null,
                'Funcția apare la PĂTRAT, iar $\pi$ în fața integralei.',
                ['r_ss22'], 'XII.2'],

            // ==================== Combinatorică ====================
            ['permutari', 'combinatorica', 'Permutările',
                'P_n = n! = 1 \cdot 2 \cdot \ldots \cdot n,\qquad 0! = 1',
                'Numărul de moduri de a așeza $n$ obiecte distincte în ordine.',
                null, null,
                [], 'XII.3'],

            ['aranjamente', 'combinatorica', 'Aranjamentele',
                'A_n^k = \frac{n!}{(n - k)!},\qquad 0 \leq k \leq n',
                'Câte șiruri ORDONATE de $k$ elemente se pot forma din $n$: ordinea contează (numere formate din cifre, clasamente).',
                null, null,
                ['r_sb22', 'r_ss22', 'r_sb26', 'u_sb21', 'u_sb23'], 'XII.3'],

            ['combinari', 'combinatorica', 'Combinările',
                'C_n^k = \frac{n!}{k!\,(n - k)!},\qquad 0 \leq k \leq n',
                'Câte submulțimi de $k$ elemente se pot alege din $n$: ordinea NU contează (o echipă, o mână de bile). $C_n^k = C_n^{n-k}$.',
                null,
                'Aranjamente sau combinări? Dacă schimbând ordinea obții același rezultat — combinări.',
                ['r_ss21', 'r_sb22', 'r_ss22', 'r_sb23', 'r_ss23', 'r_sb24', 'r_ss24', 'r_sb25', 'r_ss25', 'r_sb26', 'r_ss26', 'u_ss22', 'u_ss23', 'u_sb24', 'u_sb26'], 'XII.3'],

            ['binomul-lui-newton', 'combinatorica', 'Binomul lui Newton',
                '(a + b)^n = C_n^0 a^n + C_n^1 a^{n-1} b + C_n^2 a^{n-2} b^2,\quad {} + \cdots + C_n^k a^{n-k} b^k + \cdots + C_n^n b^n',
                'Dezvoltarea are $n + 1$ termeni. Suma coeficienților binomiali e $2^n$, iar suma celor de rang par e egală cu a celor de rang impar: $2^{n-1}$.',
                null, null,
                $realAll22, 'XII.3'],

            ['termenul-general', 'combinatorica', 'Termenul general al dezvoltării',
                'T_{k+1} = C_n^k a^{n-k} b^k,\qquad k \in \{0, 1, \ldots, n\}',
                'Termenul de rang $k + 1$. Pentru „termenul care nu-l conține pe $x$" se pune condiția ca exponentul lui $x$ din $T_{k+1}$ să fie 0.',
                null,
                'Termenul al treilea e $T_3$, deci $k = 2$ — rangul e cu 1 mai mare decât $k$.',
                $realAll22, 'XII.3'],
        ];
    }
}
