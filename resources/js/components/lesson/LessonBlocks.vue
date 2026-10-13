<script setup lang="ts">
import { nextTick, onMounted } from 'vue';
import CodeBlock from '@/components/CodeBlock.vue';
import GapButton from '@/components/lesson/GapButton.vue';
import Icon from '@/components/Icon.vue';
import MathText from '@/components/MathText.vue';
import Plot from '@/components/lesson/Plot.vue';
import VideoEmbed from '@/components/lesson/VideoEmbed.vue';

/**
 * Randează blocurile unei lecții.
 *
 * Conținutul nu e HTML liber, ci o listă de blocuri tipizate — fiecare cu
 * componentul lui. Așa se poate afișa diferit pe mobil, se poate traduce bloc cu
 * bloc, se poate valida automat, iar un editor nu poate injecta markup arbitrar.
 */
interface Block {
    type: string;
    id?: string;
    [key: string]: unknown;
}

const props = defineProps<{
    blocks: Block[];
    skillId: number;
    lessonId: number;
    markedBlocks?: string[];
    /** Blocul spre care s-a venit din lista „Ce nu am înțeles". */
    highlight?: string | null;
}>();

/**
 * Derulare până la blocul marcat.
 *
 * `block: 'center'` în loc de implicitul 'start': antetul e lipicios și ar
 * acoperi exact ce vrem să arătăm. Cu întârziere, pentru că blocurile cu KaTeX
 * își schimbă înălțimea după ce se încarcă motorul de formule — o derulare
 * imediată ar nimeri lângă.
 */
