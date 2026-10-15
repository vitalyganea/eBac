<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import type { Gamification } from '@/types';

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

const props = defineProps<{
    subjects: DashboardSubject[];
    exam: { name: string; days_left: number | null; phase: string | null } | null;
    leaderboard_opt_in: boolean;
}>();

const page = usePage();
const game = computed<Gamification | null>(() => page.props.gamification ?? null);

const phaseLabels: Record<string, string> = {
    acumulare: 'Acum e perioada de materie nouă.',
    consolidare: 'Acum e perioada de exersare mixtă.',
    simulari: 'Acum e perioada simulărilor săptămânale.',
    'puncte-slabe': 'Acum lucrăm doar pe punctele slabe.',
    formule: 'Acum repetăm formulele și greșelile tipice.',
};

const nf = new Intl.NumberFormat('ro-MD');

/** „1 zi", „19 zile", „229 de zile" — de la 20 în sus, româna cere „de". */
function days(n: number): string {
    if (n === 1) return '1 zi';
    const rest = n % 100;
    return rest === 0 || rest >= 20 ? `${nf.format(n)} de zile` : `${nf.format(n)} zile`;
}

/**
 * Disciplina de pe butonul mare: cea de profil, dacă are conținut; altfel cea
 * cu cele mai puține răspunsuri corecte. Panoul răspunde la „ce fac acum?",
 * nu doar arată o listă din care elevul trebuie să aleagă singur.
 */
const next = computed<DashboardSubject | null>(() => {
    const playable = props.subjects.filter((s) => s.is_playable);
    return (
        playable.find((s) => s.is_primary) ??
        [...playable].sort((a, b) => (a.accuracy ?? 0) - (b.accuracy ?? 0))[0] ??
        null
    );
});

const starting = ref(false);

function startPractice(subject: DashboardSubject) {
    starting.value = true;
    router.post(
        route('practice.start'),
        { subject: subject.slug, mode: 'practice' },
        { onFinish: () => (starting.value = false) },
    );
}

const dailyPct = computed(() =>
    game.value ? Math.min(100, Math.round((game.value.xp_today / game.value.daily_cap) * 100)) : 0,
);

// Disciplinele fără conținut rămân vizibile și selectabile (§2a), dar la coadă:
// sus stă ce se poate exersa azi.
const ordered = computed(() => [
    ...props.subjects.filter((s) => s.is_playable),
    ...props.subjects.filter((s) => !s.is_playable),
]);

function caption(subject: DashboardSubject): string {
    if (!subject.is_playable) return 'Conținut în pregătire';
    if (subject.rank) {
        return `locul ${nf.format(subject.rank.rank)} din ${nf.format(subject.rank.total)}, ${nf.format(subject.xp)} XP`;
    }
    return `${nf.format(subject.items_count)} întrebări, încă fără loc în clasament`;
}
</script>

<template>
    <Head title="Panou" />

    <AppLayout>
        <div class="space-y-8">
            <!-- Numărătoarea inversă până la examen: ea decide ce face platforma
                 cu tine. Cu 200 de zile rămase înveți; cu 10, repari găurile. -->
            <div v-if="exam" class="text-sm">
                <p v-if="exam.days_left !== null" class="font-semibold text-slate-200">
                    {{ days(exam.days_left) }} până la BAC
                </p>
                <p v-if="exam.phase" class="mt-0.5 text-slate-400">
                    {{ phaseLabels[exam.phase] ?? exam.phase }}
                </p>
            </div>

            <!-- Ținta de azi: capul zilnic de XP (§6) ca obiectiv, nu ca limită
                 ascunsă, plus un singur buton care pornește antrenamentul. -->
            <section class="rounded-3xl bg-hero p-5 text-on-hero sm:p-6">
                <div class="flex items-center justify-between gap-3 text-sm font-semibold">
                    <h2>Ținta de azi</h2>
                    <span v-if="game" class="inline-flex items-center gap-1.5 tabular-nums">
                        <Icon name="flame" />
                        {{ game.streak_days === 1 ? '1 zi la rând' : `${game.streak_days} zile la rând` }}
                    </span>
                </div>

                <template v-if="game">
                    <p class="mt-3 font-display text-5xl font-extrabold tracking-tight tabular-nums text-hero-accent">
                        {{ nf.format(game.xp_today) }}<span class="text-xl font-semibold text-on-hero"> / {{ nf.format(game.daily_cap) }} XP</span>
                    </p>
                    <div
                        class="mt-4 h-3.5 overflow-hidden rounded-full bg-on-hero/15"
                        role="progressbar"
                        :aria-valuenow="game.xp_today"
                        aria-valuemin="0"
                        :aria-valuemax="game.daily_cap"
                        aria-label="XP câștigat azi"
                    >
                        <div class="h-full min-w-3.5 rounded-full bg-hero-accent transition-all" :style="{ width: dailyPct + '%' }" />
                    </div>
                </template>

                <button
                    v-if="next"
                    type="button"
                    class="btn-primary mt-5 w-full"
                    :disabled="starting"
                    @click="startPractice(next)"
                >
                    {{ starting ? 'Se pregătește…' : `Continuă la ${next.name.toLowerCase()}` }}
                </button>
            </section>

            <section>
                <div class="mb-2 flex items-baseline justify-between">
                    <h2 class="font-display text-lg font-semibold">Disciplinele mele</h2>
                    <Link :href="route('subjects.edit')" class="text-sm font-semibold text-brand-400 hover:underline">
                        Modifică
                    </Link>
                </div>

                <ul class="divide-y divide-white/10">
                    <li v-for="subject in ordered" :key="subject.slug">
                        <Link
                            :href="route('subject.show', subject.slug)"
                            class="flex min-h-16 items-center gap-4 py-3 transition hover:opacity-80"
                            :class="subject.is_playable ? '' : 'opacity-60'"
                        >
                            <!-- Clasamentul pe disciplină: comparația care are sens.
                                 Un XP global ar amesteca matematica cu engleza. -->
                            <span
                                class="grid size-11 shrink-0 place-items-center rounded-xl font-display text-base font-extrabold"
                                :class="subject.rank ? 'bg-brand-500/15 text-brand-400' : 'bg-white/5 text-slate-400'"
                            >
                                <template v-if="subject.rank">{{ nf.format(subject.rank.rank) }}</template>
                                <Icon v-else :name="subject.icon ?? 'book'" />
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-x-2">
                                    <span class="font-semibold">{{ subject.name }}</span>
                                    <span
                                        v-if="subject.is_primary"
                                        class="rounded-full bg-brand-500/15 px-2 text-xs font-semibold text-brand-400"
                                    >profil</span>
                                </span>
                                <span class="mt-0.5 block text-sm text-slate-400">{{ caption(subject) }}</span>
                                <span v-if="subject.open_gaps" class="mt-0.5 block text-sm text-amber-400">
                                    <Icon name="circle-question-mark" class="mr-1" />{{ subject.open_gaps }}
                                    {{ subject.open_gaps === 1 ? 'lucru nelămurit' : 'lucruri nelămurite' }}
                                </span>
                            </span>

                            <span v-if="subject.accuracy !== null" class="shrink-0 text-right">
                                <span class="block font-bold tabular-nums">{{ Math.round(subject.accuracy * 100) }}%</span>
                                <span class="block text-xs text-slate-400">corecte</span>
                            </span>
                        </Link>
                    </li>
                </ul>
            </section>

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
