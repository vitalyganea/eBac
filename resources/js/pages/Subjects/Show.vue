<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, Deferred } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Avatar from '@/components/Avatar.vue';
import Icon from '@/components/Icon.vue';
import ReportButton from '@/components/ReportButton.vue';

interface BoardEntry {
    rank: number;
    username: string;
    avatar: string | null;
    avatar_url: string | null;
    xp: number;
    level: number;
    is_me: boolean;
}

const props = defineProps<{
    subject: { slug: string; name: string; icon: string | null };
    progress: { level: number; xp: number; current: number; needed: number; percent: number };
    stats: {
        attempts: number;
        correct: number;
        accuracy: number | null;
        rating: number;
        last_activity_at: string | null;
    };
    rank: { rank: number; total: number; xp: number } | null;
    open_gaps: number;
    sections: {
        course: { available: boolean; lessons_count: number };
        tests: { available: boolean; items_count: number };
        mock: {
            available: boolean;
            reason: string | null;
            plan: {
                session: string;
                profile_label: string;
                items: number;
                minutes: number;
                auto_points: number;
                total_points: number;
            } | null;
            history: { uuid: string; score: number; auto_points: number; completed_at: string }[];
        };
    };
    board: { entries: BoardEntry[]; me: BoardEntry | null; total: number } | null;
}>();

const nf = new Intl.NumberFormat('ro-MD');

function startPractice() {
    router.post(route('practice.start'), { subject: props.subject.slug, mode: 'practice' });
}

const startingMock = ref(false);

function startMock() {
    startingMock.value = true;
    router.post(route('mock.start'), { subject: props.subject.slug }, {
        onFinish: () => (startingMock.value = false),
    });
}

const dateFormat = new Intl.DateTimeFormat('ro-MD', { day: 'numeric', month: 'short' });
</script>

<template>
    <Head :title="subject.name" />

    <AppLayout>
        <div class="space-y-6">
            <Link :href="route('dashboard')" class="inline-block text-sm text-slate-400 hover:text-slate-200">
                ← Panou
            </Link>

            <!-- ============ NIVELUL LA ACEASTĂ DISCIPLINĂ ============ -->
            <header class="card">
                <div class="flex items-center gap-4">
                    <Icon :name="subject.icon ?? 'book'" class="text-4xl text-slate-300" :stroke-width="1.75" />

                    <div class="min-w-0 flex-1">
                        <h1 class="truncate text-xl font-bold">{{ subject.name }}</h1>
                        <p class="text-sm text-slate-400 tabular-nums">
                            {{ nf.format(progress.xp) }} XP la această disciplină
                        </p>
                    </div>

                    <div class="shrink-0 text-right">
                        <p class="text-3xl font-black tabular-nums text-brand-400">
                            {{ progress.level }}
                        </p>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">nivel</p>
                    </div>
                </div>

                <div class="mt-4 space-y-1">
                    <div class="h-2 overflow-hidden rounded-full bg-white/5">
                        <div class="h-full rounded-full bg-brand-500 transition-all duration-500"
                            :style="{ width: progress.percent + '%' }" />
                    </div>
                    <p class="text-xs text-slate-500 tabular-nums">
                        {{ nf.format(progress.current) }} / {{ nf.format(progress.needed) }} XP
                        până la nivelul {{ progress.level + 1 }}
                    </p>
                </div>

                <div v-if="rank" class="mt-4 flex items-center gap-4 border-t border-white/10 pt-4 text-sm">
                    <span>
                        <span class="font-black tabular-nums text-brand-400">#{{ nf.format(rank.rank) }}</span>
                        <span class="text-slate-400"> din {{ nf.format(rank.total) }}</span>
                    </span>
                    <span v-if="stats.accuracy !== null" class="text-slate-400 tabular-nums">
                        {{ Math.round(stats.accuracy * 100) }}% corecte
                    </span>
                    <span class="ml-auto text-slate-500 tabular-nums">
                        {{ nf.format(stats.attempts) }} răspunsuri
                    </span>
                </div>
            </header>

            <!-- Nelămuririle nu au voie să se adune nevăzute: apar pe drumul
                 obișnuit al elevului, nu doar într-o pagină separată. -->
            <Link
                v-if="open_gaps"
                :href="route('gaps.index')"
                class="card flex items-center gap-3 border-amber-500/25 transition hover:border-amber-500/40"
            >
                <Icon name="circle-question-mark" class="text-xl text-amber-400" />
                <span class="flex-1 text-sm">
                    <strong class="text-amber-400">{{ open_gaps }}</strong>
                    {{ open_gaps === 1 ? 'lucru nelămurit' : 'lucruri nelămurite' }}
                    la {{ subject.name }}
                </span>
                <span class="text-xs text-slate-500">vezi lista →</span>
            </Link>

            <!-- ============ CE POȚI FACE AICI ============ -->
            <div class="grid gap-3 sm:grid-cols-2">
