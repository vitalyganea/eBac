#!/usr/bin/env node
//
// Verifică bugetul de JavaScript după `vite build` (invariante §10).
//
// De ce un script și nu doar `chunkSizeWarningLimit`: avertismentul lui Vite
// e pe chunk individual și nu oprește nimic. Bugetul real e ce descarcă
// telefonul înainte să vadă ceva — intrarea plus tot ce importă ea static.
// Intrarea a depășit pragul cu 2 KB fără ca cineva să observe; de aici încolo,
// depășirea pică build-ul în CI.
//
// Verifică două lucruri:
//   1. intrarea + importurile ei statice ≤ 200 KB (brut, ca în raportul Vite);
//   2. KaTeX nu ajunge în închiderea statică a niciunei pagini — se încarcă
//      la cerere din `MathText.vue`, altfel fiecare pagină plătește 258 KB.
//
// Rulare:  npm run build && node bin/bundle-size.mjs

import { readFileSync, statSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const BUILD = join(ROOT, 'public', 'build');
const ENTRY = 'resources/js/app.ts';
const BUDGET_KB = 200;

const manifest = JSON.parse(readFileSync(join(BUILD, 'manifest.json'), 'utf8'));

/** Cheile din manifest încărcate static pornind de la `key` (inclusiv el). */
function staticClosure(key, seen = new Set()) {
    if (seen.has(key)) return seen;
    seen.add(key);
    for (const dep of manifest[key]?.imports ?? []) staticClosure(dep, seen);
    return seen;
}

const kb = (key) => statSync(join(BUILD, manifest[key].file)).size / 1000;

const failures = [];

const entryKeys = [...staticClosure(ENTRY)];
const entryKb = entryKeys.reduce((sum, key) => sum + kb(key), 0);
console.log(`  Intrare (${entryKeys.length} fișiere): ${entryKb.toFixed(2)} KB din ${BUDGET_KB} KB`);
if (entryKb > BUDGET_KB) {
    failures.push(`Intrarea are ${entryKb.toFixed(2)} KB, peste bugetul de ${BUDGET_KB} KB.`);
}

for (const page of manifest[ENTRY].dynamicImports ?? []) {
    const heavy = [...staticClosure(page)].filter((key) => /katex/i.test(manifest[key].file));
    if (heavy.length > 0) {
        failures.push(`${page} importă static KaTeX (${heavy.join(', ')}). Folosește MathText.vue.`);
    }
}

if (failures.length > 0) {
    console.error('\n  Bugetul de bundle e depășit:\n');
    for (const f of failures) console.error(`    - ${f}`);
    console.error('');
    process.exit(1);
}

console.log('  Bugetul de bundle e respectat ✓');
