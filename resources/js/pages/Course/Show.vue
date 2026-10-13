<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import LessonBlocks from '@/components/lesson/LessonBlocks.vue';
import VideoEmbed from '@/components/lesson/VideoEmbed.vue';

interface Video {
    provider_id: string;
    title: string;
    channel_name: string | null;
    channel_url: string | null;
    duration_seconds: number | null;
    is_embeddable: boolean;
}

const props = defineProps<{
    subject: { slug: string; name: string; icon: string | null };
    skill: { id: number; slug: string; name: string; curriculum_code: string | null };
    marked_blocks: string[];
    highlight: string | null;
    highlighted_gap: number | null;
    lesson: {
        id: number;
        title: string;
        summary: string | null;
        objectives: string[];
        blocks: Record<string, unknown>[];
        reading_minutes: number;
    };
    videos: Video[];
    prerequisites: { slug: string; name: string; has_lesson: boolean }[];
    is_completed: boolean;
}>();

/**
     * Închide bucla direct din lecție.
     *
     * Fără asta, elevul ar trebui să recitească aici, apoi să se întoarcă în
     * listă ca să bifeze — două pagini pentru o singură decizie. În practică,
     * n-ar face-o, iar lista ar crește la nesfârșit.
     */
function markUnderstood() {
    if (! props.highlighted_gap) return;

    router.put(route('gaps.update', props.highlighted_gap), { action: 'resolve' }, {
        preserveScroll: true,
    });
}

function markComplete() {
    router.post(route('course.complete', [props.subject.slug, props.skill.slug]), {}, {
        preserveScroll: true,
    });
}

function goToTests() {
    router.post(route('practice.start'), { subject: props.subject.slug, mode: 'practice' });
}
</script>

<template>
    <Head :title="lesson.title" />

    <AppLayout>
        <article class="space-y-6">
            <Link :href="route('course.index', subject.slug)" class="inline-block text-sm text-slate-400 hover:text-slate-200">
                ← Curs · {{ subject.name }}
            </Link>

            <header class="space-y-3">
                <h1 class="text-2xl font-black tracking-tight">{{ lesson.title }}</h1>

                <p class="text-sm text-slate-500">
                    {{ lesson.reading_minutes }} min de citit
                    <template v-if="skill.curriculum_code"> · {{ skill.curriculum_code }}</template>
                </p>

                <p v-if="lesson.summary" class="text-slate-300">{{ lesson.summary }}</p>
            </header>

            <!-- Ce trebuie știut înainte, din graful de prerechizite -->
            <div v-if="prerequisites.length" class="card border-amber-500/25">
                <p class="text-sm font-bold">Înainte de asta, ai nevoie de</p>
                <ul class="mt-2 flex flex-wrap gap-2">
                    <li v-for="p in prerequisites" :key="p.slug">
                        <Link v-if="p.has_lesson"
                            :href="route('course.show', [subject.slug, p.slug])"
                            class="inline-block rounded-lg bg-white/5 px-3 py-1.5 text-sm hover:bg-white/10">
                            {{ p.name }}
                        </Link>
                        <span v-else class="inline-block rounded-lg bg-white/5 px-3 py-1.5 text-sm text-slate-400">
                            {{ p.name }}
                        </span>
                    </li>
                </ul>
            </div>

            <!-- Obiectivele: ce vei ști la final -->
            <div v-if="lesson.objectives.length" class="card">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
                    După lecția asta vei putea
                </p>
                <ul class="space-y-1.5 text-sm text-slate-300">
                    <li v-for="(obj, i) in lesson.objectives" :key="i" class="flex gap-2">
                        <span class="text-brand-400">→</span>
                        <span>{{ obj }}</span>
                    </li>
                </ul>
            </div>

            <!-- Ai venit din lista de nelămuriri: spunem de ce e evidențiat
                 ceva și oferim acțiunea, aici, nu în altă pagină. -->
            <div
                v-if="highlight"
                class="sticky top-16 z-10 flex items-center gap-3 rounded-xl border border-amber-500/30 bg-slate-900/95 px-4 py-3 backdrop-blur"
            >
                <Icon name="circle-question-mark" class="text-lg text-amber-400" />
                <p class="flex-1 text-sm text-slate-300">
                    Ai marcat această parte ca neînțeleasă.
                </p>
                <button
                    v-if="highlighted_gap"
                    type="button"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-emerald-500/15 px-3 py-1.5 text-xs font-semibold text-emerald-400 transition hover:bg-emerald-500/25"
                    @click="markUnderstood"
                >
                    <Icon name="check" />
                    Acum am înțeles
                </button>
                <span v-else class="inline-flex shrink-0 items-center gap-1 text-xs text-emerald-400">
                    <Icon name="check" />
                    lămurit
                </span>
            </div>

            <LessonBlocks
                :blocks="(lesson.blocks as any)"
                :skill-id="skill.id"
                :lesson-id="lesson.id"
                :marked-blocks="marked_blocks"
                :highlight="highlight" />

            <!-- Videoclipuri: separate de blocuri, ca un clip care dispare de pe
                 YouTube să nu lase o gaură în mijlocul teoriei. -->
            <section v-if="videos.length">
                <h2 class="mb-2 text-lg font-bold">Explicații video</h2>
                <VideoEmbed
                    v-for="video in videos"
                    :key="video.provider_id"
                    :provider-id="video.provider_id"
                    :title="video.title"
                    :channel-name="video.channel_name"
                    :channel-url="video.channel_url"
                    :duration-seconds="video.duration_seconds"
                    :is-embeddable="video.is_embeddable" />
            </section>

            <!-- Teoria nu dă XP: se învață răspunzând, nu derulând. -->
            <div class="card space-y-3">
                <p class="font-semibold">Ai citit teoria. Acum verifică dacă a rămas.</p>
                <p class="text-sm text-slate-400">
                    Progresul se câștigă din răspunsuri, nu din citit — lecția marcată ca
                    parcursă nu îți dă XP și nu îți crește nivelul.
                </p>

                <div class="flex flex-wrap gap-3">
                    <button type="button" class="btn-primary flex-1" @click="goToTests">
                        Treci la teste
                    </button>
                    <button type="button" class="btn-ghost inline-flex items-center gap-1.5"
                            :disabled="is_completed" @click="markComplete">
                        <Icon v-if="is_completed" name="circle-check" class="text-emerald-400" />
                        {{ is_completed ? 'Parcursă' : 'Marchează ca parcursă' }}
                    </button>
                </div>
            </div>
        </article>
    </AppLayout>
</template>
