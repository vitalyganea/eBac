<?php

namespace Database\Seeders\Informatica;

/**
 * Testele ANCE de informatică din 2024 (12_inf_test_r_ro_sb24.pdf,
 * 12_inf_test_r_ro_ss24.pdf și baremele cu același nume).
 */
final class Ance2024
{
    use AnceItems;

    /** @return list<array<string, mixed>> */
    public static function items(): array
    {
        return [...self::baza(), ...self::suplimentara()];
    }

    private static function baza(): array
    {
        $d = fn (string $n) => self::doc('sb24', $n);

        return [
            self::num($d('I.1a'), 'codificare', 4,
                'La o competiție de schi au participat n sportivi. Numele lor au fost codificate cu ajutorul cuvintelor binare de lungime minimă egală. Lungimea minimă a cuvintelor binare este egală cu 7. S-a constatat că 20 dintre aceste cuvinte binare nu au fost utilizate. Determinați numărul total de sportivi care au participat la competiție.',
                108,
                [
                    'Lungimea minimă $m$ satisface $m \geq \log_2 n$; cu 7 biți se pot forma $2^7$ cuvinte.',
                    '$2^7 = 128$.',
                    'Nefolosite au rămas 20.',
                ],
                '$2^7 = 128$ de cuvinte, dintre care 20 nefolosite: au participat $128 - 20 = 108$ sportivi.'),

            self::num($d('I.1b'), 'cantitatea-informatie', 5,
                'O fotografie digitală color cu dimensiunea de 128×256 pixeli conține cantitatea de informație de 60 Kiloocteți (KB). Determinați numărul de niveluri de luminanță ale imaginii.',
                32,
                [
                    'Formula: $I = 3 \cdot m_x \cdot m_y \cdot \log_2 n$.',
                    '$60$ KB $= 60 \cdot 2^{13}$ biți; $3 \cdot 128 \cdot 256 = 3 \cdot 2^{15}$.',
                    '$\log_2 n = 5$.',
                ],
                'Din $60 \cdot 2^{13} = 3 \cdot 2^{15} \cdot \log_2 n$ rezultă $\log_2 n = 5$, deci $n = 32$.'),

            self::blanks($d('I.2a'), 'sisteme-numeratie', 2,
                'Scrieți două cele mai mari baze ale sistemelor de numerație poziționale care nu conțin cifra 7.',
                ['b1' => ['baza mai mică', '6'], 'b2' => ['baza mai mare', '7']],
                [
                    'În baza $b$ cifrele sunt $0, 1, \ldots, b - 1$.',
                    'Cifra 7 apare în orice bază mai mare decât 7.',
                    'Baza 7 folosește cifrele 0…6, baza 6 — 0…5.',
                ],
                'Cifra 7 există doar în bazele mai mari decât 7, deci cele mai mari baze fără ea sunt 7 și 6.'),

            self::blanks($d('I.2b'), 'conversii-baze', 4,
                'Fie dat numărul $N = (A2F{,}8)_{16}$. Determinați relația dintre $N$ și $X = (2607{,}3)_{10}$ și scrieți $N$ în sistemul binar.',
                [
                    'rel' => ['N ? X', ['>'], ['<', '>', '=']],
                    'bin' => ['$N = (\ldots)_2$', ['101000101111,1', '101000101111.1', '101000101111,1000']],
                ],
                [
                    '$(A2F{,}8)_{16} = 10 \cdot 16^2 + 2 \cdot 16 + 15 + 8 \cdot 16^{-1}$.',
                    '$N = 2560 + 32 + 15 + 0{,}5 = 2607{,}5$.',
                    'În binar, fiecare cifră hexazecimală devine 4 biți: A = 1010, 2 = 0010, F = 1111, 8 = 1000.',
                ],
                '$N = (2607{,}5)_{10} > (2607{,}3)_{10}$. În binar: $(1010\,0010\,1111{,}1000)_2 = (101000101111{,}1)_2$.'),

            self::blanks($d('II.1'), 'tipuri-simple', 8,
                'Considerând declarațiile și valorile date, completați:',
                [
                    't1' => ['b) un tip de date predefinit', ['char', 'integer', 'int']],
                    't2' => ['b) alt tip de date predefinit', ['char', 'integer', 'int']],
                    'e1' => ['c) q / 4 * p – 10  /  q / 4.0 * p – 10', '2.5'],
                    'e2' => ['c) p – q * ord(w)  /  p – q * int(w)', '10'],
                    'e3' => ['c) chr(ord(y) + ord(x))  /  char(y + x)', ['C']],
                ],
                [
                    'Predefinite sunt tipurile limbajului, nu cele declarate de noi (rx, enumerarea anonimă).',
                    '5 / 4 = 1,25 (împărțire reală); 1,25 · 10 − 10 = 2,5. w = t4 are numărul de ordine 0.',
                    'x = C are numărul de ordine 2, deci caracterul cu 2 după „A".',
                ],
                'Tipuri predefinite: char și integer (int în C++). Valorile: 2,5; 10; „C".',
                <<<'PAS'
type rx = (A, B, C, D);
var x: rx;
    w: (t4, t5, t6, t7);
    y: char;
    p, q: integer;

x := C;  y := 'A';
q := 5;  p := 10;
w := t4;
PAS,
                <<<'CPP'
enum rx {A, B, C, D};
rx x;
enum {t4, t5, t6, t7} w;
char y;
int p, q;

x = C;  y = 'A';
q = 5;  p = 10;
w = t4;
CPP),

            self::blanks($d('II.3a'), 'instructiuni', 6,
                'Scrieți ce se va afișa în rezultatul execuției secvenței de program.',
                ['a' => ['primul număr', '-10'], 'b' => ['al doilea număr', '17'], 'c' => ['al treilea număr', '-7']],
                [
                    'a > b este fals, deci se execută ramura else.',
                    'abs(trunc(-17.4)) = 17 și abs(trunc(17.35)) = 17 — egale, deci se modifică c.',
                    'c = trunc(-17.4) + 10 = -7. La afișare cu 0 zecimale, -10.2 devine -10, iar 17.35 — 17.',
                ],
                'Se modifică doar c: trunc(-17,4) + 10 = -7. Cu zero zecimale se afișează „-10 17 -7".',
                <<<'PAS'
var a, b, c: real;
begin
  a := -10.2; b := 17.35; c := -17.4;
  if a > b then a := trunc(a)*2
  else
    if abs(trunc(c)) = abs(trunc(b)) then c := trunc(c) + 10
    else b := trunc(b) + 10;
  write(a:0:0, ' ', b:0:0, ' ', c:0:0);
PAS,
                <<<'CPP'
float a = -10.2, b = 17.35, c = -17.4;
if (a > b) a = trunc(a)*2;
else
  if (abs(trunc(c)) == abs(trunc(b))) c = trunc(c) + 10;
  else b = trunc(b) + 10;
cout << fixed << setprecision(0) << a << ' ' << b << ' ' << c;
CPP),

            self::blanks($d('III.1'), 'subprograme', 6,
                'Analizați programul pr1 și completați:',
                [
                    'a' => ['a) numărul de variabile locale', '1'],
                    'c' => ['c) parametrul actual la al doilea apel al lui ch', '7'],
                    'd' => ['d) valoarea returnată de ch la ultimul apel', ['false', 'False', '0']],
                    'e' => ['e) „pr1 conține funcții predefinite"', ['Adevărat'], ['Adevărat', 'Fals']],
                    'f1' => ['f) primul număr afișat (w)', '194'],
                    'f2' => ['f) al doilea număr afișat (q)', '2'],
                ],
                [
                    'Singura variabilă declarată în interiorul unui subprogram e k. sqrt, sqr, trunc, pow sunt predefinite.',
                    'num parcurge cifrele lui 24971 de la dreapta: 1, 7, 9, 4, 2. ch verifică dacă cifra e pătrat perfect.',
                    '1, 9 și 4 sunt pătrate perfecte și se lipesc în w în ordinea întâlnirii; 7 și 2 se numără în q.',
                ],
                'Variabile locale: k (1). Al doilea apel: ch(7). Ultimul apel, ch(2), întoarce false. Se folosesc funcții predefinite (Adevărat). Se afișează „194 2".',
                <<<'PAS'
program pr1;
var x, q, w: integer;
function ch(y: integer): boolean;
  var k : integer;
begin
  k := sqr(trunc(sqrt( y )));
  if k = y then ch := true
  else ch := false;
end;
procedure num(x: integer);
begin
  q := 0;
  repeat
    if ch(x mod 10)
      then w := w*10 + (x mod 10)
      else q := q + 1;
    x := x div 10;
  until x = 0;
end;
begin
  x := 24971; w := 0;
  num(x);
  writeln( w, ' ', q );
end.
PAS,
                <<<'CPP'
#include <iostream>
#include <cmath>
using namespace std;
int x, q, w;
bool ch( int y ) {
  int k;
  k = pow( int (sqrt( y )), 2 );
  if (k == y) return true;
  else return false;
}
void num ( int x ) {
  q = 0;
  do {
    if ( ch( x % 10) )
      w = w*10 + (x % 10);
    else q = q + 1;
    x = x / 10;
  } while ( x != 0 );
}
int main() {
  x = 24971; w = 0;
  num (x);
  cout << w << ' ' << q;
}
CPP),
        ];
    }

