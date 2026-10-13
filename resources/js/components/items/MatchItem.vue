<script setup lang="ts">
import { computed, ref } from 'vue';
import MathText from '@/components/MathText.vue';
import type { Feedback, StudentAnswer, StudentItem } from '@/types';

/** Potrivire: tap pe stânga, apoi tap pe dreapta. Fără drag, la fel ca la ordonare. */
const props = defineProps<{
    item: StudentItem;
    modelValue: StudentAnswer | null;
    feedback: Feedback | null;
    disabled: boolean;
}>();

const emit = defineEmits<{ 'update:modelValue': [StudentAnswer | null] }>();

const pending = ref<string | null>(null);
const pairs = computed<Record<string, string>>(
    () => (props.modelValue?.pairs as Record<string, string>) ?? {},
);
const correctPairs = computed<Record<string, string>>(
    () => (props.feedback?.correct_answer?.pairs as Record<string, string>) ?? {},
);

function tapLeft(key: string) {
    if (props.disabled) return;
    if (pairs.value[key]) {
        const next = { ...pairs.value };
        delete next[key];
        emit('update:modelValue', { pairs: next });
        return;
    }
    pending.value = pending.value === key ? null : key;
}

function tapRight(rightKey: string) {
    if (props.disabled || !pending.value) return;
    emit('update:modelValue', { pairs: { ...pairs.value, [pending.value]: rightKey } });
    pending.value = null;
}

const usedRight = computed(() => Object.values(pairs.value));

function leftClass(key: string): string {
    if (props.feedback) {
        return correctPairs.value[key] === pairs.value[key]
            ? 'choice-correct'
            : 'choice-wrong';
    }
    if (pending.value === key) return 'choice-selected';
    return pairs.value[key] ? 'border-brand-500/40 bg-brand-500/5' : '';
}

function rightLabel(rightKey: string) {
    return props.item.payload.right?.find((r) => r.key === rightKey);
}
</script>

<template>
    <div class="space-y-5">
        <p class="text-lg leading-relaxed">
            <MathText :text="item.payload.stem" />
        </p>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
                <button
                    v-for="left in item.payload.left ?? []"
                    :key="left.key"
                    type="button"
                    class="choice text-sm"
                    :class="leftClass(left.key)"
                    :disabled="disabled"
                    @click="tapLeft(left.key)"
                >
                    <div class="flex-1">
                        <MathText :text="left.text" :latex="left.latex" />
                        <p v-if="pairs[left.key]" class="mt-1 text-xs text-brand-400">
                            → <MathText :text="rightLabel(pairs[left.key])?.text" :latex="rightLabel(pairs[left.key])?.latex" />
                        </p>
                    </div>
                </button>
            </div>

            <div class="space-y-2">
                <button
                    v-for="right in item.payload.right ?? []"
                    :key="right.key"
                    type="button"
                    class="choice text-sm"
                    :class="usedRight.includes(right.key) ? 'opacity-40' : ''"
                    :disabled="disabled || !pending"
                    @click="tapRight(right.key)"
                >
                    <MathText :text="right.text" :latex="right.latex" class="flex-1" />
                </button>
            </div>
        </div>

        <p v-if="pending && !disabled" class="text-sm text-brand-400">
            Acum alege potrivirea din coloana a doua.
        </p>
    </div>
</template>
