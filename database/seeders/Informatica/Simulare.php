<?php

namespace Database\Seeders\Informatica;

/**
 * Itemii rezervați simulărilor (§8): variante cu alte date ale sarcinilor din
 * testele ANCE 2024–2026. Programele păstrează structura celor din test;
 * datele și răspunsurile sunt ale noastre.
 */
final class Simulare
{
    use AnceItems;

    /** @return list<array<string, mixed>> */
    public static function items(): array
    {
        return [...self::subiectulI(), ...self::subiectulII(), ...self::subiectulIII()];
    }

    private static function subiectulI(): array
    {
        return [
            self::num(self::variant(self::doc('sb26', 'I.1a')), 'cantitatea-informatie', 4,
                'Utilizatorii unui sistem primesc câte o parolă care conține 8 simboluri distincte dintr-un set de 64 simboluri, codificate cu cuvinte binare de lungime minimă egală. Pentru păstrarea parolelor au fost rezervați 6 KB. Determinați numărul de parole care pot fi păstrate.',
                1024,
                [
                    '$I = \log_2 64$ biți pentru un simbol.',
                    'O parolă: $8 \cdot 6 = 48$ biți $= 6$ B.',
                    '$6$ KB $= 6144$ B.',
                ],
                'O parolă ocupă 6 B, iar $6144 : 6 = 1024$ de parole.',
                'parole'),

            self::num(self::variant(self::doc('sb26', 'I.1b')), 'cantitatea-informatie', 5,
                'Un tutorial video are durata de 16 minute, frecvența de 25 cadre pe secundă și cantitatea de informație a unui cadru egală cu 4 Mbiți. Determinați cantitatea de informație în Gigabiți (Gbiți).',
                93.75,
                [
                    '$V = T \cdot F \cdot I$.',
                    '$V = 16 \cdot 60 \cdot 25 \cdot 4 = 96000$ Mbiți.',
                    '1 Gbit $= 1024$ Mbiți.',
                ],
                '$V = 96000$ Mbiți $= 96000 : 1024 = 93{,}75$ Gbiți.',
                'Gbiți'),

            self::num(self::variant(self::doc('sb25', 'I.1a')), 'codificare', 4,
                'O revistă conține imaginile a 37 modele, codificate cu cuvinte binare de lungime minimă egală, egală cu 6. Determinați numărul total de cuvinte binare care nu au fost utilizate pentru codificare.',
                27,
                [
                    'Cu 6 biți se formează $2^6$ cuvinte.',
                    '$2^6 = 64$.',
                    'S-au folosit 37.',
                ],
                '$64 - 37 = 27$ de cuvinte nefolosite.'),

            self::num(self::variant(self::doc('ss26', 'I.1a')), 'cantitatea-informatie', 4,
                'O carte este alcătuită din 10 pagini cu text. Fiecare pagină conține 64 rânduri, fiecare rând conține câte 32 simboluri în codul ASCII extins (un caracter = 1 B). Determinați cantitatea totală de informație în KB.',
                20,
                [
                    '$V = N \cdot I$, cu $I = 1$ B.',
                    '$N = 10 \cdot 64 \cdot 32 = 20480$.',
                    '1 KB $= 1024$ B.',
                ],
                '$V = 20480$ B $= 20$ KB.',
                'KB'),

            self::blanks(self::variant(self::doc('ss25', 'I.2a')), 'conversii-baze', 3,
                'Realizați conversiunea numărului $(45{,}75)_{10}$ în sistemul binar de numerație.',
                ['b' => ['$(45{,}75)_{10} = (\ldots)_2$', ['101101,11', '101101.11']]],
                [
                    'Partea întreagă: împărțiri succesive la 2.',
                    '$45 = (101101)_2$.',
                    '$0{,}75 \cdot 2 = 1{,}5$; $0{,}5 \cdot 2 = 1$.',
                ],
                '$(45{,}75)_{10} = (101101{,}11)_2$.'),

            self::blanks(self::variant(self::doc('sb24', 'I.2b')), 'conversii-baze', 4,
                'Fie $N = (B3{,}4)_{16}$. Determinați relația dintre $N$ și $X = (179{,}3)_{10}$ și scrieți $N$ în sistemul binar.',
                [
                    'rel' => ['N ? X', ['<'], ['<', '>', '=']],
                    'bin' => ['$N = (\ldots)_2$', ['10110011,01', '10110011.01', '10110011,0100']],
                ],
                [
                    '$(B3{,}4)_{16} = 11 \cdot 16 + 3 + 4 \cdot 16^{-1}$.',
                    '$N = 179{,}25$.',
                    'B = 1011, 3 = 0011, 4 = 0100.',
                ],
                '$N = 179{,}25 < 179{,}3$; în binar $(10110011{,}01)_2$.'),

            self::blanks(self::variant(self::doc('ss24', 'I.2a')), 'sisteme-numeratie', 2,
                'Fie $A = (531{,}24)_x$. Scrieți două cele mai mici baze în care numărul $A$ este scris corect.',
                ['b1' => ['baza cea mai mică', '6'], 'b2' => ['următoarea bază', '7']],
                [
                    'Cea mai mare cifră este 5.',
                    'Baza trebuie să fie mai mare decât orice cifră.',
                    'Deci baza e cel puțin 6.',
                ],
                'Cifra maximă e 5, deci bazele minime sunt 6 și 7.'),

            self::blanks(self::variant(self::doc('ss26', 'I.3c')), 'circuite-logice', 2,
                'Un registru de deplasare de la dreapta spre stânga de 4 biți se află inițial în starea 1011. Scrieți două stări consecutive ale registrului.',
                ['t1' => ['starea la t1', ['0110']], 't2' => ['starea la t2', ['1100']]],
                [
                    'Biții se mută o poziție spre stânga.',
                    'Bitul din stânga iese, în dreapta intră 0.',
                    '1011 → 0110 → …',
                ],
                '1011 → 0110 → 1100.'),
        ];
    }