    private static function suplimentara(): array
    {
        $d = fn (string $n) => self::doc('ss24', $n);

        return [
            self::num($d('I.1a'), 'codificare', 4,
                'O bibliotecă publică este frecventată de 2048 de cititori. Numele lor formează mulțimea de mesaje posibile ale unei surse. Determinați lungimea minimă a cuvintelor binare de lungime egală necesare pentru codificarea și decodificarea univocă a tuturor mesajelor sursei date.',
                11,
                [
                    'Lungimea minimă $m$ satisface $m \geq \log_2 n$.',
                    '$2048 = 2^{11}$.',
                    '$\log_2 2048 = 11$.',
                ],
                '$m \geq \log_2 2048 = 11$, deci lungimea minimă este 11 biți.',
                'biți'),

            self::num($d('I.1b'), 'cantitatea-informatie', 5,
                'O bibliotecă publică este frecventată de 2048 de cititori, ale căror nume formează mulțimea de mesaje posibile ale unei surse. Determinați cantitatea totală de informație în Kilobiți (Kbit) ce se conține în toate mesajele sursei date.',
                22,
                [
                    'Formula: $V = N \cdot I$, cu $I = \log_2 2048 = 11$ biți pe mesaj.',
                    '$V = 2048 \cdot 11 = 2^{11} \cdot 11$ biți.',
                    '1 Kbit $= 2^{10}$ biți.',
                ],
                '$V = 2^{11} \cdot 11 = 2 \cdot 2^{10} \cdot 11$ biți $= 22$ Kbit.',
                'Kbit'),

            self::blanks($d('I.2a'), 'sisteme-numeratie', 2,
                'Fie dat numărul $A = (420{,}12)_x$, pentru care nu este cunoscută baza $x$. Scrieți două cele mai mici baze ale sistemelor de numerație poziționale în care numărul $A$ este scris corect.',
                ['b1' => ['baza cea mai mică', '5'], 'b2' => ['următoarea bază', '6']],
                [
                    'Cea mai mare cifră din număr este 4.',
                    'Baza trebuie să fie mai mare decât orice cifră.',
                    'Deci baza e cel puțin 5.',
                ],
                'Cifra maximă e 4, deci baza minimă e 5; următoarea e 6.'),

            self::blanks($d('I.2b'), 'conversii-baze', 5,
                'Fie dat numărul $N = (1305{,}4)_8$. Realizați conversiunea lui în sistemul zecimal și în cel binar. Scrieți și denumirea unui sistem nepozițional de numerație.',
                [
                    'dec' => ['$N = (\ldots)_{10}$', '709.5'],
                    'bin' => ['$N = (\ldots)_2$', ['1011000101,1', '1011000101.1', '001011000101,100']],
                    'nep' => ['un sistem nepozițional', ['roman', 'Roman', 'sistemul roman', 'romană', 'sistemul de numerație roman']],
                ],
                [
                    '$(1305{,}4)_8 = 1 \cdot 8^3 + 3 \cdot 8^2 + 0 \cdot 8 + 5 + 4 \cdot 8^{-1}$.',
                    'În binar, fiecare cifră octală devine 3 biți: 1 = 001, 3 = 011, 0 = 000, 5 = 101, 4 = 100.',
                    'Un sistem în care valoarea cifrei nu depinde de poziție — ca la cifrele de pe cadranele ceasurilor vechi.',
                ],
                '$(1305{,}4)_8 = 512 + 192 + 0 + 5 + 0{,}5 = (709{,}5)_{10} = (1011000101{,}1)_2$. Sistem nepozițional: cel roman.'),

            self::blanks($d('I.3b'), 'circuite-logice', 2,
                'Din lista codificatorul, numărătorul direct, multiplexorul, registrul, bistabilul RS, scrieți denumirea a două circuite combinaționale.',
                [
                    'c1' => ['primul circuit', ['codificatorul', 'codificator', 'multiplexorul', 'multiplexor']],
                    'c2' => ['al doilea circuit', ['codificatorul', 'codificator', 'multiplexorul', 'multiplexor']],
                ],
                [
                    'Combinațional = fără memorie.',
                    'Numărătorul, registrul și bistabilul păstrează o stare.',
                    'Rămân două circuite care doar transformă intrările.',
                ],
                'Combinaționale sunt codificatorul și multiplexorul; numărătorul, registrul și bistabilul RS sunt secvențiale.'),

            self::blanks($d('II.2'), 'instructiuni', 8,
                'Scrieți ce se va afișa în rezultatul execuției fiecărui fragment.',
                [
                    'f1' => ['fragmentul 1 — primul număr', '4'],
                    'f2' => ['fragmentul 1 — al doilea număr', '7'],
                    'w1' => ['fragmentul 2 — rândul 1', ['True', 'true', '1']],
                    'w2' => ['fragmentul 2 — rândul 2', ['True', 'true', '1']],
                    'w3' => ['fragmentul 2 — rândul 3', ['False', 'false', '0']],
                    'r1' => ['fragmentul 3 — primul număr', '4'],
                    'r2' => ['fragmentul 3 — al doilea număr', '16'],
                    'r3' => ['fragmentul 3 — al treilea număr', '256'],
                ],
                [
                    'Fragmentul 1: verifică 2·i mod 3 pentru i de la 3 la 9.',
                    'Fragmentul 2: b primește valoarea lui a > 10 ÎNAINTE de împărțire: 14536, apoi 145, apoi 1.',
                    'Fragmentul 3: f3 = f1·f2, apoi ambele devin f3 — valorile se ridică la pătrat: 4, 16, 256.',
                ],
                'Fragmentul 1 afișează 4 7 (8 mod 3 = 2, 14 mod 3 = 2); fragmentul 2: True, True, False (în C++: 1, 1, 0); fragmentul 3: 4 16 256.',
                <<<'PAS'
{ 1 } for i := 3 to 9 do
        if 2 * i mod 3 = 2 then write(i, ' ');

{ 2 } b := true; a := 14536;
      while b do begin
        b := a > 10; writeln(b); a := a div 100;
      end;

{ 3 } f1 := 2; f2 := 2;
      repeat
        f3 := f1*f2; write( f3, ' ');
        f2 := f3; f1 := f2;
      until f3 > 30;
PAS,
                <<<'CPP'
// 1
for (i = 3; i <= 9; i++)
  if (2 * i % 3 == 2) cout << i << ' ';

// 2
b = 1; a = 14536;
while ( b ) { b = a > 10;
  cout << b << endl; a = a / 100; }

// 3
f1 = 2; f2 = 2;
do { f3 = f1 * f2; cout << f3 << ' ';
     f2 = f3; f1 = f2; } while (f3 < 30);
CPP),

            self::blanks($d('III.1'), 'subprograme', 6,
                'Analizați programul pr1 și completați:',
                [
                    'a' => ['a) numărul de variabile globale de tip simplu', '2'],
                    'b' => ['b) operația care calculează restul împărțirii', ['mod', '%']],
                    'c' => ['c) tipul valorii returnate de qq', ['integer', 'int']],
                    'e' => ['e) valoarea lui a[2]', '3'],
                    'f1' => ['f) primul număr afișat', '6'],
                    'f2' => ['f) al doilea număr afișat', '10'],
                ],
                [
                    'Globale de tip simplu: n și i (tabloul a e structurat).',
                    'tt pune a[k] = k div 2 + 2 pentru k par și 1 pentru k impar: a = 1, 3, 1, 4, 1.',
                    'Se afișează qq(a[i]) doar pentru a[i] > 1: qq(3) = 1+2+3, qq(4) = 1+2+3+4.',
                ],
                'n și i sunt globale de tip simplu (2); restul: mod / %; qq întoarce integer / int; a[2] = 2 div 2 + 2 = 3; se afișează „6 10".',
                <<<'PAS'
Program pr1;
var a : array[1..20] of integer;
    n, i: integer;
Procedure tt( );
  var k : integer;
begin
  for k := 1 to n do
    if k mod 2 <> 1
      then a[k] := k div 2 + 2
      else a[k] := 1;
end;
Function qq (x : integer): integer;
  var s, j: integer;
begin
  s := 0;
  for j := 1 to x do
    s := s + j;
  qq := s;
end;
begin
  n := 5;
  tt ( );
  for i := 1 to n do
    if a[i] > 1
      then write ( qq (a[i]), ' ');
end.
PAS,
                <<<'CPP'
#include <iostream>
using namespace std;
int a[21];
int n, i;
void tt( ){
  int k;
  for ( k = 1; k <= n; k++)
    if (k % 2 != 1) a[k] = k / 2 + 2;
    else a[k] = 1;
}
int qq (int x){
  int s = 0, j;
  for ( j = 1; j <= x; j++)
    s = s + j;
  return s;
}
int main(){
  n = 5;
  tt ( );
  for ( i = 1; i <= n; i++)
    if ( a[i] > 1 )
      cout << qq (a[i]) << ' ';
  return 0;
}
CPP),
        ];
    }
}
