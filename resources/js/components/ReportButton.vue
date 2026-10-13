<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';

/**
 * Raportarea conținutului public al altui utilizator.
 *
 * Deliberat discret: un buton mare de raportare lângă fiecare nume invită la
 * abuz într-un clasament competitiv. Se deschide la cerere, cere un motiv, și
 * nu confirmă niciodată ce s-a întâmplat cu contul raportat.
 */
const props = defineProps<{
    username: string;
    subject: 'avatar' | 'username';
    hasAvatar?: boolean;
}>();

const open = ref(false);
const sent = ref(false);

const REASONS = [
    { value: 'ofensator', label: 'Conținut ofensator' },
    { value: 'impersonare', label: 'Se dă drept altcineva' },
    { value: 'contact', label: 'Conține date de contact' },
    { value: 'spam', label: 'Spam sau reclamă' },
    { value: 'altul', label: 'Altul' },
];

const form = useForm({
    username: props.username,
    subject: props.subject,
    reason: '',
    comment: '',
});

function submit() {
    if (!form.reason) return;

    form.post(route('report.store'), {
        preserveScroll: true,
        onSuccess: () => {
            sent.value = true;
            open.value = false;
            form.reset('reason', 'comment');
        },
    });
}
</script>

<template>
    <span class="relative">
        <button
            v-if="!sent"
            type="button"
            class="rounded px-1.5 py-0.5 text-xs text-slate-600 transition hover:text-slate-300"
            :title="'Raportează ' + username"
            @click="open = !open"
        >
            <Icon name="flag" label="Raportează" />
        </button>

        <span v-else class="px-1.5 text-xs text-slate-600" title="Semnalare trimisă">
            <Icon name="check" label="Semnalare trimisă" />
        </span>

        <div
            v-if="open"
            class="absolute right-0 top-6 z-20 w-64 space-y-2 rounded-xl border border-white/15 bg-slate-900 p-3 shadow-xl"
        >
            <p class="text-xs text-slate-400">
                Raportezi <strong class="text-slate-200">{{ username }}</strong>
            </p>

            <select
                v-model="form.reason"
                class="w-full rounded-lg border border-white/10 bg-slate-950 px-2 py-1.5 text-sm outline-none focus:border-brand-500"
            >
                <option value="">Alege motivul…</option>
                <option v-for="r in REASONS" :key="r.value" :value="r.value">{{ r.label }}</option>
            </select>

            <textarea
                v-model="form.comment"
                rows="2"
                placeholder="Detalii (opțional)"
                class="w-full resize-none rounded-lg border border-white/10 bg-slate-950 px-2 py-1.5 text-sm outline-none focus:border-brand-500"
            />

            <div class="flex gap-2">
                <button type="button" class="flex-1 rounded-lg bg-white/5 px-2 py-1.5 text-xs hover:bg-white/10"
                    @click="open = false">
                    Renunț
                </button>
                <button type="button"
                    class="flex-1 rounded-lg bg-brand-500 px-2 py-1.5 text-xs font-semibold text-on-accent disabled:opacity-50"
                    :disabled="!form.reason || form.processing"
                    @click="submit">
                    Trimite
                </button>
            </div>
        </div>
    </span>
</template>
