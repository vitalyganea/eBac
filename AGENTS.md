<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

---

# eBac — invariante de arhitectură

Regulile de mai jos nu sunt preferințe de stil. Fiecare a fost aleasă pentru un
motiv concret, iar încălcarea lor produce bug-uri greu de reparat mai târziu.

## 1. `attempts` este singura sursă de adevăr

Tabelul e **append-only**: fără UPDATE, fără DELETE, fără `updated_at`. Modelul
`Attempt` refuză activ ambele operații.

`users.xp`, `users.level`, `users.rating`, `users.streak_days`, tabelul `mastery`
și clasamentele din Redis sunt **derivate** și reconstruibile cu
`php artisan ebac:recompute-user`.

Consecințe:
- Schimbi formula de XP → rulezi o recalculare, nu o migrație distructivă.
- Sincronizarea cu aplicația mobilă e trivială: faptele imuabile nu intră în
  conflict, deci nu există merge de rezolvat.
- Ștergerea datelor unui utilizator se face doar prin
  `App\Domain\Attempts\PurgeUserData`.

## 2a. Profilul e ghidaj, niciodată o poartă

Bacalaureatul e din mai multe probe. `users.profile` (real/umanist/arte/sport)
adaugă doar o etichetă informativă — „obligatoriu", „disciplină de profil",
„la alegere" — și preselectează sugestii la înscriere. NU filtrează la ce are
acces elevul.

Disciplinele se aleg explicit, se salvează în `user_subjects` și se pot schimba
oricând. Un elev de la real trebuie să poată exersa istorie fără să-și schimbe
profilul. Nu adăuga niciodată un `where('profile', ...)` pe conținut.

Deselectarea unei discipline nu șterge progresul: `attempts` e append-only și
`mastery` rămâne, deci întoarcerea după două luni continuă de unde a rămas.

