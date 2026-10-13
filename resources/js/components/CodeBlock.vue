<script setup lang="ts">
import { computed, ref, watch } from 'vue';

/**
 * Bloc de cod cu comutator de limbaj.
 *
 * Examenul de informatică din Moldova acceptă **Pascal sau C/C++, la alegerea
 * candidatului**, iar itemii oficiali ANCE afișează programul în ambele limbaje,
 * în coloane paralele. Un elev care a învățat Pascal nu trebuie să descifreze
 * C++ ca să răspundă.
 *
 * Alegerea se ține în localStorage, ca să nu comute la fiecare item. E o
 * preferință locală de afișare, nu date de progres — dacă localStorage e blocat
 * (mod privat, setări restrictive), componenta funcționează cu limbajul implicit.
 */
const props = defineProps<{
    code?: string | null;
    variants?: Record<string, string> | null;
}>();

const LABELS: Record<string, string> = {
    cpp: 'C++',
    c: 'C',
    pascal: 'Pascal',
    python: 'Python',
};

const STORAGE_KEY = 'ebac:code-lang';

const available = computed(() =>
    Object.entries(props.variants ?? {})
        .filter(([, v]) => Boolean(v))
        .map(([k]) => k),
);

function readPreference(): string | null {
    try {
        return localStorage.getItem(STORAGE_KEY);
    } catch {
        return null;
    }
}

const stored = readPreference();
const active = ref<string>(
    stored && available.value.includes(stored) ? stored : (available.value[0] ?? 'cpp'),
);

// Dacă itemul următor nu oferă limbajul preferat, cădem pe primul disponibil.
watch(available, (langs) => {
    if (langs.length && !langs.includes(active.value)) {
        const pref = readPreference();
        active.value = pref && langs.includes(pref) ? pref : langs[0];
    }
});

function select(lang: string) {
    active.value = lang;
    try {
        localStorage.setItem(STORAGE_KEY, lang);
    } catch {
        // Preferința nu se salvează; afișarea rămâne corectă pentru sesiunea asta.
    }
}

const source = computed(() =>
    props.variants ? (props.variants[active.value] ?? '') : (props.code ?? ''),
);
</script>

<template>
    <div v-if="source" class="overflow-hidden rounded-xl border border-white/10 bg-black/40">
        <div v-if="available.length > 1" class="flex gap-1 border-b border-on-dark/10 p-1">
            <button
                v-for="lang in available"
                :key="lang"
                type="button"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                :class="active === lang
                    ? 'bg-on-dark/10 text-on-dark'
                    : 'text-on-dark/60 hover:text-on-dark'"
                @click="select(lang)"
            >
                {{ LABELS[lang] ?? lang }}
            </button>
        </div>

        <!-- Codul trebuie să deruleze în propriul container, nu să lățească pagina. -->
        <pre class="overflow-x-auto p-4 text-sm leading-relaxed text-on-dark"><code>{{ source }}</code></pre>
    </div>
</template>
