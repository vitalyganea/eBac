<script setup lang="ts">
import { computed } from 'vue';

/**
 * Avatarul unui utilizator: imagine încărcată, altfel emoji, altfel implicit.
 *
 * Emoji-ul nu e un fallback de avarie, ci opțiunea implicită — nu ocupă spațiu,
 * nu cere moderare și e disponibil instant. Imaginea e un plus.
 */
const props = withDefaults(defineProps<{
    url?: string | null;
    emoji?: string | null;
    size?: 'sm' | 'md' | 'lg' | 'xl';
    alt?: string;
}>(), { url: null, emoji: null, size: 'md', alt: '' });

const box = computed(() => ({
    sm: 'size-6 text-sm',
    md: 'size-8 text-lg',
    lg: 'size-12 text-2xl',
    xl: 'size-24 text-5xl',
}[props.size]));
</script>

<template>
    <span class="inline-grid shrink-0 place-items-center overflow-hidden rounded-full bg-white/5"
          :class="box">
        <img v-if="url" :src="url" :alt="alt" loading="lazy" decoding="async"
             class="h-full w-full object-cover" />
        <span v-else>{{ emoji || '🙂' }}</span>
    </span>
</template>
