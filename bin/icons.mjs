#!/usr/bin/env node
//
// Generează `resources/icons/icons.json` din pachetul `lucide-static`.
//
// De ce un generator și nu o dependență în bundle: o bibliotecă de iconițe
// aduce un runtime (o funcție de creare de componente, un index cu toate cele
// 2000+ de forme) pentru ceva ce este, la bază, un șir de caractere cu un
// `path`. Intrarea e aproape de plafonul de 200 KB (vezi bin/bundle-size.mjs).
//
// De ce nu path-uri scrise de mână: un `d` greșit nu dă eroare — desenează o
// mâzgălitură. Aici formele vin din sursa oficială și se pot regenera la un
// upgrade, dar `lucide-static` rămâne devDependency: nimic din el nu ajunge la
// client.
//
// De ce un singur JSON și nu câte un fișier per iconiță: aceeași sursă e citită
// și de Vue (`Icon.vue`), și de Blade (`app/Support/Icons.php`). Paginile
// publice n-au JavaScript (vezi invariante §14), deci o soluție doar-Vue ar
// însemna două seturi de iconițe care ar ajunge să difere.
//
// Rulare:  node bin/icons.mjs

import { readFileSync, writeFileSync, existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const SRC = join(ROOT, 'node_modules', 'lucide-static', 'icons');
const OUT = join(ROOT, 'resources', 'icons', 'icons.json');

// Numele sunt cele din Lucide, nu unele semantice („hint", „streak"): o formă
// servește de obicei mai multe roluri — flacăra e și streak-ul zilnic, și
// realizarea de 30 de zile — iar nume semantice ar duplica același path.
const ICONS = [
    // Cromatica aplicației
    'circle-question-mark', // „nu înțeleg" — lacunele de învățare
    'lightbulb',            // indiciu
    'circle-check',         // răspuns corect
    'circle-x',             // răspuns greșit
    'check',                // bifă în linie
    'flame',                // streak
    'star',                 // XP
    'trending-up',          // progres
    'brain',                // bonus de retenție
    'target',
    'book-open',            // lecție
    'book',                 // disciplină, iconiță implicită
    'printer',
    'triangle-alert',       // blocul „greșeala tipică"
    'flag',                 // semnalare de conținut
    'square-check',         // casetă bifată
    'square',               // casetă goală
    'trophy',
    'biceps-flexed',
    'undo-2',
    'settings',             // setările contului, în antet
    'log-out',              // deconectare, în antet
    'clipboard-check',      // simularea BAC
    'timer',                // timpul rămas în simulare

    // Disciplinele (`subjects.icon`)
    'radical',              // matematică
    'book-open-text',       // limba și literatura română
    'globe',                // istorie
    'languages',            // limba engleză
    'atom',                 // fizică
    'flask-conical',        // chimie
    'dna',                  // biologie
    'laptop',               // informatică
    'map',                  // geografie
    'messages-square',      // limba română pentru alolingvi

    // Realizările (`achievements.icon`)
    'medal',
    'swords',               // dueluri
    'skull',                // boss de temă
    'map-pin',              // clasament național
    'graduation-cap',
];

/** Markup-ul din interiorul `<svg>`, fără wrapper, comentarii sau indentare. */
function extract(name) {
    const file = join(SRC, `${name}.svg`);
    if (!existsSync(file)) {
        throw new Error(`Iconița „${name}" nu există în lucide-static.`);
    }

    const svg = readFileSync(file, 'utf8');
    const inner = svg.match(/<svg[^>]*>([\s\S]*)<\/svg>/);
    if (!inner) {
        throw new Error(`Nu pot extrage conținutul din ${name}.svg`);
    }

    return inner[1].replace(/\s+/g, ' ').replace(/\s*\/>/g, '/>').trim();
}

const out = {};
for (const name of ICONS.slice().sort()) {
    out[name] = extract(name);
}

writeFileSync(OUT, `${JSON.stringify(out, null, 2)}\n`);

const bytes = readFileSync(OUT).byteLength;
console.log(`  ${Object.keys(out).length} iconițe → resources/icons/icons.json (${(bytes / 1024).toFixed(1)} KB)`);
