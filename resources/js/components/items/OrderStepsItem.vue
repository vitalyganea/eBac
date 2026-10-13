<script setup lang="ts">
import { computed } from 'vue';
import MathText from '@/components/MathText.vue';
import type { ItemFeedback, StudentAnswer, StudentItem } from '@/types';

/**
 * Ordonare — order_steps, timeline, build_formula.
 *
 * Interacțiunea e tap-to-append, nu drag & drop: pe telefon drag-ul e imprecis,
 * iar o bibliotecă de drag ar adăuga zeci de KB pentru zero câștig.
 *
 * Punctajul se dă pe ordinea RELATIVĂ corectă (vezi OrderingGrader), deci un
 * elev care a inversat doi pași dintr-o rezolvare de șase primește aproape tot
 * punctajul — exact ca la baremul analitic.
 */
const props = defineProps<{
    item: StudentItem;
    modelValue: StudentAnswer | null;
    feedback: ItemFeedback | null;
    disabled: boolean;
}>();

const emit = defineEmits<{ 'update:modelValue': [StudentAnswer | null] }>();

const order = computed<string[]>(() => (props.modelValue?.order as string[]) ?? []);
const steps = computed(() => props.item.payload.steps ?? []);
const remaining = computed(() => steps.value.filter((s) => !order.value.includes(s.key)));
const correctOrder = computed<string[]>(
    () => (props.feedback?.correct_answer?.order as string[]) ?? [],
);

function add(key: string) {
    if (props.disabled) return;
    emit('update:modelValue', { order: [...order.value, key] });
}

function removeAt(index: number) {
    if (props.disabled) return;
    emit('update:modelValue', { order: order.value.filter((_, i) => i !== index) });
}

function label(key: string) {
    return steps.value.find((s) => s.key === key);
}

function positionClass(index: number): string {
    if (!props.feedback) return 'border-brand-500/40 bg-brand-500/10';
    return correctOrder.value[index] === order.value[index]
        ? 'border-emerald-500 bg-emerald-500/15'
        : 'border-rose-500 bg-rose-500/15';
}
</script>

<template>
    <div class="space-y-5">
        <p class="text-lg leading-relaxed">
            <MathText :text="item.payload.stem" />
        </p>

        <div v-if="item.payload.problem" class="rounded-xl bg-white/5 p-4">
            <MathText :latex="item.payload.problem" display />
        </div>

        <!-- Rezolvarea construită de elev -->
        <div class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                Rezolvarea ta
            </p>

            <p v-if="!order.length" class="rounded-xl border border-dashed border-white/15 p-4 text-sm text-slate-400">
                Apasă pașii în ordinea corectă.
            </p>

            <button
                v-for="(key, i) in order"
                :key="key"
                type="button"
                class="flex w-full items-center gap-3 rounded-xl border-2 px-4 py-3 text-left"
                :class="positionClass(i)"
                :disabled="disabled"
                @click="removeAt(i)"
            >
                <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-white/10 text-xs font-bold">
                    {{ i + 1 }}
                </span>
                <MathText :text="label(key)?.text" :latex="label(key)?.latex" class="flex-1" />
                <span v-if="!disabled" class="text-xs text-slate-400">scoate</span>
            </button>
        </div>

        <!-- Pașii disponibili -->
        <div v-if="remaining.length" class="space-y-2 border-t border-white/10 pt-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                Pași disponibili
            </p>
            <button
                v-for="step in remaining"
                :key="step.key"
                type="button"
                class="choice"
                :disabled="disabled"
                @click="add(step.key)"
            >
                <MathText :text="step.text" :latex="step.latex" class="flex-1" />
            </button>
        </div>
    </div>
</template>