Disciplinele fără itemi sunt selectabile, dar etichetate cinstit („conținut în
pregătire") pe baza numărătorii reale — nu ascunse.

## 2. Cheia de corectare nu ajunge la client înainte de submit

`items.answer` e o coloană **separată** de `items.payload`, tocmai ca să fie
imposibil de scăpat accidental la serializare. E în `#[Hidden]` pe model.

Itemii pleacă spre client **exclusiv** prin `ItemForStudentResource`, care are
listă albă de câmpuri, nu listă neagră. Nu adăuga niciodată `$item->toArray()`
într-un prop Inertia sau într-un răspuns de API.

Aceeași regulă se aplică **indiciilor**: `items.hints` e o listă ordonată, iar
TEXTUL nu pleacă odată cu itemul — doar `{allowed, total, revealed}`. Fiecare
indiciu se cere individual prin `App\Domain\Sessions\RevealHint`, care
incrementează `session_items.hints_used`.

`hints_used` NU se acceptă din request. Dacă textele ar veni în props și
contorul din browser, elevul le-ar citi și ar raporta zero, păstrând tot XP-ul.
Penalizarea e în trepte (20% / 35% / 50%, plafon 60%) și nu ajunge niciodată la
100%: cine a cerut ajutor și a rezolvat a învățat ceva — vrem să ceară, nu să
abandoneze.

Ținta de conținut: **fiecare item publicat are indicii**, în trepte — primul
împinge spre metodă, ultimul e aproape rezolvarea. `HintsTest` verifică
acoperirea și eșuează dacă apare un item fără indicii.

## 3. Logica de business nu stă în controllere

Trăiește în `app/Domain`. `Web\PracticeController` și `Api\V1\AttemptController`
apelează exact aceeași acțiune `SubmitAnswer`, cu aceeași validare
(`SubmitAnswerRequest`). Așa nu vom reimplementa corectarea pentru mobil.

## 4. Sincron doar ce vede elevul

- **Sincron:** INSERT în attempts, corectare, XP, `ZINCRBY` pe clasament.
- **Asincron (Horizon):** mastery, recalibrare Elo pe item, achievements.

Recalculul de mastery într-un request transformă 30 ms în 300 ms la vârf.

## 5. Timpul se măsoară pe server

`session_items.served_at` e ștampilat când itemul pleacă spre client.
`attempts.client_ms` e informativ. Timerul din browser e decor.

Răspunsurile fără cronometraj de server (offline, mobil) au `is_ranked = false`:
dau XP și mastery, dar nu intră în rating-ul competitiv.

## 6. XP-ul premiază învățarea, nu volumul

Reguli în `App\Domain\Xp\XpCalculator`: cooldown pe itemii rezolvați recent,
bonus de retenție pentru greșeli reparate după ≥7 zile, bonus de viteză doar la
itemii de recunoaștere, cap zilnic de 800 XP.

Nu adăuga surse de XP proporționale cu numărul de click-uri.

## 7. Structura examenului stă în date

ANCE ajustează numărul de itemi între sesiuni. `exam_blueprints` e versionat pe
sesiune și profil. Nu hardcoda structura probelor.

## 8. Itemii de holdout nu se ard în antrenament

`Item::practicePool()` vs `Item::examPool()`. Simulările folosesc itemi rezervați,
altfel estimarea de notă nu mai măsoară nimic după două luni.

## 8b. Simularea BAC

`App\Domain\Exams`. Pornește din `MockExamController`, NU din `practice.start`:
itemii vin din structura examenului (`exam_blueprints`), nu din selecția
adaptivă — o simulare măsoară, nu antrenează, deci nu se calibrează pe nivelul
elevului (un elev slab ar primi un test ușor și o estimare umflată).

- **Secțiunile** se mapează pe bancă prin `structure[].types`,
  `structure[].mock_items` și, opțional, `structure[].skills` (capitolele —
  la matematică toate secțiunile au aceleași tipuri, deci fără capitol o
  „Geometrie" s-ar umple cu algebră). Punctele secțiunii se împart egal între itemii ei,
  deci ponderea din examen se păstrează. Secțiunile fără `types` sau cu
  `auto_gradable: false` nu se simulează și se declară în rezultat
  („acoperă 30 din 100 de puncte") — nu se ghicesc (§13).
- **Termenul** e `practice_sessions.expires_at`, fixat la pornire. `SubmitAnswer`
  refuză (`MockClosed`) după termen, după predare și al doilea răspuns la
  aceeași întrebare. Timerul din browser e decor (§5).
- **Fără feedback pe parcurs.** Cheia și explicația pleacă spre client abia în
  `MockReport`, după predare, doar celui care a dat simularea.
- **Itemii văzuți** într-o simulare nu mai măsoară nimic pentru acel elev:
  `MockItemPicker` îi preferă pe cei nevăzuți și declară reutilizarea
  (`config.reused`). Tipurile cerute și de secțiunile următoare se aleg ultimele,
  altfel o secțiune timpurie le poate consuma. Selecția nu depinde de ordinea
  rândurilor din bază — un bug s-a ascuns în teste exact așa.
- **Profilul** alege structura, dar nu blochează (§2a): fără structură pentru
  profilul elevului, primește cea disponibilă.

## 9. Elevii sunt minori

În clasamente și dueluri apare **doar** `users.username`, niciodată numele real
și niciodată emailul. Videoclipurile se redau prin `youtube-nocookie.com`.

Participarea la clasamente e **activă implicit** (`leaderboard_opt_in` = true):
un clasament la care nu participă nimeni nu e un clasament. Protecția care
contează e pseudonimul, nu absența din listă — iar retragerea rămâne posibilă
oricând din `/setari`. Retragerea oprește apariția în clasament, NU contorizarea
progresului.

Ștergerea contului există și e obligatorie legal (Legea nr. 133/2011): trece
prin `PurgeUserData`, singurul loc care are voie să atingă `attempts`.

**Nu cerem apartenența la o instituție.** Nu există tabel `schools`: platforma
poate servi candidați care repetă examenul sau care învață pe cont propriu, iar
datele de școală cerute unor minori fără o utilizare clară sunt un cost fără
beneficiu.

## 10. Bundle-ul sub 200 KB

Publicul e pe Android ieftin cu date mobile limitate. KaTeX (258 KB) se încarcă
la cerere în `MathText.vue`, nu în bundle-ul paginii. Nu importa biblioteci grele
sincron în pagini.

Intrarea e aproape în întregime Vue + Inertia; a trecut sub prag doar după ce am
oprit suportul pentru Options API (`__VUE_OPTIONS_API__: false` în
`vite.config.ts`). Scrie componente doar cu `<script setup>`: `data()`,
`methods:` sau mixin-urile nu mai funcționează. `npm run bundle-size` verifică
pragul și absența KaTeX din importurile statice ale paginilor; CI-ul pică la
depășire.

## 10b. Clasamentele sunt PE DISCIPLINĂ

„La matematică sunt al 34-lea din 938" e informația care contează pentru elev.
Un XP global amestecă matematica cu engleza, care nu se compară.

`subject_stats` (user × disciplină) e stare derivată din `attempts`, ca `mastery`
— reconstruibilă cu `ebac:recompute-user`. `attempts.subject_id` e denormalizat
din item, ca interogările per disciplină să nu facă join pe zeci de milioane de
rânduri.

Clasamentul live stă în Redis: `ZINCRBY` la scriere, `ZREVRANK` + `ZCARD` pentru
poziție și total. Redis e **cache, nu sursă de adevăr** — `Leaderboard::rebuildSubject()`
îl reconstruiește din `subject_stats` după un flush. Există test pentru asta.

Un elev fără niciun răspuns la o disciplină întoarce `null`, nu „ultimul din N".

## 10c. XP și nivel sunt PE DISCIPLINĂ

`subject_stats.xp` e sursa pentru nivel și clasament. `users.xp` rămâne suma
tuturor disciplinelor — util pe profil, inutil pentru comparație, pentru că
amestecă matematica cu engleza.

Curba de nivel trăiește ÎNTR-UN SINGUR LOC: `App\Domain\Xp\Level`. Era
duplicată în `SubmitAnswer` și `RecomputeUser`; cu nivelul pe disciplină ar fi
devenit trei implementări ale aceleiași formule. Nu o rescrie inline.

Antetul aplicației arată doar semnalele cu adevărat globale — streak-ul și
progresul spre capul zilnic de XP. Nivelul se vede pe pagina disciplinei.

## 10d. Trei zone per disciplină

`/disciplina/{slug}` (`SubjectHubController`) e spațiul de lucru al elevului
autentificat, distinct de pagina publică indexabilă `/{subject}`:

    Curs       teoria — în construcție, declarată explicit ca atare
    Teste      antrenamentul — funcțional
    Formule    de consultat și de învățat („ascunde și verifică"), §10k
    Clasament  poziția la ACEASTĂ disciplină, cu rândul propriu

Cursul apare vizibil ca „în construcție" în loc să fie ascuns: elevul vede unde
merge produsul. Clasamentul afișează primii N plus rândul propriu dacă elevul e
în afara lor — fără el, cineva de pe locul 340 vede doar necunoscuți.

## 10e. Cursul: blocuri tipizate, grafice desenate

Teoria stă în `lessons.blocks` — o listă ORDONATĂ de blocuri tipizate
(`heading`, `text`, `formula`, `plot`, `table`, `code`, `example`, `warning`,
`video`), nu HTML
liber. Fiecare tip are componentul lui, deci se randează diferit pe mobil, se
traduce bloc cu bloc pentru segmentul alolingv, se validează automat, iar un
editor nu poate injecta markup arbitrar.

**Graficele se DESENEAZĂ, nu se încarcă.** Blocul `plot` conține puncte
precalculate în PHP; `Plot.vue` le transformă în SVG. Câțiva kiloocteți în loc de
zeci, clar pe orice ecran, se adaptează la temă, trăiește în Git alături de
lecție — și nu avem nevoie de niciun pipeline de assets. Punctele se calculează
pe server ca să nu ajungem la `eval` sau la un parser de formule în bundle.

Nu adăuga fișiere imagine în lecții de matematică: `CourseTest` respinge orice
referință la `.png` sau `.jpg`.

**Lecția NU dă XP.** A citi teoria nu înseamnă a ști; progresul se câștigă din
răspunsuri. `lesson_progress` e deliberat separat de `mastery` și de
`subject_stats`, iar „marchează ca parcursă" nu atinge XP-ul.

**Videoclipurile stau în `skill_videos`, separat de blocuri**, ca un clip care
dispare de pe YouTube să nu lase o gaură în mijlocul teoriei. `VideoEmbed.vue`
încarcă iframe-ul abia la click — un player YouTube trage ~1 MB de JavaScript.

## 10f. Fișierele încărcate nu se stochează niciodată

`App\Domain\Users\ProcessAvatar` decodează imaginea primită în pixeli și scrie
una **complet nouă**. Nu e o optimizare, e regula de securitate:

- fișierele „poliglote" (imagine validă care transportă și cod) nu supraviețuiesc
  reencodării;
- metadatele EXIF dispar — inclusiv **coordonatele GPS** pe care telefoanele le
  scriu implicit. Utilizatorii sunt minori; un avatar nu are de ce să spună unde
  locuiește cineva;
- validarea devine reală: dacă GD nu poate decoda fișierul, nu e o imagine —
  indiferent ce spun extensia sau antetul MIME trimis de browser.

Orientarea EXIF se aplică ÎNAINTE de a arunca metadatele, altfel portretele apar
culcate.

`users.avatar_path` NU e în `#[Fillable]`: e o cale pe disc, scrisă doar de
ProcessAvatar. Un câmp trimis din formular ar însemna cale controlată de
utilizator — pentru afișare sau pentru ștergere.

Se generează două dimensiuni (64 și 256). Un avatar de 256px într-o casetă de
24px din antet ar fi risipă pe date mobile.

**Emoji-ul rămâne implicit**, nu e un fallback de avarie: nu ocupă spațiu, nu
cere moderare, e instant.

## 10g. Moderarea conținutului public

Public e doar `username` și avatarul. Numele real și emailul nu ies din cont.

**Nimic ales de utilizator nu ajunge public înainte să-l vadă un om**, dar
utilizatorul NU e blocat. Se înscrie cu numele dorit, folosește platforma
imediat, iar ceilalți îl văd sub `public_alias` până la aprobare. Compromisul
opus — a bloca înscrierea până la aprobare — ar omorî conversia și ar transforma
coada într-un blocaj de creștere.

`publicName()` = ce văd CEILALȚI. Pentru ce vede elevul despre sine, folosește
`username` direct. Confuzia dintre cele două e ușor de făcut și greu de observat.

**Emoji-urile sunt auto-aprobate** — vin dintr-o listă fixă de 12, nu au ce
modera, iar majoritatea utilizatorilor nu observă nicio întârziere. Doar
imaginile încărcate intră în coadă.

Orice SCHIMBARE retrimite la moderare: un nume aprobat nu e un permis pentru ce
urmează. La fel pentru înlocuirea imaginii.

Atenție la `select()` restrâns pe User: `publicName()` are nevoie de
`username_status` și `public_alias`, iar `avatarUrl()` de `avatar_status`. O
coloană lipsă nu dă eroare — metoda o vede ca null și returnează tăcut altceva
decât trebuie. Exact așa a apărut un bug în clasament.

**Numele se filtrează automat** (`UsernamePolicy`), la înscriere ȘI în setări —
dacă apare un al treilea loc unde se poate seta, regula `AcceptableUsername`
trebuie adăugată și acolo, altfel filtrul devine ocolibil.

Normalizarea e obligatorie: fără ea, „adm1n", „@dmin" și „a-d-m-i-n" trec toate.
Caracterele ambigue (`1` = i sau l) generează variante verificate separat, cu
număr plafonat — altfel un nume cu douăzeci de cifre ar fi un vector de atac
prin consum de procesor.

Listele au comportament diferit după riscul de fals-pozitiv:
`reserved_exact` se compară ca nume întreg (`mod` ca substring ar bloca „model"),
`reserved_contains` se caută oriunde (impersonarea arată a compunere:
„ebac_oficial"). Termenul `ance` a trebuit mutat la exact — apare în
„stefancelmare".

**Avatarul nu se poate filtra automat**: rămâne raportarea. La 3 semnalări de la
OAMENI DISTINCȚI (`distinct('reported_by')`, altfel un cont ar putea ascunde
avatarul oricui prin repetare), avatarul se ascunde automat. E o măsură
temporară, nu un verdict: fișierul rămâne pe disc, iar respingerea semnalării îl
readuce.

Sancțiunile sunt proporționale și **nu includ ștergerea contului** — un nume
nepotrivit nu justifică pierderea progresului unui elev. Redenumirea forțată
blochează navigarea, dar lasă accesibile setările și deconectarea, iar alegerea
unui nume acceptabil o încheie.

Prinde `ReportRejected`, nu `RuntimeException`: catch-ul larg a ascuns odată o
eroare de mass-assignment, prezentând-o utilizatorului ca mesaj liniștitor.

## 10h. „Ce nu am înțeles" — istoricul dificultăților

Captura e la nivel de BLOC (precisă), organizarea pe COMPETENȚĂ (așa gândește un
profesor).

Funcționează în DOUĂ contexte, cu comportament diferit:
  - în LECȚIE, butonul stă sub un bloc anume — elevul deja arată cu degetul;
  - după un EXERCIȚIU GREȘIT, i se arată blocurile lecției temei și poate bifa
    oricâte. „Nu înțeleg tema" e prea gros ca să folosească unui profesor.

Endpoint-ul primește `block_ids` ca listă (goală = toată tema), iar nota se
atașează primului element: e o singură întrebare, chiar dacă trimite spre mai
multe locuri din lecție.

Fiecare element din listă duce ÎNAPOI în lecție, la blocul lui:
`/disciplina/{d}/curs/{c}?bloc={id}`. Parametru, nu ancoră `#` — ancora
declanșează saltul nativ al browserului, care ignoră antetul lipicios și nu
așteaptă randarea formulelor (KaTeX schimbă înălțimile după încărcare).

Acolo, blocul se evidențiază și apare acțiunea „Acum am înțeles". Fără ea,
elevul ar trebui să recitească în lecție, apoi să revină în listă ca să bifeze —
două pagini pentru o decizie, deci în practică n-ar face-o.

Dacă blocul nu mai există (lecția s-a editat), evidențierea se ignoră tăcut:
o evidențiere spre nimic arată ca o defecțiune. „Nu înțeleg derivatele" e inutilizabil; „nu înțelege regula produsului
și exemplul cu tangenta" e o lecție de 10 minute.

Blocurile au `id` STABIL, atribuit la prima salvare în `Lesson::booted()`. Fără
el, un bloc inserat la mijlocul unei lecții ar face ca tot ce a marcat un elev să
arate spre alt conținut. Nu regenera id-urile existente.

`learning_gaps.excerpt` e o FOTOGRAFIE a textului la momentul marcării — lecția
se poate edita sau șterge, dar istoricul trebuie să rămână inteligibil.

**Lista e strict privată.** Nici alți elevi, nici administratorii. Dacă elevul ar
bănui că e urmărit, ar înceta să recunoască ce nu înțelege, iar funcția și-ar
pierde rostul. O duce la profesor când vrea el (există și mod de print).

**Nu acordă XP și nu penalizează nimic.** Orice recompensă ar transforma-o în
ceva de exploatat; orice penalizare ar face-o să dispară.

Deliberat separat de `mastery`: mastery-ul măsoară ce POATE face elevul, dedus
din răspunsuri; asta înregistrează ce SIMTE că nu înțelege. Un elev poate
răspunde corect din intuiție și totuși să nu înțeleagă.

Cheia de unicitate e `(user_id, skill_id, block_id)` cu `NULLS NOT DISTINCT` —
fără `skill_id`, două teme marcate fără bloc s-ar suprascrie reciproc, pentru că
`where block_id is null` găsește primul rând.

## 10i. Iconițele se desenează, emoji-ul rămâne doar la avatar

Interfața folosea emoji ca pictograme. Nu mai o face, din trei motive concrete:
steagurile (🇷🇴, 🇬🇧) apar pe Windows ca două litere, nu ca steag; desenul și
lățimea celorlalte variază de la un Android la altul — exact publicul nostru; și
nu se pot colora în tema aplicației.

Nu am adăugat o bibliotecă de iconițe. `resources/icons/icons.json` e un set de
~36 de forme extrase din Lucide (ISC) cu `node bin/icons.mjs`; `lucide-static`
rămâne devDependency și nimic din el nu ajunge la client. Tot setul costă ~2,5 KB
comprimat, într-un chunk partajat — intrarea a rămas neatinsă (§10).

**Un singur fișier, doi consumatori:** `Icon.vue` pentru aplicație și
`<x-ebac-icon>` (`App\Support\Icons`) pentru paginile publice, care n-au niciun
JavaScript (§14). O soluție doar-Vue ar fi lăsat Blade-ul descoperit și, în
practică, ar fi produs un al doilea set care ajunge să difere.

Componentul se numește `ebac-icon`, nu `icon`: `<x-icon>` e deja înregistrat de
blade-ui-kit/blade-icons, o dependență a Filament, și componentul lui de clasă
câștigă în fața celui anonim.

`subjects.icon` și `achievements.icon` stochează NUME de iconițe, nu emoji.
Citește-le prin `iconName()`: un nume necunoscut randează nimic — o casetă goală
care arată a defecțiune — iar metoda întoarce iconița implicită.

Iconița are 1em și `vertical-align: -0.125em`, deci crește cu textul din jur ca
emoji-ul pe care îl înlocuiește. În proză o lași inline (`class="mr-1"`); `flex`
pe un paragraf rupe încadrarea textului, fiindcă fiecare nod devine element de
flex.

`IconTest` verifică fiecare nume folosit, fiecare iconiță din baza de date și
faptul că emoji-urile nu se strecoară înapoi. **Avatarurile sunt exceptate
deliberat** — lista fixă de 12 emoji rămâne opțiunea implicită pentru că nu
ocupă spațiu, nu cere moderare și e instant (§10f, §10g).

## 10j. Identitatea vizuală: albastru și galben, zi și noapte

Culorile steagului. Tema întunecată (implicită) stă pe bleumarin `#0a1636`, nu
pe negru; cea luminoasă pe alb, cu cerneală bleumarin. Tokenurile din
`resources/css/app.css` își schimbă VALOAREA cu tema, nu rolul:

- `action` / `on-action` — acțiunea principală (`btn-primary`, bara de progres,
  răspunsul corect): galbenă pe întunecat, albastră pe luminos. Galbenul pe alb
  n-ar avea contrast, de aceea nu e o singură culoare.
- `hero` / `on-hero` / `hero-accent` — blocul „Ținta de azi" de pe panou.
- `slate-*`, `white`, `brand-*` — rampa existentă, redefinită (§ „Tema
  luminoasă" din app.css). Componentele nu se rescriu la o schimbare de paletă.

Fonturile — Onest pentru text, Unbounded doar pentru siglă, cifre mari și câteva
titluri — stau în `resources/fonts`, generate cu `bin/fonts.sh`. NU de pe Google
Fonts: utilizatorii sunt minori (§9). Fiecare font e tăiat în latin, literele
românești (~3 KB) și chirilic, cu `unicode-range`; fișierul „latin-ext" întreg
(până la 118 KB) s-ar fi descărcat la primul „ș". Nu-l adăuga înapoi.

## 10k. Formule: ce apare în anexă și ce trebuie știut

Testele ANCE de matematică au la final o anexă cu formule, dar FIECARE TEST
își alege formulele după itemii lui. `formulas.annex` ține exact testele în
care a apărut o formulă (`r_sb26`, `u_ss24`…, citite din cele 23 de anexe
2021–2026), iar eticheta e „în anexă la 11 din 12 teste", nu „o primești la
examen". Ce n-a apărut niciodată e „de știut pe de rost", cu sursa în
programă (unitatea de conținut). Profilul doar alege ce numărătoare se
arată (§2a); arte și sport dau testul de la umanist.

„Ascunde și verifică" nu dă XP și nu atinge `mastery` (§6): a recunoaște o
formulă nu înseamnă a ști s-o aplici. Bifele stau în `formula_progress`,
șterse odată cu contul.

Formulele lungi se rup pe rânduri la seeding (`MathFormulaSeeder::stacked`):
KaTeX nu rupe singur o formulă afișată, iar pe un telefon de 360px cinci
egalități pe un rând ies din ecran.

## 11. Conținutul se validează automat, nu prin recitire

`ContentIntegrityTest` verifică structural tot conținutul publicat: cheia de
corectare există între variante, cheile pașilor se potrivesc, perechile de
potrivire nu sunt ambigue, fiecare item are competență, sursă, explicație și
indicii, graful de prerechizite nu are cicluri.

E poarta care face banca de itemi scalabilă: la mii de itemi, recitirea manuală
nu mai depistează un item cu cheia greșită. Nu ocoli testul — dacă un item nou
îl încalcă, itemul e greșit, nu testul.

## 11b. Fiecare item se sprijină pe o resursă oficială

Regulă cerută explicit de autorul proiectului. Sursele acceptate: programa de
examen ANCE, testele și baremele ANCE din sesiunile anterioare și testele model,
curriculumul MEC și manualele aprobate de MEC. Nu inventa fapte, date, citate
sau formulări de cerință care nu se regăsesc într-o sursă oficială.

`items.source` numește documentul oficial, iar `items.source_ref` duce la locul
exact (link + pagină/subiect). O valoare generică de tipul `seed` nu e o sursă.

Convenția: `source` = `ance:<fișier>` (ex. `ance:12_llro_barem_r_sb26`),
`source_ref` = URL-ul PDF-ului ANCE + `#item<N>`. Cheia de corectare e ce
acceptă baremul, nu o variantă aleasă de noi. Textele literare se citează doar
pe scurt (o propoziție), cu autorul și opera.

Itemii întreabă MATERIA, nu baremul: testul ANCE nu întreabă niciodată „câte
puncte primește…". Cum se notează proba e strategie utilă elevului, deci stă în
lecții (`RomanaLessonSeeder`), iar `ContentIntegrityTest` respinge enunțurile
despre punctaj sau barem.

`ContentIntegrityTest` verifică regula pe disciplinele auditate (lista
`$auditate`), care crește pe măsură ce se face auditul. Starea la 2026-10-13:
toate disciplinele cu itemi — limba română, matematica, informatica, istoria
și engleza — sunt construite din teste ANCE (testele 2024–2026, la matematică
2021–2026, la engleză 2022–2026; `database/seeders/Matematica`, `Informatica`,
`Istorie`, `Engleza`); itemii vechi, fără sursă, sunt retrași, nu șterși.
Geografia pornește din programa ANCE (testul model cu barem și exemplele de
itemi): doar itemii care se rezolvă din ce e pe ecran. Cei care cer atlasul —
coordonate, distanțe, ora pe Glob, harta-contur — așteaptă o componentă de
hartă. Climogramele și piramidele se desenează din cifre (§10e).

La istorie, ca la română, toată proba se corectează pe barem; în Teste se
exersează doar sarcinile „Identifică în sursă", cu sursa reală din test,
citată pe scurt, și cu răspunsurile din barem.

La informatică, „ce afișează programul" se SCRIE, ca la examen, nu se alege
dintr-o listă — variantele greșite ar fi inventate. Itemii numerici și de
completat pot arăta și ei cod (`payload.code_variants`).

Itemii rezervați simulării nu pot fi chiar problemele oficiale — acelea sunt
publice, cu barem cu tot. Sunt variante cu alte date ale unei probleme ANCE
anume (`source_ref` = PDF-ul + `#itemN-variantaK`), iar explicația spune că
răspunsul e calculat de noi.

## 12. Informatica: codul se dă în ambele limbaje

Examenul din Moldova acceptă **Pascal sau C/C++, la alegerea candidatului**, iar
itemii oficiali ANCE afișează programul în coloane paralele. Itemii cu cod
folosesc `payload.code_variants = {cpp, pascal}`, iar `CodeBlock.vue` comută
între ele și ține preferința în localStorage.

Un item cu cod într-un singur limbaj dezavantajează jumătate din candidați;
`ContentIntegrityTest` îl respinge.

## 13. Limbile străine: 70% din punctaj NU e auto-corectabil

Programa ANCE „Limba străină" (nivel **B1**, nu B2) are 3 subiecte:
Subiectul I comprehensiune scrisă — 30p (auto-corectabil), Subiectul II eseu
structurat 180–200 de cuvinte — 40p, Subiectul III articol/scrisoare pe o temă
de cultură — 30p. **Producerea scrisă valorează 70 din 100 de puncte.**

Itemii sunt sarcinile Subiectului I din testele ANCE. Baremul de limbă străină
nu dă chei — răspunsul corect e „în baza textului" —, deci explicația citează
fraza care îl justifică. Textul întreg (400–450 de cuvinte) nu se reproduce:
fiecare item arată doar fragmentul de care are nevoie, iar `source_ref` duce la
PDF. Ordonarea ideilor și ideea principală cer textul întreg, deci lipsesc.
Simularea nu poate folosi variante „cu alte date" — ar fi texte inventate —, așa
că folosește două teste întregi (2022 și 2023, sesiunea suplimentară), ținute în
afara antrenamentului. Gramatica și registrul stau în lecții.

Nu pretinde nicăieri în interfață că acoperă eseul: `structure` din
`exam_blueprints` marchează fiecare subiect cu `auto_gradable`.

Eseul are nevoie de autoevaluare pe barem, eseuri model adnotate și feedback —
muncă editorială, nu generare de itemi. Aceeași limitare se aplică la Limba și
literatura română.

Convenția de limbă la itemii de limbi străine: enunț și variante în limba
străină, indicii și explicații în română. `items.language` rămâne `ro` — el
descrie varianta lingvistică a PLATFORMEI (ro/ru pentru segmentul alolingv), nu
limba textului din item.

## 14. Public vs. aplicație: două produse, două tehnologii

`routes/web.php` are trei zone. Paginile **publice** (`/`, `/{disciplina}`,
`/{disciplina}/{competenta}`, `/bac-{an}`, `/sitemap.xml`) sunt Blade, **fără
niciun JavaScript** — trebuie citite de crawlere fără randare și încărcate
instant pe telefoane ieftine. Aplicația rămâne Inertia și e marcată `noindex`.

Rutele cu parametru liber (`/{subject}`) se înregistrează **ULTIMELE**, altfel
înghit rutele aplicației. Există test pentru asta.

**Itemii de holdout nu ajung NICIODATĂ pe o pagină publică.** Publicarea unui
item îl arde — oricine îl găsește cu răspuns cu tot. Paginile publice folosesc
exclusiv `Item::practicePool()`, iar `SeoTest` verifică fiecare item de holdout
că nu apare nicăieri public, nici enunțul, nici explicația.

Ziggy expune doar rutele din `config/ziggy.php` → `only`. Implicit, directiva
`@routes` serializa toată harta aplicației în fiecare pagină, inclusiv `/admin`,
`/horizon`, `/pulse` și ruta PUT de upload. Orice rută nouă folosită din Vue se
adaugă acolo explicit.

`APP_URL` generează canonical-urile și sitemap-ul. În producție trebuie să fie
domeniul real, altfel indexezi `localhost`.

## Tipuri noi de item

1. Valoare nouă în `App\Enums\ItemType`
2. Mapare în `App\Domain\Grading\GraderRegistry` (de obicei un grader existent)
3. Component Vue în `resources/js/components/items/` + `index.ts`
4. Condiția de completitudine în `isAnswerComplete()`

Zero migrații. 12 tipuri de item folosesc 5 graderi și 5 componente.
