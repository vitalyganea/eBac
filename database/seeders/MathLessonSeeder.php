<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Skill;
use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Teoria pentru matematică — secțiunea „Curs".
 *
 * Structura fiecărei lecții urmează felul în care se învață efectiv:
 *   intuiție → definiție → reguli → exemplu rezolvat → greșeala tipică
 *
 * Graficele sunt PRECALCULATE aici, în PHP, și trimise ca puncte. Componenta le
 * desenează ca SVG. Nu există niciun fișier imagine, deci nici pipeline de
 * assets — pentru matematică, aproape tot ce ar fi „o imagine" e de fapt un
 * grafic sau o figură, care se desenează mai bine decât se încarcă.
 */
class MathLessonSeeder extends Seeder
{
    public function run(): void
    {
        $subject = Subject::where('slug', 'matematica')->firstOrFail();

        foreach ($this->lessons() as $slug => $data) {
            $skill = Skill::where('subject_id', $subject->id)->where('slug', $slug)->first();

            if ($skill === null) {
                continue;
            }

            Lesson::updateOrCreate(
                ['skill_id' => $skill->id],
                [
                    'title' => $data['title'],
                    'summary' => $data['summary'],
                    'objectives' => $data['objectives'],
                    'blocks' => $data['blocks'],
                    'status' => 'published',
                    'language' => 'ro',
                ],
            );
        }
    }

    /**
     * Eșantionează o funcție pe un interval.
     *
     * Punctele se calculează AICI, nu în browser: evaluarea unei formule scrise
     * ca text ar însemna fie `eval`, fie un parser în plus în bundle-ul pe care
     * l-am ținut sub 200 KB.
     *
     * @return list<array{0: float, 1: float}>
     */
    private function sample(callable $f, float $from, float $to, int $steps = 90): array
    {
        $points = [];
        $dx = ($to - $from) / $steps;

        for ($i = 0; $i <= $steps; $i++) {
            $x = $from + $i * $dx;
            $y = $f($x);

            if (is_finite($y)) {
                $points[] = [round($x, 4), round($y, 4)];
            }
        }

        return $points;
    }

