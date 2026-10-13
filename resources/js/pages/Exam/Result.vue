<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import MathText from '@/components/MathText.vue';
import { componentFor } from '@/components/items';
import type { ItemFeedback, StudentAnswer, StudentItem } from '@/types';

interface Section {
    name: string;
    points: number;
    earned: number;
    items: number;
    answered: number;
    correct: number;
}

interface ReviewEntry {
    position: number;
    section: string | null;
    item: StudentItem;
    answer: StudentAnswer | null;
    feedback: ItemFeedback & { explanation: string | null };
}

const props = defineProps<{
    subject: { slug: string; name: string } | null;
    result: {
        uuid: string;
        blueprint: { session: string | null; profile_label: string | null } | null;
        earned: number;
        auto_points: number;
        total_points: number;
        percent: number;
        sections: Section[];
        manual_sections: { name: string; points: number; note: string | null }[];
        minutes: number;
        used_seconds: number | null;
        reused: number;
        answered: number;
        total_items: number;
        review: ReviewEntry[];
    };
}>();

const nf = new Intl.NumberFormat('ro-MD', { maximumFractionDigits: 1 });

const usedLabel = computed(() => {
    const s = Math.min(props.result.used_seconds ?? 0, props.result.minutes * 60);
    return `${Math.floor(s / 60)} min ${String(s % 60).padStart(2, '0')} s`;
});

const verdict = computed(() => {
    const p = props.result.percent;
    if (p >= 85) return { icon: 'trophy', color: 'text-amber-400' };
    if (p >= 60) return { icon: 'trending-up', color: 'text-emerald-400' };
    return { icon: 'target', color: 'text-slate-400' };
});

function status(entry: ReviewEntry): { label: string; class: string } {
    if (entry.answer === null) return { label: 'fără răspuns', class: 'bg-white/5 text-slate-400' };
    if (entry.feedback.is_correct) return { label: 'corect', class: 'bg-emerald-500/15 text-emerald-400' };
    if (entry.feedback.score > 0) {
        return { label: `parțial ${Math.round(entry.feedback.score * 100)}%`, class: 'bg-amber-500/15 text-amber-400' };
    }
    return { label: 'greșit', class: 'bg-rose-500/15 text-rose-400' };
}

function again() {
    if (props.subject) router.post(route('mock.start'), { subject: props.subject.slug });
}
</script>

<template>
    <Head title="Rezultatul simulării" />

    <AppLayout>
        <div class="space-y-6">
            <Link
                v-if="subject"
                :href="route('subject.show', subject.slug)"
                class="inline-block text-sm text-slate-400 hover:text-slate-200"
            >
                ← {{ subject.name }}
            </Link>

            <!-- ============ PUNCTAJUL ============ -->
            <header class="card text-center">
                <p class="text-xs font-semibold uppercase tracking-wide text-brand-400">
                    Simulare BAC<template v-if="subject"> · {{ subject.name }}</template>
                </p>
                <p v-if="result.blueprint" class="mt-1 text-xs text-slate-500">
                    Structura {{ result.blueprint.session }}, profil {{ result.blueprint.profile_label }}
                </p>

                <p class="mt-4 text-5xl" :class="verdict.color">
                    <Icon :name="verdict.icon" :stroke-width="1.75" />
                </p>
                <p class="mt-2 text-4xl font-black tabular-nums">
                    {{ nf.format(result.earned) }}
                    <span class="text-xl font-semibold text-slate-400">/ {{ result.auto_points }} puncte</span>
                </p>
                <p class="mt-1 text-sm text-slate-400 tabular-nums">
                    {{ result.percent }}% · {{ result.answered }} din {{ result.total_items }} întrebări
                    · {{ usedLabel }} din {{ result.minutes }} min
                </p>
            </header>

            <!-- Cinstit despre ce NU măsoară simularea (§13): eseul și răspunsul
                 extins nu se corectează automat, deci nu se ghicesc. -->
            <div
                v-if="result.manual_sections.length"
                class="card border-amber-500/25 text-sm"
            >
                <p class="font-semibold text-amber-400">
                    Simularea acoperă {{ result.auto_points }} din {{ result.total_points }} de puncte ale examenului
                </p>
                <ul class="mt-2 space-y-1 text-slate-300">
                    <li v-for="section in result.manual_sections" :key="section.name">
                        <strong>{{ section.name }}</strong> ({{ section.points }} p) — nu se corectează automat.
                        <span v-if="section.note" class="text-slate-400">{{ section.note }}</span>
                    </li>
                </ul>
            </div>

            <p v-if="result.reused" class="text-xs text-slate-500">
                <Icon name="triangle-alert" class="mr-1 text-amber-400/80" />
                {{ result.reused }}
                {{ result.reused === 1 ? 'întrebare apăruse' : 'întrebări apăruseră' }}
                într-o simulare anterioară, deci rezultatul e mai puțin precis.
            </p>

            <!-- ============ PE SECȚIUNI ============ -->
            <section class="card space-y-4">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Pe subiecte</h2>
                <div v-for="section in result.sections" :key="section.name" class="space-y-1">
                    <div class="flex items-baseline justify-between gap-3 text-sm">
                        <span class="font-medium">{{ section.name }}</span>
                        <span class="shrink-0 tabular-nums text-slate-400">
                            {{ nf.format(section.earned) }} / {{ section.points }} p
                        </span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-white/5">
                        <div
                            class="h-full rounded-full bg-brand-500"
                            :style="{ width: (section.points ? (section.earned / section.points) * 100 : 0) + '%' }"
                        />
                    </div>
                    <p class="text-xs text-slate-500 tabular-nums">
                        {{ section.correct }} corecte din {{ section.items }}
                        <template v-if="section.answered < section.items">
                            · {{ section.items - section.answered }} fără răspuns
                        </template>
                    </p>
                </div>
            </section>

            <!-- ============ REZOLVĂRILE ============ -->
            <section class="space-y-4">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Rezolvările</h2>

                <article v-for="entry in result.review" :key="entry.position" class="card space-y-4">
                    <div class="flex items-center justify-between gap-3 text-xs">
                        <span class="text-slate-400">
                            {{ entry.position }}. {{ entry.section }}
                        </span>
                        <span class="rounded-md px-2 py-0.5 font-semibold" :class="status(entry).class">
                            {{ status(entry).label }}
                        </span>
                    </div>

                    <component
                        :is="componentFor(entry.item.type)"
                        :item="entry.item"
                        :model-value="entry.answer"
                        :feedback="entry.feedback"
                        disabled
                    />

                    <div
                        v-if="entry.feedback.explanation"
                        class="border-t border-white/10 pt-4 text-sm leading-relaxed text-slate-300"
                    >
                        <MathText :text="entry.feedback.explanation" />
                    </div>
                </article>
            </section>

            <div class="flex flex-wrap gap-3">
                <button v-if="subject" type="button" class="btn-primary flex-1" @click="again">
                    Încă o simulare
                </button>
                <Link
                    :href="subject ? route('subject.show', subject.slug) : route('dashboard')"
                    class="btn-ghost flex-1 text-center"
                >
                    Înapoi la disciplină
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
