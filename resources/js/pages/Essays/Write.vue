<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { countWords, errorPoints, preview, volumePoints, type Rubric } from '@/lib/essayScore';

const props = defineProps<{
    subject: { slug: string; name: string };
    prompt: {
        slug: string;
        kind: string;
        title: string;
        session: string;
        task: string;
        words: [number, number] | null;
        rubric: Rubric;
        source_ref: string;
        lang: 'en' | 'ro';
    };
}>();

// ---- Pasul 1: scrisul --------------------------------------------------------
const step = ref<'write' | 'assess'>('write');
const onPaper = ref(false);

const form = useForm({
    body: '',
    word_count: null as number | null,
    criteria: {} as Record<string, number>,
    // Numărul de greșeli. Nu `errors`: acela e câmpul Inertia pentru erorile de validare.
    mistakes: {} as Record<string, number>,
});

// Ciorna rămâne pe dispozitiv dacă pagina se închide din greșeală. Doar o
// comoditate locală: ce contează se salvează pe server la final.
const draftKey = `ebac:eseu:${props.prompt.slug}`;
onMounted(() => {
    try {
        form.body = localStorage.getItem(draftKey) ?? '';
    } catch {
        /* stocare indisponibilă: se scrie de la zero */
    }
});
watch(
    () => form.body,
    (v) => {
        try {
            localStorage.setItem(draftKey, v);
        } catch {
            /* idem */
        }
    },
);

const words = computed(() => (onPaper.value ? (form.word_count ?? 0) : countWords(form.body)));

const wordState = computed(() => {
    if (!props.prompt.words) return 'neutral';
    const [min, max] = props.prompt.words;
    return words.value < min ? 'under' : words.value > max ? 'over' : 'ok';
});

const canAssess = computed(() => (onPaper.value ? form.word_count !== null || !props.prompt.words : words.value > 0));

// ---- Pasul 2: autoevaluarea -------------------------------------------------
const groups = computed(() => {
    const out: { name: string; items: Rubric['criteria'] }[] = [];
    for (const c of props.prompt.rubric.criteria) {
        const last = out.at(-1);
        if (last && last.name === c.group) last.items.push(c);
        else out.push({ name: c.group, items: [c] });
    }
    return out;
});

const answered = computed(
    () =>
        props.prompt.rubric.criteria.every((c) => form.criteria[c.key] !== undefined) &&
        (props.prompt.rubric.errors ?? []).every((e) => Number.isInteger(form.mistakes[e.key])),
);

const score = computed(() => preview(props.prompt.rubric, form.criteria, form.mistakes, words.value));

function submit() {
    form.transform(({ mistakes, ...d }) => ({
        ...d,
        errors: mistakes,
        body: onPaper.value ? null : d.body,
        word_count: onPaper.value ? d.word_count : null,
    })).post(route('essays.store', props.prompt.slug), {
        onSuccess: () => {
            try {
                localStorage.removeItem(draftKey);
            } catch {
                /* idem */
            }
        },
    });
}
</script>

