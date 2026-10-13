<script setup lang="ts">
import { computed, ref, watchEffect } from 'vue';

/**
 * Randează text cu formule LaTeX intercalate în delimitatori $...$.
 *
 * KaTeX se încarcă LA CERERE, nu în bundle-ul paginii.
 *
 * Motivul e concret: KaTeX are ~270 KB, iar dacă intră sincron în ecranul de joc
 * îl duce de la 198 KB la 273 KB — peste ținta de 200 KB pentru prima încărcare.
 * Publicul e pe Android ieftin cu date mobile limitate; un bundle gras pe 3G
 * rural înseamnă utilizator pierdut. Itemii de la istorie sau engleză nu au
 * nicio formulă, deci nu trebuie să plătească pentru un motor de LaTeX.
 *
 * Până se încarcă, textul se afișează brut — lizibil, doar neformatat.
 */
const props = withDefaults(
    defineProps<{ text?: string | null; latex?: string | null; display?: boolean }>(),
    { text: null, latex: null, display: false },
);

/** Doar suprafața din KaTeX pe care o folosim efectiv. */
interface KatexApi {
    renderToString(tex: string, options?: Record<string, unknown>): string;
}

// Promisiune la nivel de modul: KaTeX se încarcă o singură dată pe sesiune,
// oricâte componente MathText există pe ecran.
let katexPromise: Promise<KatexApi> | undefined;
const katex = ref<KatexApi | null>(null);

function loadKatex(): Promise<KatexApi> {
    katexPromise ??= Promise.all([
        import('katex'),
        import('katex/dist/katex.min.css'),
    ]).then(([mod]) => (mod.default ?? mod) as unknown as KatexApi);

    return katexPromise;
}

const needsMath = computed(
    () => Boolean(props.latex) || (props.text?.includes('$') ?? false),
);

watchEffect(() => {
    if (needsMath.value && !katex.value) {
        loadKatex().then((k) => (katex.value = k));
    }
});

function escapeHtml(s: string): string {
    return s.replace(/[&<>"']/g, (c) =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c] as string,
    );
}

/**
 * Text simplu, escapat, cu `**accent**` → <strong>. Lecțiile și explicațiile
 * folosesc accentul de la început, dar era afișat literal, cu steluțe.
 * Înlocuirea se face DUPĂ escapare, deci nu poate introduce alt markup.
 */
function prose(s: string): string {
    return escapeHtml(s).replace(/\*\*([^*\n]+?)\*\*/g, '<strong class="font-semibold">$1</strong>');
}

function render(tex: string, displayMode: boolean): string {
    if (!katex.value) {
        return escapeHtml(tex);
    }

    try {
        return katex.value.renderToString(tex, {
            displayMode,
            throwOnError: false,
            strict: false,
            output: 'html',
        });
    } catch {
        // Un item cu LaTeX invalid trebuie să rămână citibil, nu să spargă pagina.
        return escapeHtml(tex);
    }
}

const html = computed(() => {
    if (props.latex) {
        return render(props.latex, props.display);
    }

    if (!props.text) {
        return '';
    }

    if (!needsMath.value) {
        return prose(props.text);
    }

    // Împărțim pe $...$ și randăm doar segmentele matematice; restul se escapează.
    return props.text
        .split(/(\$[^$]+\$)/g)
        .map((part) =>
            part.startsWith('$') && part.endsWith('$') && part.length > 2
                ? render(part.slice(1, -1), false)
                : prose(part),
        )
        .join('');
});
</script>

<template>
    <span v-html="html" />
</template>
