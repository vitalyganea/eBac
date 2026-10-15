<script setup lang="ts">
import Climogram from '@/components/charts/Climogram.vue';
import PopulationPyramid from '@/components/charts/PopulationPyramid.vue';
import type { ItemChartData } from '@/types';

/** Graficul unui item (`payload.chart`), ales după tip. */
defineProps<{ chart: ItemChartData }>();
</script>

<template>
    <Climogram v-if="chart.type === 'climogram'" :chart="chart" />

    <div v-else-if="chart.type === 'pyramids'" class="rounded-2xl bg-slate-900 p-3">
        <div class="flex gap-3">
            <PopulationPyramid
                v-for="series in chart.series"
                :key="series.title"
                :series="series"
                :groups="chart.groups"
                :max="chart.max"
            />
        </div>
        <p v-if="chart.source" class="mt-2 text-center text-xs text-slate-400">{{ chart.source }}</p>
    </div>
</template>
