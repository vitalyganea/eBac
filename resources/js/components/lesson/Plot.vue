<script setup lang="ts">
import { computed } from 'vue';

/**
 * Grafic de funcție, desenat ca SVG din puncte PRECALCULATE pe server.
 *
 * De ce nu imagini încărcate: pentru matematică, majoritatea „imaginilor" sunt
 * grafice și figuri. Desenate ca SVG, ele sunt de câțiva kiloocteți în loc de
 * zeci, rămân clare pe orice ecran, se adaptează la temă și trăiesc în Git
 * alături de lecție — fără niciun pipeline de fișiere.
 *
 * De ce puncte precalculate și nu o expresie evaluată în browser: evaluarea unui
 * șir de caractere ca formulă înseamnă fie `eval`, fie un parser în plus în
 * bundle. Punctele vin din PHP, unde autorul lecției le-a definit o dată.
 */
interface Series {
    label?: string;
    points: [number, number][];
    color?: 'brand' | 'amber' | 'emerald' | 'rose' | 'slate';
    dashed?: boolean;
    /** Umple aria dintre curbă și axa Ox — pentru integrale definite. */
    fill?: boolean;
}

interface Marker {
    x: number;
    y: number;
    label?: string;
}

const props = withDefaults(defineProps<{
    series: Series[];
    xRange?: [number, number];
    yRange?: [number, number];
    caption?: string;
}>(), { xRange: undefined, yRange: undefined, caption: undefined });

const markers = defineModel<Marker[]>('markers', { default: () => [] });

const W = 600;
const H = 380;
const PAD = 36;

const COLORS: Record<string, string> = {
    brand: '#4f7dff',
    amber: '#f59e0b',
    emerald: '#10b981',
    rose: '#f43f5e',
    slate: '#94a3b8',
};

/** Limitele: cele declarate, altfel deduse din puncte cu o marjă de 10%. */
const bounds = computed(() => {
    const xs = props.series.flatMap((s) => s.points.map((p) => p[0]));
    const ys = props.series.flatMap((s) => s.points.map((p) => p[1]));

    const pad = (min: number, max: number) => {
        const span = max - min || 1;
        return [min - span * 0.1, max + span * 0.1] as [number, number];
    };

    return {
        x: props.xRange ?? pad(Math.min(...xs), Math.max(...xs)),
        y: props.yRange ?? pad(Math.min(...ys), Math.max(...ys)),
    };
});

const sx = (x: number) => {
    const [a, b] = bounds.value.x;
    return PAD + ((x - a) / (b - a)) * (W - 2 * PAD);
};

// SVG are y-ul inversat față de convenția matematică.
const sy = (y: number) => {
    const [a, b] = bounds.value.y;
    return H - PAD - ((y - a) / (b - a)) * (H - 2 * PAD);
};

const path = (points: [number, number][]) =>
    points.map((p, i) => `${i === 0 ? 'M' : 'L'}${sx(p[0]).toFixed(1)},${sy(p[1]).toFixed(1)}`).join(' ');

/** Aceeași curbă, dar închisă până la axa Ox — conturul ariei de sub grafic. */
const areaPath = (points: [number, number][]) => {
    if (points.length < 2) return '';
    const y0 = sy(0);
    const first = points[0];
    const last = points[points.length - 1];
    return `${path(points)} L${sx(last[0]).toFixed(1)},${y0.toFixed(1)} L${sx(first[0]).toFixed(1)},${y0.toFixed(1)} Z`;
};

/** Diviziuni întregi, dar nu mai mult de ~8 pe axă, ca să nu se aglomereze. */
function ticks([a, b]: [number, number]): number[] {
    const step = Math.max(1, Math.ceil((b - a) / 8));
    const out: number[] = [];
    for (let v = Math.ceil(a); v <= b; v += step) {
        out.push(v);
    }
    return out;
}

const xTicks = computed(() => ticks(bounds.value.x));
const yTicks = computed(() => ticks(bounds.value.y));

const axisY = computed(() => sy(Math.min(Math.max(0, bounds.value.y[0]), bounds.value.y[1])));
const axisX = computed(() => sx(Math.min(Math.max(0, bounds.value.x[0]), bounds.value.x[1])));

const color = (s: Series) => COLORS[s.color ?? 'brand'] ?? COLORS.brand;
</script>

<template>
    <figure class="my-5">
        <div class="overflow-x-auto rounded-xl border border-white/10 bg-white/[0.02]">
            <svg :viewBox="`0 0 ${W} ${H}`" class="h-auto w-full min-w-[320px]" role="img"
                 :aria-label="caption ?? 'Grafic de funcție'">
                <!-- Grilă -->
                <g stroke="currentColor" class="text-white/[0.06]">
                    <line v-for="t in xTicks" :key="'gx' + t" :x1="sx(t)" :y1="PAD" :x2="sx(t)" :y2="H - PAD" />
                    <line v-for="t in yTicks" :key="'gy' + t" :x1="PAD" :y1="sy(t)" :x2="W - PAD" :y2="sy(t)" />
                </g>

                <!-- Axe -->
                <g stroke="currentColor" class="text-slate-500" stroke-width="1.5">
                    <line :x1="PAD" :y1="axisY" :x2="W - PAD" :y2="axisY" />
                    <line :x1="axisX" :y1="PAD" :x2="axisX" :y2="H - PAD" />
                </g>

                <!-- Valori pe axe -->
                <g class="fill-slate-500" font-size="11" text-anchor="middle">
                    <text v-for="t in xTicks" :key="'tx' + t" :x="sx(t)" :y="axisY + 15" v-show="t !== 0">
                        {{ t }}
                    </text>
                </g>
                <g class="fill-slate-500" font-size="11" text-anchor="end">
                    <text v-for="t in yTicks" :key="'ty' + t" :x="axisX - 7" :y="sy(t) + 4" v-show="t !== 0">
                        {{ t }}
                    </text>
                </g>

                <!-- Aria de sub curbă, unde e cerută (integrale definite) -->
                <path v-for="(s, i) in series.filter((x) => x.fill)" :key="'a' + i"
                    :d="areaPath(s.points)"
                    :fill="color(s)"
                    fill-opacity="0.18"
                    stroke="none" />

                <!-- Curbele -->
                <path v-for="(s, i) in series" :key="i"
                    :d="path(s.points)"
                    fill="none"
                    :stroke="color(s)"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    :stroke-dasharray="s.dashed ? '6 5' : undefined" />

                <!-- Puncte marcate -->
                <g v-for="(m, i) in markers" :key="'m' + i">
                    <circle :cx="sx(m.x)" :cy="sy(m.y)" r="4.5" fill="#f59e0b" />
                    <text v-if="m.label" :x="sx(m.x) + 9" :y="sy(m.y) - 8"
                          class="fill-amber-400" font-size="12">{{ m.label }}</text>
                </g>
            </svg>
        </div>

        <!-- Legendă, doar dacă e nevoie de ea -->
        <div v-if="series.some((s) => s.label)" class="mt-2 flex flex-wrap gap-4 text-xs">
            <span v-for="(s, i) in series.filter((x) => x.label)" :key="i" class="flex items-center gap-1.5">
                <span class="inline-block h-0.5 w-5 rounded" :style="{ background: color(s) }" />
                <span class="text-slate-400">{{ s.label }}</span>
            </span>
        </div>

        <figcaption v-if="caption" class="mt-2 text-xs text-slate-500">{{ caption }}</figcaption>
    </figure>
</template>
