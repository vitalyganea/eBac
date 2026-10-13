<script setup lang="ts">
import { computed } from 'vue';
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
</script>

<template>
    <div class="space-y-5">
        <p class="text-lg leading-relaxed">
            <MathText :text="item.payload.stem" />
        </p>

        <MathText v-if="item.payload.stem_latex" :latex="item.payload.stem_latex" display />

        <div class="flex items-center gap-3">
            <input
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

        <!-- Elevii din Moldova scriu virgula ca separator zecimal; graderul o acceptă. -->
        <p class="text-xs text-slate-500">Poți scrie și cu virgulă (ex. 3,14).</p>
    </div>
</template>
