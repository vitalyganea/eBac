<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import MathText from '@/components/MathText.vue';

type Status = 'known' | 'review' | null;
type Profile = 'r' | 'u';

interface FormulaRow {
    id: number;
    name: string;
    latex: string;
    body: string;
    variables: [string, string][] | null;
    pitfall: string | null;
    annex: Record<Profile, number>;
    skill: { slug: string; name: string; has_lesson: boolean } | null;
    status: Status;
    source_ref: string;
}

const props = defineProps<{
    subject: { slug: string; name: string; icon: string | null };
    profile: Profile | null;
    tests: Record<Profile, number>;
    formulas: FormulaRow[];
}>();

const profileName: Record<Profile, string> = { r: 'real', u: 'umanist' };

/**
 * „De știut pe de rost" = nu a apărut în NICIO anexă a profilului elevului.
 * Fără profil, doar ce n-a apărut nicăieri.
 */
function mustKnow(f: FormulaRow): boolean {
    return props.profile ? f.annex[props.profile] === 0 : f.annex.r === 0 && f.annex.u === 0;
}

// Starea locală pornește din server și se actualizează la fiecare bifă,
// fără să aștepte răspunsul: pe date mobile lente, „Știam" trebuie să
// treacă imediat la formula următoare.
const statuses = ref<Record<number, Status>>(Object.fromEntries(props.formulas.map((f) => [f.id, f.status])));

const known = computed(() => props.formulas.filter((f) => statuses.value[f.id] === 'known').length);

// ---- Filtrul și modul ----------------------------------------------------
type Filter = 'all' | 'must' | 'annex';
const filter = ref<Filter>('all');
const mode = ref<'list' | 'learn'>('list');

const filtered = computed(() =>
    props.formulas.filter((f) =>
        filter.value === 'all' ? true : filter.value === 'must' ? mustKnow(f) : !mustKnow(f),
    ),
);

const counts = computed(() => ({
    all: props.formulas.length,
    must: props.formulas.filter(mustKnow).length,
    annex: props.formulas.filter((f) => !mustKnow(f)).length,
}));

/** Grupate pe competență, în ordinea programei (serverul le trimite deja așa). */
const groups = computed(() => {
    const out: { name: string; items: FormulaRow[] }[] = [];
    for (const f of filtered.value) {
        const name = f.skill?.name ?? 'Altele';
        const last = out.at(-1);
        if (last && last.name === name) last.items.push(f);
        else out.push({ name, items: [f] });
    }
    return out;
});

const open = ref<Set<number>>(new Set());
function toggle(id: number) {
    const next = new Set(open.value);
    if (next.has(id)) next.delete(id);
    else next.add(id);
    open.value = next;
}

// ---- „Ascunde și verifică" ------------------------------------------------
// Pachetul se fixează la pornire: cele neîncercate întâi, apoi cele de
// repetat, la urmă cele știute. Nu se reordonează sub degetul elevului.
const deck = ref<FormulaRow[]>([]);
const position = ref(0);
const revealed = ref(false);
const session = ref({ known: 0, review: 0 });

const rank = (s: Status) => (s === null ? 0 : s === 'review' ? 1 : 2);

function startLearning(onlyReview = false) {
    const pool = onlyReview
        ? filtered.value.filter((f) => statuses.value[f.id] === 'review')
        : filtered.value;
    deck.value = [...pool].sort((a, b) => rank(statuses.value[a.id]) - rank(statuses.value[b.id]));
    position.value = 0;
    revealed.value = false;
    session.value = { known: 0, review: 0 };
    mode.value = 'learn';
}

const current = computed(() => deck.value[position.value] ?? null);
const finished = computed(() => mode.value === 'learn' && deck.value.length > 0 && current.value === null);

function mark(status: 'known' | 'review') {
    const f = current.value;
    if (!f) return;

    statuses.value = { ...statuses.value, [f.id]: status };
    session.value[status]++;
    position.value++;
    revealed.value = false;

    router.post(route('formulas.progress', f.id), { status }, {
        preserveScroll: true,
        preserveState: true,
        only: [],
    });
}

