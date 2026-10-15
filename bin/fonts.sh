#!/usr/bin/env bash
#
# Generează `resources/fonts/*.woff2` din pachetele Fontsource (Onest, Unbounded).
#
# Fișierele stau în repo, nu se iau de pe Google Fonts la rulare: utilizatorii
# sunt minori (§9), iar fiecare pagină ar trimite IP-ul lor unui terț.
#
# Fontsource împarte fontul pe alfabete, dar fișierul „latin-ext" (28 KB la
# Onest, 118 KB la Unbounded) acoperă și poloneza, turca, vietnameza… Unei
# pagini în română îi trebuie de acolo doar ă â î ș ț și majusculele lor, iar
# orice „ș" dintr-un titlu ar fi descărcat tot fișierul. Scriptul păstrează
# „latin" și „cyrillic" așa cum sunt și taie din „latin-ext" doar literele
# românești (~3 KB). `unicode-range` din resources/css/app.css trebuie să
# corespundă listei de aici.
#
# Pachetele @fontsource-variable/* rămân devDependencies: nimic din ele nu
# ajunge la client în afara fișierelor generate.
#
# Cere `pyftsubset` (pip install fonttools brotli).
#
# Rulare:  bin/fonts.sh

set -euo pipefail

cd "$(dirname "$0")/.."

RO='U+0102,U+0103,U+00C2,U+00E2,U+00CE,U+00EE,U+0218-021B,U+015E,U+015F,U+0162,U+0163'
OUT=resources/fonts

mkdir -p "$OUT"

for font in onest unbounded; do
    src="node_modules/@fontsource-variable/$font/files"

    cp "$src/$font-latin-wght-normal.woff2" "$OUT/$font-latin.woff2"
    cp "$src/$font-cyrillic-wght-normal.woff2" "$OUT/$font-cyrillic.woff2"

    pyftsubset "$src/$font-latin-ext-wght-normal.woff2" \
        --unicodes="$RO" \
        --layout-features='*' \
        --flavor=woff2 \
        --output-file="$OUT/$font-ro.woff2"
done

ls -l "$OUT"
