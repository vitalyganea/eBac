<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';

interface SubjectRank {
    rank: number;
    total: number;
    xp: number;
}

interface DashboardSubject {
    slug: string;
    name: string;
    icon: string | null;
    is_primary: boolean;
    items_count: number;
    is_playable: boolean;
    xp: number;
    accuracy: number | null;
    rank: SubjectRank | null;
    open_gaps: number;
}

defineProps<{
    subjects: DashboardSubject[];
    exam: { name: string; days_left: number | null; phase: string | null } | null;
    leaderboard_opt_in: boolean;
}>();

const phaseLabels: Record<string, string> = {
    acumulare: 'Acumulare — materie nouă',
    consolidare: 'Consolidare — exersare mixtă',
    simulari: 'Simulări săptămânale',
    'puncte-slabe': 'Doar puncte slabe',
    formule: 'Doar formule și greșeli tipice',
};

function openSubject(subject: string) {
    router.get(route('subject.show', subject));
}

const nf = new Intl.NumberFormat('ro-MD');

/** Procentul de utilizatori pe care îi depășește — mai motivant decât poziția brută. */
function percentile(rank: SubjectRank): number {
    return Math.round(((rank.total - rank.rank) / Math.max(1, rank.total - 1)) * 100);
}
</script>

<template>
    <Head title="Panou" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Numărătoarea inversă până la examen: ea decide ce face platforma
                 cu tine. Cu 200 de zile rămase înveți; cu 10, repari găurile. -->
            <div v-if="exam" class="card border-brand-500/30">
                <div class="flex items-baseline justify-between">
                    <p class="text-sm text-slate-400">{{ exam.name }}</p>
                    <p v-if="exam.days_left !== null" class="text-2xl font-black tabular-nums">
                        {{ exam.days_left }} <span class="text-sm font-normal text-slate-400">zile</span>
                    </p>
                </div>
                <p v-if="exam.phase" class="mt-2 text-sm text-brand-400">
                    {{ phaseLabels[exam.phase] ?? exam.phase }}
                </p>
            </div>

            <div>
                <div class="mb-3 flex items-baseline justify-between">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Disciplinele mele
                    </h2>
                    <Link :href="route('subjects.edit')" class="text-sm text-brand-400 hover:underline">
                        Modifică
                    </Link>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <button
                        v-for="subject in subjects"
                        :key="subject.slug"
                        type="button"
                        class="card text-left transition"
                        :class="subject.is_playable
                            ? 'hover:border-white/25 active:scale-[0.99]'
                            : 'cursor-not-allowed opacity-60'"
                        @click="openSubject(subject.slug)"
                    >
                        <div class="flex items-start justify-between">
                            <Icon :name="subject.icon ?? 'book'" class="text-2xl text-slate-300" />
                            <span
                                v-if="subject.is_primary"
                                class="rounded-md bg-brand-500/20 px-2 py-0.5 text-[10px] font-bold uppercase text-brand-400"
                            >
                                profil
                            </span>
                        </div>

                        <p class="mt-2 font-semibold">{{ subject.name }}</p>

                        <!-- Clasamentul pe disciplină: comparația care are sens.
                             Un XP global ar amesteca matematica cu engleza. -->
                        <div v-if="subject.rank" class="mt-3 space-y-1">
                            <p class="text-sm">
                                <span class="font-black tabular-nums text-brand-400">
                                    #{{ nf.format(subject.rank.rank) }}
                                </span>
                                <span class="text-slate-400">
                                    din {{ nf.format(subject.rank.total) }}
                                </span>
                            </p>

                            <div class="h-1 overflow-hidden rounded-full bg-white/5">
                                <div class="h-full rounded-full bg-brand-500 transition-all"
                                    :style="{ width: percentile(subject.rank) + '%' }" />
                            </div>

                            <p class="text-xs text-slate-500 tabular-nums">
                                {{ nf.format(subject.xp) }} XP
                                <template v-if="subject.accuracy !== null">
                                    · {{ Math.round(subject.accuracy * 100) }}% corecte
                                </template>
                            </p>
                        </div>

                        <p v-if="subject.open_gaps" class="mt-2 text-xs text-amber-400/80">
                            <Icon name="circle-question-mark" class="mr-1" />{{ subject.open_gaps }}
                            {{ subject.open_gaps === 1 ? 'lucru nelămurit' : 'lucruri nelămurite' }}
                        </p>

                        <p v-if="!subject.rank" class="mt-3 text-xs" :class="subject.is_playable ? 'text-slate-400' : 'text-amber-400/80'">
                            {{ subject.is_playable
                                ? 'Încă neclasat · ' + subject.items_count + ' întrebări'
                                : 'Conținut în pregătire' }}
                        </p>
                    </button>
                </div>
            </div>

            <p v-if="!leaderboard_opt_in" class="card text-sm text-slate-400">
                Nu apari în clasamente.
                <Link :href="route('settings.edit')" class="text-brand-400 hover:underline">
                    Activează participarea
                </Link>
                ca să vezi pe ce loc ești la fiecare disciplină.
            </p>
        </div>
    </AppLayout>
</template>