<template>
    <Head :title="prompt.title" />

    <AppLayout>
        <div class="space-y-6">
            <Link :href="route('essays.index', subject.slug)" class="inline-block text-sm text-slate-400 hover:text-slate-200">
                ← Eseu · {{ subject.name }}
            </Link>

            <header class="space-y-1">
                <p class="text-sm text-slate-400">{{ prompt.session }}</p>
                <h1 class="font-display text-2xl font-extrabold leading-tight">{{ prompt.title }}</h1>
            </header>

            <section class="card space-y-3">
                <h2 class="font-semibold">Cerința</h2>
                <p class="whitespace-pre-line text-sm leading-relaxed text-slate-300" :lang="prompt.lang">{{ prompt.task }}</p>
                <a :href="prompt.source_ref" target="_blank" rel="noopener" class="inline-block text-xs text-slate-400 hover:underline">
                    Baremul ANCE al acestui test
                </a>
            </section>

            <!-- ============ 1. SCRIE ============ -->
            <template v-if="step === 'write'">
                <div class="flex gap-2" role="group" aria-label="Unde scrii">
                    <button
                        v-for="[paper, label] in ([[false, 'Scriu aici'], [true, 'Am scris pe hârtie']] as [boolean, string][])"
                        :key="label"
                        type="button"
                        class="min-h-11 flex-1 rounded-full border text-sm font-semibold transition"
                        :class="onPaper === paper ? 'border-brand-400 bg-brand-500/15 text-brand-400' : 'border-white/10 text-slate-300'"
                        :aria-pressed="onPaper === paper"
                        @click="onPaper = paper"
                    >
                        {{ label }}
                    </button>
                </div>

                <!-- Fără corector ortografic: la examen nu-l ai, iar greșelile
                     trebuie să le găsești tu, la autoevaluare. -->
                <div v-if="!onPaper" class="space-y-2">
                    <textarea
                        v-model="form.body"
                        :lang="prompt.lang"
                        spellcheck="false"
                        autocapitalize="sentences"
                        rows="14"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900 p-4 leading-relaxed outline-none focus:border-brand-500"
                        :placeholder="prompt.lang === 'en' ? 'Start writing here…' : 'Începe să scrii aici…'"
                        aria-label="Textul tău"
                    />
                </div>

                <label v-else class="block space-y-1.5">
                    <span class="text-sm text-slate-300">Câte cuvinte are textul tău?</span>
                    <input
                        v-model.number="form.word_count"
                        type="number"
                        min="0"
                        inputmode="numeric"
                        class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 outline-none focus:border-brand-500"
                    />
                    <span class="block text-xs text-slate-400">La examen, orice semn separat prin spații se numără ca un cuvânt.</span>
                </label>

                <p v-if="prompt.words" class="text-sm tabular-nums" :class="{
                    'text-action': wordState === 'ok',
                    'text-amber-400': wordState === 'under',
                    'text-rose-400': wordState === 'over',
                }">
                    {{ words }} cuvinte — se cer {{ prompt.words[0] }}–{{ prompt.words[1] }}.
                    <template v-if="wordState === 'over'">Peste limită, volumul se depunctează.</template>
                </p>
                <p v-else-if="!onPaper" class="text-sm text-slate-400 tabular-nums">{{ words }} cuvinte. La examen se cer 1–1,5 pagini.</p>

                <button type="button" class="btn-primary min-h-14 w-full" :disabled="!canAssess" @click="step = 'assess'">
                    Am terminat — mă notez pe barem
                </button>
            </template>

            <!-- ============ 2. AUTOEVALUARE ============ -->
            <template v-else>
                <details v-if="!onPaper && form.body" class="card">
                    <summary class="cursor-pointer font-semibold">Textul tău ({{ words }} cuvinte)</summary>
                    <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-300" :lang="prompt.lang">{{ form.body }}</p>
                </details>

                <p class="text-sm leading-relaxed text-slate-400">
                    Citește-ți textul ca un corector și alege, la fiecare criteriu, ce ai realizat cu adevărat.
                    Criteriile sunt cele din baremul ANCE al acestui test.
                </p>

                <section v-for="group in groups" :key="group.name" class="space-y-3">
                    <h2 class="font-display text-lg font-semibold">{{ group.name }}</h2>

                    <fieldset v-for="c in group.items" :key="c.key" class="card space-y-3">
                        <legend class="sr-only">{{ c.label }}</legend>
                        <p class="font-semibold leading-snug">{{ c.label }}</p>
                        <div class="flex flex-col gap-2">
                            <button
                                v-for="[points, text] in c.options"
                                :key="points"
                                type="button"
                                class="flex min-h-12 items-center gap-3 rounded-xl border-2 px-3 py-2 text-left text-sm transition"
                                :class="form.criteria[c.key] === points ? 'border-brand-400 bg-brand-500/15' : 'border-transparent bg-white/5 hover:border-white/20'"
                                :aria-pressed="form.criteria[c.key] === points"
                                @click="form.criteria[c.key] = points"
                            >
                                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-white/10 font-display text-sm font-extrabold tabular-nums">{{ points }}</span>
                                <span>{{ text }}</span>
                            </button>
                        </div>
                        <p v-if="(form.errors as Record<string, string>)[`criteria.${c.key}`]" class="text-sm text-rose-400">
                            {{ (form.errors as Record<string, string>)[`criteria.${c.key}`] }}
                        </p>
                    </fieldset>
                </section>

                <section v-if="prompt.rubric.errors?.length || prompt.rubric.volume" class="space-y-3">
                    <h2 class="font-display text-lg font-semibold">Corectitudinea limbii</h2>
                    <p class="text-sm leading-relaxed text-slate-400">
                        Numără greșelile din textul tău. O greșeală care se repetă se numără o singură dată.
                        Punctajul maxim depinde de numărul de cuvinte, ca în barem.
                    </p>

                    <label v-for="e in prompt.rubric.errors ?? []" :key="e.key" class="card block space-y-2">
                        <span class="font-semibold">{{ e.label }}</span>
                        <input
                            v-model.number="form.mistakes[e.key]"
                            type="number"
                            min="0"
                            inputmode="numeric"
                            class="w-full rounded-xl border border-white/10 bg-slate-950/40 px-4 py-3 outline-none focus:border-brand-500"
                            placeholder="Numărul de greșeli"
                        />
                        <span class="block text-xs text-slate-400">
                            Se scade 1 punct la fiecare {{ e.per }} greșeli.
                            <template v-if="Number.isInteger(form.mistakes[e.key])">
                                Ai {{ errorPoints(e, form.mistakes[e.key], words) }} din {{ e.max }} p.
                            </template>
                        </span>
                    </label>

                    <div v-if="prompt.rubric.volume" class="card flex items-center justify-between">
                        <span class="font-semibold">Volumul ({{ words }} cuvinte)</span>
                        <span class="font-display font-extrabold tabular-nums">{{ volumePoints(prompt.rubric, words) }} / {{ prompt.rubric.volume.max }}</span>
                    </div>
                </section>

                <div class="sticky bottom-3 z-10 rounded-2xl bg-hero p-4 text-on-hero shadow-lg">
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-display text-2xl font-extrabold tabular-nums">
                            {{ score }}<span class="text-base font-semibold"> / {{ prompt.rubric.total }}</span>
                        </p>
                        <button type="button" class="btn-primary" :disabled="!answered || form.processing" @click="submit">
                            {{ form.processing ? 'Se salvează…' : 'Salvează nota' }}
                        </button>
                    </div>
                    <p v-if="!answered" class="mt-1 text-xs">Alege un nivel la fiecare criteriu.</p>
                </div>

                <button type="button" class="text-sm text-slate-400 hover:text-slate-200" @click="step = 'write'">← Înapoi la text</button>
            </template>
        </div>
    </AppLayout>
</template>
