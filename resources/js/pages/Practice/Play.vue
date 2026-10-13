<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import GapButton from '@/components/lesson/GapButton.vue';
import Icon from '@/components/Icon.vue';
import MathText from '@/components/MathText.vue';
import { componentFor, isAnswerComplete } from '@/components/items';
import { uuidv4 } from '@/lib/uuid';
import type { Feedback, StudentAnswer, StudentItem } from '@/types';

const props = defineProps<{
    session: {
        uuid: string;
        mode: string;
        mode_label: string;
        progress: { total: number; answered: number; correct: number; xp: number };
    };
    item: StudentItem;
    feedback: Feedback | null;
    submitted: StudentAnswer | null;
    lesson: {
        id: number;
        title: string;
        skill_slug: string;
        blocks: { id: string; type: string; label: string }[];
    } | null;
    has_next: boolean;
}>();

const page = usePage();

const answer = ref<StudentAnswer | null>(null);
const submitting = ref(false);
const loadingHint = ref(false);

/** Textele indiciilor primite în această vizualizare, în ordine. */
const revealedHints = ref<string[]>([]);

/**
 * UUID-ul se generează O DATĂ per item și se refolosește la fiecare reîncercare.
 * Ăsta e ce face submit-ul idempotent: dacă rețeaua cade și request-ul se
 * retrimite, serverul recunoaște faptul deja înregistrat și nu acordă XP dublu.
 */
const clientUuid = ref(uuidv4());
const shownAt = ref(Date.now());

watch(
    () => props.item.uuid,
    () => {
        answer.value = null;
        revealedHints.value = [];
        clientUuid.value = uuidv4();
        shownAt.value = Date.now();
    },
);

const isAnswered = computed(() => props.feedback !== null);

// După răspuns afișăm ce a trimis elevul, venit de la server — nu starea locală,
// care poate diverge.
const displayedAnswer = computed(() => (isAnswered.value ? props.submitted : answer.value));

const canSubmit = computed(
    () => !isAnswered.value && !submitting.value && isAnswerComplete(props.item, answer.value),
);

const progressPct = computed(() =>
    Math.round((props.session.progress.answered / props.session.progress.total) * 100),
);

// ---- Indicii -------------------------------------------------------------
// Contorul vine de la server (`item.hints.revealed`). Textele deja citite se
// pierd la reîncărcarea paginii, dar contorul nu — deci penalizarea rămâne.
const hints = computed(() => props.item.hints);
const hintsLeft = computed(() => hints.value.total - hints.value.revealed);
const canAskHint = computed(
    () => hints.value.allowed && hintsLeft.value > 0 && !isAnswered.value && !loadingHint.value,
);

const hintButtonLabel = computed(() => {
    if (hints.value.revealed === 0) return 'Indiciu';
    return `Încă un indiciu (${hintsLeft.value} rămase)`;
});

function requestHint() {
    if (!canAskHint.value) return;
    loadingHint.value = true;

    router.post(
        route('practice.hint', props.session.uuid),
        { item_uuid: props.item.uuid },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                const flashed = page.props.flash?.hint;
                if (flashed?.text && !revealedHints.value.includes(flashed.text)) {
                    revealedHints.value.push(flashed.text);
                }
            },
            onFinish: () => (loadingHint.value = false),
        },
    );
}

function submit() {
    if (!canSubmit.value) return;
    submitting.value = true;

    router.post(
        route('practice.answer', props.session.uuid),
        {
            client_uuid: clientUuid.value,
            item_uuid: props.item.uuid,
            session_uuid: props.session.uuid,
            answer: answer.value,
            // Informativ. Cronometrajul care contează e cel de pe server.
            client_ms: Date.now() - shownAt.value,
        },
        {
            preserveScroll: true,
            onFinish: () => (submitting.value = false),
        },
    );
}

function next() {
    router.get(route('practice.play', props.session.uuid));
}
</script>

