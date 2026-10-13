<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';

interface CourseSkill {
    slug: string;
    name: string;
    has_lesson: boolean;
    reading_minutes: number | null;
    is_completed: boolean;
}

defineProps<{
    subject: { slug: string; name: string; icon: string | null };
    chapters: { name: string; skills: CourseSkill[] }[];
}>();
</script>

<template>
    <Head :title="'Curs · ' + subject.name" />

    <AppLayout>
        <div class="space-y-6">
            <Link :href="route('subject.show', subject.slug)" class="inline-block text-sm text-slate-400 hover:text-slate-200">
                ← {{ subject.name }}
            </Link>

            <header>
                <h1 class="text-2xl font-bold">
                    <Icon :name="subject.icon ?? 'book'" class="text-brand-400" :stroke-width="1.75" />
                    Curs
                </h1>
                <p class="mt-1 text-sm text-slate-400">
                    Teoria pe competențe. Nu e obligatorie — dacă știi materia, mergi direct la teste.
                </p>
            </header>

            <section v-for="chapter in chapters" :key="chapter.name">
                <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
                    {{ chapter.name }}
                </h2>

                <ul class="space-y-1.5">
                    <li v-for="skill in chapter.skills" :key="skill.slug">
                        <Link v-if="skill.has_lesson"
                            :href="route('course.show', [subject.slug, skill.slug])"
                            class="flex items-center gap-3 rounded-lg border border-white/10 px-3 py-2.5 text-sm transition hover:border-white/25">
                            <Icon :name="skill.is_completed ? 'circle-check' : 'book-open'"
                                  class="text-base"
                                  :class="skill.is_completed ? 'text-emerald-400' : 'text-slate-400'" />
                            <span class="flex-1">{{ skill.name }}</span>
                            <span v-if="skill.reading_minutes" class="shrink-0 text-xs text-slate-500">
                                {{ skill.reading_minutes }} min
                            </span>
                        </Link>

                        <div v-else
                            class="flex items-center gap-3 rounded-lg border border-dashed border-white/10 px-3 py-2.5 text-sm text-slate-500">
                            <span class="shrink-0">·</span>
                            <span class="flex-1">{{ skill.name }}</span>
                            <span class="shrink-0 text-xs">în pregătire</span>
                        </div>
                    </li>
                </ul>
            </section>
        </div>
    </AppLayout>
</template>
