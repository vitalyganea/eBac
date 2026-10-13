<script setup lang="ts">
import { computed } from 'vue';
import MathText from '@/components/MathText.vue';
import type { Feedback, StudentAnswer, StudentItem } from '@/types';

/** Completare de spații — fill_blanks și balance_equation (coeficienți). */
const props = defineProps<{
    item: StudentItem;
    modelValue: StudentAnswer | null;
    feedback: Feedback | null;
    disabled: boolean;
}>();

const emit = defineEmits<{ 'update:modelValue': [StudentAnswer | null] }>();

const values = computed<Record<string, string>>(
    () => (props.modelValue?.values as Record<string, string>) ?? {},
);

const correctBlanks = computed<string[]>(
    () => (props.feedback?.breakdown?.correct_blanks as string[]) ?? [],
);

function set(key: string, v: string) {
    emit('update:modelValue', { values: { ...values.value, [key]: v } });
}

function blankClass(key: string): string {
    if (!props.feedback) return 'border-white/15 focus:border-brand-500';
    return correctBlanks.value.includes(key) ? 'border-emerald-500' : 'border-rose-500';
}
</script>

<template>
    <div class="space-y-5">
        <div
            v-if="item.payload.passage"
            class="max-h-72 overflow-y-auto rounded-xl border border-white/10 bg-white/[0.03] p-4 text-sm leading-relaxed text-slate-300"
        >
            <p class="whitespace-pre-line">{{ item.payload.passage }}</p>
        </div>

        <p class="text-lg leading-relaxed">
            <MathText :text="item.payload.stem" />
        </p>

        <div v-if="item.payload.stem_template" class="rounded-xl bg-white/5 p-4 text-center text-xl">
            <MathText :text="item.payload.stem_template" />
        </div>

        <div class="space-y-3">
            <div
                v-for="blank in item.payload.blanks ?? []"
                :key="blank.key"
                class="flex items-center gap-3"
            >
                <span class="w-20 shrink-0 text-sm text-slate-400">{{ blank.key }}</span>

                <!-- Cu variante predefinite dăm butoane; altfel câmp liber. -->
                <div v-if="blank.options?.length" class="flex flex-wrap gap-2">
                    <button
                        v-for="opt in blank.options"
                        :key="opt"
                        type="button"
                        class="rounded-lg border-2 px-3 py-2 text-sm transition"
                        :class="values[blank.key] === opt
                            ? 'border-brand-500 bg-brand-500/15'
                            : 'border-white/10 bg-white/[0.03] hover:border-white/25'"
                        :disabled="disabled"
                        @click="set(blank.key, opt)"
                    >
                        <MathText :text="opt" />
                    </button>
                </div>

                <input
                    v-else
                    :value="values[blank.key] ?? ''"
                    type="text"
                    autocomplete="off"
                    class="w-full rounded-lg border-2 bg-white/[0.03] px-3 py-2 outline-none"
                    :class="blankClass(blank.key)"
                    :disabled="disabled"
                    @input="set(blank.key, ($event.target as HTMLInputElement).value)"
                />
            </div>
        </div>
    </div>
</template>
