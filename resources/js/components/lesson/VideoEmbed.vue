<script setup lang="ts">
import { ref } from 'vue';

/**
 * Videoclip explicativ, prin playerul oficial YouTube.
 *
 * Reguli respectate: nu descărcăm și nu reîncărcăm materialul altcuiva, nu
 * ascundem branding-ul și nu suprapunem elemente peste player. Domeniul e
 * `youtube-nocookie.com` — utilizatorii sunt minori și n-au de ce să primească
 * cookie-uri de urmărire publicitară doar ca să vadă o explicație la derivate.
 *
 * Iframe-ul se încarcă abia la click (`facade`): un player YouTube trage ~1 MB
 * de JavaScript, iar o lecție cu trei videoclipuri ar deveni de neîncărcat pe
 * date mobile.
 */
const props = defineProps<{
    providerId: string;
    title: string;
    channelName?: string | null;
    channelUrl?: string | null;
    durationSeconds?: number | null;
    isEmbeddable?: boolean;
}>();

const playing = ref(false);

const embedUrl = `https://www.youtube-nocookie.com/embed/${props.providerId}?rel=0&modestbranding=1&autoplay=1`;
const watchUrl = `https://www.youtube.com/watch?v=${props.providerId}`;
const thumbUrl = `https://i.ytimg.com/vi/${props.providerId}/hqdefault.jpg`;

function duration(): string | null {
    if (!props.durationSeconds) return null;
    const m = Math.floor(props.durationSeconds / 60);
    const s = props.durationSeconds % 60;
    return `${m}:${String(s).padStart(2, '0')}`;
}
</script>

<template>
    <figure class="my-5">
        <!-- O parte din clipuri au embed-ul dezactivat de autor. Nu e o eroare:
             trimitem pe YouTube, nu afișăm un player gol. -->
        <a v-if="isEmbeddable === false" :href="watchUrl" target="_blank" rel="noopener"
           class="card flex items-center gap-3 transition hover:border-white/25">
            <span class="text-2xl">▶️</span>
            <span class="min-w-0 flex-1">
                <span class="block truncate font-medium">{{ title }}</span>
                <span class="block text-xs text-slate-400">Se deschide pe YouTube</span>
            </span>
        </a>

        <div v-else class="overflow-hidden rounded-xl border border-white/10 bg-black">
            <div class="relative aspect-video">
                <iframe v-if="playing"
                    :src="embedUrl"
                    :title="title"
                    class="absolute inset-0 h-full w-full"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    referrerpolicy="strict-origin-when-cross-origin"
                    allowfullscreen />

                <button v-else type="button"
                    class="group absolute inset-0 h-full w-full"
                    :aria-label="'Redă: ' + title"
                    @click="playing = true">
                    <img :src="thumbUrl" :alt="''" loading="lazy"
                         class="h-full w-full object-cover opacity-70 transition group-hover:opacity-90" />
                    <span class="absolute inset-0 grid place-items-center">
                        <span class="grid size-16 place-items-center rounded-full bg-black/70 text-2xl transition group-hover:scale-110">
                            ▶️
                        </span>
                    </span>
                </button>
            </div>
        </div>

        <figcaption class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
            <span class="text-slate-400">{{ title }}</span>
            <template v-if="channelName">
                <span>·</span>
                <a v-if="channelUrl" :href="channelUrl" target="_blank" rel="noopener nofollow"
                   class="hover:text-slate-300">{{ channelName }}</a>
                <span v-else>{{ channelName }}</span>
            </template>
            <span v-if="duration()">· {{ duration() }}</span>
        </figcaption>
    </figure>
</template>
