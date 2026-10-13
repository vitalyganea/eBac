<script setup lang="ts">
import { computed } from 'vue';
import CodeBlock from '@/components/CodeBlock.vue';
import MathText from '@/components/MathText.vue';
import type { Feedback, StudentAnswer, StudentItem } from '@/types';

/**
 * Alegere dintre variante — acoperă mcq, next_step, find_error, code_output și
 * label_diagram. Diferă prezentarea, nu mecanica.
 */
const props = defineProps<{
    item: StudentItem;
    modelValue: StudentAnswer | null;
    feedback: Feedback | null;
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
    if (chosen.value === key) return 'choice-wrong';
    return 'opacity-50';
}
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

        <p class="text-lg leading-relaxed">
            <MathText :text="item.payload.stem" />
        </p>

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

        <div class="space-y-3">
            <button
                v-for="(choice, i) in item.payload.choices ?? []"
                :key="choice.key"
                type="button"
                class="choice"
                :class="classFor(choice.key)"
                :disabled="disabled"
                @click="select(choice.key)"
            >
                <span
                    class="grid size-8 shrink-0 place-items-center rounded-lg bg-white/10 text-sm font-bold"
                >
                    {{ 'ABCD'[i] ?? i + 1 }}
                </span>
                <MathText :text="choice.text" :latex="choice.latex" class="flex-1" />
            </button>
        </div>
    </div>
</template>
