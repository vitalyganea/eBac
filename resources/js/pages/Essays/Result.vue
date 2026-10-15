<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

interface Line {
    key: string;
    label: string;
    points: number;
    max: number;
}

const props = defineProps<{
    subject: { slug: string; name: string };
    prompt: { slug: string; title: string; task: string; kind: string };
    attempt: {
        body: string | null;
        word_count: number | null;
        breakdown: Line[];
        score: number;
        max: number;
        date: string;
    };
}>();

/**
 * Unde ai pierdut cele mai multe puncte — acolo merită lucrat data viitoare.
 * Doar criteriile la care lipsesc puncte, cele mai mari pierderi întâi.
 */
const weakest = computed(() =>
    [...props.attempt.breakdown]
        .filter((l) => l.points < l.max)
        .sort((a, b) => b.max - b.points - (a.max - a.points))
        .slice(0, 3),
);
</script>

<template>
    <Head :title="'Autoevaluare · ' + prompt.title" />

    <AppLayout>
        <div class="space-y-6">
            <Link :href="route('essays.index', subject.slug)" class="inline-block text-sm text-slate-400 hover:text-slate-200">
                ← Eseu · {{ subject.name }}
            </Link>

            <section class="rounded-3xl bg-hero p-5 text-on-hero">
                <p class="text-sm font-semibold">{{ prompt.title }}</p>
                <p class="mt-2 font-display text-5xl font-extrabold tabular-nums text-hero-accent">
                    {{ attempt.score }}<span class="text-xl font-semibold text-on-hero"> / {{ attempt.max }}</span>
                </p>
                <p class="mt-2 text-sm">
                    Nota ta, pe criteriile baremului.
                    <template v-if="attempt.word_count !== null">{{ attempt.word_count }} cuvinte.</template>
                </p>
            </section>

            <section v-if="weakest.length" class="space-y-2">
                <h2 class="font-display text-lg font-semibold">De lucrat data viitoare</h2>
                <ul class="space-y-2">
                    <li v-for="l in weakest" :key="l.key" class="rounded-xl border border-amber-500/25 bg-amber-500/10 p-3 text-sm">
                        {{ l.label }} — <strong>{{ l.max - l.points }}</strong> {{ l.max - l.points === 1 ? 'punct pierdut' : 'puncte pierdute' }}
                    </li>
                </ul>
            </section>

            <section class="space-y-2">
                <h2 class="font-display text-lg font-semibold">Pe criterii</h2>
                <ul class="space-y-3">
                    <li v-for="l in attempt.breakdown" :key="l.key">
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="text-slate-300">{{ l.label }}</span>
                            <span class="shrink-0 font-semibold tabular-nums">{{ l.points }} / {{ l.max }}</span>
                        </div>
                        <div class="mt-1 h-2 overflow-hidden rounded-full bg-white/5">
                            <div class="h-full rounded-full bg-action" :style="{ width: (l.points / Math.max(1, l.max)) * 100 + '%' }" />
                        </div>
                    </li>
                </ul>
            </section>

            <details v-if="attempt.body" class="card">
                <summary class="cursor-pointer font-semibold">Textul tău</summary>
                <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-300">{{ attempt.body }}</p>
            </details>

            <div class="flex flex-col gap-3 sm:flex-row">
                <Link :href="route('essays.show', prompt.slug)" class="btn-primary flex-1">Scrie din nou</Link>
                <Link :href="route('essays.index', subject.slug)" class="btn-ghost flex-1">Altă cerință</Link>
            </div>
        </div>
    </AppLayout>
</template>
