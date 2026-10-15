<script setup lang="ts">
import { computed } from 'vue';
import type { PyramidSeries } from '@/types';

/**
 * Piramida populației: bărbații la stânga, femeile la dreapta, grupele de vârstă
 * de jos în sus. Desenată din procente (§10e), ca o comparație Africa/Europa
 * să încapă una lângă alta chiar pe un telefon îngust.
 */
const props = defineProps<{ series: PyramidSeries; groups: string[]; max: number }>();

const W = 200;
const rowH = 14;
const mid = 34; // coloana etichetelor de vârstă
const half = (W - mid) / 2;
const top = 4;
const H = computed(() => top + rowH * props.groups.length + 18);

// Rândul 0 e grupa cea mai tânără și stă JOS.
const rows = computed(() =>
    props.groups.map((label, i) => {
        const y = top + (props.groups.length - 1 - i) * rowH;
        const m = (props.series.male[i] / props.max) * half;
        const f = (props.series.female[i] / props.max) * half;
        return { label, y, m, f };
    }),
);

const description = computed(
    () =>
        `Piramida populației, ${props.series.title}. Procente pe grupe de vârstă, de la ${props.groups[0]} la ${props.groups.at(-1)}. ` +
        `Bărbați: ${props.series.male.join(', ')}. Femei: ${props.series.female.join(', ')}.`,
);
</script>

<template>
    <figure class="min-w-0 flex-1">
        <figcaption class="mb-1 text-center text-sm font-semibold">{{ series.title }}</figcaption>
        <svg :viewBox="`0 0 ${W} ${H}`" class="w-full" role="img" :aria-label="description">
            <template v-for="row in rows" :key="row.label">
                <rect :x="half - row.m" :y="row.y" :width="row.m" :height="rowH - 1.5" class="fill-brand-500" />
                <rect :x="half + mid" :y="row.y" :width="row.f" :height="rowH - 1.5" class="fill-amber-400" />
                <text :x="half + mid / 2" :y="row.y + rowH - 4" text-anchor="middle" class="fill-slate-400 text-[9px]">{{ row.label }}</text>
            </template>
            <text :x="half / 2" :y="H - 3" text-anchor="middle" class="fill-slate-300 text-[10px]">Bărbați, %</text>
            <text :x="half + mid + half / 2" :y="H - 3" text-anchor="middle" class="fill-slate-300 text-[10px]">Femei, %</text>
        </svg>
    </figure>
</template>
