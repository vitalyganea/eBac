<script setup lang="ts">
import { computed } from 'vue';
import CodeBlock from '@/components/CodeBlock.vue';
import ItemChart from '@/components/charts/ItemChart.vue';
import MathText from '@/components/MathText.vue';
import type { ItemFeedback, StudentAnswer, StudentItem } from '@/types';

/**
 * Alegere dintre variante — acoperă mcq, next_step, find_error, code_output și
 * label_diagram. Diferă prezentarea, nu mecanica.
 */
const props = defineProps<{
    item: StudentItem;
    modelValue: StudentAnswer | null;
    feedback: ItemFeedback | null;
    disabled: boolean;
}>();

const emit = defineEmits<{ 'update:modelValue': [StudentAnswer | null] }>();

const chosen = computed(() => (props.modelValue?.choice as string) ?? null);
const correctKeys = computed<string[]>(
    () => (props.feedback?.correct_answer?.correct as string[]) ?? [],
);

function select(key: string) {
    if (props.disabled) return;
    emit('update:modelValue', { choice: key });
}

function classFor(key: string): string {
    if (!props.feedback) {
        return chosen.value === key ? 'choice-selected' : '';
    }
    if (correctKeys.value.includes(key)) return 'choice-correct';
    if (chosen.value === key) return 'choice-wrong line-through decoration-rose-400';
    return 'opacity-50';
}

/** Pătrățelul cu litera urmează starea variantei, ca să se vadă și fără culoarea chenarului. */
function tileFor(key: string): string {
    if (!props.feedback) {
        return chosen.value === key ? 'bg-brand-400 text-on-action' : 'bg-white/10 text-brand-400';
    }
    if (correctKeys.value.includes(key)) return 'bg-on-action text-action';
    if (chosen.value === key) return 'bg-rose-500 text-on-accent';
    return 'bg-white/10 text-slate-400';
}

/**
 * Patru variante scurte („șirul este constant", „√2") încap în grilă 2×2:
 * ținte mari pentru deget, totul vizibil fără derulare. Variantele lungi, cele
 * de lângă un text-suport sau un program rămân una sub alta — în două coloane
 * pe un telefon de 360px s-ar rupe pe cinci rânduri.
 */
const grid = computed(() => {
    const choices = props.item.payload.choices ?? [];
    return (
        choices.length === 4 &&
        !props.item.payload.passage &&
        !props.item.payload.code &&
        !props.item.payload.code_variants &&
        choices.every((c) => (c.text ?? '').length <= 32)
    );
});
</script>

<template>
    <div class="space-y-5">
        <!-- Text-suport: comprehensiunea scrisă (Subiectul I la limbi străine)
             cere un text necunoscut de 400-450 de cuvinte. Se afișează ÎNAINTE
             de întrebare, cu derulare proprie ca să nu împingă variantele
             în afara ecranului pe telefon. -->
        <div
            v-if="item.payload.passage"
            class="max-h-72 overflow-y-auto rounded-xl border border-white/10 bg-white/[0.03] p-4 text-sm leading-relaxed text-slate-300"
        >
            <p v-if="item.payload.passage_title" class="mb-2 font-semibold text-slate-100">
                {{ item.payload.passage_title }}
            </p>
            <p class="whitespace-pre-line">{{ item.payload.passage }}</p>
        </div>

        <p class="text-xl font-medium leading-relaxed">
            <MathText :text="item.payload.stem" />
        </p>

        <!-- Geografie: climograma, piramidele populației — desenate din date. -->
        <ItemChart v-if="item.payload.chart" :chart="item.payload.chart" />

        <MathText
            v-if="item.payload.stem_latex"
            :latex="item.payload.stem_latex"
            display
            class="block overflow-x-auto"
        />

        <!-- Informatică: programul itemului. Examenul din Moldova permite
             Pascal sau C/C++, deci codul se afișează în limbajul ales de elev. -->
        <CodeBlock
            v-if="item.payload.code || item.payload.code_variants"
            :code="item.payload.code"
            :variants="item.payload.code_variants"
        />

        <!-- Pentru biologie/geografie: schema de identificat. -->
        <img
            v-if="item.payload.image"
            :src="item.payload.image"
            alt=""
            class="mx-auto max-h-64 rounded-xl"
        />

        <div :class="grid ? 'grid grid-cols-2 gap-3' : 'space-y-3'">
            <button
                v-for="(choice, i) in item.payload.choices ?? []"
                :key="choice.key"
                type="button"
                class="choice"
                :class="[classFor(choice.key), grid ? 'min-h-28 flex-col items-start justify-between gap-3' : '']"
                :disabled="disabled"
                @click="select(choice.key)"
            >
                <span
                    class="grid size-8 shrink-0 place-items-center rounded-lg font-display text-sm font-extrabold transition"
                    :class="tileFor(choice.key)"
                >
                    {{ 'ABCD'[i] ?? i + 1 }}
                </span>
                <MathText :text="choice.text" :latex="choice.latex" :class="grid ? '' : 'flex-1'" />
            </button>
        </div>
    </div>
</template>
