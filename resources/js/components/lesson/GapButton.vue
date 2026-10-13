<script setup lang="ts">
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';

/**
 * „Nu înțeleg asta".
 *
 * Are două moduri, pentru că cele două contexte sunt diferite:
 *
 *  ÎN LECȚIE — butonul stă sub un bloc anume. Elevul deja arată cu degetul,
 *  deci nu are ce alege.
 *
 *  DUPĂ UN EXERCIȚIU GREȘIT — nu vine dinspre un bloc, dar „nu înțeleg tema" e
 *  prea gros ca să folosească cuiva. Îi arătăm blocurile din lecția temei și
 *  poate bifa exact ce nu i-e clar, oricâte.
 *
 * Nu dă XP și nu penalizează nimic: a recunoaște că nu înțelegi trebuie să fie
 * gratuit în ambele sensuri.
 */
interface PickableBlock {
    id: string;
    type: string;
    label: string;
}

const props = defineProps<{
    skillId: number;
    lessonId?: number | null;
    blockId?: string | null;
    /** Când e prezentă, butonul devine selector. */
    blocks?: PickableBlock[] | null;
    marked?: boolean;
    source?: 'lesson' | 'practice';
    /** Textul butonului; implicit „Nu înțeleg". */
    label?: string;
    compact?: boolean;
}>();

const TYPE_LABELS: Record<string, string> = {
    text: 'Explicație',
    formula: 'Formulă',
    plot: 'Grafic',
    example: 'Exemplu',
    warning: 'Greșeala tipică',
    table: 'Tabel',
    code: 'Program',
    video: 'Video',
};

const open = ref(false);
const done = ref(props.marked ?? false);
const selected = ref<string[]>([]);

const isPicker = computed(() => (props.blocks?.length ?? 0) > 0);

const form = useForm<{
    skill_id: number;
    lesson_id: number | null;
    block_ids: string[];
    note: string;
    source: string;
}>({
    skill_id: props.skillId,
    lesson_id: props.lessonId ?? null,
    block_ids: props.blockId ? [props.blockId] : [],
    note: '',
    source: props.source ?? 'lesson',
});

function toggleBlock(id: string) {
    const i = selected.value.indexOf(id);
    i === -1 ? selected.value.push(id) : selected.value.splice(i, 1);
}

function submit() {
    // În modul selector, lista goală înseamnă „toată tema" — o alegere validă.
    form.block_ids = isPicker.value
        ? [...selected.value]
        : (props.blockId ? [props.blockId] : []);

    form.post(route('gaps.store'), {
        preserveScroll: true,
        onSuccess: () => {
            done.value = true;
            open.value = false;
            selected.value = [];
            form.reset('note');
        },
    });
}
</script>

<template>
    <div class="relative">
        <button
            v-if="!done"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-xs text-slate-500 transition hover:bg-white/5 hover:text-amber-400"
            @click="open = !open"
        >
            <Icon name="circle-question-mark" />
            {{ label ?? (compact ? 'Nu înțeleg' : 'Nu înțeleg asta') }}
        </button>

        <span v-else class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-xs text-amber-400/80">
            <Icon name="circle-question-mark" />
            notat
        </span>

        <div
            v-if="open"
            class="absolute right-0 z-20 mt-1 space-y-2 rounded-xl border border-white/15 bg-slate-900 p-3 shadow-xl"
            :class="isPicker ? 'w-80' : 'w-72'"
        >
            <p class="text-xs text-slate-400">
                Se salvează în lista ta privată. Nimeni altcineva nu o vede.
            </p>

            <!-- Selectorul de blocuri, doar după un exercițiu -->
            <template v-if="isPicker">
                <p class="text-xs font-semibold text-slate-300">Ce parte din lecție nu e clară?</p>

                <div class="max-h-56 space-y-1 overflow-y-auto pr-1">
                    <button
                        v-for="block in blocks"
                        :key="block.id"
                        type="button"
                        class="flex w-full items-start gap-2 rounded-lg border px-2 py-1.5 text-left text-xs transition"
                        :class="selected.includes(block.id)
                            ? 'border-amber-500/50 bg-amber-500/10'
                            : 'border-white/10 hover:border-white/25'"
                        @click="toggleBlock(block.id)"
                    >
                        <Icon :name="selected.includes(block.id) ? 'square-check' : 'square'"
                              class="mt-0.5 text-sm"
                              :class="selected.includes(block.id) ? 'text-amber-400' : 'text-slate-500'" />
                        <span class="min-w-0 flex-1">
                            <span class="block font-medium text-slate-300">
                                {{ TYPE_LABELS[block.type] ?? 'Conținut' }}
                            </span>
                            <span class="block truncate text-slate-500">{{ block.label }}</span>
                        </span>
                    </button>
                </div>

                <p class="text-xs text-slate-500">
                    {{ selected.length
                        ? selected.length + ' selectate'
                        : 'Fără selecție, se notează toată tema.' }}
                </p>
            </template>

            <textarea
                v-model="form.note"
                rows="2"
                placeholder="Ce anume nu e clar? (opțional)"
                class="w-full resize-none rounded-lg border border-white/10 bg-slate-950 px-2 py-1.5 text-sm outline-none focus:border-amber-500/60"
            />

            <div class="flex gap-2">
                <button type="button" class="flex-1 rounded-lg bg-white/5 px-2 py-1.5 text-xs hover:bg-white/10"
                    @click="open = false">
                    Renunț
                </button>
                <button type="button"
                    class="flex-1 rounded-lg bg-amber-500 px-2 py-1.5 text-xs font-semibold text-on-warning disabled:opacity-50"
                    :disabled="form.processing"
                    @click="submit">
                    Notează
                </button>
            </div>
        </div>
    </div>
</template>
