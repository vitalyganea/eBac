<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';

interface CatalogSubject {
    id: number;
    slug: string;
    name: string;
    icon: string | null;
    color: string | null;
    role: string | null;
    role_label: string | null;
    is_selected: boolean;
    is_primary: boolean;
    items_count: number;
    is_playable: boolean;
}

const props = defineProps<{
    subjects: CatalogSubject[];
    profile: string | null;
    profile_label: string | null;
    suggested: number[];
    is_onboarding: boolean;
}>();

// Ordinea selecției contează: prima disciplină devine cea principală.
const selected = ref<number[]>(
    props.subjects.filter((s) => s.is_selected).map((s) => s.id),
);
const primaryId = ref<number | null>(
    props.subjects.find((s) => s.is_primary)?.id ?? null,
);
const saving = ref(false);

function toggle(id: number) {
    const i = selected.value.indexOf(id);

    if (i === -1) {
        selected.value.push(id);
        primaryId.value ??= id;
        return;
    }

    selected.value.splice(i, 1);
    if (primaryId.value === id) {
        primaryId.value = selected.value[0] ?? null;
    }
}

function applySuggestion() {
    selected.value = [...props.suggested];
    primaryId.value = props.suggested[0] ?? null;
}

function save() {
    saving.value = true;
    router.put(
        route('subjects.update'),
        { subject_ids: selected.value, primary_id: primaryId.value },
        { onFinish: () => (saving.value = false) },
    );
}

const isSelected = (id: number) => selected.value.includes(id);

// Grupăm după rolul din profilul elevului — pur informativ, ca să vadă rapid ce
// e obligatoriu. Nu restrânge nimic: orice disciplină e selectabilă.
const groups = computed(() => {
    const order = ['obligatoriu', 'profil', 'la_alegere', null] as (string | null)[];
    const labels: Record<string, string> = {
        obligatoriu: 'Obligatorii pentru profilul tău',
        profil: 'Disciplina de profil',
        la_alegere: 'La alegere pentru profilul tău',
    };

    return order
        .map((role) => ({
            key: String(role),
            label: role ? labels[role] : 'Alte discipline',
            items: props.subjects.filter((s) => s.role === role),
        }))
        .filter((g) => g.items.length > 0);
});

const withContent = computed(() => selected.value.filter(
    (id) => props.subjects.find((s) => s.id === id)?.is_playable,
).length);
</script>

<template>
    <Head title="Disciplinele mele" />

    <AppLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold">
                    {{ is_onboarding ? 'Ce dai la BAC?' : 'Disciplinele mele' }}
                </h1>
                <p class="mt-2 text-sm text-slate-400">
                    Alege oricâte discipline. Le poți schimba oricând — dacă renunți la
                    una, progresul rămâne salvat.
                </p>
            </div>

            <div v-if="profile_label" class="card flex flex-wrap items-center gap-3 text-sm">
                <span class="text-slate-400">Profilul tău:</span>
                <span class="rounded-lg bg-white/10 px-2 py-1 font-semibold">{{ profile_label }}</span>
                <button
                    v-if="suggested.length"
                    type="button"
                    class="ml-auto text-brand-400 hover:underline"
                    @click="applySuggestion"
                >
                    Selectează cele recomandate
                </button>
            </div>

            <div v-for="group in groups" :key="group.key" class="space-y-3">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                    {{ group.label }}
                </h2>

                <div class="grid gap-2 sm:grid-cols-2">
                    <button
                        v-for="subject in group.items"
                        :key="subject.id"
                        type="button"
                        class="flex items-center gap-3 rounded-xl border-2 px-4 py-3 text-left transition active:scale-[0.99]"
                        :class="isSelected(subject.id)
                            ? 'border-brand-500 bg-brand-500/10'
                            : 'border-white/10 bg-white/[0.03] hover:border-white/25'"
                        @click="toggle(subject.id)"
                    >
                        <Icon :name="subject.icon ?? 'book'" class="text-xl text-slate-300" />

                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-medium">{{ subject.name }}</span>
                            <span class="mt-0.5 block text-xs" :class="subject.is_playable ? 'text-slate-400' : 'text-amber-400/80'">
                                {{ subject.is_playable
                                    ? subject.items_count + ' întrebări'
                                    : 'conținut în pregătire' }}
                            </span>
                        </span>

                        <span
                            v-if="primaryId === subject.id"
                            class="shrink-0 rounded-md bg-brand-500/20 px-2 py-0.5 text-[10px] font-bold uppercase text-brand-400"
                        >
                            principal
                        </span>
                        <Icon v-else-if="isSelected(subject.id)" name="check" class="text-brand-400" />
                    </button>
                </div>
            </div>

            <div class="sticky bottom-0 -mx-4 border-t border-white/10 bg-slate-950/90 px-4 py-4 backdrop-blur">
                <div class="flex items-center gap-3">
                    <p class="flex-1 text-sm text-slate-400">
                        <span class="font-semibold text-slate-200">{{ selected.length }}</span>
                        selectate
                        <span v-if="selected.length && withContent < selected.length" class="text-amber-400/80">
                            ({{ withContent }} cu conținut disponibil)
                        </span>
                    </p>

                    <Link v-if="!is_onboarding" :href="route('dashboard')" class="btn-ghost">
                        Renunță
                    </Link>

                    <button
                        type="button"
                        class="btn-primary"
                        :disabled="saving || selected.length === 0"
                        @click="save"
                    >
                        {{ saving ? 'Se salvează…' : 'Salvează' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
