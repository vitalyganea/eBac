<script setup lang="ts">
import { computed, ref } from 'vue';
import MathText from '@/components/MathText.vue';
import type { Feedback, StudentAnswer, StudentItem } from '@/types';

const props = defineProps<{
    item: StudentItem;
    modelValue: StudentAnswer | null;
    feedback: Feedback | null;
    disabled: boolean;
}>();

const emit = defineEmits<{ 'update:modelValue': [StudentAnswer | null] }>();

const value = computed({
    get: () => (props.modelValue?.value as string) ?? '',
    set: (v: string) => emit('update:modelValue', v === '' ? null : { value: v }),
});

/**
 * Tastatura `inputmode="decimal"` de pe telefon are doar cifre și separator
 * zecimal: fără `/`, iar pe iPhone fără minus. Un elev nu putea scrie „3/2"
 * sau un rezultat negativ. Butoanele inserează caracterul la cursor.
 */
const input = ref<HTMLInputElement | null>(null);

function insert(char: string) {
    const el = input.value;
    const current = value.value;
    const start = el?.selectionStart ?? current.length;
    const end = el?.selectionEnd ?? current.length;

    value.value = current.slice(0, start) + char + current.slice(end);

    requestAnimationFrame(() => {
        el?.focus();
        el?.setSelectionRange(start + char.length, start + char.length);
    });
}
</script>

<template>
    <div class="space-y-5">
        <p class="text-lg leading-relaxed">
            <MathText :text="item.payload.stem" />
        </p>

        <MathText v-if="item.payload.stem_latex" :latex="item.payload.stem_latex" display />

        <div class="flex items-center gap-3">
            <input
                ref="input"
                v-model="value"
                type="text"
                inputmode="decimal"
                autocomplete="off"
                placeholder="Răspunsul tău"
                class="w-full rounded-xl border-2 border-white/10 bg-white/[0.03] px-4 py-4 text-lg
                       outline-none focus:border-brand-500 disabled:opacity-60"
                :class="feedback ? (feedback.is_correct ? 'border-emerald-500' : 'border-rose-500') : ''"
                :disabled="disabled"
            />
            <span v-if="item.payload.unit" class="shrink-0 text-lg text-slate-400">
                {{ item.payload.unit }}
            </span>
        </div>

        <div v-if="!disabled" class="flex items-center gap-2">
            <button
                v-for="char in ['−', '/', ',']"
                :key="char"
                type="button"
                class="grid size-10 place-items-center rounded-lg border border-white/10 bg-white/[0.03] text-lg
                       transition hover:border-white/25 active:scale-95"
                :aria-label="char === '−' ? 'minus' : char === '/' ? 'fracție' : 'virgulă'"
                @mousedown.prevent
                @click="insert(char === '−' ? '-' : char)"
            >
                {{ char }}
            </button>
            <!-- Elevii din Moldova scriu virgula ca separator zecimal; graderul o
                 acceptă, la fel ca fracțiile (3/2). -->
            <p class="text-xs text-slate-500">Poți scrie cu virgulă (3,14) sau ca fracție (3/2).</p>
        </div>
    </div>
</template>
