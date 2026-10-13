<?php

namespace Database\Seeders\Informatica;

/**
 * Testele ANCE de informatică din 2026 (12_inf_test_r_ro_sb26.pdf,
 * 12_inf_test_r_ro_ss26.pdf și baremele cu același nume).
 */
final class Ance2026
{
    use AnceItems;

    /** @return list<array<string, mixed>> */
    public static function items(): array
    {
        return [...self::baza(), ...self::suplimentara()];
    }

    private static function baza(): array
    {
        $d = fn (string $n) => self::doc('sb26', $n);

        return [
            self::num($d('I.1a'), 'cantitatea-informatie', 4,
                'La înregistrarea într-un sistem de calcul utilizatorii primesc câte o parolă care conține 16 simboluri distincte dintr-un set de 32 simboluri. Aceste 32 de simboluri se consideră mesaje ale unei surse de informație care au fost codificate cu ajutorul cuvintelor binare de lungime minimă egală (în biți). Pentru păstrarea tuturor parolelor utilizatorilor înregistrați într-o anumită perioadă au fost rezervați 12 KB. Determinați numărul de parole care pot fi păstrate în cei 12 KB.',
                1228,
                [
                    'Cantitatea de informație a unui mesaj al unei surse cu $n$ mesaje: $I = \log_2 n$.',
                    'Un simbol ocupă $\log_2 32 = 5$ biți, deci o parolă: $16 \cdot 5 = 80$ biți $= 10$ B.',
                    '$12$ KB $= 12 \cdot 1024 = 12288$ B. Câte parole întregi de 10 B încap?',
                ],
                'O parolă ocupă $16 \cdot \log_2 32 = 80$ biți $= 10$ B. $12$ KB $= 12288$ B, iar $12288 : 10 = 1228{,}8$, deci încap 1228 de parole întregi.',
                'parole'),

            self::num($d('I.1b'), 'cantitatea-informatie', 5,
                'Un tutorial video are durata de 32 minute, frecvența de 25 cadre pe secundă și cantitatea de informație a unui cadru egală aproximativ cu 8 Mbiți. Determinați cantitatea de informație în Gigabiți (Gbiți) care se conține în tutorialul dat.',
                375,
                [
                    'Formula: $V = T \cdot F \cdot I$ — durata în secunde × cadre pe secundă × informația unui cadru.',
                    '$V = 32 \cdot 60 \cdot 25 \cdot 8$ Mbiți.',
                    '$V = 384000$ Mbiți; 1 Gbit $= 1024$ Mbiți.',
                ],
                '$V = 32 \cdot 60 \cdot 25 \cdot 8 = 375 \cdot 2^{10}$ Mbiți, adică $375$ Gbiți.',
                'Gbiți'),

            self::choice($d('I.2a'), 'codificare', 1,
                'Bifați răspunsul corect pentru afirmația: „Codurile ASCII extinse sunt 10-poziționale.”',
                ['Fals', 'Adevărat'],
                [
                    'Câte simboluri are tabelul ASCII extins?',
                    'Are 256 de simboluri, iar $256 = 2^8$.',
                    'Un cod ASCII extins are deci 8 poziții (biți), nu 10.',
                ],
                'Codul ASCII extins are 256 de simboluri, deci cuvinte de cod de 8 poziții: afirmația e falsă.'),

            self::order($d('I.2b'), 'sisteme-numeratie', 3,
                'Scrieți următoarele numere în ordine descrescătoare: $(43{,}5)_{16}$, $(1100100{,}011)_2$, $(38{,}125)_{10}$, $(47{,}25)_8$.',
                ['$(1100100{,}011)_2$', '$(43{,}5)_{16}$', '$(47{,}25)_8$', '$(38{,}125)_{10}$'],
                [
                    'Adu toate numerele în aceeași bază — baza 10 sau baza 2.',
                    '$(43{,}5)_{16} = 67{,}3125$, $(1100100{,}011)_2 = 100{,}375$.',
                    '$(47{,}25)_8 = 39{,}328125$, iar $(38{,}125)_{10}$ rămâne ca atare.',
                ],
                'În baza 10: $(1100100{,}011)_2 = 100{,}375$, $(43{,}5)_{16} = 67{,}3125$, $(47{,}25)_8 = 39{,}328125$, $(38{,}125)_{10}$. Ordinea descrescătoare urmează aceste valori.'),

            self::blanks($d('I.2b-conversie'), 'conversii-baze', 2,
                'Realizați conversiunea numărului $(38{,}125)_{10}$ în sistemul binar de numerație.',
                ['b' => ['$(38{,}125)_{10} = (\ldots)_2$', ['100110,001', '100110.001']]],
                [
                    'Partea întreagă: împarte repetat la 2 și citește resturile de jos în sus.',
                    '$38 = (100110)_2$.',
                    'Partea fracționară: înmulțește repetat cu 2 — $0{,}125 \to 0{,}25 \to 0{,}5 \to 1{,}0$.',
                ],
                '$38 = (100110)_2$ (resturile 0, 1, 1, 0, 0, 1 citite invers), iar $0{,}125 = (0{,}001)_2$. Deci $(38{,}125)_{10} = (100110{,}001)_2$.'),

            self::choice($d('I.3c'), 'circuite-logice', 2,
                'Care dintre următoarele circuite este combinațional? (La examen se bifează ambele circuite combinaționale din listă; mai mult de două bife anulează punctele.)',
                ['Multiplexorul', 'Registrul de deplasare', 'Sumatorul', 'Bistabilul RS', 'Numărătorul direct'],
                [
                    'Un circuit combinațional nu are memorie: ieșirile depind doar de intrările din acel moment.',
                    'Registrele, bistabilele și numărătoarele păstrează o stare — sunt secvențiale.',
                    'Au rămas două circuite fără memorie.',
                ],
                'Multiplexorul și sumatorul sunt combinaționale; registrul de deplasare, bistabilul RS și numărătorul memorează o stare, deci sunt secvențiale.',
                [0, 2]),

            self::blanks($d('II.2'), 'instructiuni', 9,
                'Scrieți ce va afișa programul pr2 pentru fiecare set de valori ale variabilelor op, a și b. (Valoarea lui y se afișează cu două zecimale.)',
                [
                    'px' => ["op = '+'; a = 10.5; b = -5.7 — x", '4'],
                    'py' => ['… y', '0.15'],
                    'mx' => ["op = '-'; a = 10.5; b = -5.7 — x", '15'],
                    'my' => ['… y', '7.00'],
                    'ox' => ["op = '*'; a = 2.5; b = -3.3 — x", '-8'],
                    'oy' => ['… y', '-8.25'],
                    'rx' => ["op = '%'; a = 27.5; b = 5.2 — x", '2'],
                    'ry' => ['… y', '0.15'],
                    'dx' => ["op = '/'; a = 1.2; b = -3.4 — x", '0'],
                    'dy' => ['… y', '0.15'],
                ],
                [
                    'Pentru fiecare set, urmărește o singură ramură: y pleacă de la 0.15 și se schimbă doar în ramurile „-" și „*".',
                    'trunc / conversia la int taie zecimalele spre zero: trunc(-8.25) = -8, iar trunc(b) pentru b = -5.7 este -5.',
                    'Pentru „-": x = trunc(10.5 - (-5)) = 15, apoi y = x div 2 = 7. Pentru „/" se execută ramura else / default.',
                ],
                'Rezultatele din barem: „+" → x=4, y=0.15; „-" → x=15, y=7.00; „*" → x=-8, y=-8.25; „%" → x=2, y=0.15; „/" (ramura else/default) → x=0, y=0.15.',
                <<<'PAS'
Program pr2;
var a, b, y : real;
    x : integer;
    op : char;
begin
  y := 0.15;
  read(op, a, b);
  case (op) of
    '+': x := trunc(a + b);
    '-': begin x := trunc(a - trunc(b)); y := x div 2; end;
    '*': begin y := a * b; x := trunc(y); end;
    '%': x := trunc(a) mod trunc(b);
    else x := trunc(y);
  end;
  write('x=', x, ' ', 'y=', y:0:2);
end.
PAS,
                <<<'CPP'
#include <iostream>
#include <iomanip>
using namespace std;
int main() {
  float a, b, y = 0.15;
  int x;
  char op;
  cin >> op >> a >> b;
  switch (op) {
    case '+': x = int(a + b); break;
    case '-': x = a - int(b); y = x / 2; break;
    case '*': y = a * b; x = y; break;
    case '%': x = int(a) % int(b); break;
    default: x = y;
  }
  cout << "x=" << x << " " << "y=" << fixed << setprecision(2) << y;
  return 0;
}
CPP),

            self::blanks($d('II.3b'), 'instructiuni', 2,
                'Scrieți ce se va afișa în rezultatul execuției programului dat.',
                ['v1' => ['primul număr afișat', '10'], 'v2' => ['al doilea număr afișat', '7']],
                [
                    'Atenție: k crește la fiecare pas, iar i scade — condiția ciclului se apropie din ambele părți.',
                    'Iterații: (i=10, k=3), (i=9, k=4), (i=8, k=5), (i=7, k=6). Pentru i=6, k=7 — ciclul se oprește.',
                    'Se afișează doar i cu i mod 4 > 1: 10 mod 4 = 2, 9 mod 4 = 1, 8 mod 4 = 0, 7 mod 4 = 3.',
                ],
                'Se execută pașii pentru i = 10, 9, 8, 7 (k crește în paralel), iar condiția i mod 4 > 1 e adevărată pentru 10 și 7: se afișează „10 7".',
                <<<'PAS'
Program pp;
var i, k: integer;
begin
  k := 3;
  for i := 10 downto k do
  begin
    if i > k then
      if i mod 4 > 1 then
        write(i, ' ');
    k := k+1;
  end;
end.
PAS,
                <<<'CPP'
#include <iostream>
using namespace std;
int main()
{
  int i, k=3;
  for(i=10; i > k; i--) {
    if(i % 4 > 1)
      cout << i << ' ';
    k++;
  }
  return 0;
}
CPP),

            self::blanks($d('III.1'), 'subprograme', 6,
                'Analizați programul pr1 și completați:',
                [
                    'a' => ['a) numărul parametrilor formali', '3'],
                    'c' => ['c) instrucțiunea repetitivă cu test inițial', ['while']],
                    'd' => ['d) „pr1 conține comunicarea prin variabile globale"', ['Adevărat'], ['Adevărat', 'Fals']],
                    'e' => ['e) valoarea lui t[9]', '9'],
                    'f1' => ['f) primul număr afișat', '43'],
                    'f2' => ['f) al doilea număr afișat', '13'],
                ],
                [
                    'Parametrii formali sunt cei din antete: q la fs, a și b la xx.',
                    'Procedura xx modifică doar t[34] și t[35]; restul tabloului rămâne t[i] = i. Tabloul t e global.',
                    't[34] (par) devine 4*10 + 3 = 43; t[35] (impar) devine suma divizorilor proprii ai lui 35: 1 + 5 + 7 = 13.',
                ],
                'Parametri formali: q, a, b (3); instrucțiunea cu test inițial: while; t e variabilă globală folosită în xx (Adevărat); t[9] = 9 nu se modifică; se afișează „43 13".',
                <<<'PAS'
Program pr1;
var t : array[1..40] of integer;
    i : integer;
function fs( q : integer ): integer;
  var nr, j, c : integer;
begin
  nr := 0; j := 1; c := q div 2;
  while j <= c do
  begin
    if q mod j = 0 then nr := nr + j;
    j := j + 1;
  end;
  fs := nr;
end;
procedure xx ( a, b : integer);
  var k : integer;
begin
  for k := a to b do
    if k mod 2 <> 0 then t[k] := fs(k)
    else t[k] := t[k] mod 10 * 10 + t[k] div 10;
end;
begin
  for i := 1 to 40 do t[i] := i;
  xx(34, 35);
  for i := 34 to 35 do write(t[i], ' ');
end.
PAS,
                <<<'CPP'
#include <iostream>
using namespace std;
int t[41];
int fs( int q ) {
  int nr = 0, j = 1, c = q / 2;
  while ( j <= c) {
    if (q % j == 0) nr = nr + j;
    j++;
  }
  return nr;
}
void xx (int a, int b){
  int k;
  for (k = a; k <= b; k++)
    if(k % 2 != 0) t[k] = fs(k);
    else t[k] = t[k] % 10 * 10 + t[k] / 10;
}
int main() {
  int i;
  for (i = 1; i <= 40; i++) t[i] = i;
  xx(34, 35);
  for (i = 34; i <= 35; i++)
    cout << t[i] << " ";
  return 0;
}
CPP),

            self::blanks($d('III.3b'), 'metode-iterative', 3,
                'Programul calculează soluția ecuației f(x) = 0 pe segmentul [0; 0,3] pentru 30 de iterații prin metoda coardelor. Completați părțile lipsă ale instrucțiunilor (spațiile „____") în limbajul studiat.',
                [
                    'tip' => ['tipul variabilelor a, b, e, c, x', ['real', 'double']],
                    'semn' => ['semnul din f(c)*f(a) ____ 0', ['>']],
                    'final' => ['capătul ciclului for', ['to n', '<= n', '<=n', 'to 30', '<= 30', '<=30']],
                ],
                [
                    'Variabilele sunt reale: în Pascal real, în C++ ca funcția f — double.',
                    'Extremitatea fixă e cea în care f are același semn ca în punctul c: dacă f(c)·f(a) > 0, fixă e b.',
                    'Ciclul face n = 30 de iterații: for i := 1 to n / for (i = 1; i <= n; i++).',
                ],
                'Baremul: tipul real (Pascal) / double (C++), semnul „>" în f(c)*f(a) > 0, iar ciclul „to n" (Pascal) sau „i <= n" (C++).',
                <<<'PAS'
program p3;
var a, b, e, c, x: ____ ;
    n, i: integer;
function f(x:real):real;
begin
  f := exp(3*x) - 7*x - 0.5;
end;
begin
  a := 0; b := 0.3; n := 30;
  c := a - (f(a))/(f(b)-f(a))*(b-a);
  if f(c)*f(a) ____ 0 then
    begin e:=b; x:=a; end
  else begin e:=a; x:=b; end;
  for i := 1 ____ do
    x := x-(f(x))/(f(e)-f(x))*(e-x);
  write(x:10:8);
end.
PAS,
                <<<'CPP'
#include <iostream>
#include <math.h>
using namespace std;
____ a, b, c, e, x;
int i, n;
double f(double x)
{ return exp(3*x) - 7*x - 0.5; }
int main(){
  a = 0; b = 0.3; n = 30;
  c = a - f(a)/(f(b)-f(a))*(b-a);
  if (f(c)*f(a) ____ 0){e=b; x=a;}
  else {e=a; x=b;}
  for (i = 1; i ____ ; i++)
    x = x-(f(x))/(f(e)-f(x))*(e-x);
  cout << x;
  return 0;
}
CPP),
        ];
    }