function annexLabel(f: FormulaRow, p: Profile): string {
    return f.annex[p] === 0
        ? `n-a apărut în anexă (${profileName[p]})`
        : `în anexă la ${f.annex[p]} din ${props.tests[p]} teste (${profileName[p]})`;
}
</script>

<template>
    <Head :title="'Formule · ' + subject.name" />

    <AppLayout>
        <div class="space-y-6">
            <Link :href="route('subject.show', subject.slug)" class="inline-block text-sm text-slate-400 hover:text-slate-200">
                ← {{ subject.name }}
            </Link>

            <header class="space-y-3">
                <h1 class="font-display text-2xl font-extrabold">Formule</h1>
                <p class="text-sm leading-relaxed text-slate-400">
                    Testul ANCE are la final o anexă cu câteva formule, dar nu aceleași în fiecare an.
                    Nici cele care apar des nu sunt garantate, iar cele marcate
                    <strong class="text-amber-400">de știut pe de rost</strong> n-au apărut niciodată
                    în anexă din 2021 încoace.
                </p>

                <div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-300">Știi {{ known }} din {{ formulas.length }}</span>
                        <span class="text-slate-400 tabular-nums">{{ Math.round((known / Math.max(1, formulas.length)) * 100) }}%</span>
                    </div>
                    <div class="mt-1.5 h-2.5 overflow-hidden rounded-full bg-white/5">
                        <div class="h-full rounded-full bg-action transition-all" :style="{ width: (known / Math.max(1, formulas.length)) * 100 + '%' }" />
                    </div>
                </div>
            </header>

            <!-- Filtrul se aplică în ambele moduri. -->
            <div class="flex flex-wrap gap-2" role="group" aria-label="Ce formule arăți">
                <button
                    v-for="[key, label] in ([['all', 'Toate'], ['must', 'De știut pe de rost'], ['annex', 'Apar în anexă']] as [Filter, string][])"
                    :key="key"
                    type="button"
                    class="min-h-10 rounded-full border px-4 text-sm font-semibold transition"
                    :class="filter === key ? 'border-brand-400 bg-brand-500/15 text-brand-400' : 'border-white/10 text-slate-300 hover:border-white/25'"
                    :aria-pressed="filter === key"
                    @click="filter = key; mode = 'list'"
                >
                    {{ label }} <span class="tabular-nums text-slate-400">{{ counts[key] }}</span>
                </button>
            </div>

            <!-- ============ LISTA ============ -->
            <template v-if="mode === 'list'">
                <button type="button" class="btn-primary w-full" :disabled="!filtered.length" @click="startLearning()">
                    Învață cu „ascunde și verifică"
                </button>

                <section v-for="group in groups" :key="group.name" class="space-y-3">
                    <h2 class="font-display text-lg font-semibold">{{ group.name }}</h2>

                    <article v-for="f in group.items" :key="f.id" class="card space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="font-semibold">{{ f.name }}</h3>
                            <span v-if="statuses[f.id] === 'known'" class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-action">
                                <Icon name="check" />știi
                            </span>
                            <span v-else-if="statuses[f.id] === 'review'" class="shrink-0 text-xs font-semibold text-amber-400">de repetat</span>
                        </div>

                        <div class="overflow-x-auto rounded-xl bg-slate-950/40 px-3 py-2">
                            <MathText :latex="f.latex" display />
                        </div>

                        <div class="flex flex-wrap gap-2 text-xs">
                            <template v-if="mustKnow(f)">
                                <span class="rounded-full bg-amber-400/15 px-2.5 py-1 font-semibold text-amber-400">de știut pe de rost</span>
                            </template>
                            <template v-else>
                                <span
                                    v-for="p in (profile ? [profile] : (['r', 'u'] as Profile[]))"
                                    :key="p"
                                    class="rounded-full bg-white/5 px-2.5 py-1 text-slate-300"
                                >{{ annexLabel(f, p) }}</span>
                            </template>
                        </div>

                        <button
                            type="button"
                            class="text-sm font-semibold text-brand-400 hover:underline"
                            :aria-expanded="open.has(f.id)"
                            @click="toggle(f.id)"
                        >
                            {{ open.has(f.id) ? 'Ascunde explicația' : 'Ce înseamnă și când o folosești' }}
                        </button>

                        <div v-if="open.has(f.id)" class="space-y-3 border-t border-white/10 pt-3 text-sm leading-relaxed text-slate-300">
                            <p><MathText :text="f.body" /></p>

                            <dl v-if="f.variables?.length" class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1">
                                <template v-for="v in f.variables" :key="v[0]">
                                    <dt><MathText :latex="v[0]" /></dt>
                                    <dd class="text-slate-400">{{ v[1] }}</dd>
                                </template>
                            </dl>

                            <p v-if="f.pitfall" class="rounded-xl border border-amber-500/25 bg-amber-500/10 p-3">
                                <Icon name="triangle-alert" class="mr-1 text-amber-400" />
                                <MathText :text="f.pitfall" />
                            </p>

                            <div class="flex flex-wrap gap-x-4 gap-y-1">
                                <Link
                                    v-if="f.skill?.has_lesson"
                                    :href="route('course.show', [subject.slug, f.skill.slug])"
                                    class="font-semibold text-brand-400 hover:underline"
                                >
                                    Lecția: {{ f.skill.name }}
                                </Link>
                                <a :href="f.source_ref" target="_blank" rel="noopener" class="text-slate-400 hover:underline">
                                    Sursa oficială
                                </a>
                            </div>
                        </div>
                    </article>
                </section>
            </template>

            <!-- ============ ASCUNDE ȘI VERIFICĂ ============ -->
            <template v-else>
                <div class="flex items-center justify-between text-sm text-slate-400">
                    <button type="button" class="hover:text-slate-200" @click="mode = 'list'">← Înapoi la listă</button>
                    <span v-if="current" class="tabular-nums">{{ position + 1 }} / {{ deck.length }}</span>
                </div>

                <section v-if="current" class="card space-y-5">
                    <p v-if="current.skill" class="inline-block rounded-full bg-brand-500/15 px-3 py-1 text-sm font-semibold text-brand-400">
                        {{ current.skill.name }}
                    </p>

                    <h2 class="font-display text-2xl font-extrabold leading-tight">{{ current.name }}</h2>

                    <template v-if="!revealed">
                        <p class="text-slate-400">Scrie formula pe o foaie, din memorie. Apoi verifică.</p>
                        <button type="button" class="btn-primary min-h-14 w-full" @click="revealed = true">
                            Arată formula
                        </button>
                    </template>

                    <template v-else>
                        <div class="overflow-x-auto rounded-xl bg-slate-950/40 px-3 py-3 text-lg">
                            <MathText :latex="current.latex" display />
                        </div>
                        <p class="text-sm leading-relaxed text-slate-300"><MathText :text="current.body" /></p>
                        <p v-if="current.pitfall" class="rounded-xl border border-amber-500/25 bg-amber-500/10 p-3 text-sm">
                            <Icon name="triangle-alert" class="mr-1 text-amber-400" />
                            <MathText :text="current.pitfall" />
                        </p>

                        <div class="flex gap-3">
                            <button type="button" class="btn-ghost min-h-14 flex-1" @click="mark('review')">Încă nu</button>
                            <button type="button" class="btn-primary min-h-14 flex-1" @click="mark('known')">Știam</button>
                        </div>
                    </template>
                </section>

                <section v-else-if="finished" class="card space-y-4 text-center">
                    <h2 class="font-display text-xl font-extrabold">Tură încheiată</h2>
                    <p class="text-slate-300">
                        Ai știut {{ session.known }} din {{ deck.length }}.
                        <template v-if="session.review">{{ session.review }} {{ session.review === 1 ? 'rămâne' : 'rămân' }} de repetat.</template>
                    </p>
                    <button v-if="session.review" type="button" class="btn-primary w-full" @click="startLearning(true)">
                        Repetă doar ce n-ai știut
                    </button>
                    <button type="button" class="btn-ghost w-full" @click="mode = 'list'">Înapoi la listă</button>
                </section>
            </template>
        </div>
    </AppLayout>
</template>
