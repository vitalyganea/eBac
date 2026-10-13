<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';

const props = defineProps<{
    session: {
        uuid: string;
        mode: string;
        mode_label: string;
        progress: { total: number; answered: number; correct: number; xp: number };
    };
}>();

const pct = computed(() =>
    props.session.progress.total > 0
        ? Math.round((props.session.progress.correct / props.session.progress.total) * 100)
        : 0,
);

const verdict = computed(() => {
    if (pct.value >= 90) return { icon: 'trophy', color: 'text-amber-400', text: 'Excelent' };
    if (pct.value >= 70) return { icon: 'biceps-flexed', color: 'text-emerald-400', text: 'Bine' };
    if (pct.value >= 50) return { icon: 'trending-up', color: 'text-brand-400', text: 'În creștere' };
    return { icon: 'target', color: 'text-slate-400', text: 'Mai exersăm' };
});
</script>

<template>
    <Head title="Sesiune încheiată" />

    <AppLayout>
        <div class="space-y-6 py-6 text-center">
            <p class="text-5xl" :class="verdict.color">
                <Icon :name="verdict.icon" :label="verdict.text" :stroke-width="1.75" />
            </p>
            <h1 class="text-2xl font-bold">{{ verdict.text }}</h1>

            <div class="grid grid-cols-3 gap-3">
                <div class="card">
                    <p class="text-2xl font-black tabular-nums">
                        {{ session.progress.correct }}/{{ session.progress.total }}
                    </p>
                    <p class="mt-1 text-xs text-slate-400">corecte</p>
                </div>
                <div class="card">
                    <p class="text-2xl font-black tabular-nums">{{ pct }}%</p>
                    <p class="mt-1 text-xs text-slate-400">acuratețe</p>
                </div>
                <div class="card">
                    <p class="text-2xl font-black tabular-nums text-brand-400">
                        +{{ session.progress.xp }}
                    </p>
                    <p class="mt-1 text-xs text-slate-400">XP</p>
                </div>
            </div>

            <Link :href="route('dashboard')" class="btn-primary inline-flex">
                Înapoi la panou
            </Link>
        </div>
    </AppLayout>
</template>