    private static function suplimentara(): array
    {
        $d = fn (string $n) => self::doc('ss26', $n);

        return [
            self::num($d('I.1a'), 'cantitatea-informatie', 4,
                'O carte cu povești este alcătuită din 20 pagini cu text. Fiecare pagină conține 32 rânduri, fiecare rând conține câte 56 simboluri în codul ASCII extins. Știind că un caracter din codul ASCII extins conține un octet (B), determinați cantitatea totală de informație în Kiloocteți (KB) care se conține în cartea dată.',
                35,
                [
                    'Formula: $V = N \cdot I$ — numărul de caractere × informația unui caracter.',
                    '$N = 20 \cdot 32 \cdot 56$ caractere, fiecare de 1 B.',
                    '$V = 2^2 \cdot 5 \cdot 2^5 \cdot 7 \cdot 2^3$ B $= 35 \cdot 2^{10}$ B.',
                ],
                '$V = 20 \cdot 32 \cdot 56 = 35840$ B $= 35 \cdot 1024$ B $= 35$ KB.',
                'KB'),

            self::num($d('I.1b'), 'cantitatea-informatie', 5,
                'Coperta cărții reprezintă o imagine color cu dimensiunile de 3072×2048 pixeli și 32 niveluri de luminanță pentru fiecare din cele trei culori de bază. Determinați cantitatea de informație în Mbiți care se conține în imaginea de pe copertă.',
                90,
                [
                    'Formula pentru o imagine color: $I = 3 \cdot m_x \cdot m_y \cdot \log_2 n$.',
                    '$\log_2 32 = 5$; $3072 = 3 \cdot 2^{10}$, $2048 = 2 \cdot 2^{10}$.',
                    '$I = 3 \cdot 3 \cdot 2^{10} \cdot 2 \cdot 2^{10} \cdot 5 = 90 \cdot 2^{20}$ biți.',
                ],
                '$I = 3 \cdot 3072 \cdot 2048 \cdot 5 = 90 \cdot 2^{20}$ biți $= 90$ Mbiți.',
                'Mbiți'),

            self::choice($d('I.2a'), 'sisteme-numeratie', 2,
                'Care dintre numerele de mai jos este scris corect? (La examen se bifează ambele numere scrise corect.)',
                ['$(953{,}47)_{10}$', '$(F5{,}2G)_{16}$', '$(296{,}75)_8$', '$(2012{,}21)_3$'],
                [
                    'Într-o bază $b$ se folosesc doar cifrele de la 0 la $b - 1$.',
                    'În baza 16 cifrele merg până la F; în baza 8, până la 7.',
                    'G nu e cifră hexazecimală, iar 9 nu e cifră octală.',
                ],
                'Sunt corecte $(953{,}47)_{10}$ și $(2012{,}21)_3$ (în baza 3 se folosesc cifrele 0, 1, 2). $(F5{,}2G)_{16}$ conține G, iar $(296{,}75)_8$ conține 9.',
                [0, 3]),

            self::blanks($d('I.2b'), 'sisteme-numeratie', 3,
                'Determinați relația dintre numerele din fiecare rând și alegeți semnul <, > sau =.',
                [
                    'r1' => ['$(5EB{,}7)_{16}$ ? $(10111101011{,}101)_2$', ['<'], ['<', '>', '=']],
                    'r2' => ['$(634{,}65)_8$ ? $(19C{,}D4)_{16}$', ['='], ['<', '>', '=']],
                    'r3' => ['$(185{,}25)_{10}$ ? $(253{,}6)_8$', ['>'], ['<', '>', '=']],
                ],
                [
                    'Pentru 8 și 16 trece prin binar: fiecare cifră octală are 3 biți, fiecare hexazecimală 4.',
                    '$(5EB{,}7)_{16} = (10111101011{,}0111)_2$; compară partea fracționară cu $0{,}101$.',
                    '$(253{,}6)_8 = 128 + 40 + 3 + 0{,}75 = 171{,}75$.',
                ],
                'Rândul 1: părțile întregi coincid, iar $0{,}0111 < 0{,}101$ în binar, deci <. Rândul 2: ambele sunt $(110011100{,}110101)_2$, deci =. Rândul 3: $185{,}25 > 171{,}75$, deci >.'),

            self::choice($d('I.3b'), 'circuite-logice', 1,
                'Care este tipul circuitului în care valorile variabilelor de ieșire sunt determinate în orice moment de combinația valorilor variabilelor de intrare conform funcțiilor logice ale circuitului și în care nu intervine parametrul timp?',
                ['combinațional', 'secvențial'],
                [
                    'Definiția spune: ieșirile depind DOAR de intrările din acel moment.',
                    'Un circuit secvențial ține minte o stare — depinde și de ce s-a întâmplat înainte.',
                    'Fără memorie și fără timp: combinațional.',
                ],
                'Este definiția circuitului combinațional: ieșirile depind numai de intrări, fără memorie și fără parametrul timp.'),

            self::blanks($d('I.3c'), 'circuite-logice', 2,
                'Un registru de deplasare de la dreapta spre stânga de 4 biți se află inițial în starea 0110 (d3 d2 d1 d0). Scrieți două stări consecutive ale registrului.',
                ['t1' => ['starea la t1', ['1100']], 't2' => ['starea la t2', ['1000']]],
                [
                    'La fiecare tact, toți biții se mută o poziție spre stânga.',
                    'Bitul din stânga iese, iar în dreapta intră 0.',
                    '0110 → 1100 → …',
                ],
                'La fiecare pas biții se deplasează spre stânga și în d0 intră 0: 0110 → 1100 → 1000.'),

            self::blanks($d('II.1'), 'tipuri-simple', 6,
                'Considerând declarațiile și atribuirile date, îndepliniți sarcinile.',
                [
                    'struct' => ['numele unei variabile de tip structurat', ['t', 'r']],
                    'i1' => ['„x := y mod 5” / „x = y % 5”', ['Incorect'], ['Corect', 'Incorect']],
                    'i2' => ['„m := g” / „m = g”', ['Corect'], ['Corect', 'Incorect']],
                    'i3' => ['„w := r” / „w = r”', ['Incorect'], ['Corect', 'Incorect']],
                    'e1' => ['valoarea expresiei a mod ord(w) / a % w', '2'],
                    'e2' => ['valoarea expresiei a div b * b / a / b * b', '18'],
                    'e3' => ['valoarea expresiei chr(ord(q) + trunc(x / b)) / char(q + int(x / b))', ['D']],
                ],
                [
                    'Tablourile t și r sunt tipuri structurate. mod/% se aplică doar întregilor; un tablou nu se atribuie unei enumerări.',
                    'w = d are numărul de ordine 3 în (g, h, c, d, e). 20 mod 3 = 2.',
                    'a div b = 3 (împărțire întreagă), apoi 3 · 6 = 18. trunc(14,4 / 6) = 2, iar „B" + 2 = „D".',
                ],
                'Variabile structurate: t, r. x := y mod 5 e greșit (y e real), m := g e corect (g e o valoare a tipului lui m), w := r e greșit. Valorile: 2, 18, „D".',
                <<<'PAS'
var t, r : array[0..9] of char;
    a, b : integer;
    x, y : real; q : char;
    w, m : (g, h, c, d, e);

q := 'B';  x := 14.4;
a := 20;  b := 6;  w := d;
PAS,
                <<<'CPP'
char t[10], r[10];
int a, b;
float x, y; char q;
enum {g, h, c, d, e} w, m;

q = 'B';  x = 14.4;
a = 20;  b = 6;  w = d;
CPP),

            self::blanks($d('II.3'), 'tipuri-simple', 8,
                'Patru secvențe de instrucțiuni afișează valorile: a) -3.4563e+003, b) -3456.28, c) 0.003, d) 2.74e-003. Scrieți litera valorii afișate de fiecare secvență (numerotate 1–4 în program).',
                [
                    's1' => ['secvența 1', ['c'], ['a', 'b', 'c', 'd']],
                    's2' => ['secvența 2', ['d'], ['a', 'b', 'c', 'd']],
                    's3' => ['secvența 3', ['b'], ['a', 'b', 'c', 'd']],
                    's4' => ['secvența 4', ['a'], ['a', 'b', 'c', 'd']],
                ],
                [
                    'Formatul cu număr fix de zecimale (x:0:3 / fixed) dă scriere obișnuită; fără zecimale fixate (x:10 / scientific) — scriere cu exponent.',
                    'Secvențele 1 și 2 afișează 0.00273687, secvențele 3 și 4 afișează -3456.2796.',
                    'Cu 3 zecimale, 0.00273687 devine 0.003; cu 2 zecimale, -3456.2796 devine -3456.28.',
                ],
                '1 → c (0.003, fix cu 3 zecimale); 2 → d (2.74e-003, științific); 3 → b (-3456.28, fix cu 2 zecimale); 4 → a (-3.4563e+003, științific).',
                <<<'PAS'
{ 1 } x := 0.00273687;  write(x:0:3);
{ 2 } x := 0.00273687;  write(x:10);
{ 3 } x := -3456.2796;  write(x:5:2);
{ 4 } x := -3456.2796;  write(x:12);
PAS,
                <<<'CPP'
// 1
x = 0.00273687; cout << fixed << setprecision(3) << x;
// 2
x = 0.00273687; cout << scientific << setw(10) << setprecision(2) << x;
// 3
x = -3456.2796; cout << fixed << setw(5) << setprecision(2) << x;
// 4
x = -3456.2796; cout << scientific << setw(12) << setprecision(4) << x;
CPP),

            self::blanks($d('III.1'), 'subprograme', 6,
                'Analizați programul pr1 și completați:',
                [
                    'g1' => ['a) o variabilă globală folosită în fs', ['nr', 's']],
                    'g2' => ['a) cealaltă variabilă globală folosită în fs', ['nr', 's']],
                    'd' => ['d) tipul instrucțiunii repetitive', ['ciclu cu contor'], ['ciclu cu test inițial', 'ciclu cu test final', 'ciclu cu contor']],
                    'e1' => ['e) primul cuvânt afișat', ['CTG']],
                    'e2' => ['e) numărul afișat', '2'],
                ],
                [
                    'În fs se modifică variabile declarate în afara ei: care?',
                    'Comparația a > b compară șirurile caracter cu caracter: „A" vine înaintea lui „X".',
                    'Deci se apelează fs(b): în „Xc5tgW7" sunt 2 cifre și literele mici c, t, g.',
                ],
                'fs folosește variabilele globale nr și s; ciclul for este un ciclu cu contor. Cum „Abc9…" < „Xc5…", se prelucrează b: literele mici c, t, g devin „CTG", iar cifrele 5 și 7 dau nr = 2. Se afișează „CTG 2".',
                <<<'PAS'
program pr1;
var a, b, s : string;
    nr: integer;
function ff(x : char ): char;
begin
  ff := chr(ord(x)+ord('A')-ord('a'));
end;
procedure fs(w: string; q:integer);
  var i : integer;
begin
  for i := 1 to q do
  begin
    if (w[i]>='0') and (w[i]<='9')
      then nr := nr + 1;
    if (w[i]>='a') and (w[i]<='z')
      then s := s + ff(w[i]);
  end;
end;
begin
  a := 'Abc9Dd1pr7E9';
  b := 'Xc5tgW7';
  if a > b then fs(a, length(a))
  else fs(b, length(b));
  write ( s , ' ' , nr);
end.
PAS,
                <<<'CPP'
#include <iostream>
#include <string>
using namespace std;
string s;
int nr;
char ff(char x ) {
  return char ( x + 'A' - 'a');
}
void fs(string w, int q) {
  int i;
  for (i = 0; i < q; i++){
    if(w[i]>='0' && w[i]<='9') nr++;
    if(w[i]>='a' && w[i]<='z')
      s = s + ff(w[i]);
  }
}
int main() {
  string a, b;
  a = "Abc9Dd1pr7E9";
  b = "Xc5tgW7";
  if(a > b) fs(a, a.length());
  else fs(b, b.length());
  cout << s << ' ' << nr;
  return 0;
}
CPP),

            self::blanks($d('III.3b'), 'integrare-numerica', 4,
                'Programul calculează integrala definită a funcției f(x) pe segmentul [0; 1] prin metoda dreptunghiurilor de dreapta, pentru 30 de divizări. Completați părțile lipsă („____").',
                [
                    'tip' => ['tipul rezultatului funcției f', ['real', 'double']],
                    'h' => ['h := ____ / n', ['(b - a)', '(b-a)', 'b - a', 'b-a']],
                    'arg' => ['f(a + ____)', ['i * h', 'i*h', 'h * i', 'h*i']],
                    'afis' => ['valoarea afișată', ['S', 'S:']],
                ],
                [
                    'Funcția întoarce un număr real.',
                    'Lungimea unui segment elementar este lungimea segmentului împărțită la numărul de divizări.',
                    'La dreptunghiurile de dreapta, înălțimea se ia în capătul drept al fiecărui segment: $x_i = a + ih$.',
                ],
                'Baremul: function f(x:real): real / double f(double x); h := (b - a) / n; S := S + h * f(a + i * h); se afișează S.',
                <<<'PAS'
program dr;
var S, a, b, h : real;
    i, n : integer;
function f(x:real): ____ ;
begin
  f := sqr(x)*sqrt(1+3*sqr(x));
end;
begin
  a := 0; b := 1; n := 30; S := 0;
  h := ____ / n;
  for i := 1 to n do
    S := S + h * f(a + ____);
  write('I=', ____ 0:6);
end.
PAS,
                <<<'CPP'
#include <iostream>
#include <math.h>
using namespace std;
double S, a, b, h;
int i, n;
____ f(double x){
  return pow(x,2)*sqrt(1+3*pow(x,2));
}
int main(){
  a = 0; b = 1; n = 30; S = 0;
  h = ____ / n;
  for (i = 1; i <= n; i++)
    S = S + h * f(a + ____);
  cout << "I=" << ____;
  return 0;
}
CPP),
        ];
    }
}