<template>
    <Head :title="session.mode_label" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Progresul sesiunii -->
            <div class="space-y-2">
                <div class="flex items-center justify-between text-sm text-slate-400">
                    <span>{{ session.mode_label }}</span>
                    <span class="tabular-nums">
                        {{ session.progress.answered }} / {{ session.progress.total }}
                    </span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-white/5">
                    <div class="h-full rounded-full bg-brand-500 transition-all duration-500"
                        :style="{ width: progressPct + '%' }" />
                </div>
            </div>

            <div class="card">
                <p v-if="item.skill" class="mb-4 text-xs font-semibold uppercase tracking-wide text-brand-400">
                    {{ item.skill.name }}
                </p>

                <!-- Componenta se alege după tipul itemului. O materie nouă nu
                     atinge acest fișier. -->
                <component
                    :is="componentFor(item.type)"
                    :item="item"
                    :model-value="displayedAnswer"
                    :feedback="feedback"
                    :disabled="isAnswered"
                    @update:model-value="answer = $event"
                />
            </div>

            <!-- Indicii dezvăluite, în trepte -->
            <div v-if="revealedHints.length" class="space-y-2">
                <div
                    v-for="(hint, i) in revealedHints"
                    :key="i"
                    class="rounded-xl border border-amber-500/25 bg-amber-500/10 p-4 text-sm"
                >
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-amber-400/80">
                        Indiciu {{ i + 1 }} din {{ hints.total }}
                    </p>
                    <MathText :text="hint" />
                </div>
            </div>

            <!-- Indicii folosite într-o vizualizare anterioară (ex. după refresh):
                 contorul se păstrează pe server, textele nu. -->
            <p
                v-else-if="hints.revealed > 0 && !isAnswered"
                class="text-xs text-amber-400/70"
            >
                Ai folosit {{ hints.revealed }}
                {{ hints.revealed === 1 ? 'indiciu' : 'indicii' }} la această întrebare.
            </p>

            <!-- Feedback -->
            <div
                v-if="feedback"
                class="card"
                :class="feedback.is_correct ? 'border-emerald-500/40' : 'border-rose-500/40'"
            >
                <div class="flex items-center gap-3">
                    <Icon :name="feedback.is_correct ? 'circle-check' : 'circle-x'"
                          class="text-2xl"
                          :class="feedback.is_correct ? 'text-emerald-400' : 'text-rose-400'"
                          :label="feedback.is_correct ? 'Corect' : 'Greșit'" />
                    <div class="flex-1">
                        <p class="font-bold">
                            {{ feedback.is_correct ? 'Corect' : 'Greșit' }}
                            <span
                                v-if="!feedback.is_correct && feedback.score > 0"
                                class="ml-1 text-sm font-normal text-amber-400"
                            >
                                — punctaj parțial {{ Math.round(feedback.score * 100) }}%
                            </span>
                        </p>
                        <p v-if="feedback.server_ms" class="text-xs text-slate-500">
                            {{ (feedback.server_ms / 1000).toFixed(1) }}s
                        </p>
                    </div>

                    <div class="text-right">
                        <p class="font-bold tabular-nums" :class="feedback.xp.amount > 0 ? 'text-brand-400' : 'text-slate-500'">
                            +{{ feedback.xp.amount }} XP
                        </p>
                        <p v-if="feedback.rating_delta !== null" class="text-xs tabular-nums text-slate-400">
                            {{ feedback.rating_delta > 0 ? '+' : '' }}{{ feedback.rating_delta }} rating
                        </p>
                    </div>
                </div>

                <!-- Regulile de XP devin vizibile, nu magice. -->
                <p v-if="feedback.xp.reasons.cooldown" class="mt-3 text-xs text-slate-500">
                    Ai rezolvat corect acest item recent — de aceea nu primești XP din nou.
                </p>
                <p v-if="feedback.xp.reasons.retention" class="mt-3 text-xs text-emerald-400">
                    <Icon name="brain" class="mr-1" />Bonus de retenție: ai reparat o greșeală veche.
                </p>
                <p v-if="feedback.xp.reasons.hints" class="mt-3 text-xs text-amber-400/80">
                    <Icon name="lightbulb" class="mr-1" />{{ feedback.xp.reasons.hints_used }}
                    {{ feedback.xp.reasons.hints_used === 1 ? 'indiciu folosit' : 'indicii folosite' }}:
                    {{ feedback.xp.reasons.hints }} XP
                </p>
                <p v-if="feedback.xp.capped" class="mt-3 text-xs text-amber-400">
                    Ai atins limita zilnică de XP. Progresul se contorizează în continuare.
                </p>

                <div v-if="feedback.explanation" class="mt-4 border-t border-white/10 pt-4 text-sm leading-relaxed text-slate-300">
                    <MathText :text="feedback.explanation" />
                </div>

                <!-- Apare și după un răspuns CORECT, nu doar după unul greșit:
                     se poate nimeri răspunsul fără să înțelegi de ce, iar exact
                     acela e cazul în care marcarea contează cel mai mult. -->
                <div v-if="item.skill" class="mt-3 flex justify-end">
                    <GapButton
                        :skill-id="item.skill.id"
                        :lesson-id="lesson?.id"
                        :blocks="lesson?.blocks"
                        source="practice"
                        :label="feedback.is_correct ? 'Am nimerit, dar nu am înțeles' : 'Nu înțeleg'"
                        compact
                    />
                </div>
            </div>

            <!-- Acțiuni -->
            <div class="flex gap-3">
                <button
                    v-if="canAskHint"
                    type="button"
                    class="btn-ghost inline-flex items-center gap-1.5"
                    :disabled="loadingHint"
                    @click="requestHint"
                >
                    <template v-if="loadingHint">…</template>
                    <template v-else>
                        <Icon name="lightbulb" class="text-amber-400" />
                        {{ hintButtonLabel }}
                    </template>
                </button>

                <button v-if="!isAnswered" type="button" class="btn-primary flex-1" :disabled="!canSubmit" @click="submit">
                    {{ submitting ? 'Se trimite…' : 'Verifică' }}
                </button>

                <button v-else type="button" class="btn-primary flex-1" @click="next">
                    {{ has_next ? 'Continuă' : 'Vezi rezultatul' }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>
