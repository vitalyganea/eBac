# eBac 🇲🇩

Platformă gamificată de pregătire pentru examenul național de bacalaureat din
Republica Moldova. Multi-disciplină, cu un singur motor de joc comun.

**Stack:** Laravel 13 · PHP 8.3 · PostgreSQL 18 · Valkey (Redis) · Inertia 2 ·
Vue 3 · TypeScript · Tailwind 4 · KaTeX

---

## Instalare

### 1. Dependențe de sistem (o singură dată, necesită sudo)

```sh
sudo apt install -y php8.3-pgsql php8.3-intl php8.3-gd
sudo usermod -aG docker $USER   # apoi deloghează-te și reloghează-te
```

- `php8.3-pgsql` — driverul de bază de date
- `php8.3-intl` — cerut de Filament (panoul de administrare)
- `php8.3-gd` — procesare de imagini (diagrame la biologie/geografie)

### 2. Node 22

```sh
curl -fsSL https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.7/install.sh | bash
nvm install 22 && nvm alias default 22
```

Vite 8 cere Node 20+. Node 18 nu funcționează.

### 3. Servicii locale

```sh
docker compose up -d
```

Pornește PostgreSQL 18 (`:5433`), Valkey (`:6379` → `:6380`) și Mailpit
(SMTP `:1025`, interfață web `:8025`).

Porturile sunt **deliberat non-implicite**: pe mașina de dezvoltare rulează și
alte proiecte (`crm-redis` ocupă 6379, `crm-mariadb` ocupă 3306). Așa pot rula
ambele stive în paralel.

Postgres 18+ cere **un singur mount** la `/var/lib/postgresql`, nu la
`.../data` — imaginea pune datele într-un subdirector cu numele versiunii majore.

### 4. Aplicația

```sh
composer install
npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm run build
```

### 5. Dezvoltare

```sh
node -v             # trebuie v22.x — dacă arată v18, rulează: nvm use
composer dev
```

Pornește patru procese într-un singur terminal: **server** (`artisan serve`,
:8000), **vite** (:5173, hot reload), **horizon** (coada) și **logs**.
`Ctrl+C` le oprește pe toate.

Deschide http://localhost:8000.

**Node 18 nu funcționează.** `php artisan dev` moștenește `node` din PATH-ul
shell-ului; dacă e cel de sistem (v18), Vite cade cu
`SyntaxError: Invalid regular expression flags` — flagul de regex `/v` folosit de
`string-width` există doar din Node 20.

`composer dev` și `composer build` trec prin `bin/with-node.sh`, care caută
un Node potrivit în nvm și, dacă nu-l găsește, afișează instrucțiunile în loc de
un stack trace din `node_modules`. Fișierul `.nvmrc` fixează versiunea; `nvm use` o aplică.

Dacă `nvm` nu e găsit deloc, terminalul a fost deschis înainte de instalarea
nvm — `source ~/.bashrc`.

**De ce `artisan serve` și nu Octane în dezvoltare:** Octane e instalat și își
înregistrează propria comandă de dev (`octane:start --watch`), care implicit
caută RoadRunner. Dar motivul principal e altul — sub Octane workerii persistă
între request-uri și state-ul static supraviețuiește, exact ce nu vrei cât timp
scrii cod. `AppServiceProvider::configureDevServer()` readuce `artisan serve`
în dezvoltare; Octane rămâne pentru producție.

### Teste

```sh
sg docker -c 'docker exec ebac-postgres psql -U ebac -d ebac -c "CREATE DATABASE ebac_test OWNER ebac;"'
vendor/bin/pest
vendor/bin/pint --test
npm run typecheck
npm run build && npm run bundle-size
```

CI-ul (`.github/workflows/ci.yml`) rulează exact aceiași pași la fiecare push.

Testele rulează pe **Postgres**, nu pe SQLite in-memory: migrațiile folosesc
`jsonb`, indici GIN și secvențe, iar diagnosticul e un CTE recursiv. Un SQLite
care „trece" ne-ar minți. Redis folosește indexul 15, ca testele să nu polueze
clasamentele locale.

### Panoul de administrare

http://localhost:8000/admin — coada de validare a itemilor. Accesul cere
`role` = `editor` sau `admin` (vezi `User::canAccessPanel()`); elevii nu intră,
pentru că ar vedea cheile de corectare.

---

## Ce e deja construit