    private static function subiectulII(): array
    {
        return [
            self::blanks(self::variant(self::doc('sb26', 'II.2')), 'instructiuni', 6,
                'Scrieți ce va afișa programul pr2 pentru fiecare set de valori (y cu două zecimale).',
                [
                    'px' => ["op = '+'; a = 7.8; b = -2.5 — x", '5'],
                    'py' => ['… y', '0.15'],
                    'mx' => ["op = '-'; a = 7.8; b = -2.5 — x", '9'],
                    'my' => ['… y', '4.00'],
                    'ox' => ["op = '*'; a = 1.5; b = -4.2 — x", '-6'],
                    'oy' => ['… y', '-6.30'],
                ],
                [
                    'trunc taie zecimalele spre zero.',
                    'La „-": trunc(-2.5) = -2, deci x = trunc(9.8) = 9 și y = 9 div 2.',
                    'La „*": y = -6.3, x = trunc(-6.3) = -6.',
                ],
                '„+" → x=5, y=0.15; „-" → x=9, y=4.00; „*" → x=-6, y=-6.30.',
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

            self::blanks(self::variant(self::doc('ss24', 'II.2')), 'instructiuni', 8,
                'Scrieți ce se va afișa în rezultatul execuției fiecărui fragment.',
                [
                    'f1' => ['fragmentul 1 — primul număr', '2'],
                    'f2' => ['fragmentul 1 — al doilea număr', '5'],
                    'f3' => ['fragmentul 1 — al treilea număr', '8'],
                    'w1' => ['fragmentul 2 — rândul 1', ['True', 'true', '1']],
                    'w2' => ['fragmentul 2 — rândul 2', ['True', 'true', '1']],
                    'w3' => ['fragmentul 2 — rândul 3', ['False', 'false', '0']],
                    'r1' => ['fragmentul 3 — primul număr', '9'],
                    'r2' => ['fragmentul 3 — al doilea număr', '81'],
                ],
                [
                    'Fragmentul 1: 2·i mod 3 = 1 pentru i = 2, 5, 8.',
                    'Fragmentul 2: a ia valorile 98765, 987, 9.',
                    'Fragmentul 3: valorile se ridică la pătrat pornind de la 3.',
                ],
                'Fragmentul 1: 2 5 8; fragmentul 2: True, True, False (C++: 1, 1, 0); fragmentul 3: 9 81.',
                <<<'PAS'
{ 1 } for i := 2 to 8 do
        if 2 * i mod 3 = 1 then write(i, ' ');

{ 2 } b := true; a := 98765;
      while b do begin
        b := a > 10; writeln(b); a := a div 100;
      end;

{ 3 } f1 := 3; f2 := 3;
      repeat
        f3 := f1*f2; write( f3, ' ');
        f2 := f3; f1 := f2;
      until f3 > 50;
PAS,
                <<<'CPP'
// 1
for (i = 2; i <= 8; i++)
  if (2 * i % 3 == 1) cout << i << ' ';

// 2
b = 1; a = 98765;
while ( b ) { b = a > 10;
  cout << b << endl; a = a / 100; }

// 3
f1 = 3; f2 = 3;
do { f3 = f1 * f2; cout << f3 << ' ';
     f2 = f3; f1 = f2; } while (f3 < 50);
CPP),

            self::blanks(self::variant(self::doc('sb24', 'II.3a')), 'instructiuni', 6,
                'Scrieți ce se va afișa în rezultatul execuției secvenței de program.',
                ['a' => ['primul număr', '-8'], 'b' => ['al doilea număr', '12'], 'c' => ['al treilea număr', '-2']],
                [
                    'a > b e fals.',
                    'abs(trunc(-12.9)) = 12 = abs(trunc(12.4)): se modifică c.',
                    'La afișarea cu zero zecimale, -7.6 se rotunjește la -8.',
                ],
                'c = -12 + 10 = -2; se afișează „-8 12 -2".',
                <<<'PAS'
var a, b, c: real;
begin
  a := -7.6; b := 12.4; c := -12.9;
  if a > b then a := trunc(a)*2
  else
    if abs(trunc(c)) = abs(trunc(b)) then c := trunc(c) + 10
    else b := trunc(b) + 10;
  write(a:0:0, ' ', b:0:0, ' ', c:0:0);
PAS,
                <<<'CPP'
float a = -7.6, b = 12.4, c = -12.9;
if (a > b) a = trunc(a)*2;
else
  if (abs(trunc(c)) == abs(trunc(b))) c = trunc(c) + 10;
  else b = trunc(b) + 10;
cout << fixed << setprecision(0) << a << ' ' << b << ' ' << c;
CPP),

            self::blanks(self::variant(self::doc('ss26', 'II.1')), 'tipuri-simple', 3,
                'Considerând declarațiile și atribuirile date, calculați valoarea fiecărei expresii.',
                [
                    'e1' => ['a mod ord(w)  /  a % w', '1'],
                    'e2' => ['a div b * b  /  a / b * b', '15'],
                    'e3' => ['chr(ord(q) + trunc(x / b))  /  char(q + int(x / b))', ['E']],
                ],
                [
                    'w = e are numărul de ordine 4.',
                    '17 div 5 = 3.',
                    'trunc(10.5 / 5) = 2.',
                ],
                '17 mod 4 = 1; 3 · 5 = 15; „C" + 2 = „E".',
                <<<'PAS'
var a, b : integer;
    x : real; q : char;
    w : (g, h, c, d, e);

q := 'C';  x := 10.5;
a := 17;  b := 5;  w := e;
PAS,
                <<<'CPP'
int a, b;
float x; char q;
enum {g, h, c, d, e} w;

q = 'C';  x = 10.5;
a = 17;  b = 5;  w = e;
CPP),
        ];
    }

    private static function subiectulIII(): array
    {
        return [
            self::blanks(self::variant(self::doc('sb26', 'III.1')), 'subprograme', 4,
                'Analizați programul pr1 (procedura xx se apelează acum cu xx(20, 21)) și completați:',
                [
                    'e' => ['valoarea lui t[9]', '9'],
                    'f1' => ['primul număr afișat', '2'],
                    'f2' => ['al doilea număr afișat', '11'],
                ],
                [
                    'xx modifică doar t[20] și t[21].',
                    't[20] e par: 0 · 10 + 2 = 2.',
                    't[21] e impar: suma divizorilor proprii ai lui 21.',
                ],
                't[9] = 9; t[20] = 2; t[21] = 1 + 3 + 7 = 11. Se afișează „2 11".',
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
  xx(20, 21);
  for i := 20 to 21 do write(t[i], ' ');
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
  xx(20, 21);
  for (i = 20; i <= 21; i++)
    cout << t[i] << " ";
  return 0;
}
CPP),

            self::blanks(self::variant(self::doc('sb24', 'III.1')), 'subprograme', 5,
                'Analizați programul pr1 (cu x = 31694) și completați:',
                [
                    'c' => ['parametrul actual la al doilea apel al lui ch', '9'],
                    'd' => ['valoarea returnată de ch la ultimul apel', ['false', 'False', '0']],
                    'f1' => ['primul număr afișat (w)', '491'],
                    'f2' => ['al doilea număr afișat (q)', '2'],
                ],
                [
                    'Cifrele se parcurg de la dreapta: 4, 9, 6, 1, 3.',
                    'Pătrate perfecte: 4, 9, 1.',
                    'Ultimul apel e ch(3).',
                ],
                'w = 491 (cifrele 4, 9, 1), q = 2 (cifrele 6 și 3); al doilea apel e ch(9), ultimul ch(3) = false.',
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
  x := 31694; w := 0;
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
  x = 31694; w = 0;
  num (x);
  cout << w << ' ' << q;
}
CPP),

            self::blanks(self::variant(self::doc('ss24', 'III.1')), 'subprograme', 4,
                'Analizați programul pr1 (cu n = 6) și completați:',
                [
                    'e' => ['valoarea lui a[2]', '3'],
                    'f1' => ['primul număr afișat', '6'],
                    'f2' => ['al doilea număr afișat', '10'],
                    'f3' => ['al treilea număr afișat', '15'],
                ],
                [
                    'a = 1, 3, 1, 4, 1, 5.',
                    'Se afișează qq(a[i]) pentru a[i] > 1.',
                    'qq(x) = 1 + 2 + … + x.',
                ],
                'Pentru a[2] = 3, a[4] = 4, a[6] = 5 se afișează 6, 10, 15.',
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
  n := 6;
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
  n = 6;
  tt ( );
  for ( i = 1; i <= n; i++)
    if ( a[i] > 1 )
      cout << qq (a[i]) << ' ';
  return 0;
}
CPP),

            self::blanks(self::variant(self::doc('ss26', 'III.1')), 'subprograme', 3,
                'Analizați programul pr1 (cu alte șiruri) și scrieți ce se va afișa.',
                ['e1' => ['cuvântul afișat', ['NQ']], 'e2' => ['numărul afișat', '2']],
                [
                    'Compară „Mn3Pq8" cu „Ab12cd" caracter cu caracter.',
                    '„M" > „A", deci se prelucrează a.',
                    'Literele mici n, q devin majuscule; cifrele 3 și 8 se numără.',
                ],
                'Se apelează fs(a): „NQ" și nr = 2.',
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
  a := 'Mn3Pq8';
  b := 'Ab12cd';
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
  a = "Mn3Pq8";
  b = "Ab12cd";
  if(a > b) fs(a, a.length());
  else fs(b, b.length());
  cout << s << ' ' << nr;
  return 0;
}
CPP),
        ];
    }
}