onMounted(() => {
    if (! props.highlight) {
        return;
    }

    nextTick(() => {
        setTimeout(() => {
            document
                .getElementById(`bloc-${props.highlight}`)
                ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 250);
    });
});

const isHighlighted = (block: Block) => Boolean(block.id) && block.id === props.highlight;

/**
 * Titlurile nu primesc buton: „nu înțeleg titlul secțiunii" nu e o informație
 * utilă nimănui. Doar conținutul propriu-zis.
 */
const MARKABLE = ['text', 'formula', 'plot', 'example', 'warning', 'table', 'code', 'video'];

const canMark = (block: Block) => Boolean(block.id) && MARKABLE.includes(block.type);
const isMarked = (block: Block) => (props.markedBlocks ?? []).includes(block.id ?? '');
</script>

<template>
    <div class="space-y-4">
        <div
            v-for="(block, i) in blocks"
            :id="block.id ? `bloc-${block.id}` : undefined"
            :key="block.id ?? i"
            class="scroll-mt-24 rounded-2xl transition"
            :class="isHighlighted(block) ? 'bg-amber-500/[0.06] p-3 ring-2 ring-amber-500/50' : ''"
        >
            <!-- Titlu de secțiune -->
            <h2 v-if="block.type === 'heading'" class="pt-3 text-lg font-bold">
                {{ block.text }}
            </h2>

            <!-- Paragraf, cu formule intercalate în $...$ -->
            <p v-else-if="block.type === 'text'" class="leading-relaxed text-slate-300">
                <MathText :text="(block.text as string)" />
            </p>

            <!-- Formulă evidențiată -->
            <div v-else-if="block.type === 'formula'"
                 class="rounded-xl border border-brand-500/25 bg-brand-500/[0.07] px-4 py-5 text-center">
                <MathText :latex="(block.latex as string)" display />
                <p v-if="block.caption" class="mt-2 text-xs text-slate-400">{{ block.caption }}</p>
            </div>

            <!-- Program, în limbajul ales de elev.
                 Examenul de informatică din Moldova acceptă Pascal sau C/C++, iar
                 itemii oficiali ANCE dau codul în ambele. Teoria trebuie să facă
                 la fel: un elev care a învățat Pascal nu descifrează C++. -->
            <figure v-else-if="block.type === 'code'" class="my-5">
                <CodeBlock :code="(block.code as string)" :variants="(block.variants as any)" />
                <figcaption v-if="block.caption" class="mt-2 text-xs text-slate-500">
                    {{ block.caption }}
                </figcaption>
            </figure>

            <!-- Tabel comparativ.
                 Pe telefon derulează în propriul container: un tabel de gramatică
                 cu patru coloane nu are cum să încapă pe 360px, dar nici nu are
                 voie să lățească pagina. -->
            <figure v-else-if="block.type === 'table'" class="my-5">
                <div class="overflow-x-auto rounded-xl border border-white/10">
                    <table class="w-full min-w-[520px] border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-white/10 bg-white/[0.04]">
                                <th v-for="(h, hi) in (block.headers as string[])" :key="hi"
                                    class="px-3 py-2.5 text-left font-semibold">
                                    {{ h }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, ri) in (block.rows as string[][])" :key="ri"
                                class="border-b border-white/5 last:border-0">
                                <td v-for="(cell, ci) in row" :key="ci"
                                    class="px-3 py-2.5 align-top"
                                    :class="ci === 0 ? 'font-medium text-slate-200' : 'text-slate-400'">
                                    <MathText :text="cell" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <figcaption v-if="block.caption" class="mt-2 text-xs text-slate-500">
                    {{ block.caption }}
                </figcaption>
            </figure>

            <!-- Grafic desenat, nu încărcat -->
            <Plot v-else-if="block.type === 'plot'"
                :series="(block.series as any)"
                :x-range="(block.x_range as any)"
                :y-range="(block.y_range as any)"
                :markers="(block.markers as any) ?? []"
                :caption="(block.caption as string)" />

            <!-- Exemplu rezolvat, pas cu pas -->
            <div v-else-if="block.type === 'example'" class="card border-emerald-500/25">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-emerald-400">
                    {{ block.title ?? 'Exemplu rezolvat' }}
                </p>

                <p v-if="block.problem" class="mb-3">
                    <MathText :text="(block.problem as string)" />
                </p>

                <!-- Un exemplu de informatică începe cu programul, apoi îl urmărește. -->
                <CodeBlock v-if="block.code || block.variants"
                    class="mb-4"
                    :code="(block.code as string)"
                    :variants="(block.variants as any)" />

                <ol class="space-y-2.5">
                    <li v-for="(step, s) in (block.steps as any[])" :key="s" class="flex gap-3 text-sm">
                        <span class="grid size-6 shrink-0 place-items-center rounded-lg bg-white/10 text-xs font-bold">
                            {{ s + 1 }}
                        </span>
                        <span class="flex-1 leading-relaxed text-slate-300">
                            <MathText :text="step.text" />
                            <MathText v-if="step.latex" :latex="step.latex" display class="mt-1 block" />
                        </span>
                    </li>
                </ol>

                <p v-if="block.result" class="mt-3 border-t border-white/10 pt-3 text-sm">
                    <span class="text-slate-400">Rezultat: </span>
                    <MathText :text="(block.result as string)" />
                </p>
            </div>

            <!-- Greșeala tipică: ce pierde puncte la examen -->
            <div v-else-if="block.type === 'warning'" class="rounded-xl border border-rose-500/30 bg-rose-500/[0.07] p-4">
                <p class="mb-1 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-rose-400">
                    <Icon name="triangle-alert" />
                    {{ block.title ?? 'Greșeala tipică' }}
                </p>
                <p class="text-sm leading-relaxed text-slate-300">
                    <MathText :text="(block.text as string)" />
                </p>
            </div>

            <!-- Videoclip explicativ -->
            <VideoEmbed v-else-if="block.type === 'video' && block.provider_id"
                :provider-id="(block.provider_id as string)"
                :title="(block.title as string)"
                :channel-name="(block.channel_name as string)"
                :channel-url="(block.channel_url as string)"
                :duration-seconds="(block.duration_seconds as number)"
                :is-embeddable="(block.is_embeddable as boolean)" />
            <!-- Butonul stă sub blocul la care se referă, aliniat la dreapta:
                 vizibil când ai nevoie de el, fără să întrerupă lectura. -->
            <div v-if="canMark(block)" class="mt-1 flex justify-end">
                <GapButton
                    :skill-id="skillId"
                    :lesson-id="lessonId"
                    :block-id="block.id"
                    :marked="isMarked(block)"
                    compact
                />
            </div>
        </div>
    </div>
</template>
