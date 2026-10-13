<?php

namespace Database\Seeders\Informatica;

/**
 * Testele ANCE de informatică din 2025 (12_inf_test_r_ro_sb25.pdf,
 * 12_inf_test_r_ro_ss25.pdf și baremele cu același nume).
 */
final class Ance2025
{
    use AnceItems;

    /** @return list<array<string, mixed>> */
    public static function items(): array
    {
        return [...self::baza(), ...self::suplimentara()];
    }

    private static function baza(): array
    {
        $d = fn (string $n) => self::doc('sb25', $n);

        return [
            self::num($d('I.1a'), 'codificare', 4,
                'O revistă conține imaginile a 53 modele de tricotat. Modelele au fost codificate cu ajutorul cuvintelor binare de lungime minimă egală. Lungimea minimă a cuvintelor binare este egală cu 6. S-a constatat că dintre aceste cuvinte binare câteva nu au fost utilizate pentru codificare. Determinați numărul total de cuvinte binare care nu au fost utilizate pentru codificare.',
                11,
                [
                    'Formula: lungimea minimă $m$ satisface $m \geq \log_2 n$.',
                    'Cu $m = 6$ biți se pot forma $2^6 = 64$ cuvinte binare distincte.',
                    'S-au folosit 53 dintre ele.',
                ],
                'Cu 6 biți există $2^6 = 64$ de cuvinte; s-au folosit 53, deci au rămas nefolosite $64 - 53 = 11$.'),

            self::num($d('I.1b'), 'cantitatea-informatie', 5,
                'Un model reprezintă o fotografie digitală color cu dimensiunea de 128×128 pixeli și 32 niveluri de luminanță. Determinați cantitatea de informație care se conține în imaginea unui model în KiloOcteți (KB).',
                30,
                [
                    'Imagine color: $I = 3 \cdot m_x \cdot m_y \cdot \log_2 n$.',
                    '$I = 3 \cdot 128 \cdot 128 \cdot \log_2 32 = 3 \cdot 2^7 \cdot 2^7 \cdot 5$ biți.',
                    '$15 \cdot 2^{14}$ biți; 1 KB $= 2^{13}$ biți.',
                ],
                '$I = 3 \cdot 2^7 \cdot 2^7 \cdot 5 = 15 \cdot 2^{14} = 30 \cdot 2^{13}$ biți $= 30$ KB.',
                'KB'),

            self::blanks($d('I.2a'), 'codificare', 1,
                'Scrieți denumirea unui cod alfanumeric 8-pozițional utilizat pentru calculatoarele moderne.',
                ['cod' => ['codul', ['ASCII extins', 'codul ASCII extins', 'ASCII', 'EBCDIC', 'codul EBCDIC']]],
                [
                    '8 poziții binare înseamnă $2^8 = 256$ de simboluri.',
                    'Tabelul de coduri pe care îl folosesc testele ANCE are exact 256 de simboluri.',
                    'Este varianta extinsă a codului american de 7 biți.',
                ],
                'Baremul acceptă codul ASCII extins sau codul EBCDIC — ambele au cuvinte de 8 biți.'),

            self::match($d('I.2b'), 'conversii-baze', 3,
                'Potriviți fiecare număr din coloana A cu numărul egal cu el din coloana B.',
                [
                    '$(153{,}2)_8$' => '$(107{,}25)_{10}$',
                    '$(124{,}75)_{10}$' => '$(174{,}6)_8$',
                    '$(5A{,}C)_{16}$' => '$(1011010{,}11)_2$',
                ],
                ['$(1011010{,}101)_2$'],
                [
                    'Convertește fiecare număr din A în baza numărului cu care l-ai potrivi.',
                    '$(153{,}2)_8 = 64 + 40 + 3 + 0{,}25$.',
                    '$(5A{,}C)_{16}$: fiecare cifră hexazecimală devine 4 biți — $5 = 0101$, $A = 1010$, $C = 1100$.',
                ],
                '$(153{,}2)_8 = (107{,}25)_{10}$; $(124{,}75)_{10} = (174{,}6)_8$ (124 = 1·64 + 7·8 + 4, 0,75·8 = 6); $(5A{,}C)_{16} = (1011010{,}11)_2$. Numărul $(1011010{,}101)_2$ nu are pereche.'),

            self::choice($d('I.3c'), 'circuite-logice', 1,
                'Bistabilul asincron RS reprezintă un circuit secvențial destinat păstrării unui bit de informație. Câte ieșiri are bistabilul asincron RS?',
                ['2', '1', '3', '4'],
                [
                    'Bistabilul are două intrări: S (set) și R (reset).',
                    'La ieșire dă bitul memorat și opusul lui.',
                    'Ieșirile sunt Q și Q̄.',
                ],
                'Bistabilul RS are două ieșiri: Q și complementul său Q̄.'),

            self::blanks($d('II.1'), 'tipuri-simple', 4,
                'Considerând declarațiile și atribuirile date, completați:',
                [
                    'struct' => ['b) tipul de date structurat din declarații', ['string']],
                    'e1' => ['c) m div n + 10  /  m / n + 10', '11'],
                    'e2' => ['c) m mod n * ord(q)  /  m % n * q', '9'],
                    'e3' => ['c) (ord(a) – ord(b)) / x  /  (a - b) / x', '-0.75'],
                ],
                [
                    'Un șir de caractere e un tip structurat — e alcătuit din componente.',
                    'm = 7, n = 4: împărțirea întreagă dă 1, restul 3. q = U are numărul de ordine 3.',
                    'Codul lui „A" e cu 3 mai mic decât al lui „D", iar împărțirea la x real dă rezultat real.',
                ],
                'Tipul structurat: string. 7 div 4 + 10 = 11; 7 mod 4 · 3 = 9; (65 − 68) / 4 = −0,75.',
                <<<'PAS'
var m, n : integer; a, b : char;
    x, y : real;    s : string;
    q : (R, T, O, U, P);

m := 7;    n := 4;    x := 4;
a := 'A';  b := 'D';  q := U;
PAS,
                <<<'CPP'
int m, n;      char a, b;
float x, y;    string s;
enum {R, T, O, U, P} q;

m = 7;    n = 4;    x = 4;
a = 'A';  b = 'D';  q = U;
CPP),

            self::blanks($d('II.2'), 'instructiuni', 8,
                'Fie variabila de tip caracter c și variabilele de tip întreg x = 5, y = 10, s = 0, q = 1, i. Scrieți ce afișează fiecare secvență.',
                [
                    'r1' => ['secvența 1', ['False', 'false', '0']],
                    'r2' => ['secvența 2', '5'],
                    'r3' => ['secvența 3', '1'],
                    'r4' => ['secvența 4', '20'],
                ],
                [
                    'Secvența 1: caracterul cu codul cu 2 mai mare nu poate fi mai mic. Pascal afișează valoarea logică, C++ — 0 sau 1.',
                    'Secvența 2: true or … e mereu adevărat. Secvența 3: 5² = 25 nu e mai mic decât 2 · 10, deci corpul nu se execută.',
                    'Secvența 4: ciclul coboară i de la 5 la 2, iar s păstrează ultima atribuire.',
                ],
                'Baremul: False (Pascal) / 0 (C++); 5; 1; 20 — ultima valoare a lui s este 2 · 10.',
                <<<'PAS'
{ 1 } write( chr(ord(c) + 2) < c );

{ 2 } if true or (x >= y) then write(x)
      else write(y);

{ 3 } while sqr(x) < 2 * y do
      begin q := q * x; x := x - 1; end;
      write( q );

{ 4 } for i := x downto 2 do s := i * y;
      write( s );
PAS,
                <<<'CPP'
// 1
cout << (char(c + 2) < c);

// 2
if (true || x >= y) cout << x;
else cout << y;

// 3
while (pow(x,2) < 2 * y)
  { q = q * x; x = x - 1; }
cout << q;

// 4
for (i = x; i >= 2; i--) s = i * y;
cout << s;
CPP),

            self::blanks($d('II.3a'), 'instructiuni', 2,
                'De la tastatură se citesc numerele: -10 18 21 -12 22 30 50 24 16 50. Scrieți ce se va afișa în rezultatul execuției programului p03.',
                ['it' => ['primul rând (it)', '7'], 'num' => ['al doilea rând (num)', '3']],
                [
                    'Ciclul se oprește la PRIMUL 50 citit — restul numerelor nu se mai citesc.',
                    'Până la primul 50 inclusiv se citesc 7 numere.',
                    'Divizibile cu 3 și mai mari ca 10: 18, 21, 30.',
                ],
                'Se citesc -10, 18, 21, -12, 22, 30, 50 (it = 7), iar condiția e îndeplinită de 18, 21 și 30 (num = 3).',
                <<<'PAS'
Program p03;
Var num, a, it: integer;
Begin
  num := 0; it := 0;
  repeat
    it := it + 1;
    read(a);
    if (a mod 3 = 0) and (a > 10)
      then num := num + 1;
  until a = 50;
  writeln(it); write(num);
End.
PAS,
                <<<'CPP'
#include <iostream>
using namespace std;
int main()
{
  int num, a, it;
  num = 0; it = 0;
  do {
    it++;
    cin >> a;
    if(a % 3 == 0 && a > 10) num++;
  } while (a != 50);
  cout << it << endl; cout << num;
}
CPP),

            self::blanks($d('III.1'), 'subprograme', 6,
                'Analizați programul pr1 și completați:',
                [
                    'a' => ['a) tipul structurat definit de utilizator', ['xx']],
                    'c' => ['c) o funcție predefinită care prelucrează un șir', ['length', 'length()']],
                    'd' => ['d) numărul de variabile locale din funcția sir', '4'],
                    'e' => ['e) valoarea returnată de sir la primul apel', '0'],
                    'f1' => ['f) primul rând afișat', ['4567654']],
                    'f2' => ['f) al doilea rând afișat', '2'],
                ],
                [
                    'Funcția sir construiește în w șirul s inversat și verifică dacă e palindrom.',
                    'Variabilele locale ale lui sir: s, w, j, t. Primul șir nu e palindrom.',
                    'fc afișează palindromurile și numără celelalte: doar „4567654" e palindrom.',
                ],
                'Tipul definit de utilizator: xx; funcția predefinită: length. În sir sunt 4 variabile locale. Primul șir nu e palindrom (0). Se afișează palindromul 4567654, apoi numărul de nepalindromuri, 2.',
                <<<'PAS'
program p1;
type xx = array[1..10] of string;
var x : xx; n, q : integer;
function sir(k: integer): integer;
  var s, w : string;
      j, t : integer;
begin
  s := x[k]; w := ''; t := length(s);
  for j := 1 to t do w := s[j] + w;
  if w = s then sir := 1
  else sir := 0;
end;
function fc: integer;
  var i, q : integer;
begin
  q := 0;
  for i := 1 to n do
    if sir(i) = 0 then q := q + 1
    else writeln(x[i]);
  fc := q;
end;
begin
  n := 3;
  x[1] := '12452'; x[2] := '4567654';
  x[3] := 'm237q8d3mn';
  write(fc);
end.
PAS,
                <<<'CPP'
#include <iostream>
#include <string>
using namespace std;
typedef string xx[10];
xx x; int n, q;
int sir(int k){
  string s = x[k], w = "";
  int j, t = s.length();
  for (j=0; j < t; j++) w = s[j] + w;
  if (w == s) return 1;
  else return 0;
}
int fc(){
  int i, q=0;
  for(i = 0; i < n; i++)
    if (sir(i) == 0) q++;
    else cout << x[i] << endl;
  return q;
}
int main(){
  n=3;
  x[0] = "12432"; x[1] = "4567654";
  x[2] = "m237q8d3mn";
  cout << fc();
  return 0;
}
CPP),
        ];
    }