<component
                    :is="sections.course.available ? 'a' : 'div'"
                    :href="sections.course.available ? route('course.index', subject.slug) : undefined"
                    class="card block text-left transition"
                    :class="sections.course.available
                        ? 'hover:border-white/25 active:scale-[0.99]'
                        : 'opacity-60'"
                >
                    <p class="text-2xl text-slate-300"><Icon name="book-open" :stroke-width="1.75" /></p>
                    <p class="mt-2 font-semibold">Curs</p>
                    <p class="mt-1 text-sm text-slate-400">
                        Teoria pe competențe, cu grafice și exemple rezolvate.
                    </p>
                    <p class="mt-3 text-xs" :class="sections.course.available ? 'text-brand-400' : 'text-amber-400/80'">
                        {{ sections.course.available
                            ? sections.course.lessons_count + ' lecții disponibile'
                            : 'în pregătire' }}
                    </p>
                </component>

                <button
                    type="button"
                    class="card text-left transition"
                    :class="sections.tests.available
                        ? 'hover:border-white/25 active:scale-[0.99]'
                        : 'cursor-not-allowed opacity-60'"
                    :disabled="!sections.tests.available"
                    @click="sections.tests.available && startPractice()"
                >
                    <p class="text-2xl text-slate-300"><Icon name="target" :stroke-width="1.75" /></p>
                    <p class="mt-2 font-semibold">Teste</p>
                    <p class="mt-1 text-sm text-slate-400">
                        Întrebări calibrate pe nivelul tău, cu indicii pas cu pas.
                    </p>
                    <p class="mt-3 text-xs" :class="sections.tests.available ? 'text-brand-400' : 'text-amber-400/80'">
                        {{ sections.tests.available
                            ? nf.format(sections.tests.items_count) + ' întrebări disponibile'
                            : 'conținut în pregătire' }}
                    </p>
                </button>
            </div>

            <!-- ============ SIMULARE BAC ============ -->
            <!-- Vizibilă și când nu se poate porni, cu motivul real (§2a). -->
            <section class="card">
                <div class="flex items-start gap-4">
                    <p class="text-2xl text-slate-300"><Icon name="clipboard-check" :stroke-width="1.75" /></p>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold">Simulare BAC</p>
                        <p class="mt-1 text-sm text-slate-400">
                            Structura examenului, cu timp limitat și întrebări pe care nu le-ai văzut la antrenament.
                            Corectarea o vezi la final.
                        </p>

                        <p v-if="sections.mock.plan?.items" class="mt-3 text-xs text-slate-400 tabular-nums">
                            {{ sections.mock.plan.items }} întrebări · {{ sections.mock.plan.minutes }} min
                            · acoperă {{ sections.mock.plan.auto_points }} din {{ sections.mock.plan.total_points }} de puncte
                            <span class="text-slate-500">(profil {{ sections.mock.plan.profile_label }})</span>
                        </p>
                        <p v-if="!sections.mock.available" class="mt-3 text-xs text-amber-400/80">
                            {{ sections.mock.reason ?? 'în pregătire' }}
                        </p>
                    </div>
                </div>

                <button
                    v-if="sections.mock.available"
                    type="button"
                    class="btn-primary mt-4 w-full"
                    :disabled="startingMock"
                    @click="startMock"
                >
                    {{ startingMock ? 'Se pregătește…' : 'Începe simularea' }}
                </button>

                <ul v-if="sections.mock.history.length" class="mt-4 space-y-1 border-t border-white/10 pt-3 text-sm">
                    <li v-for="entry in sections.mock.history" :key="entry.uuid">
                        <Link
                            :href="route('practice.play', entry.uuid)"
                            class="flex items-center justify-between gap-3 rounded-lg px-2 py-1.5 hover:bg-white/5"
                        >
                            <span class="text-slate-400">{{ dateFormat.format(new Date(entry.completed_at)) }}</span>
                            <span class="tabular-nums">
                                <strong>{{ nf.format(entry.score) }}</strong>
                                <span class="text-slate-400"> / {{ entry.auto_points }} p</span>
                            </span>
                        </Link>
                    </li>
                </ul>
            </section>

            <!-- ============ CLASAMENT ============ -->
            <section>
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">
                    Clasament — {{ subject.name }}
                </h2>

                <Deferred data="board">
                    <template #fallback>
                        <div class="card animate-pulse text-sm text-slate-500">Se încarcă clasamentul…</div>
                    </template>

                    <div v-if="board && board.entries.length" class="card !p-0">
                        <ol>
                            <li
                                v-for="entry in board.entries"
                                :key="entry.rank"
                                class="flex items-center gap-3 border-b border-white/5 px-4 py-3 last:border-0"
                                :class="entry.is_me ? 'bg-brand-500/10' : ''"
                            >
                                <span class="w-8 shrink-0 text-sm font-bold tabular-nums"
                                      :class="entry.rank <= 3 ? 'text-brand-400' : 'text-slate-500'">
                                    {{ entry.rank }}
                                </span>
                                <Avatar :url="entry.avatar_url" :emoji="entry.avatar" size="sm" :alt="entry.username" />
                                <span class="min-w-0 flex-1 truncate text-sm"
                                      :class="entry.is_me ? 'font-bold' : ''">
                                    {{ entry.username }}<span v-if="entry.is_me" class="text-brand-400"> · tu</span>
                                </span>
                                <span class="shrink-0 rounded-md bg-white/5 px-2 py-0.5 text-xs tabular-nums">
                                    Nv. {{ entry.level }}
                                </span>
                                <span class="w-16 shrink-0 text-right text-sm tabular-nums text-slate-400">
                                    {{ nf.format(entry.xp) }}
                                </span>

                                <!-- Discret intenționat: un buton vizibil de
                                     raportare lângă fiecare nume invită la abuz
                                     într-un clasament competitiv. -->
                                <ReportButton
                                    v-if="!entry.is_me"
                                    :username="entry.username"
                                    :subject="entry.avatar_url ? 'avatar' : 'username'" />
                                <span v-else class="w-6" />
                            </li>

                            <!-- Rândul propriu, dacă e în afara primilor. Fără el, un
                                 elev de pe locul 340 vede doar necunoscuți. -->
                            <li v-if="board.me"
                                class="flex items-center gap-3 border-t border-white/10 bg-brand-500/10 px-4 py-3">
                                <span class="w-8 shrink-0 text-sm font-bold tabular-nums text-brand-400">
                                    {{ board.me.rank }}
                                </span>
                                <Avatar :url="board.me.avatar_url" :emoji="board.me.avatar" size="sm" :alt="board.me.username" />
                                <span class="min-w-0 flex-1 truncate text-sm font-bold">
                                    {{ board.me.username }}<span class="text-brand-400"> · tu</span>
                                </span>
                                <span class="shrink-0 rounded-md bg-white/5 px-2 py-0.5 text-xs tabular-nums">
                                    Nv. {{ board.me.level }}
                                </span>
                                <span class="w-20 shrink-0 text-right text-sm tabular-nums text-slate-400">
                                    {{ nf.format(board.me.xp) }}
                                </span>
                            </li>
                        </ol>
                    </div>

                    <p v-else class="card text-sm text-slate-400">
                        Nimeni nu are încă XP la această disciplină. Rezolvă primul test și ești pe locul 1.
                    </p>
                </Deferred>
            </section>
        </div>
    </AppLayout>
</template>