    /** @return array<string, array<string, mixed>> */
    private function lessons(): array
    {
        return [
            // ==================================================================
            'derivate' => [
                'title' => 'Derivate',
                'summary' => 'Ce măsoară derivata, cum se calculează și de ce panta tangentei e același lucru cu viteza de variație.',
                'objectives' => [
                    'Să înțelegi ce măsoară derivata, nu doar cum se calculează',
                    'Să aplici regulile de derivare: sumă, produs, cât, funcție compusă',
                    'Să scrii ecuația tangentei la grafic într-un punct dat',
                    'Să eviți greșelile care se penalizează cel mai des la examen',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Ideea, înainte de formule'],

                    ['type' => 'text', 'text' => 'Derivata răspunde la o singură întrebare: **cât de repede se schimbă** o funcție într-un punct. Dacă $f(t)$ e distanța parcursă la momentul $t$, atunci $f\'(t)$ e viteza. Dacă $f(x)$ e profitul la un preț $x$, $f\'(x)$ arată cât câștigi dacă crești prețul cu puțin.'],

                    ['type' => 'text', 'text' => 'Geometric, derivata în punctul $x_0$ este **panta tangentei** la grafic în acel punct. Cele două formulări — viteză de variație și pantă — sunt același lucru privit diferit.'],

                    [
                        'type' => 'plot',
                        'caption' => 'Graficul funcției f(x) = x³ − 2x și tangenta în punctul A(1, −1). Panta tangentei este f′(1) = 1.',
                        'x_range' => [-2.3, 2.3],
                        'y_range' => [-4, 4],
                        'series' => [
                            [
                                'label' => 'f(x) = x³ − 2x',
                                'color' => 'brand',
                                'points' => $this->sample(fn ($x) => $x ** 3 - 2 * $x, -2.2, 2.2),
                            ],
                            [
                                'label' => 'tangenta: y = x − 2',
                                'color' => 'amber',
                                'dashed' => true,
                                'points' => $this->sample(fn ($x) => $x - 2, -1.2, 2.2, 2),
                            ],
                        ],
                        'markers' => [['x' => 1, 'y' => -1, 'label' => 'A(1, −1)']],
                    ],

                    ['type' => 'heading', 'text' => 'Definiția'],

                    ['type' => 'text', 'text' => 'Formal, derivata este limita raportului dintre variația funcției și variația argumentului, când a doua tinde la zero:'],

                    [
                        'type' => 'formula',
                        'latex' => "f'(x_0) = \\lim_{h \\to 0} \\frac{f(x_0 + h) - f(x_0)}{h}",
                        'caption' => 'Raportul din interior e panta dreptei prin două puncte apropiate. Când h → 0, dreapta devine tangentă.',
                    ],

                    ['type' => 'text', 'text' => 'La examen nu vei calcula aproape niciodată derivata prin limită — folosești tabelul și regulile. Dar definiția explică **de ce** funcționează regulile, iar itemii care cer interpretarea derivatei pornesc de la ea.'],

                    ['type' => 'heading', 'text' => 'Derivatele pe care trebuie să le știi pe de rost'],

                    [
                        'type' => 'formula',
                        'latex' => "\\begin{aligned} (c)' &= 0 & (x^n)' &= n x^{n-1} \\\\ (\\sin x)' &= \\cos x & (\\cos x)' &= -\\sin x \\\\ (e^x)' &= e^x & (\\ln x)' &= \\frac{1}{x} \\\\ (\\operatorname{tg} x)' &= \\frac{1}{\\cos^2 x} & (\\sqrt{x})' &= \\frac{1}{2\\sqrt{x}} \\end{aligned}",
                    ],

                    ['type' => 'text', 'text' => 'Două repere care ajută la verificare: $e^x$ este **singura** funcție egală cu derivata sa, iar derivata unei constante e mereu zero — o funcție care nu se schimbă are viteză de variație nulă.'],

                    ['type' => 'heading', 'text' => 'Cele patru reguli'],

                    [
                        'type' => 'formula',
                        'latex' => "\\begin{aligned} (f + g)' &= f' + g' \\\\ (f \\cdot g)' &= f'g + fg' \\\\ \\left(\\frac{f}{g}\\right)' &= \\frac{f'g - fg'}{g^2} \\\\ \\big(f(g(x))\\big)' &= f'(g(x)) \\cdot g'(x) \\end{aligned}",
                        'caption' => 'Sumă, produs, cât, funcție compusă (regula lanțului).',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Greșeala numărul unu la produs',
                        'text' => 'Derivata unui produs **NU** este produsul derivatelor: $(f \\cdot g)\' \\ne f\' \\cdot g\'$. Verifică pe un caz simplu: pentru $f = g = x$, produsul e $x^2$, cu derivata $2x$. Dacă ai înmulți derivatele ai obține $1 \\cdot 1 = 1$. Regula corectă dă $1 \\cdot x + x \\cdot 1 = 2x$.',
                    ],

                    ['type' => 'heading', 'text' => 'Ecuația tangentei — item clasic de examen'],

                    [
                        'type' => 'example',
                        'title' => 'Exemplu rezolvat',
                        'problem' => 'Scrie ecuația tangentei la graficul funcției $f(x) = x^3 - 2x$ în punctul de abscisă $x_0 = 1$.',
                        'steps' => [
                            ['text' => 'Calculăm ordonata punctului de tangență.', 'latex' => 'f(1) = 1^3 - 2 \\cdot 1 = -1'],
                            ['text' => 'Calculăm derivata funcției.', 'latex' => "f'(x) = 3x^2 - 2"],
                            ['text' => 'Aflăm panta, adică derivata în $x_0$.', 'latex' => "m = f'(1) = 3 - 2 = 1"],
                            ['text' => 'Scriem ecuația tangentei prin punctul $A(x_0, f(x_0))$ cu panta $m$.', 'latex' => 'y - (-1) = 1 \\cdot (x - 1)'],
                            ['text' => 'Aducem la forma explicită.', 'latex' => 'y = x - 2'],
                        ],
                        'result' => 'Tangenta are ecuația $y = x - 2$ — exact dreapta punctată din graficul de mai sus.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Ordinea contează în barem',
                        'text' => 'Baremul acordă puncte separat pentru $f(x_0)$, pentru $f\'(x)$ și pentru panta $f\'(x_0)$. Chiar dacă greșești calculul final, pașii scriși corect îți aduc punctaj parțial — deci scrie-i pe toți, explicit.',
                    ],

                    ['type' => 'heading', 'text' => 'Unde se folosește mai departe'],

                    ['type' => 'text', 'text' => 'Derivata e prerechizit direct pentru **integrale**: integrarea e operația inversă derivării. De asemenea, semnul derivatei spune unde funcția crește ($f\' > 0$) și unde scade ($f\' < 0$), iar punctele în care $f\' = 0$ sunt candidate pentru maxime și minime.'],
                ],
            ],

            // ==================================================================
            'logaritmi' => [
                'title' => 'Logaritmi',
                'summary' => 'Logaritmul ca exponent, proprietățile care apar la examen și condițiile de existență care aduc puncte separate în barem.',
                'objectives' => [
                    'Să citești un logaritm ca răspuns la întrebarea „la ce putere ridic baza?"',
                    'Să aplici proprietățile produsului, câtului și puterii',
                    'Să pui corect condițiile de existență și să elimini soluțiile străine',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Ce este, de fapt, un logaritm'],

                    ['type' => 'text', 'text' => 'Un logaritm e răspunsul la întrebarea: **la ce putere trebuie să ridic baza ca să obțin numărul?** Scrierea $\\log_2 8 = 3$ înseamnă exact $2^3 = 8$. Nimic mai mult.'],

                    [
                        'type' => 'formula',
                        'latex' => '\\log_a b = c \\iff a^c = b, \\quad a > 0,\\ a \\ne 1,\\ b > 0',
                        'caption' => 'Condițiile din dreapta nu sunt decor: din ele vin toate restricțiile de la examen.',
                    ],

                    [
                        'type' => 'plot',
                        'caption' => 'Funcțiile logaritmice cresc, dar din ce în ce mai încet. Toate trec prin punctul (1, 0), pentru că orice bază la puterea zero dă 1.',
                        'x_range' => [0, 8],
                        'y_range' => [-3, 3],
                        'series' => [
                            [
                                'label' => 'log₂ x',
                                'color' => 'brand',
                                'points' => $this->sample(fn ($x) => log($x, 2), 0.12, 8),
                            ],
                            [
                                'label' => 'ln x',
                                'color' => 'emerald',
                                'points' => $this->sample(fn ($x) => log($x), 0.12, 8),
                            ],
                        ],
                        'markers' => [['x' => 1, 'y' => 0, 'label' => '(1, 0)']],
                    ],

                    ['type' => 'heading', 'text' => 'Proprietățile care apar la examen'],

                    [
                        'type' => 'formula',
                        'latex' => '\\begin{aligned} \\log_a(x \\cdot y) &= \\log_a x + \\log_a y \\\\ \\log_a\\frac{x}{y} &= \\log_a x - \\log_a y \\\\ \\log_a x^n &= n \\log_a x \\\\ \\log_a x &= \\frac{\\log_b x}{\\log_b a} \\end{aligned}',
                        'caption' => 'Produsul devine sumă, câtul devine diferență, puterea coboară în față.',
                    ],

                    [
                        'type' => 'example',
                        'title' => 'Exemplu rezolvat',
                        'problem' => 'Rezolvă ecuația $\\log_2(x-1) + \\log_2(x+1) = 3$.',
                        'steps' => [
                            ['text' => 'Punem condițiile de existență, ÎNAINTE de orice calcul.', 'latex' => 'x - 1 > 0 \\ \\text{și}\\ x + 1 > 0 \\Rightarrow x > 1'],
                            ['text' => 'Aplicăm proprietatea produsului, ca să avem un singur logaritm.', 'latex' => '\\log_2\\big((x-1)(x+1)\\big) = 3'],
                            ['text' => 'Trecem la forma exponențială, din definiție.', 'latex' => '(x-1)(x+1) = 2^3 = 8'],
                            ['text' => 'Rezolvăm ecuația obținută.', 'latex' => 'x^2 - 1 = 8 \\Rightarrow x^2 = 9 \\Rightarrow x = \\pm 3'],
                            ['text' => 'Verificăm condiția $x > 1$ și eliminăm soluția străină.', 'latex' => 'x = -3 \\ \\text{nu convine};\\quad x = 3'],
                        ],
                        'result' => '$x = 3$',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Jumătate din punctaj se pierde aici',
                        'text' => 'Condițiile de existență se pun **înainte** de a rezolva, iar verificarea lor la final e un pas separat în barem. Un elev care obține corect $x_1 = 3$ și $x_2 = -3$, dar nu respinge explicit soluția străină, pierde punctele pentru ultimul pas — deși calculele sunt impecabile.',
                    ],
                ],
            ],

            // ==================================================================
            'ecuatii-inecuatii' => [
                'title' => 'Ecuații și inecuații',
                'summary' => 'Ecuația de gradul al doilea, semnul funcției, tabelul de semne și capcana ecuațiilor iraționale.',
                'objectives' => [
                    'Să rezolvi ecuații de gradul al doilea și să interpretezi discriminantul',
                    'Să citești semnul unei funcții de gradul al doilea de pe grafic și din tabel',
                    'Să rezolvi inecuații fără să greșești sensul inegalității',
                    'Să pui condițiile de existență și să elimini soluțiile străine',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Ecuația de gradul al doilea'],

                    ['type' => 'text', 'text' => 'Orice ecuație de forma $ax^2 + bx + c = 0$, cu $a \neq 0$, se rezolvă cu aceeași formulă. Tot ce decide numărul de soluții este **discriminantul**.'],

                    [
                        'type' => 'formula',
                        'latex' => '\\Delta = b^2 - 4ac, \\qquad x_{1,2} = \\frac{-b \\pm \\sqrt{\\Delta}}{2a}',
                        'caption' => 'Δ > 0 → două soluții reale · Δ = 0 → o soluție dublă · Δ < 0 → nicio soluție reală',
                    ],

                    [
                        'type' => 'plot',
                        'caption' => 'f(x) = x² − 5x + 6 taie axa Ox în punctele 2 și 3 — exact soluțiile ecuației. Între ele funcția e negativă, în rest pozitivă.',
                        'x_range' => [-0.5, 5.5],
                        'y_range' => [-1.5, 6.5],
                        'series' => [
                            [
                                'label' => 'f(x) = x² − 5x + 6',
                                'color' => 'brand',
                                'points' => $this->sample(fn ($x) => $x ** 2 - 5 * $x + 6, -0.4, 5.4),
                            ],
                        ],
                        'markers' => [
                            ['x' => 2, 'y' => 0, 'label' => 'x₁ = 2'],
                            ['x' => 3, 'y' => 0, 'label' => 'x₂ = 3'],
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Graficul spune tot ce trebuie despre semn: parabola cu $a > 0$ e **negativă între rădăcini** și pozitivă în rest. Dacă $a < 0$, totul se inversează. Regula se ține minte scurt: *semn contrar lui $a$ între rădăcini, semnul lui $a$ în afara lor*.'],

                    ['type' => 'heading', 'text' => 'Inecuații și tabelul de semne'],

                    ['type' => 'text', 'text' => 'O inecuație de gradul al doilea nu se „rezolvă" ca o ecuație — se citește semnul. Găsești rădăcinile, faci tabelul de semne, apoi alegi intervalele care satisfac inegalitatea. Pentru $x^2 - 5x + 6 < 0$ răspunsul este $x \in (2, 3)$.'],

                    [
                        'type' => 'warning',
                        'title' => 'Sensul inegalității se inversează',
                        'text' => 'Când înmulțești sau împarți o inecuație cu un număr **negativ**, sensul se schimbă: din $-2x > 6$ rezultă $x < -3$, nu $x > -3$. Și nu împărți niciodată la o expresie cu $x$ fără să discuți semnul ei — poate fi zero sau negativă.',
                    ],

                    ['type' => 'heading', 'text' => 'Ecuații iraționale'],

                    ['type' => 'text', 'text' => 'Ecuațiile cu radical sunt itemul unde se pierd cele mai multe puncte, și aproape niciodată din cauza calculelor. Ridicarea la pătrat poate **introduce soluții false**, deci condițiile se pun înainte și se verifică la final.'],

                    [
                        'type' => 'example',
                        'title' => 'Exemplu rezolvat',
                        'problem' => 'Rezolvă ecuația $\sqrt{x + 3} = x - 3$.',
                        'steps' => [
                            ['text' => 'Punem condițiile: radicalul cere argument pozitiv, iar membrul drept trebuie să fie pozitiv, pentru că un radical nu poate fi negativ.', 'latex' => 'x + 3 \\ge 0 \\ \\text{și}\\ x - 3 \\ge 0 \\Rightarrow x \\ge 3'],
                            ['text' => 'Ridicăm la pătrat ambii membri.', 'latex' => 'x + 3 = (x-3)^2'],
                            ['text' => 'Aducem la forma canonică.', 'latex' => 'x^2 - 7x + 6 = 0'],
                            ['text' => 'Rezolvăm ecuația de gradul al doilea.', 'latex' => 'x_1 = 1, \\quad x_2 = 6'],
                            ['text' => 'Verificăm condiția $x \\ge 3$ și respingem explicit soluția străină.', 'latex' => 'x = 1 \\ \\text{nu convine};\\quad x = 6'],
                        ],
                        'result' => '$x = 6$',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Condițiile aduc puncte separat în barem',
                        'text' => 'Baremul punctează distinct stabilirea condițiilor și verificarea lor la final. Un elev care obține $x_1 = 1$ și $x_2 = 6$ dar nu respinge explicit prima soluție pierde puncte — chiar dacă scrie răspunsul corect la sfârșit.',
                    ],
                ],
            ],

            // ==================================================================
            'integrale' => [
                'title' => 'Integrale',
                'summary' => 'Primitiva ca operație inversă derivării, integrala definită și aria de sub grafic, integrarea prin părți.',
                'objectives' => [
                    'Să înțelegi integrala ca operație inversă a derivării',
                    'Să folosești tabelul de primitive și să nu uiți constanta',
                    'Să calculezi o integrală definită cu formula Leibniz-Newton',
                    'Să alegi corect u și dv la integrarea prin părți',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Integrala e derivarea, invers'],

                    ['type' => 'text', 'text' => 'Dacă derivata îți spune cât de repede se schimbă o funcție, integrala pornește de la viteză și reconstruiește funcția. O funcție $F$ se numește **primitivă** a lui $f$ dacă derivând-o obții exact $f$.'],

                    [
                        'type' => 'formula',
                        'latex' => "\\int f(x)\\, dx = F(x) + C \\iff F'(x) = f(x)",
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Constanta C nu e opțională',
                        'text' => 'Derivata unei constante e zero, deci $x^2$, $x^2 + 5$ și $x^2 - 100$ au **toate** derivata $2x$. Când mergi invers, nu ai cum să știi de la care ai plecat — de aceea răspunsul e o familie de funcții, scrisă cu $+ C$. Omiterea lui se penalizează la fiecare integrală nedefinită.',
                    ],

                    ['type' => 'heading', 'text' => 'Tabelul de primitive'],

                    [
                        'type' => 'formula',
                        'latex' => '\\begin{aligned} \\int x^n dx &= \\frac{x^{n+1}}{n+1} + C \\ (n \\ne -1) & \\int \\frac{1}{x} dx &= \\ln|x| + C \\\\ \\int e^x dx &= e^x + C & \\int \\sin x\\, dx &= -\\cos x + C \\\\ \\int \\cos x\\, dx &= \\sin x + C & \\int \\frac{1}{\\cos^2 x} dx &= \\operatorname{tg} x + C \\end{aligned}',
                        'caption' => 'Verificare rapidă: derivează rezultatul și trebuie să obții funcția de sub integrală.',
                    ],

                    ['type' => 'heading', 'text' => 'Integrala definită și aria'],

                    ['type' => 'text', 'text' => 'Integrala definită nu mai e o familie de funcții, ci un **număr**: aria dintre grafic și axa Ox, pe intervalul dat. Se calculează cu formula Leibniz-Newton, iar constanta $C$ se simplifică singură.'],

                    [
                        'type' => 'formula',
                        'latex' => '\\int_a^b f(x)\\, dx = F(b) - F(a)',
                    ],

                    [
                        'type' => 'plot',
                        'caption' => 'Aria hașurată de sub f(x) = x², între 0 și 2, este exact valoarea integralei definite: 8/3 ≈ 2,67.',
                        'x_range' => [-0.4, 2.8],
                        'y_range' => [-0.5, 5.5],
                        'series' => [
                            [
                                'label' => 'f(x) = x²',
                                'color' => 'brand',
                                'points' => $this->sample(fn ($x) => $x ** 2, -0.3, 2.7),
                            ],
                            [
                                'label' => 'aria pe [0, 2]',
                                'color' => 'emerald',
                                'fill' => true,
                                'points' => $this->sample(fn ($x) => $x ** 2, 0, 2, 40),
                            ],
                        ],
                    ],

                    [
                        'type' => 'example',
                        'title' => 'Exemplu rezolvat',
                        'problem' => 'Calculează $\int_0^2 x^2\, dx$.',
                        'steps' => [
                            ['text' => 'Găsim primitiva, din tabel.', 'latex' => 'F(x) = \\frac{x^3}{3}'],
                            ['text' => 'Aplicăm formula Leibniz-Newton.', 'latex' => '\\int_0^2 x^2 dx = F(2) - F(0)'],
                            ['text' => 'Înlocuim capetele intervalului.', 'latex' => '= \\frac{8}{3} - \\frac{0}{3} = \\frac{8}{3}'],
                        ],
                        'result' => '$\frac{8}{3} \approx 2{,}67$ — aria hașurată din grafic.',
                    ],

                    ['type' => 'heading', 'text' => 'Integrarea prin părți'],

                    ['type' => 'text', 'text' => 'Când sub integrală apare un **produs** de două funcții de tipuri diferite — de exemplu un polinom înmulțit cu o exponențială — tabelul nu ajunge. Se folosește formula de integrare prin părți.'],

                    [
                        'type' => 'formula',
                        'latex' => '\\int u\\, dv = uv - \\int v\\, du',
                    ],

                    [
                        'type' => 'example',
                        'title' => 'Exemplu rezolvat',
                        'problem' => 'Calculează $\int x e^x\, dx$.',
                        'steps' => [
                            ['text' => 'Alegem ca $u$ funcția care se simplifică prin derivare.', 'latex' => 'u = x \\Rightarrow du = dx'],
                            ['text' => 'Restul devine $dv$, pe care îl integrăm.', 'latex' => 'dv = e^x dx \\Rightarrow v = e^x'],
                            ['text' => 'Aplicăm formula.', 'latex' => '\\int x e^x dx = x e^x - \\int e^x dx'],
                            ['text' => 'Integrala rămasă e imediată.', 'latex' => '= x e^x - e^x + C'],
                        ],
                        'result' => '$e^x(x - 1) + C$',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Alegerea greșită a lui u complică problema',
                        'text' => 'Dacă ai fi ales $u = e^x$ și $dv = x\, dx$, ai fi obținut $\int \frac{x^2}{2} e^x dx$ — o integrală **mai grea** decât cea de la care ai plecat. Regula practică: $u$ se alege funcția care devine mai simplă prin derivare, de obicei polinomul.',
                    ],
                ],
            ],

            // ==================================================================
            'matrici' => [
                'title' => 'Matrici și determinanți',
                'summary' => 'Operațiile cu matrici, regula rând × coloană la înmulțire și calculul determinanților de ordinul 2 și 3.',
                'objectives' => [
                    'Să aduni matrici și să le înmulțești cu un scalar',
                    'Să înmulțești două matrici după regula rând × coloană',
                    'Să calculezi determinanți de ordinul 2 și 3',
                    'Să recunoști când o operație nu e definită',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Ce este o matrice'],

                    ['type' => 'text', 'text' => 'O matrice e un tablou dreptunghiular de numere, organizat pe **linii** și **coloane**. O matrice cu $m$ linii și $n$ coloane are dimensiunea $m \times n$ — întâi liniile, apoi coloanele, mereu în ordinea asta.'],

                    [
                        'type' => 'formula',
                        'latex' => 'A = \\begin{pmatrix} 3 & 5 \\\\ 2 & 4 \\end{pmatrix} \\quad \\text{are dimensiunea } 2 \\times 2',
                    ],

                    ['type' => 'heading', 'text' => 'Adunarea și înmulțirea cu un scalar'],

                    ['type' => 'text', 'text' => 'Astea sunt operațiile simple: se lucrează **element cu element**. Adunarea cere ca ambele matrici să aibă exact aceeași dimensiune.'],

                    [
                        'type' => 'formula',
                        'latex' => '\\begin{pmatrix} 1 & 2 \\\\ 3 & 4 \\end{pmatrix} + \\begin{pmatrix} 5 & 0 \\\\ 1 & 2 \\end{pmatrix} = \\begin{pmatrix} 6 & 2 \\\\ 4 & 6 \\end{pmatrix}, \\qquad 2 \\cdot \\begin{pmatrix} 1 & 2 \\\\ 3 & 4 \\end{pmatrix} = \\begin{pmatrix} 2 & 4 \\\\ 6 & 8 \\end{pmatrix}',
                    ],

                    ['type' => 'heading', 'text' => 'Înmulțirea matricelor: rând × coloană'],

                    ['type' => 'text', 'text' => 'Aici e partea care se greșește. Înmulțirea **nu** se face element cu element. Elementul de pe linia $i$ și coloana $j$ al rezultatului se obține înmulțind linia $i$ din prima matrice cu coloana $j$ din a doua, termen cu termen, și adunând.'],

                    [
                        'type' => 'example',
                        'title' => 'Exemplu rezolvat',
                        'problem' => 'Calculează produsul $\begin{pmatrix} 1 & 2 \\\\ 3 & 4 \end{pmatrix} \cdot \begin{pmatrix} 5 & 6 \\\\ 7 & 8 \end{pmatrix}$.',
                        'steps' => [
                            ['text' => 'Linia 1 × coloana 1, pentru elementul din stânga sus.', 'latex' => '1 \\cdot 5 + 2 \\cdot 7 = 19'],
                            ['text' => 'Linia 1 × coloana 2.', 'latex' => '1 \\cdot 6 + 2 \\cdot 8 = 22'],
                            ['text' => 'Linia 2 × coloana 1.', 'latex' => '3 \\cdot 5 + 4 \\cdot 7 = 43'],
                            ['text' => 'Linia 2 × coloana 2.', 'latex' => '3 \\cdot 6 + 4 \\cdot 8 = 50'],
                            ['text' => 'Așezăm rezultatele la locul lor.', 'latex' => '\\begin{pmatrix} 19 & 22 \\\\ 43 & 50 \\end{pmatrix}'],
                        ],
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Două capcane la înmulțire',
                        'text' => 'Prima: produsul $A \cdot B$ există doar dacă numărul de **coloane** din $A$ e egal cu numărul de **linii** din $B$. A doua: înmulțirea matricelor **nu e comutativă** — în general $A \cdot B \neq B \cdot A$, iar uneori unul dintre produse nici măcar nu e definit.',
                    ],

                    ['type' => 'heading', 'text' => 'Determinantul'],

                    ['type' => 'text', 'text' => 'Determinantul e un **număr** asociat unei matrici **pătratice** — pentru o matrice dreptunghiulară nu are sens. Pentru ordinul 2 se calculează imediat; pentru ordinul 3 se folosește regula lui Sarrus.'],

                    [
                        'type' => 'formula',
                        'latex' => '\\begin{vmatrix} a & b \\\\ c & d \\end{vmatrix} = ad - bc',
                        'caption' => 'Produsul diagonalei principale minus produsul diagonalei secundare.',
                    ],

                    [
                        'type' => 'example',
                        'title' => 'Exemplu rezolvat',
                        'problem' => 'Calculează $\begin{vmatrix} 3 & 5 \\\\ 2 & 4 \end{vmatrix}$.',
                        'steps' => [
                            ['text' => 'Înmulțim pe diagonala principală.', 'latex' => '3 \\cdot 4 = 12'],
                            ['text' => 'Înmulțim pe diagonala secundară.', 'latex' => '5 \\cdot 2 = 10'],
                            ['text' => 'Scădem, în ordinea din formulă.', 'latex' => '12 - 10 = 2'],
                        ],
                        'result' => '$2$',
                    ],

                    ['type' => 'text', 'text' => 'Pentru ordinul 3, regula lui Sarrus: repeți primele două coloane la dreapta matricei, aduni produsele celor trei diagonale descendente și scazi produsele celor trei diagonale ascendente.'],

                    [
                        'type' => 'formula',
                        'latex' => '\\begin{vmatrix} a_1 & a_2 & a_3 \\\\ b_1 & b_2 & b_3 \\\\ c_1 & c_2 & c_3 \\end{vmatrix} = a_1b_2c_3 + a_2b_3c_1 + a_3b_1c_2 - a_3b_2c_1 - a_1b_3c_2 - a_2b_1c_3',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Sarrus merge doar la ordinul 3',
                        'text' => 'Regula lui Sarrus **nu** se generalizează la matrici de ordin 4 sau mai mare — acolo se dezvoltă după o linie sau o coloană. Iar determinantul nu există deloc pentru matrici care nu sunt pătratice.',
                    ],
                ],
            ],

            // ==================================================================
            'functii' => [
                'title' => 'Funcții',
                'summary' => 'Domeniul de definiție, citirea graficului și proprietățile care se cer la examen — baza pe care se sprijină limitele și derivatele.',
                'objectives' => [
                    'Să determini domeniul de definiție al unei funcții',
                    'Să citești de pe grafic zerourile, semnul și monotonia',
                    'Să recunoști funcțiile pare, impare și pe cele bijective',
                    'Să compui două funcții și să afli inversa',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Ce este o funcție'],

                    ['type' => 'text', 'text' => 'O funcție e o regulă care asociază **fiecărui** element dintr-o mulțime exact **un singur** element din alta. Cele două cuvinte îngroșate sunt tot ce contează: dacă un element nu are corespondent, sau are două, nu e funcție.'],

                    ['type' => 'text', 'text' => 'Vizual, asta înseamnă că orice dreaptă verticală taie graficul în **cel mult un punct**. E testul cel mai rapid pentru a decide dacă o curbă reprezintă o funcție.'],

                    ['type' => 'heading', 'text' => 'Domeniul de definiție'],

                    ['type' => 'text', 'text' => 'Domeniul e mulțimea valorilor pentru care formula are sens. La examen apare ca item de sine stătător și e primul pas obligatoriu în orice ecuație — condițiile de existență de care ai auzit la logaritmi și radicali sunt exact asta.'],

                    [
                        'type' => 'table',
                        'caption' => 'Trei situații acoperă aproape tot ce apare la BAC.',
                        'headers' => ['Formula conține', 'Condiția', 'Exemplu'],
                        'rows' => [
                            ['Fracție', 'Numitorul $\\neq 0$', '$f(x)=\\frac{1}{x-2} \\Rightarrow D = \\mathbb{R} \\setminus \\{2\\}$'],
                            ['Radical de ordin par', 'Ce e sub radical $\\ge 0$', '$f(x)=\\sqrt{x-3} \\Rightarrow D = [3, +\\infty)$'],
                            ['Logaritm', 'Argumentul $> 0$', '$f(x)=\\ln(x+1) \\Rightarrow D = (-1, +\\infty)$'],
                        ],
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Când apar mai multe condiții, se intersectează',
                        'text' => 'Pentru $f(x) = \\frac{\\sqrt{x-1}}{x-3}$ ai două cerințe simultan: $x - 1 \\ge 0$ și $x - 3 \\neq 0$. Domeniul e **intersecția** lor, adică $[1, 3) \\cup (3, +\\infty)$ — nu reuniunea. Confuzia dintre „și" și „sau" e una dintre cele mai frecvente greșeli la acest item.',
                    ],

                    ['type' => 'heading', 'text' => 'Ce citești de pe grafic'],

                    [
                        'type' => 'plot',
                        'caption' => 'f(x) = x² − 4. Zerourile sunt −2 și 2, funcția scade pe (−∞, 0] și crește pe [0, +∞), iar minimul e în (0, −4).',
                        'x_range' => [-3.5, 3.5],
                        'y_range' => [-5, 6],
                        'series' => [
                            [
                                'label' => 'f(x) = x² − 4',
                                'color' => 'brand',
                                'points' => $this->sample(fn ($x) => $x ** 2 - 4, -3.2, 3.2),
                            ],
                        ],
                        'markers' => [
                            ['x' => -2, 'y' => 0, 'label' => 'zero'],
                            ['x' => 2, 'y' => 0, 'label' => 'zero'],
                            ['x' => 0, 'y' => -4, 'label' => 'minim'],
                        ],
                    ],

                    [
                        'type' => 'table',
                        'headers' => ['Proprietate', 'Cum se vede pe grafic'],
                        'rows' => [
                            ['Zerourile', 'Punctele în care graficul taie axa Ox'],
                            ['Semnul', 'Deasupra axei → $f(x) > 0$; sub axă → $f(x) < 0$'],
                            ['Monotonia', 'Urcă → crescătoare; coboară → descrescătoare'],
                            ['Extremele', 'Vârfurile: minim jos, maxim sus'],
                            ['Paritatea', 'Simetrie față de Oy → pară; față de origine → impară'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Funcții pare și impare'],

                    [
                        'type' => 'formula',
                        'latex' => 'f(-x) = f(x) \\ \\text{(pară)}, \\qquad f(-x) = -f(x) \\ \\text{(impară)}',
                        'caption' => 'Verificarea se face algebric, înlocuind x cu −x. Graficul doar confirmă.',
                    ],

                    ['type' => 'text', 'text' => 'Exemple: $x^2$ și $\\cos x$ sunt pare, $x^3$ și $\\sin x$ sunt impare. Majoritatea funcțiilor nu sunt nici una, nici alta — de exemplu $x^2 + x$.'],

                    ['type' => 'heading', 'text' => 'Injectivitate, surjectivitate, bijectivitate'],

                    [
                        'type' => 'table',
                        'headers' => ['Proprietate', 'Definiție', 'Test grafic'],
                        'rows' => [
                            ['Injectivă', 'Valori diferite ale lui $x$ dau rezultate diferite', 'Orice dreaptă orizontală taie graficul cel mult o dată'],
                            ['Surjectivă', 'Fiecare element din codomeniu e atins', 'Graficul acoperă tot codomeniul pe verticală'],
                            ['Bijectivă', 'Și injectivă, și surjectivă', 'Orice orizontală taie exact o dată'],
                        ],
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Doar funcțiile bijective au inversă',
                        'text' => 'Funcția $f(x) = x^2$ pe $\\mathbb{R}$ nu e injectivă — $f(-2) = f(2) = 4$ — deci nu are inversă. Devine bijectivă abia dacă restrângi domeniul la $[0, +\\infty)$, iar atunci inversa e $\\sqrt{x}$. La examen, întrebarea „are inversă?" e de fapt întrebarea „e bijectivă?".',
                    ],

                    ['type' => 'heading', 'text' => 'Compunerea'],

                    [
                        'type' => 'example',
                        'title' => 'Exemplu rezolvat',
                        'problem' => 'Fie $f(x) = 2x + 1$ și $g(x) = x^2$. Calculează $(f \circ g)(x)$ și $(g \circ f)(x)$.',
                        'steps' => [
                            ['text' => 'Compunerea $f \\circ g$ înseamnă „întâi g, apoi f" — se citește de la dreapta la stânga.', 'latex' => '(f \\circ g)(x) = f(g(x)) = f(x^2)'],
                            ['text' => 'Înlocuim în formula lui $f$.', 'latex' => '= 2x^2 + 1'],
                            ['text' => 'În ordinea cealaltă, întâi f, apoi g.', 'latex' => '(g \\circ f)(x) = g(2x+1) = (2x+1)^2'],
                        ],
                        'result' => 'Rezultatele diferă: compunerea **nu e comutativă**.',
                    ],

                    ['type' => 'text', 'text' => 'Funcțiile sunt prerechizit direct pentru **limite** și, prin ele, pentru **derivate**. Dacă greșești constant la derivate, verifică întâi dacă problema nu e de fapt aici — la domeniu sau la citirea graficului.'],
                ],
            ],

            // ==================================================================
            'limite' => [
                'title' => 'Limite de funcții',
                'summary' => 'Ce înseamnă „se apropie de", cazurile de nedeterminare și legătura cu continuitatea și derivatele.',
                'objectives' => [
                    'Să înțelegi limita ca tendință, nu ca valoare atinsă',
                    'Să calculezi limite prin înlocuire directă și să recunoști când nu merge',
                    'Să rezolvi nedeterminările 0/0 și ∞/∞',
                    'Să verifici continuitatea unei funcții într-un punct',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Limita e o tendință, nu o valoare'],

                    ['type' => 'text', 'text' => 'Întrebarea „care e limita lui $f$ când $x$ tinde la $a$" nu înseamnă „cât e $f(a)$". Înseamnă: **spre ce se apropie** valorile funcției, când $x$ se apropie de $a$ — fără să ajungă neapărat acolo.'],

                    ['type' => 'text', 'text' => 'Distincția pare formală, dar e exact motivul pentru care limitele există. Funcția poate să nici nu fie definită în $a$, și totuși limita să existe.'],

                    [
                        'type' => 'example',
                        'title' => 'Cazul care lămurește tot',
                        'problem' => 'Fie $f(x) = \frac{x^2 - 1}{x - 1}$. Cât e $f(1)$ și cât e $\lim_{x \to 1} f(x)$?',
                        'steps' => [
                            ['text' => 'În $x = 1$ numitorul se anulează, deci $f(1)$ **nu există** — punctul nu e în domeniu.'],
                            ['text' => 'Dar pentru orice $x \\neq 1$ putem simplifica.', 'latex' => '\\frac{x^2-1}{x-1} = \\frac{(x-1)(x+1)}{x-1} = x + 1'],
                            ['text' => 'Deci, apropiindu-ne de 1, valorile se apropie de 2.', 'latex' => '\\lim_{x \\to 1} f(x) = 2'],
                        ],
                        'result' => 'Funcția nu are valoare în 1, dar are limită în 1. Graficul e dreapta $y = x+1$ cu un „gol" în punctul $(1, 2)$.',
                    ],

                    ['type' => 'heading', 'text' => 'Cum se calculează, în practică'],

                    ['type' => 'text', 'text' => 'Primul lucru pe care îl încerci mereu e **înlocuirea directă**. În majoritatea cazurilor funcționează și te oprești acolo.'],

                    [
                        'type' => 'formula',
                        'latex' => '\\lim_{x \\to 3} (x^2 - 2x) = 3^2 - 2 \\cdot 3 = 3',
                    ],

                    ['type' => 'text', 'text' => 'Înlocuirea eșuează doar când obții o **nedeterminare** — o expresie care nu spune nimic despre rezultat. Cele două care apar la BAC sunt $\\frac{0}{0}$ și $\\frac{\\infty}{\\infty}$.'],

                    [
                        'type' => 'table',
                        'caption' => 'Fiecare nedeterminare are o tehnică standard.',
                        'headers' => ['Nedeterminare', 'Când apare', 'Ce faci'],
                        'rows' => [
                            ['$\\frac{0}{0}$', 'Fracție în care ambii termeni se anulează', 'Descompui în factori și simplifici'],
                            ['$\\frac{\\infty}{\\infty}$', 'Fracție de polinoame, cu $x \\to \\pm\\infty$', 'Împarți la cea mai mare putere a lui $x$'],
                        ],
                    ],

                    [
                        'type' => 'example',
                        'title' => 'Nedeterminarea ∞/∞',
                        'problem' => 'Calculează $\lim_{x \to \infty} \frac{3x^2 + 1}{x^2 - 5}$.',
                        'steps' => [
                            ['text' => 'Înlocuirea directă dă $\\frac{\\infty}{\\infty}$ — nedeterminare, deci continuăm.'],
                            ['text' => 'Împărțim și numărătorul, și numitorul la $x^2$, cea mai mare putere.', 'latex' => '\\frac{3 + \\frac{1}{x^2}}{1 - \\frac{5}{x^2}}'],
                            ['text' => 'Când $x \\to \\infty$, fracțiile cu $x$ la numitor tind la zero.', 'latex' => '\\to \\frac{3 + 0}{1 - 0} = 3'],
                        ],
                        'result' => 'La fracții de polinoame de același grad, limita la infinit e raportul coeficienților dominanți.',
                    ],

                    ['type' => 'heading', 'text' => 'Limite laterale și asimptote'],

                    ['type' => 'text', 'text' => 'Uneori funcția se comportă diferit când te apropii din stânga față de dreapta. Notațiile sunt $x \\to a^-$ și $x \\to a^+$, iar limita există **doar dacă cele două coincid**.'],

                    [
                        'type' => 'plot',
                        'caption' => 'f(x) = 1/x. Când x → 0 din dreapta valorile cresc nemărginit, din stânga scad nemărginit — limitele laterale diferă, deci limita în 0 nu există. Axa Ox e asimptotă orizontală la ±∞.',
                        'x_range' => [-4, 4],
                        'y_range' => [-6, 6],
                        'series' => [
                            [
                                'label' => 'ramura x < 0',
                                'color' => 'brand',
                                'points' => $this->sample(fn ($x) => 1 / $x, -4, -0.17, 60),
                            ],
                            [
                                'label' => 'ramura x > 0',
                                'color' => 'brand',
                                'points' => $this->sample(fn ($x) => 1 / $x, 0.17, 4, 60),
                            ],
                        ],
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Limita există doar dacă ambele laterale coincid',
                        'text' => 'Pentru $f(x) = \\frac{1}{x}$ avem $\\lim_{x \\to 0^-} = -\\infty$ și $\\lim_{x \\to 0^+} = +\\infty$. Fiind diferite, $\\lim_{x \\to 0} \\frac{1}{x}$ **nu există**. A scrie „limita e infinit" fără a preciza latura e o greșeală punctată la examen.',
                    ],

                    ['type' => 'heading', 'text' => 'Continuitatea'],

                    ['type' => 'text', 'text' => 'O funcție e continuă într-un punct dacă poți desena graficul prin acel punct fără să ridici creionul. Formal, trebuie îndeplinite trei condiții deodată:'],

                    [
                        'type' => 'formula',
                        'latex' => 'f(a) \\ \\text{există}, \\quad \\lim_{x \\to a} f(x) \\ \\text{există}, \\quad \\lim_{x \\to a} f(x) = f(a)',
                    ],

                    ['type' => 'text', 'text' => 'Exemplul de la început pică pe prima condiție: $f(1)$ nu există. Funcția $\\frac{1}{x}$ pică în zero pe toate trei. Iar o funcție definită pe ramuri poate avea $f(a)$ și limita, dar diferite — atunci graficul are un „salt".'],

                    [
                        'type' => 'warning',
                        'title' => 'De ce contează pentru derivate',
                        'text' => 'Definiția derivatei este ea însăși o limită: $f\'(x_0) = \\lim_{h \\to 0} \\frac{f(x_0+h) - f(x_0)}{h}$. O funcție care nu e continuă într-un punct nu poate fi derivabilă acolo. De aceea limitele sunt prerechizit direct pentru derivate — nu ca formalitate, ci pentru că derivata *este* o limită.',
                    ],
                ],
            ],

            // ==================================================================
            'numere-reale' => [
                'title' => 'Numere reale și calcule',
                'summary' => 'Puteri, radicali și formulele de calcul prescurtat — verigile pe care se sprijină tot restul matematicii de liceu.',
                'objectives' => [
                    'Să aplici proprietățile puterilor și ale radicalilor',
                    'Să folosești corect formulele de calcul prescurtat',
                    'Să raționalizezi un numitor cu radical',
                    'Să eviți greșeala algebrică cea mai frecventă de la examen',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'De ce contează disproporționat'],

                    ['type' => 'text', 'text' => 'Această temă are pondere mică luată separat, dar apare în **fiecare** altă temă. O greșeală la desfacerea unei paranteze strică o derivată, o integrală sau o ecuație logaritmică — iar la barem se pierde tot pasul, nu doar calculul.'],

                    ['type' => 'text', 'text' => 'Graful de prerechizite o arată direct: „numere reale" e prerechizit pentru integrale, logaritmi, numere complexe și, indirect, pentru derivate.'],

                    ['type' => 'heading', 'text' => 'Puteri'],

                    [
                        'type' => 'formula',
                        'latex' => '\\begin{aligned} a^m \\cdot a^n &= a^{m+n} & \\frac{a^m}{a^n} &= a^{m-n} \\\\ (a^m)^n &= a^{mn} & (ab)^n &= a^n b^n \\\\ a^{-n} &= \\frac{1}{a^n} & a^0 &= 1 \\ (a \\neq 0) \\end{aligned}',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Exponentul nu se distribuie peste sumă',
                        'text' => 'Regula $(ab)^n = a^n b^n$ funcționează pentru **produs**, nu pentru sumă: $(a+b)^2 \\neq a^2 + b^2$. Verifică pe numere: $(2+3)^2 = 25$, dar $2^2 + 3^2 = 13$. E aceeași greșeală care apare mai jos, la formulele de calcul prescurtat — și e cea mai frecventă de la examen.',
                    ],

                    ['type' => 'heading', 'text' => 'Radicali'],

                    [
                        'type' => 'formula',
                        'latex' => '\\sqrt{ab} = \\sqrt{a}\\sqrt{b}, \\qquad \\sqrt{\\frac{a}{b}} = \\frac{\\sqrt{a}}{\\sqrt{b}}, \\qquad \\sqrt{a^2} = |a|',
                        'caption' => 'Atenție la ultima: rezultatul e MODULUL, nu a. Pentru a = −3, √9 = 3, nu −3.',
                    ],

                    ['type' => 'text', 'text' => 'Un radical se poate scrie ca putere cu exponent fracționar: $\\sqrt[n]{a^m} = a^{m/n}$. E util la derivate, unde $\\sqrt{x}$ se derivează mai comod scris ca $x^{1/2}$.'],

                    ['type' => 'heading', 'text' => 'Formulele de calcul prescurtat'],

                    [
                        'type' => 'formula',
                        'latex' => '\\begin{aligned} (a+b)^2 &= a^2 + 2ab + b^2 \\\\ (a-b)^2 &= a^2 - 2ab + b^2 \\\\ a^2 - b^2 &= (a-b)(a+b) \\\\ (a+b)^3 &= a^3 + 3a^2b + 3ab^2 + b^3 \\\\ a^3 - b^3 &= (a-b)(a^2+ab+b^2) \\end{aligned}',
                    ],

                    ['type' => 'text', 'text' => 'A treia, **diferența de pătrate**, e cea mai folosită la examen: apare la simplificarea fracțiilor, la limite cu nedeterminare $\\frac{0}{0}$ și la raționalizare.'],

                    [
                        'type' => 'example',
                        'title' => 'Diferența de pătrate, la limite',
                        'problem' => 'Calculează $\lim_{x \to 2} \frac{x^2 - 4}{x - 2}$.',
                        'steps' => [
                            ['text' => 'Înlocuirea directă dă $\\frac{0}{0}$ — nedeterminare.'],
                            ['text' => 'Numărătorul e o diferență de pătrate.', 'latex' => 'x^2 - 4 = (x-2)(x+2)'],
                            ['text' => 'Simplificăm cu numitorul.', 'latex' => '\\frac{(x-2)(x+2)}{x-2} = x + 2'],
                            ['text' => 'Acum înlocuirea funcționează.', 'latex' => '\\to 2 + 2 = 4'],
                        ],
                        'result' => 'O formulă din clasa a VIII-a rezolvă un item de clasa a XII-a.',
                    ],

                    ['type' => 'heading', 'text' => 'Raționalizarea numitorului'],

                    ['type' => 'text', 'text' => 'Când numitorul conține un radical, se amplifică fracția cu **conjugata** — expresia cu semnul schimbat. Diferența de pătrate face radicalul să dispară.'],

                    [
                        'type' => 'example',
                        'title' => 'Exemplu rezolvat',
                        'problem' => 'Raționalizează $\frac{1}{\sqrt{5} - 2}$.',
                        'steps' => [
                            ['text' => 'Conjugata numitorului este $\\sqrt{5} + 2$. Amplificăm cu ea.', 'latex' => '\\frac{1}{\\sqrt{5}-2} \\cdot \\frac{\\sqrt{5}+2}{\\sqrt{5}+2}'],
                            ['text' => 'La numitor aplicăm diferența de pătrate.', 'latex' => '(\\sqrt{5}-2)(\\sqrt{5}+2) = 5 - 4 = 1'],
                            ['text' => 'Rămâne doar numărătorul.', 'latex' => '= \\sqrt{5} + 2'],
                        ],
                        'result' => '$\sqrt{5} + 2$ — fără radical la numitor.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Ordinea operațiilor',
                        'text' => 'Puteri și radicali → înmulțiri și împărțiri → adunări și scăderi, iar parantezele au prioritate peste tot. Într-o expresie ca $2 + 3 \\cdot 4^2$ se calculează întâi $4^2 = 16$, apoi $3 \\cdot 16 = 48$, apoi $2 + 48 = 50$ — nu $(2+3) \\cdot 16$.',
                    ],
                ],
            ],
            // ==================================================================
            'numere-complexe' => [
                'title' => 'Numere complexe',
                'summary' => 'De ce a fost nevoie de $i$, cum se calculează cu forma algebrică și de ce împărțirea cere conjugatul.',
                'objectives' => [
                    'Să operezi cu numere complexe în forma algebrică $a + bi$',
                    'Să folosești conjugatul pentru a împărți două numere complexe',
                    'Să calculezi modulul și să-l citești ca distanță în plan',
                    'Să reduci orice putere a lui $i$ folosind ciclul de 4',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'De unde vine $i$'],

                    ['type' => 'text', 'text' => 'Ecuația $x^2 + 1 = 0$ nu are soluții reale: niciun număr real ridicat la pătrat nu dă $-1$. Matematicienii nu au ocolit problema, ci au **adăugat** un număr nou care, prin definiție, face exact asta.'],

                    ['type' => 'formula', 'latex' => 'i^2 = -1', 'caption' => 'Unitatea imaginară. Nu e o aproximare și nu e un truc — e o definiție.'],

                    ['type' => 'text', 'text' => 'Un **număr complex** se scrie $z = a + bi$, unde $a$ e partea reală, notată $\operatorname{Re}(z)$, iar $b$ e partea imaginară, $\operatorname{Im}(z)$. Atenție: partea imaginară e $b$, nu $bi$.'],

                    ['type' => 'heading', 'text' => 'Cele patru operații'],

                    [
                        'type' => 'table',
                        'caption' => 'Regulile de calcul. Singura noutate e că $i^2$ se înlocuiește cu $-1$.',
                        'headers' => ['Operație', 'Regulă', 'Exemplu'],
                        'rows' => [
                            ['Adunare', 'Se adună separat părțile', '$(2+3i) + (1-i) = 3 + 2i$'],
                            ['Scădere', 'Se scad separat părțile', '$(2+3i) - (1-i) = 1 + 4i$'],
                            ['Înmulțire', 'Ca la binoame, apoi $i^2 = -1$', '$(2+3i)(1-i) = 5 + i$'],
                            ['Împărțire', 'Se amplifică cu conjugatul numitorului', 'vezi exemplul de mai jos'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Conjugatul și modulul'],

                    ['type' => 'text', 'text' => '**Conjugatul** lui $z = a + bi$ este $\bar{z} = a - bi$ — același număr cu semnul schimbat la partea imaginară. Proprietatea care îl face util: produsul unui număr cu conjugatul lui este întotdeauna **real**.'],

                    ['type' => 'formula', 'latex' => 'z \cdot \bar{z} = (a+bi)(a-bi) = a^2 + b^2 = |z|^2', 'caption' => 'De aici vine și modulul.'],

                    ['type' => 'text', 'text' => 'Dacă reprezinți $z = a + bi$ ca punctul $(a, b)$ în plan, atunci $|z| = \sqrt{a^2 + b^2}$ este exact **distanța de la origine** la acel punct. Conjugatul e oglindirea față de axa reală, deci are același modul.'],

                    [
                        'type' => 'plot',
                        'caption' => 'Numărul $z = 3 + 4i$ și conjugatul lui $\bar{z} = 3 - 4i$ în planul complex. Ambii au modulul 5.',
                        'x_range' => [-1, 5],
                        'y_range' => [-5, 5],
                        'series' => [
                            ['label' => 'z = 3 + 4i', 'color' => 'brand', 'points' => [[0, 0], [3, 4]]],
                            ['label' => 'conjugatul', 'color' => 'amber', 'dashed' => true, 'points' => [[0, 0], [3, -4]]],
                        ],
                        'markers' => [
                            ['x' => 3, 'y' => 4, 'label' => 'z(3, 4)'],
                            ['x' => 3, 'y' => -4, 'label' => 'z̄(3, −4)'],
                        ],
                    ],

                    [
                        'type' => 'example',
                        'title' => 'Împărțirea, pas cu pas',
                        'problem' => 'Calculează $\dfrac{2 + 3i}{1 - i}$.',
                        'steps' => [
                            ['text' => 'Amplificăm fracția cu conjugatul numitorului, $1 + i$.', 'latex' => '\frac{2+3i}{1-i} \cdot \frac{1+i}{1+i}'],
                            ['text' => 'La numitor obținem un număr real: $1^2 + 1^2 = 2$.', 'latex' => '(1-i)(1+i) = 1 - i^2 = 2'],
                            ['text' => 'La numărător înmulțim ca la binoame și înlocuim $i^2 = -1$.', 'latex' => '(2+3i)(1+i) = 2 + 2i + 3i + 3i^2 = -1 + 5i'],
                            ['text' => 'Împărțim fiecare parte la 2.', 'latex' => '\frac{-1 + 5i}{2} = -\frac{1}{2} + \frac{5}{2} i'],
                        ],
                        'result' => '$-\frac{1}{2} + \frac{5}{2} i$. Verificare rapidă: înmulțind rezultatul cu $1 - i$ trebuie să obții $2 + 3i$.',
                    ],

                    ['type' => 'heading', 'text' => 'Puterile lui $i$ se repetă din 4 în 4'],

                    [
                        'type' => 'table',
                        'caption' => 'După $i^4$ ciclul o ia de la capăt. Împarte exponentul la 4 și uită-te doar la rest.',
                        'headers' => ['Putere', 'Valoare', 'Rest la împărțirea cu 4'],
                        'rows' => [
                            ['$i^1$', '$i$', '1'],
                            ['$i^2$', '$-1$', '2'],
                            ['$i^3$', '$-i$', '3'],
                            ['$i^4$', '$1$', '0'],
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Exemplu: $i^{2027}$. Împarți $2027 : 4 = 506$ rest $3$, deci $i^{2027} = i^3 = -i$. Nu e nevoie de niciun calcul mai lung.'],

                    [
                        'type' => 'warning',
                        'title' => 'Trei greșeli care costă puncte',
                        'text' => 'Prima: $\sqrt{-4}$ nu e $-2$, ci $2i$. A doua: regula $\sqrt{a}\cdot\sqrt{b} = \sqrt{ab}$ NU se aplică la numere negative — $\sqrt{-1}\cdot\sqrt{-1} = i^2 = -1$, nu $\sqrt{1} = 1$. A treia: modulul e un număr real pozitiv, deci un răspuns de forma $|z| = 3 + 4i$ e sigur greșit.',
                    ],
                ],
            ],
            // ==================================================================
            'valori-trigonometrice' => [
                'title' => 'Valori trigonometrice',
                'summary' => 'Definițiile în triunghiul dreptunghic, cercul trigonometric, valorile care trebuie știute pe de rost și semnele pe cadrane.',
                'objectives' => [
                    'Să definești sinusul, cosinusul și tangenta în triunghiul dreptunghic',
                    'Să reții valorile pentru $0°, 30°, 45°, 60°, 90°$ fără tabel',
                    'Să determini semnul unei funcții trigonometrice după cadran',
                    'Să treci corect între grade și radiani',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Definițiile din triunghiul dreptunghic'],

                    ['type' => 'text', 'text' => 'Într-un triunghi dreptunghic, pentru un unghi ascuțit $\alpha$, cele trei rapoarte care contează sunt:'],

                    ['type' => 'formula', 'latex' => '\sin \alpha = \frac{\text{opusă}}{\text{ipotenuză}}, \quad \cos \alpha = \frac{\text{alăturată}}{\text{ipotenuză}}, \quad \operatorname{tg} \alpha = \frac{\text{opusă}}{\text{alăturată}}', 'caption' => 'Sinus și cosinus se raportează la ipotenuză; tangenta, la cealaltă catetă.'],

                    ['type' => 'text', 'text' => 'De aici iese imediat o relație pe care o vei folosi tot timpul: $\operatorname{tg} \alpha = \dfrac{\sin \alpha}{\cos \alpha}$, pentru că ipotenuza se simplifică.'],

                    ['type' => 'heading', 'text' => 'Cercul trigonometric'],

                    ['type' => 'text', 'text' => 'Definiția din triunghi merge doar pentru unghiuri ascuțite. Cercul de rază 1 centrat în origine le extinde la orice unghi: dacă rotești o rază cu unghiul $\alpha$, capătul ei cade în punctul de coordonate $(\cos \alpha, \sin \alpha)$.'],

                    [
                        'type' => 'plot',
                        'caption' => 'Cercul trigonometric. Pentru unghiul de 60°, capătul razei este în punctul $(0{,}5;\ 0{,}866)$ — adică $\cos 60° = \frac{1}{2}$ și $\sin 60° = \frac{\sqrt{3}}{2}$.',
                        'x_range' => [-1.3, 1.3],
                        'y_range' => [-1.3, 1.3],
                        'series' => [
                            ['label' => 'cercul de rază 1', 'color' => 'brand', 'points' => $this->sample(fn ($x) => sqrt(max(0, 1 - $x ** 2)), -1, 1, 80)],
                            ['label' => '', 'color' => 'brand', 'points' => $this->sample(fn ($x) => -sqrt(max(0, 1 - $x ** 2)), -1, 1, 80)],
                            ['label' => 'raza la 60°', 'color' => 'amber', 'dashed' => true, 'points' => [[0, 0], [0.5, 0.866]]],
                        ],
                        'markers' => [['x' => 0.5, 'y' => 0.866, 'label' => '60°']],
                    ],

                    ['type' => 'heading', 'text' => 'Valorile care se învață pe de rost'],

                    [
                        'type' => 'table',
                        'caption' => 'Trucul de memorare: la sinus, numărătorii sunt $\sqrt{0}, \sqrt{1}, \sqrt{2}, \sqrt{3}, \sqrt{4}$, toți împărțiți la 2. Cosinusul e aceeași listă citită invers.',
                        'headers' => ['Unghi', '$0°$', '$30°$', '$45°$', '$60°$', '$90°$'],
                        'rows' => [
                            ['Radiani', '$0$', '$\frac{\pi}{6}$', '$\frac{\pi}{4}$', '$\frac{\pi}{3}$', '$\frac{\pi}{2}$'],
                            ['$\sin$', '$0$', '$\frac{1}{2}$', '$\frac{\sqrt{2}}{2}$', '$\frac{\sqrt{3}}{2}$', '$1$'],
                            ['$\cos$', '$1$', '$\frac{\sqrt{3}}{2}$', '$\frac{\sqrt{2}}{2}$', '$\frac{1}{2}$', '$0$'],
                            ['$\operatorname{tg}$', '$0$', '$\frac{\sqrt{3}}{3}$', '$1$', '$\sqrt{3}$', 'nedefinit'],
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Trecerea între unități se face cu o singură egalitate: $180° = \pi$ radiani. Deci din grade în radiani înmulțești cu $\frac{\pi}{180}$, iar invers, cu $\frac{180}{\pi}$.'],

                    ['type' => 'heading', 'text' => 'Semnele pe cadrane'],

                    ['type' => 'text', 'text' => 'Pe cercul trigonometric, cosinusul este coordonata $x$, iar sinusul, coordonata $y$. Semnul lor e pur și simplu semnul coordonatei în cadranul respectiv — nu trebuie memorat separat, se citește de pe desen.'],

                    [
                        'type' => 'table',
                        'caption' => 'Tangenta e pozitivă acolo unde sinusul și cosinusul au același semn.',
                        'headers' => ['Cadran', 'Unghiuri', '$\sin$', '$\cos$', '$\operatorname{tg}$'],
                        'rows' => [
                            ['I', '$0° - 90°$', '+', '+', '+'],
                            ['II', '$90° - 180°$', '+', '−', '−'],
                            ['III', '$180° - 270°$', '−', '−', '+'],
                            ['IV', '$270° - 360°$', '−', '+', '−'],
                        ],
                    ],

                    [
                        'type' => 'example',
                        'title' => 'Reducerea la primul cadran',
                        'problem' => 'Calculează $\sin 150°$.',
                        'steps' => [
                            ['text' => '$150°$ este în cadranul II, deci sinusul e pozitiv.'],
                            ['text' => 'Scriem unghiul ca $180° - 30°$ și folosim formula de reducere.', 'latex' => '\sin(180° - \alpha) = \sin \alpha'],
                            ['text' => 'Rămâne valoarea cunoscută pentru $30°$.', 'latex' => '\sin 150° = \sin 30° = \frac{1}{2}'],
                        ],
                        'result' => '$\frac{1}{2}$. Aceeași metodă merge pentru orice unghi: îl aduci în primul cadran și pui semnul dat de cadranul inițial.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Calculatorul în grade, nu în radiani',
                        'text' => 'Dacă un calculator îți dă $\sin 30 = -0{,}988$, e setat pe radiani. La BAC nu ai calculator, dar când exersezi acasă verifică modul **DEG**. Și încă ceva: $\sin^2 \alpha$ înseamnă $(\sin \alpha)^2$, nu $\sin(\alpha^2)$.',
                    ],
                ],
            ],

            // ==================================================================
            'identitati-trigonometrice' => [
                'title' => 'Identități trigonometrice',
                'summary' => 'Identitatea fundamentală, formulele pentru sumă și unghi dublu și cum alegi formula potrivită într-o problemă.',
                'objectives' => [
                    'Să folosești identitatea fundamentală în ambele sensuri',
                    'Să aplici formulele pentru $\sin(a \pm b)$ și $\cos(a \pm b)$',
                    'Să recunoști când o expresie cere formula unghiului dublu',
                    'Să eviți confuzia dintre $\cos(a+b)$ și $\cos a + \cos b$',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Identitatea fundamentală'],

                    ['type' => 'text', 'text' => 'Pe cercul de rază 1, punctul $(\cos \alpha, \sin \alpha)$ se află la distanța 1 de origine. Teorema lui Pitagora aplicată acolo dă direct cea mai folosită relație din trigonometrie:'],

                    ['type' => 'formula', 'latex' => '\sin^2 \alpha + \cos^2 \alpha = 1', 'caption' => 'Adevărată pentru ORICE unghi, fără excepții și fără condiții.'],

                    ['type' => 'text', 'text' => 'Se folosește în ambele sensuri. Din stânga spre dreapta, ca să scapi de o sumă de pătrate. Din dreapta spre stânga, ca să înlocuiești un $1$ inutil cu ceva care se simplifică: dacă ai $1 - \cos^2 \alpha$, îl scrii direct $\sin^2 \alpha$.'],

                    ['type' => 'heading', 'text' => 'Sumă și diferență de unghiuri'],

                    [
                        'type' => 'table',
                        'caption' => 'La cosinus semnul se INVERSEAZĂ față de cel din paranteză. E singurul loc unde se greșește sistematic.',
                        'headers' => ['Expresie', 'Formula'],
                        'rows' => [
                            ['$\sin(a + b)$', '$\sin a \cos b + \cos a \sin b$'],
                            ['$\sin(a - b)$', '$\sin a \cos b - \cos a \sin b$'],
                            ['$\cos(a + b)$', '$\cos a \cos b - \sin a \sin b$'],
                            ['$\cos(a - b)$', '$\cos a \cos b + \sin a \sin b$'],
                        ],
                    ],

                    ['type' => 'text', 'text' => 'Sunt utile și pentru unghiuri care nu sunt în tabel. De exemplu $75° = 45° + 30°$, deci $\sin 75°$ se calculează exact, fără aproximare.'],

                    ['type' => 'heading', 'text' => 'Unghiul dublu'],

                    ['type' => 'text', 'text' => 'Dacă în formulele de mai sus pui $b = a$, obții formulele pentru unghiul dublu. Nu sunt reguli noi de memorat — sunt aceleași formule, cu un caz particular.'],

                    ['type' => 'formula', 'latex' => '\sin 2a = 2 \sin a \cos a, \qquad \cos 2a = \cos^2 a - \sin^2 a', 'caption' => 'Folosind identitatea fundamentală, cosinusul dublu se mai scrie $1 - 2\sin^2 a$ sau $2\cos^2 a - 1$.'],

                    ['type' => 'text', 'text' => 'Cele trei forme ale lui $\cos 2a$ nu sunt redundante: alegi forma care conține funcția pe care o ai deja în problemă. Dacă în enunț apare doar $\sin a$, folosești $1 - 2\sin^2 a$ și scapi de cosinus complet.'],

                    [
                        'type' => 'example',
                        'title' => 'Exemplu rezolvat',
                        'problem' => 'Se știe că $\sin \alpha = \frac{3}{5}$, cu $\alpha$ în cadranul I. Calculează $\sin 2\alpha$.',
                        'steps' => [
                            ['text' => 'Avem nevoie de cosinus. Îl scoatem din identitatea fundamentală.', 'latex' => '\cos^2 \alpha = 1 - \left(\frac{3}{5}\right)^2 = \frac{16}{25}'],
                            ['text' => 'În cadranul I cosinusul e pozitiv, deci alegem rădăcina pozitivă.', 'latex' => '\cos \alpha = \frac{4}{5}'],
                            ['text' => 'Aplicăm formula unghiului dublu.', 'latex' => '\sin 2\alpha = 2 \cdot \frac{3}{5} \cdot \frac{4}{5} = \frac{24}{25}'],
                        ],
                        'result' => '$\frac{24}{25}$. Pasul care se punctează separat la BAC e alegerea semnului la cosinus — fără justificarea cadranului, punctul se pierde.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Sinusul nu e o funcție liniară',
                        'text' => '$\sin(a + b) \neq \sin a + \sin b$. Verifică singur cu $a = b = 30°$: stânga dă $\sin 60° = 0{,}866$, dreapta dă $0{,}5 + 0{,}5 = 1$. Aceeași capcană la $\cos$, la $\operatorname{tg}$ și la logaritmi.',
                    ],
                ],
            ],
            // ==================================================================
            'triunghiuri' => [
                'title' => 'Triunghiuri',
                'summary' => 'Ariile, teorema lui Pitagora, teorema sinusurilor și a cosinusului — și cum alegi între ele după ce ți se dă în enunț.',
                'objectives' => [
                    'Să alegi formula de arie potrivită datelor din enunț',
                    'Să aplici teorema sinusurilor și teorema cosinusului',
                    'Să recunoști triunghiurile particulare și să scurtezi calculul',
                    'Să verifici dacă trei lungimi pot forma un triunghi',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Trei formule de arie, trei situații'],

                    ['type' => 'text', 'text' => 'Nu există „formula ariei" — există trei, iar alegerea depinde exclusiv de ce ți se dă. Citește enunțul și alege formula care folosește exact datele primite, fără să calculezi altceva în plus.'],

                    [
                        'type' => 'table',
                        'caption' => 'La formula lui Heron, $p = \frac{a+b+c}{2}$ este semiperimetrul.',
                        'headers' => ['Ce ai în enunț', 'Formula', 'Când o folosești'],
                        'rows' => [
                            ['O latură și înălțimea pe ea', '$S = \frac{b \cdot h}{2}$', 'Cazul obișnuit, cu figura dată'],
                            ['Două laturi și unghiul dintre ele', '$S = \frac{1}{2} a b \sin C$', 'Când unghiul e între laturile date'],
                            ['Toate cele trei laturi', '$S = \sqrt{p(p-a)(p-b)(p-c)}$', 'Heron — fără niciun unghi'],
                        ],
                    ],

                    ['type' => 'warning', 'title' => 'Unghiul trebuie să fie ÎNTRE laturi', 'text' => 'Formula $S = \frac{1}{2} a b \sin C$ funcționează numai dacă $C$ e unghiul cuprins între laturile $a$ și $b$. Cu un unghi alăturat rezultatul e pur și simplu greșit, iar la corectare se pierde tot punctajul pe subpunct.'],

                    ['type' => 'heading', 'text' => 'Triunghiul dreptunghic'],

                    ['type' => 'formula', 'latex' => 'a^2 + b^2 = c^2', 'caption' => 'Teorema lui Pitagora: $c$ este ipotenuza, adică latura opusă unghiului drept — întotdeauna cea mai lungă.'],

                    ['type' => 'text', 'text' => 'Merită să reții tripletele care apar cel mai des, ca să le recunoști instant: $(3, 4, 5)$, $(5, 12, 13)$, $(8, 15, 17)$ și toți multiplii lor, de exemplu $(6, 8, 10)$.'],

                    [
                        'type' => 'table',
                        'caption' => 'Triunghiurile particulare: aici nu ai nevoie de nicio teoremă, doar de raporturi.',
                        'headers' => ['Triunghi', 'Laturi', 'Arie'],
                        'rows' => [
                            ['Echilateral, latura $a$', 'toate egale', '$\frac{a^2 \sqrt{3}}{4}$'],
                            ['Dreptunghic $30°-60°-90°$', '$a,\ a\sqrt{3},\ 2a$', '$\frac{a^2\sqrt{3}}{2}$'],
                            ['Dreptunghic isoscel $45°-45°-90°$', '$a,\ a,\ a\sqrt{2}$', '$\frac{a^2}{2}$'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Când triunghiul nu e dreptunghic'],

                    ['type' => 'text', 'text' => 'Pitagora nu se mai aplică. Ai două teoreme, iar alegerea se face după ce lipsește:'],

                    ['type' => 'formula', 'latex' => '\frac{a}{\sin A} = \frac{b}{\sin B} = \frac{c}{\sin C} = 2R', 'caption' => 'Teorema sinusurilor. Leagă o latură de unghiul OPUS ei; $R$ e raza cercului circumscris.'],

                    ['type' => 'formula', 'latex' => 'a^2 = b^2 + c^2 - 2bc \cos A', 'caption' => 'Teorema cosinusului. Pentru $A = 90°$, $\cos A = 0$ și rămâne exact Pitagora.'],

                    ['type' => 'text', 'text' => 'Regula practică: dacă în problemă apar **perechi latură–unghi opus**, folosești teorema sinusurilor. Dacă ai **două laturi și unghiul dintre ele**, sau **toate trei laturile**, folosești teorema cosinusului.'],

                    [
                        'type' => 'example',
                        'title' => 'Exemplu rezolvat',
                        'problem' => 'În triunghiul $ABC$: $b = 5$, $c = 8$, $\widehat{A} = 60°$. Calculează latura $a$ și aria.',
                        'steps' => [
                            ['text' => 'Avem două laturi și unghiul dintre ele, deci teorema cosinusului.', 'latex' => 'a^2 = 5^2 + 8^2 - 2 \cdot 5 \cdot 8 \cdot \cos 60°'],
                            ['text' => 'Înlocuim $\cos 60° = \frac{1}{2}$.', 'latex' => 'a^2 = 25 + 64 - 40 = 49'],
                            ['text' => 'Extragem rădăcina; lungimea e pozitivă.', 'latex' => 'a = 7'],
                            ['text' => 'Pentru arie folosim aceleași date, cu formula cu sinus.', 'latex' => 'S = \frac{1}{2} \cdot 5 \cdot 8 \cdot \frac{\sqrt{3}}{2} = 10\sqrt{3}'],
                        ],
                        'result' => '$a = 7$ și $S = 10\sqrt{3} \approx 17{,}32$.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Verifică dacă triunghiul există',
                        'text' => 'Trei lungimi formează un triunghi doar dacă fiecare e mai mică decât suma celorlalte două. Cu $2, 3, 9$ nu se poate construi nimic, pentru că $2 + 3 < 9$. Dacă un calcul îți dă un asemenea set, ai greșit undeva mai devreme.',
                    ],
                ],
            ],

            // ==================================================================
            'cerc' => [
                'title' => 'Cercul',
                'summary' => 'Lungime și arie, arce și sectoare, unghi la centru față de unghi înscris, plus proprietatea tangentei.',
                'objectives' => [
                    'Să calculezi lungimea cercului și aria discului',
                    'Să afli lungimea unui arc și aria unui sector',
                    'Să folosești relația dintre unghiul la centru și cel înscris',
                    'Să aplici perpendicularitatea tangentei pe rază',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Cele două formule de bază'],

                    ['type' => 'text', 'text' => 'Se confundă des, pentru că seamănă. Ține minte unitatea de măsură: lungimea se măsoară în centimetri, deci raza apare la puterea întâi; aria se măsoară în centimetri pătrați, deci raza apare la pătrat.'],

                    ['type' => 'formula', 'latex' => 'L = 2\pi r \qquad \text{(lungimea cercului)}', 'caption' => 'Conturul — o linie.'],

                    ['type' => 'formula', 'latex' => 'A = \pi r^2 \qquad \text{(aria discului)}', 'caption' => 'Suprafața dinăuntru.'],

                    [
                        'type' => 'plot',
                        'caption' => 'Cercul de rază 2 centrat în origine. Raza, coarda și tangenta sunt cele trei drepte care apar în majoritatea problemelor.',
                        'x_range' => [-2.6, 2.6],
                        'y_range' => [-2.6, 2.6],
                        'series' => [
                            ['label' => 'cercul, r = 2', 'color' => 'brand', 'points' => $this->sample(fn ($x) => sqrt(max(0, 4 - $x ** 2)), -2, 2, 80)],
                            ['label' => '', 'color' => 'brand', 'points' => $this->sample(fn ($x) => -sqrt(max(0, 4 - $x ** 2)), -2, 2, 80)],
                            ['label' => 'raza', 'color' => 'emerald', 'points' => [[0, 0], [2, 0]]],
                            ['label' => 'tangenta în (2, 0)', 'color' => 'amber', 'dashed' => true, 'points' => [[2, -2.4], [2, 2.4]]],
                        ],
                        'markers' => [['x' => 2, 'y' => 0, 'label' => 'punct de tangență']],
                    ],

                    ['type' => 'heading', 'text' => 'Arce și sectoare: totul e o fracție din întreg'],

                    ['type' => 'text', 'text' => 'Un arc de $\alpha$ grade e fracția $\frac{\alpha}{360}$ din cerc. Nu memora formule separate — înmulțește formula întregului cu această fracție.'],

                    [
                        'type' => 'table',
                        'caption' => 'Aceeași fracție, aplicată o dată lungimii și o dată ariei.',
                        'headers' => ['Mărime', 'Întregul cerc', 'Porțiunea de $\alpha°$'],
                        'rows' => [
                            ['Lungime', '$2\pi r$', '$\frac{\alpha}{360} \cdot 2\pi r$'],
                            ['Arie', '$\pi r^2$', '$\frac{\alpha}{360} \cdot \pi r^2$'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Unghi la centru și unghi înscris'],

                    ['type' => 'text', 'text' => 'Unghiul cu vârful în centru și unghiul cu vârful pe cerc care se sprijină pe **același arc** nu sunt egale. Cel de la centru e întotdeauna dublul celui înscris.'],

                    ['type' => 'formula', 'latex' => '\widehat{\text{centru}} = 2 \cdot \widehat{\text{înscris}}', 'caption' => 'De aici iese și rezultatul folosit cel mai des: un unghi înscris care se sprijină pe diametru are 90°.'],

                    ['type' => 'text', 'text' => 'Consecința aceasta merită reținută separat: dacă un triunghi are o latură care e diametru, atunci el este dreptunghic, iar unghiul drept e cel opus diametrului.'],

                    ['type' => 'text', 'text' => 'A doua proprietate care apare constant: **tangenta e perpendiculară pe rază** în punctul de tangență. Practic, orice problemă cu tangentă conține un triunghi dreptunghic ascuns.'],

                    [
                        'type' => 'example',
                        'title' => 'Exemplu rezolvat',
                        'problem' => 'Un sector de cerc are raza $6$ cm și unghiul la centru $120°$. Calculează lungimea arcului și aria sectorului.',
                        'steps' => [
                            ['text' => 'Sectorul reprezintă $\frac{120}{360} = \frac{1}{3}$ din cerc.'],
                            ['text' => 'Lungimea întregului cerc este $2\pi \cdot 6 = 12\pi$, deci arcul e o treime.', 'latex' => '\ell = \frac{1}{3} \cdot 12\pi = 4\pi'],
                            ['text' => 'Aria discului este $\pi \cdot 6^2 = 36\pi$, deci sectorul e tot o treime.', 'latex' => 'A = \frac{1}{3} \cdot 36\pi = 12\pi'],
                        ],
                        'result' => 'Arc $4\pi \approx 12{,}57$ cm; arie $12\pi \approx 37{,}70$ cm². Observă că răspunsurile se lasă cu $\pi$ — nu aproxima decât dacă enunțul cere.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Rază sau diametru?',
                        'text' => 'Cea mai frecventă greșeală nu e la formulă, ci la citirea enunțului: dacă se dă diametrul $d = 10$, atunci $r = 5$. Folosind $10$ în loc de $5$, aria iese de patru ori mai mare. Subliniază în enunț ce ți s-a dat, înainte să începi calculul.',
                    ],
                ],
            ],

            // ==================================================================
            'corpuri' => [
                'title' => 'Corpuri geometrice',
                'summary' => 'Volume și arii pentru prismă, piramidă, cilindru, con și sferă — cu accent pe diferența dintre înălțime și apotemă.',
                'objectives' => [
                    'Să calculezi volumul celor cinci corpuri din programă',
                    'Să distingi aria laterală de aria totală',
                    'Să nu confunzi înălțimea cu apotema sau cu generatoarea',
                    'Să identifici triunghiul dreptunghic ascuns în fiecare corp',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'O singură idee pentru volume'],

                    ['type' => 'text', 'text' => 'Corpurile „drepte" — prisma și cilindrul — au volumul $V = A_b \cdot h$: aria bazei înmulțită cu înălțimea. Cele „ascuțite" — piramida și conul — au exact **o treime** din asta, cu aceeași bază și aceeași înălțime. Nu sunt formule diferite, e aceeași formulă cu un factor $\frac{1}{3}$.'],

                    [
                        'type' => 'table',
                        'caption' => 'La con, $G$ este generatoarea (muchia oblică), nu înălțimea. La sferă nu există bază, deci nici arie laterală separată.',
                        'headers' => ['Corp', 'Volum', 'Aria laterală', 'Aria totală'],
                        'rows' => [
                            ['Prismă dreaptă', '$A_b \cdot h$', '$P_b \cdot h$', '$A_l + 2A_b$'],
                            ['Piramidă', '$\frac{A_b \cdot h}{3}$', '$\frac{P_b \cdot a_p}{2}$', '$A_l + A_b$'],
                            ['Cilindru', '$\pi r^2 h$', '$2\pi r h$', '$A_l + 2\pi r^2$'],
                            ['Con', '$\frac{\pi r^2 h}{3}$', '$\pi r G$', '$A_l + \pi r^2$'],
                            ['Sferă', '$\frac{4\pi r^3}{3}$', '—', '$4\pi r^2$'],
                        ],
                    ],

                    ['type' => 'heading', 'text' => 'Înălțime, apotemă, generatoare'],

                    ['type' => 'text', 'text' => 'Aici se pierd cele mai multe puncte la geometria în spațiu. Cele trei mărimi sunt segmente diferite, iar formulele nu sunt interschimbabile.'],

                    [
                        'type' => 'table',
                        'caption' => 'Toate trei pornesc din vârf, dar cad în locuri diferite.',
                        'headers' => ['Mărime', 'Notație', 'Unde cade'],
                        'rows' => [
                            ['Înălțimea', '$h$', 'În centrul bazei, perpendicular pe ea'],
                            ['Apotema piramidei', '$a_p$', 'La mijlocul unei muchii a bazei'],
                            ['Generatoarea conului', '$G$', 'Pe cercul bazei'],
                        ],
                    ],

                    ['type' => 'text', 'text' => 'La con cele trei mărimi formează un triunghi dreptunghic cu catetele $r$ și $h$ și ipotenuza $G$, deci $G^2 = r^2 + h^2$. Dacă enunțul îți dă două dintre ele, a treia se află întotdeauna cu Pitagora.'],

                    ['type' => 'formula', 'latex' => 'G^2 = r^2 + h^2', 'caption' => 'Relația de care ai nevoie în aproape orice problemă cu con.'],

                    [
                        'type' => 'example',
                        'title' => 'Exemplu rezolvat',
                        'problem' => 'Un con are raza bazei $3$ cm și înălțimea $4$ cm. Calculează volumul și aria totală.',
                        'steps' => [
                            ['text' => 'Volumul folosește înălțimea, nu generatoarea.', 'latex' => 'V = \frac{\pi \cdot 3^2 \cdot 4}{3} = 12\pi'],
                            ['text' => 'Pentru aria laterală ne trebuie generatoarea. O aflăm cu Pitagora.', 'latex' => 'G = \sqrt{3^2 + 4^2} = 5'],
                            ['text' => 'Aria laterală se calculează cu generatoarea.', 'latex' => 'A_l = \pi \cdot 3 \cdot 5 = 15\pi'],
                            ['text' => 'Adăugăm baza, care e un disc de rază 3.', 'latex' => 'A_t = 15\pi + 9\pi = 24\pi'],
                        ],
                        'result' => '$V = 12\pi$ cm³ și $A_t = 24\pi$ cm². Triunghiul $(3, 4, 5)$ apare des în aceste probleme — merită recunoscut din prima.',
                    ],

                    [
                        'type' => 'warning',
                        'title' => 'Unitățile de măsură se punctează',
                        'text' => 'Volumul se exprimă în cm³, aria în cm², lungimea în cm. Un rezultat numeric corect cu unitatea greșită pierde punct la corectare. Și verifică transformările: $1$ dm³ $= 1000$ cm³, nu $100$.',
                    ],
                ],
            ],
        ];
    }
}