    private static function suplimentara(): array
    {
        $d = fn (string $n) => self::doc('ss25', $n);

        return [
            self::num($d('I.1a'), 'codificare', 4,
                'La o expoziție de pictură au participat n elevi. Numele lor au fost codificate cu ajutorul cuvintelor binare de lungime minimă egală. Lungimea minimă a cuvintelor binare este egală cu 7. S-a constatat că 30 dintre aceste cuvinte binare nu au fost utilizate. Determinați numărul total de elevi care au participat la expoziție.',
                98,
                [
                    'Cu 7 biți se pot forma $2^7$ cuvinte distincte.',
                    '$2^7 = 128$.',
                    'Din ele, 30 au rămas nefolosite.',
                ],
                'Cu 7 biți există $2^7 = 128$ de cuvinte; nefolosite 30, deci au participat $128 - 30 = 98$ de elevi.'),

            self::num($d('I.1b'), 'cantitatea-informatie', 5,
                'O fotografie digitală color cu dimensiunea de 128×256 pixeli conține cantitatea de informație de 60 Kiloocteți (KB). Determinați numărul de niveluri de luminanță ale imaginii.',
                32,
                [
                    'Formula: $I = 3 \cdot m_x \cdot m_y \cdot \log_2 n$; necunoscuta e $n$.',
                    '$60$ KB $= 60 \cdot 2^{10} \cdot 2^3$ biți, iar $3 \cdot 128 \cdot 256 = 3 \cdot 2^{15}$.',
                    '$\log_2 n = \frac{60 \cdot 2^{13}}{3 \cdot 2^{15}} = 5$.',
                ],
                '$60 \cdot 2^{13} = 3 \cdot 2^{15} \cdot \log_2 n$ dă $\log_2 n = 5$, deci $n = 2^5 = 32$ de niveluri.'),

            self::blanks($d('I.2a'), 'conversii-baze', 3,
                'Realizați conversiunea numărului $(53{,}25)_{10}$ în sistemul binar de numerație.',
                ['b' => ['$(53{,}25)_{10} = (\ldots)_2$', ['110101,01', '110101.01']]],
                [
                    'Partea întreagă: împărțiri succesive la 2, resturile citite de jos în sus.',
                    '$53 = (110101)_2$.',
                    'Partea fracționară: $0{,}25 \cdot 2 = 0{,}5$, $0{,}5 \cdot 2 = 1$.',
                ],
                '$53 = (110101)_2$ și $0{,}25 = (0{,}01)_2$, deci $(53{,}25)_{10} = (110101{,}01)_2$.'),

            self::choice($d('I.2b'), 'sisteme-numeratie', 5,
                'Fie dată ecuația $(532{,}3)_x = (15A{,}6)_{16}$. Care este valoarea bazei $x$?',
                ['8', '2', '3', '16'],
                [
                    'Cifra 5 exclude bazele 2 și 3.',
                    'Trece $(15A{,}6)_{16}$ în binar: $0001\,0101\,1010{,}0110$.',
                    'Grupează biții câte 3: $101\,011\,010{,}011$ — adică cifrele 5, 3, 2 și 3.',
                ],
                '$(15A{,}6)_{16} = (101011010{,}011)_2 = (532{,}3)_8$, deci $x = 8$.'),

            self::match($d('I.3c'), 'circuite-logice', 3,
                'Conform metodei de proiectare ierarhică, componentele calculatorului se caracterizează prin niveluri de ierarhie (nivelul 1 — tranzistoare). Potriviți nivelurile 2–4 cu componentele.',
                [
                    'nivelul 2' => 'porți logice',
                    'nivelul 3' => 'semisumatoare',
                    'nivelul 4' => 'sumatoare',
                ],
                [],
                [
                    'Fiecare nivel se construiește din componentele nivelului de dedesubt.',
                    'Din tranzistoare se fac porțile logice.',
                    'Un sumator complet se face din semisumatoare.',
                ],
                'Tranzistoare → porți logice → semisumatoare → sumatoare.'),

            self::blanks($d('II.1'), 'tipuri-simple', 9,
                'Considerând declarațiile și valorile date, completați:',
                [
                    'a' => ['a) tipul neordinal de date', ['real', 'float']],
                    'b' => ['b) tipul elaborat de utilizator', ['tt']],
                    'c1' => ['c) p := d + 1  /  p = d + 1', ['Corect'], ['Corect', 'Greșit']],
                    'c2' => ['c) c := q  /  c = q', ['Greșit'], ['Corect', 'Greșit']],
                    'c3' => ['c) read(b)  /  cin >> b', ['Greșit'], ['Corect', 'Greșit']],
                    'c4' => ['c) write(q <> Y)  /  cout << (q != Y)', ['Corect'], ['Corect', 'Greșit']],
                    'd1' => ['d) d div 3 + p / 4  /  d / 3 + p / 4', '5.2'],
                    'd2' => ['d) d mod 10 – trunc(p) * ord(b)  /  d % 10 – (int)p * b', '-11'],
                ],
                [
                    'Tipurile ordinale au predecesor și succesor (întregi, caractere, logice, enumerări); real/float nu are.',
                    'Unei variabile char nu i se poate atribui o valoare de tip enumerare; valorile logice nu se citesc de la tastatură.',
                    'd div 3 = 1, p / 4 = 4,2; d mod 10 = 5, trunc(16,8) = 16, ord(true) = 1.',
                ],
                'Neordinal: real / float; tipul utilizatorului: tt. p := d + 1 e corect, c := q și citirea unei valori logice sunt greșite, afișarea lui q <> Y e corectă. Valorile: 1 + 4,2 = 5,2 și 5 − 16 = −11.',
                <<<'PAS'
type tt = (X, Y, V, W);
var q: tt;
    b: boolean;
    c: char;
    d: integer;
    p: real;

q := W; b := true; d := 5;
p := 16.8;
PAS,
                <<<'CPP'
enum tt {X, Y, V, W};
tt q = W;
bool b = true;
char c;
int d = 5;
float p = 16.8;
CPP),

            self::blanks($d('III.1'), 'subprograme', 6,
                'Analizați programul pr1 și completați:',
                [
                    'a' => ['a) mărimea care nu-și poate schimba valoarea', ['n']],
                    'b1' => ['b) un tip elaborat de utilizator', ['rr', 'tt']],
                    'b2' => ['b) celălalt tip elaborat de utilizator', ['rr', 'tt']],
                    'd' => ['d) parametrul actual la al doilea apel al lui ff', '1'],
                    'e' => ['e) „pr1 conține comunicarea prin variabile globale"', ['Adevărat'], ['Adevărat', 'Fals']],
                    'f' => ['f) ce se afișează', '8.5'],
                ],
                [
                    'n e declarat cu const. Tipurile definite de utilizator sunt înregistrarea și tabloul.',
                    'ff se apelează cu i = 0, 1, 2. Procedura pp lucrează direct cu x și mx, declarate global.',
                    'Mediile: (7+8)/2 = 7,5; (8+9)/2 = 8,5; (6+5)/2 = 5,5. Se reține cea mai mare.',
                ],
                'Constanta: n; tipurile: rr și tt; al doilea apel e ff(1); pp folosește variabilele globale x și mx (Adevărat); se afișează media maximă, 8.500 (cu trei zecimale).',
                <<<'PAS'
program pr1;
const n = 3;
type rr = record
       a, b : integer;
       m : real;
     end;
     tt = array [0..9] of rr;
var x : tt;
    mx : rr;
function ff(k : integer): real;
begin ff := (x[k].a + x[k].b)/2; end;
procedure pp();
  var i : integer;
begin
  for i := 0 to n-1 do x[i].m := ff(i);
  mx := x[0];
  for i := 1 to n-1 do
    if mx.m < x[i].m then mx := x[i];
end;
begin
  x[0].a := 7; x[0].b := 8;
  x[1].a := 8; x[1].b := 9;
  x[2].a := 6; x[2].b := 5;
  pp();
  writeln( mx.m : 0 : 3);
end.
PAS,
                <<<'CPP'
#include <iostream>
#include <iomanip>
using namespace std;
const int n = 3;
struct rr { int a, b; float m; };
typedef rr tt[10];
tt x;
rr mx;
float ff(int k)
  { return (x[k].a + x[k].b)/2.0; }
void pp() {
  int i;
  for (i = 0; i < n; i++)
    x[i].m = ff(i);
  mx = x[0];
  for (i = 1; i < n; i++)
    if (mx.m < x[i].m) mx = x[i];
}
int main() {
  x[0].a = 7; x[0].b = 8;
  x[1].a = 8; x[1].b = 9;
  x[2].a = 6; x[2].b = 5;
  pp();
  cout << fixed << setprecision(3) << mx.m;
}
CPP),
        ];
    }
}
