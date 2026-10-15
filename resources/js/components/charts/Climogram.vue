<script setup lang="ts">
import { computed } from 'vue';
import type { ClimogramChart } from '@/types';

/**
 * Climograma: precipitațiile lunare ca bare (axa din stânga), temperatura ca
 * linie (axa din dreapta) — forma din testele ANCE de geografie.
 *
 * Se DESENEAZĂ din cifre, nu se încarcă o imagine (§10e): câțiva kiloocteți,
 * clară pe orice ecran și în ambele teme. Barele sunt albastre și linia roșie,
 * convenția atlaselor: apa e albastră, căldura e roșie.
 */
const props = defineProps<{ chart: ClimogramChart }>();

const months = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

// Cadrul desenului, în unități SVG; se scalează cu lățimea ecranului.
const W = 360;
const H = 240;
const left = 40;
const right = 34;
const top = 16;
const bottom = 28;
const plotW = W - left - right;
const plotH = H - top - bottom;
const slot = plotW / 12;

const pMax = computed(() => props.chart.precip_max);
const [tMin, tMax] = props.chart.temp_range;

const yP = (v: number) => top + plotH - (v / pMax.value) * plotH;
const yT = (v: number) => top + plotH - ((v - tMin) / (tMax - tMin)) * plotH;
const xMid = (i: number) => left + slot * i + slot / 2;

const bars = computed(() =>
    props.chart.precip.map((v, i) => ({
        x: left + slot * i + slot * 0.2,
        y: yP(v),
        w: slot * 0.6,
        h: top + plotH - yP(v),
    })),
);

const line = computed(() => props.chart.temp.map((v, i) => `${xMid(i)},${yT(v)}`).join(' '));

/**
 * Gradațiile, aliniate pe cele două axe. Câte sunt vine din date (`ticks`),
 * ca pașii să iasă rotunzi pe AMBELE axe, ca în figurile ANCE: 0–350 mm și
 * 15–29 °C merg în 7 pași (50 mm, 2 °C); 0–100 și 0–20 în 5 (20 mm, 4 °C).
 */
const ticks = computed(() => {
    const n = props.chart.ticks;
    return Array.from({ length: n + 1 }, (_, k) => ({
        y: top + plotH - (k / n) * plotH,
        p: Math.round((pMax.value / n) * k),
        t: Math.round((tMin + ((tMax - tMin) / n) * k) * 10) / 10,
    }));
});

const description = computed(
    () =>
        `Climogramă. Precipitații lunare, în milimetri: ${props.chart.precip.join(', ')}. ` +
        `Temperaturi medii lunare, în grade Celsius: ${props.chart.temp.join(', ')}.`,
);
</script>

<template>
    <figure class="rounded-2xl bg-slate-900 p-3">
        <figcaption v-if="chart.annual_precip" class="mb-1 text-center text-sm font-semibold">
            Precipitații anuale: {{ chart.annual_precip }} mm
        </figcaption>

        <svg :viewBox="`0 0 ${W} ${H}`" class="w-full" role="img" :aria-label="description">
            <g class="text-[10px]" fill="currentColor">
                <template v-for="tick in ticks" :key="tick.y">
                    <line :x1="left" :x2="W - right" :y1="tick.y" :y2="tick.y" class="stroke-white/10" />
                    <text :x="left - 5" :y="tick.y + 3" text-anchor="end" class="fill-brand-400">{{ tick.p }}</text>
                    <text :x="W - right + 5" :y="tick.y + 3" class="fill-rose-400">{{ tick.t }}</text>
                </template>
            </g>

            <rect
                v-for="(bar, i) in bars"
                :key="i"
                :x="bar.x"
                :y="bar.y"
                :width="bar.w"
                :height="bar.h"
                rx="2"
                class="fill-brand-500/70"
            />

            <polyline :points="line" fill="none" class="stroke-rose-400" stroke-width="2.5" stroke-linejoin="round" />
            <circle v-for="(v, i) in chart.temp" :key="i" :cx="xMid(i)" :cy="yT(v)" r="2.5" class="fill-rose-400" />

            <text
                v-for="(m, i) in months"
                :key="m"
                :x="xMid(i)"
                :y="H - 10"
                text-anchor="middle"
                class="fill-slate-400 text-[10px]"
            >{{ m }}</text>
        </svg>

        <div class="mt-1 flex flex-wrap justify-center gap-x-5 gap-y-1 text-xs text-slate-300">
            <span class="inline-flex items-center gap-1.5"><span class="size-3 rounded-sm bg-brand-500/70" />Precipitațiile, mm (stânga)</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-0.5 w-4 bg-rose-400" />Temperatura, °C (dreapta)</span>
        </div>
    </figure>
</template>
