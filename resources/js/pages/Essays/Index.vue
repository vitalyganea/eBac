<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

interface PromptRow {
    slug: string;
    kind: 'eseu' | 'articol';
    title: string;
    session: string;
    total: number;
    best: number | null;
}

const props = defineProps<{
    subject: { slug: string; name: string };
    prompts: PromptRow[];
    history: { id: number; title: string | null; score: number; max: number; date: string }[];
}>();

const kinds: Record<PromptRow['kind'], string> = {
    eseu: 'Eseu',
    articol: 'Articol (Subiectul III)',
};

const groups = computed(() =>
    (['eseu', 'articol'] as const)
        .map((k) => ({ kind: k, items: props.prompts.filter((p) => p.kind === k) }))
        .filter((g) => g.items.length),
);

const dateFormat = new Intl.DateTimeFormat('ro-MD', { day: 'numeric', month: 'short' });
</script>

<template>
    <Head :title="'Eseu · ' + subject.name" />

    <AppLayout>
        <div class="space-y-6">
            <Link :href="route('subject.show', subject.slug)" class="inline-block text-sm text-slate-400 hover:text-slate-200">
                ← {{ subject.name }}
            </Link>

            <header class="space-y-3">
                <h1 class="font-display text-2xl font-extrabold">Eseu</h1>
                <p class="text-sm leading-relaxed text-slate-400">
                    Alege o cerință dintr-un test ANCE, scrie textul, apoi notează-te singur pe criteriile din baremul
                    aceluiași test. Nu e o corectare automată: nota e a ta, iar rostul e să vezi ce-ți lipsește.
                    Ce scrii aici vezi doar tu.
                </p>
            </header>

            <section v-if="history.length" class="space-y-2">
                <h2 class="font-display text-lg font-semibold">Ultimele tale texte</h2>
                <ul class="divide-y divide-white/10">
                    <li v-for="h in history" :key="h.id">
                        <Link :href="route('essays.attempt', h.id)" class="flex min-h-12 items-center justify-between gap-3 py-2 hover:opacity-80">
                            <span class="min-w-0 truncate">{{ h.title }}</span>
                            <span class="flex shrink-0 items-baseline gap-3 text-sm text-slate-400 tabular-nums">
                                <span>{{ dateFormat.format(new Date(h.date)) }}</span>
                                <span><strong class="text-slate-100">{{ h.score }}</strong> / {{ h.max }}</span>
                            </span>
                        </Link>
                    </li>
                </ul>
            </section>

            <section v-for="g in groups" :key="g.kind" class="space-y-3">
                <h2 class="font-display text-lg font-semibold">{{ kinds[g.kind] }}</h2>
                <Link
                    v-for="p in g.items"
                    :key="p.slug"
                    :href="route('essays.show', p.slug)"
                    class="card flex items-center gap-4 transition hover:border-white/25"
                >
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold leading-snug">{{ p.title }}</span>
                        <span class="mt-0.5 block text-sm text-slate-400">{{ p.session }}</span>
                    </span>
                    <span class="shrink-0 text-right text-sm tabular-nums">
                        <template v-if="p.best !== null">
                            <span class="block font-display font-extrabold">{{ p.best }}/{{ p.total }}</span>
                            <span class="block text-xs text-slate-400">cel mai bun</span>
                        </template>
                        <span v-else class="text-slate-400">{{ p.total }} p.</span>
                    </span>
                </Link>
            </section>
        </div>
    </AppLayout>
</template>
