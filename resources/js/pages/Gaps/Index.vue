<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';

interface GapItem {
    id: number;
    block_id: string | null;
    label: string;
    excerpt: string | null;
    note: string | null;
    source: string;
    resolved: boolean;
    created_at: string;
}

interface SkillGroup {
    skill: string;
    skill_slug: string;
    has_lesson: boolean;
    open_count: number;
    items: GapItem[];
}

const props = defineProps<{
    groups: { subject: string; subject_slug: string; skills: SkillGroup[] }[];
    open_total: number;
    resolved_total: number;
}>();

const showResolved = ref(false);

function print() {
    window.print();
}

function toggle(item: GapItem) {
    router.put(route('gaps.update', item.id), {
        action: item.resolved ? 'reopen' : 'resolve',
    }, { preserveScroll: true });
}

/**
     * Linkul spre exact locul din lecție.
     *
     * `?bloc=` în loc de ancoră `#`: ancora ar declanșa saltul nativ al
     * browserului, care ignoră antetul lipicios și nu așteaptă randarea
     * formulelor. Cu parametru, controlăm noi derularea.
     */
function lessonLink(group: { subject_slug: string }, skill: SkillGroup, item: GapItem): string {
    const base = route('course.show', [group.subject_slug, skill.skill_slug]);

    return item.block_id ? `${base}?bloc=${item.block_id}` : base;
}

function remove(item: GapItem) {
    router.delete(route('gaps.destroy', item.id), { preserveScroll: true });
}

/** Ascunde grupurile rămase fără elemente vizibile. */
const visible = computed(() =>
    props.groups
        .map((g) => ({
            ...g,
            skills: g.skills
                .map((s) => ({ ...s, items: s.items.filter((i) => showResolved.value || !i.resolved) }))
                .filter((s) => s.items.length > 0),
        }))
        .filter((g) => g.skills.length > 0),
);
</script>

<template>
    <Head title="Ce nu am înțeles" />

    <AppLayout>
        <div class="space-y-6">
            <header class="space-y-2 print:space-y-1">
                <h1 class="text-2xl font-bold">Ce nu am înțeles</h1>
                <p class="text-sm text-slate-400 print:hidden">
                    Lista ta privată. Nimeni altcineva nu o vede — nici colegii, nici administratorii.
                    Ia-o cu tine la profesor sau la meditator.
                </p>
            </header>

            <div v-if="open_total === 0 && resolved_total === 0" class="card text-center">
                <Icon name="circle-question-mark" class="text-2xl text-slate-500" />
                <p class="mt-2 font-semibold">Încă nimic aici</p>
                <p class="mt-1 text-sm text-slate-400">
                    Când citești o lecție și ceva nu e clar, apasă
                    <span class="text-amber-400"><Icon name="circle-question-mark" class="mr-1" />Nu înțeleg</span>
                    sub blocul respectiv.
                    Se adună aici, grupat pe teme.
                </p>
            </div>

            <template v-else>
                <div class="flex flex-wrap items-center gap-3 print:hidden">
                    <span class="rounded-lg bg-amber-500/15 px-3 py-1.5 text-sm text-amber-400">
                        {{ open_total }} nelămurite
                    </span>
                    <button
                        v-if="resolved_total"
                        type="button"
                        class="text-sm text-slate-400 hover:text-slate-200"
                        @click="showResolved = !showResolved"
                    >
                        {{ showResolved ? 'Ascunde' : 'Arată' }} cele {{ resolved_total }} lămurite
                    </button>

                    <button type="button"
                            class="btn-ghost !py-2 !text-sm ml-auto inline-flex items-center gap-1.5"
                            @click="print()">
                        <Icon name="printer" />
                        Printează
                    </button>
                </div>

                <section v-for="group in visible" :key="group.subject" class="space-y-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        {{ group.subject }}
                    </h2>

                    <!-- Gruparea pe competență e cea care face lista utilizabilă:
                         „la derivate am trei probleme, iată care" e ceva cu care un
                         profesor poate lucra. -->
                    <div v-for="skill in group.skills" :key="skill.skill_slug" class="card space-y-3">
                        <div class="flex items-baseline justify-between gap-3">
                            <h3 class="font-bold">{{ skill.skill }}</h3>
                            <Link
                                :href="route('course.show', [group.subject_slug, skill.skill_slug])"
                                class="shrink-0 text-xs text-brand-400 hover:underline print:hidden"
                            >
                                Recitește lecția →
                            </Link>
                        </div>

                        <ul class="space-y-2">
                            <li
                                v-for="item in skill.items"
                                :key="item.id"
                                class="rounded-xl border p-3"
                                :class="item.resolved
                                    ? 'border-white/5 bg-white/[0.02] opacity-60'
                                    : 'border-amber-500/20 bg-amber-500/[0.05]'"
                            >
                                <div class="flex items-start gap-3">
                                    <component
                                        :is="skill.has_lesson ? 'a' : 'div'"
                                        :href="skill.has_lesson ? lessonLink(group, skill, item) : undefined"
                                        class="min-w-0 flex-1 space-y-1.5"
                                        :class="skill.has_lesson ? 'cursor-pointer' : ''"
                                    >
                                        <p class="text-xs font-semibold uppercase tracking-wide"
                                           :class="item.resolved ? 'text-slate-500' : 'text-amber-400/80'">
                                            {{ item.label }}
                                            <span v-if="item.source === 'practice'" class="ml-1 font-normal normal-case text-slate-500">
                                                · dintr-un exercițiu
                                            </span>
                                        </p>

                                        <p v-if="item.excerpt" class="text-sm leading-relaxed text-slate-300">
                                            „{{ item.excerpt }}"
                                        </p>

                                        <p v-if="item.note" class="rounded-lg bg-white/5 px-2.5 py-1.5 text-sm">
                                            <span class="text-slate-500">Întrebarea mea: </span>{{ item.note }}
                                        </p>

                                        <p class="text-xs text-slate-600">
                                            {{ item.created_at }}
                                            <span v-if="skill.has_lesson" class="ml-1 text-brand-400 print:hidden">
                                                · deschide în lecție →
                                            </span>
                                        </p>
                                    </component>

                                    <div class="flex shrink-0 flex-col gap-1 print:hidden">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-xs transition"
                                            :class="item.resolved
                                                ? 'text-slate-400 hover:bg-white/5'
                                                : 'text-emerald-400 hover:bg-emerald-500/10'"
                                            @click="toggle(item)"
                                        >
                                            <Icon :name="item.resolved ? 'undo-2' : 'check'" />
                                            {{ item.resolved ? 'Încă nu' : 'Am înțeles' }}
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-lg px-2 py-1 text-xs text-slate-600 transition hover:bg-white/5 hover:text-rose-400"
                                            @click="remove(item)"
                                        >
                                            Șterge
                                        </button>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </section>
            </template>
        </div>
    </AppLayout>
</template>

<style>
/* Lista se duce la profesor pe hârtie la fel de des ca pe ecran. */
@media print {
    body {
        background: #fff !important;
        color: #000 !important;
    }

    header, footer, nav {
        display: none !important;
    }

    .card {
        border-color: #ccc !important;
        background: #fff !important;
        break-inside: avoid;
    }
}
</style>