| Zonă | Stare |
|---|---|
| Schema completă (18 migrații) | ✅ |
| Strat de domeniu (`app/Domain`) | ✅ |
| Corectare: 5 graderi / 12 tipuri de item | ✅ 17 teste |
| XP cu anti-farming + cap zilnic | ✅ |
| Elo dublu (elev + item) | ✅ |
| Selecție adaptivă de itemi (~70% succes) | ✅ |
| Mastery + repetiție spațiată (SM-2) | ✅ job async |
| XP, nivel și clasament pe disciplină | ✅ poziție + total + progres |
| Hub disciplină: Curs / Teste / Clasament | ✅ |
| Curs: matematică | 8 lecții · 68% din pondere |
| Curs: limba engleză | 4 lecții · 58% din pondere |
| Curs: informatică | 4 lecții · 38% din pondere |
| Curs: istorie | 2 lecții · 18% din pondere |
| Felie verticală web: antrenament cap-coadă | ✅ |
| API v1 + Sanctum (pentru mobil) | ✅ schelet |
| Conținut: matematică (real și umanist) | 20 de competențe, 168 de itemi din testele și baremele ANCE 2024–2026, plus 28 de variante rezervate simulării |
| Conținut: informatică | 16 competențe, 17 muchii, 19 itemi, cod C++/Pascal |
| Conținut: limba engleză | 11 competențe, 11 muchii, 20 itemi, nivel B1 |
| Conținut: istorie | 12 competențe, 12 muchii, 19 itemi, itemi pe surse |
| Conținut: limba și literatura română | 8 competențe, 45 de itemi din testele și baremele ANCE 2021–2026, o lecție despre notare |
| Poartă automată de calitate pe itemi | ✅ 8 verificări structurale |
| Suprafață publică SEO (Blade, zero JS) | ✅ 48 URL-uri în sitemap |
| Alegerea disciplinelor, decuplată de profil | ✅ toate 10 selectabile |
| Indicii progresive, autoritare pe server | ✅ 51 indicii, 3/item |
| Filament: coada de validare a itemilor | ✅ cu tab-uri pe urgență |
| Pagină de setări (profil, clasament, parolă, ștergere cont) | ✅ |
| Încărcare de avatare (reencodare, EXIF eliminat) | ✅ |
| Moderare: pre-aprobare nume + avatar, filtru automat, raportare | ✅ |
| Istoric privat al dificultăților, pe blocuri și teme | ✅ |
| Simulare BAC: structura examenului, holdout, termen pe server | ✅ matematică, informatică, engleză, istorie |
| Teste (Pest) | ✅ 300+, inclusiv toate rutele × toate rolurile |

## Ce urmează

1. **Duel-fantomă** — jucat împotriva unei sesiuni înregistrate; nu are nevoie de
   WebSockets, deci funcționează cu un singur utilizator online.
2. **Banca de itemi** — adevărata problemă a proiectului. Motorul e scris;
   10.000 de itemi buni nu sunt.
3. **Provocarea zilei + streak reminder.**
4. **PWA + offline** (`vite-plugin-pwa` e deja instalat).

---

## Producție

Un singur server Hetzner duce tot. Vezi calculul: la 100% penetrare de piață
(~20.000 de candidați/an în MD) vârful e de ordinul a 100 req/s.

```sh
php artisan octane:install --server=frankenphp
php artisan octane:start --workers=8
```

**Atenție cu Octane:** workerii persistă între request-uri. Nu ține niciodată
date de utilizator în proprietăți statice.

**Fusuri orare:** aplicația și baza de date rulează în **UTC** (corect —
`answered_at` e un moment absolut). Granițele de ZI (streak, capul zilnic de XP,
provocarea zilei) se calculează în fusul *elevului*, cu fallback
`EBAC_DEFAULT_TIMEZONE=Europe/Chisinau`. Nu pune UTC ca fallback: Chișinăul e
UTC+2/+3, deci un elev care răspunde la 01:00 local și-ar pierde streak-ul.

---

## Surse oficiale

- [ANCE — sesiunea 2026](https://ance.gov.md/clasa-sesiunea-examen/sesiunea-2026-0)
- [ANCE — programa de examen la Informatică (2021)](https://ance.gov.md/sites/default/files/programa_bac_informatica_romana.pdf) — baza arborelui de competențe pentru informatică
- [ANCE — programa de examen la Limba străină (2021)](https://ance.gov.md/sites/default/files/programa_bac_limba_straina_4.pdf) — nivel B1, 3 subiecte, 30+40+30 puncte; programă unificată pentru toate limbile străine
- [ANCE — programa de examen la Istoria românilor și universală (2021)](https://ance.gov.md/sites/default/files/programa_bac_istoria_romanilor_si_universala_romana.pdf) — 4 subiecte, 24+22+19+35 puncte; obligatorie la profil umanist
- [ANCE — programa de examen la Limba și literatura română (2021)](https://ance.gov.md/sites/default/files/programa_bac_limba_si_literatura_romana.pdf) — competențele specifice 3, 4, 5
- [ANCE — testele și baremele de română](https://ance.gov.md/content/limba-de-instruire-sesiunea-de-baz%C4%83-bac-2025) — fișierele `12_llro_{test,barem}_{r,u}_{sb,ss}{21..26}.pdf`; proba e integral cu răspuns deschis
- [ANCE — informații pentru candidați](https://ance.gov.md/content/informa%C8%9Bii-utile-pentru-candida%C8%9Bii-la-examenul-na%C8%9Bional-de-bacalaureat)
- [MEC — ajustări la probele de BAC](https://mec.gov.md/ro/content/mec-si-ance-anunta-ajustari-la-unele-probe-din-cadrul-examenului-de-bacalaureat-incepand-cu)

Structura testelor se schimbă între sesiuni (la 2026 numărul de itemi a scăzut la
mai multe discipline). De aceea `exam_blueprints` e versionat pe sesiune și nu
hardcodat.
