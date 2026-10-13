<script setup lang="ts">
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';
import { watchSystemTheme } from '@/composables/useAppearance';
import type { AuthUser, Gamification } from '@/types';

// Ascultătorul stă în layout, nu în pagina de Setări: elevul care a ales „ca
// sistemul" trebuie să vadă trecerea în modul de noapte oriunde s-ar afla, nu
// doar dacă întâmplător are Setările deschise. Layout-ul persistă între vizitele
// Inertia, deci se înregistrează o singură dată.
const stopWatchingSystemTheme = watchSystemTheme();

const page = usePage();
const user = computed<AuthUser | null>(() => page.props.auth?.user ?? null);
const game = computed<Gamification | null>(() => page.props.gamification ?? null);
const openGaps = computed<number>(() => page.props.open_gaps ?? 0);

/** Progresul spre capul zilnic de XP — face vizibilă regula de anti-farming. */
const dailyPct = computed(() =>
    game.value ? Math.min(100, Math.round((game.value.xp_today / game.value.daily_cap) * 100)) : 0,
);

/**
 * Confirmarea deconectării.
 *
 * De când „ieși" a devenit o iconiță, ținta e de ~20px și stă lipită de setări.
 * Un clic greșit scotea elevul din cont fără nicio avertizare, iar reintrarea
 * cere din nou parola — pe telefon, în mijlocul unui antrenament, e enervant.
 *
 * Nu se pierde progres (attempts e append-only, §1), deci nu e o acțiune
 * distructivă — de aceea butonul de confirmare primește focusul: cine a apăsat
 * deliberat iese cu un singur Enter.
 */
const confirmingLogout = ref(false);
const loggingOut = ref(false);
const logoutTrigger = ref<HTMLButtonElement | null>(null);
const confirmButton = ref<HTMLButtonElement | null>(null);

watch(confirmingLogout, async (open) => {
    // Fundalul nu are voie să deruleze sub modal: pe telefon, altfel, degetul
    // mișcă pagina de dedesubt în loc să apese butonul.
    document.body.style.overflow = open ? 'hidden' : '';

    await nextTick();
    (open ? confirmButton.value : logoutTrigger.value)?.focus();
});

// Plasa de siguranță: dacă layout-ul dispare cu modalul deschis, pagina ar
// rămâne blocată fără nimic vizibil care s-o explice.
onUnmounted(() => {
    document.body.style.overflow = '';
    stopWatchingSystemTheme();
});

/**
 * Modalul rămâne deschis cât timp cererea e pe drum.
 *
 * Deconectarea nu se termină cu un răspuns Inertia, ci cu o navigare completă a
 * browserului (§14) — pe date mobile lente asta poate dura secunde bune. Dacă
 * am închide modalul imediat, elevul ar vedea antetul neschimbat și ar apăsa a
 * doua oară, crezând că primul clic s-a pierdut.
 */
function logout() {
    if (loggingOut.value) {
        return;
    }

    loggingOut.value = true;
    router.post(route('logout'), {}, {
        onFinish: () => {
            loggingOut.value = false;
        },
    });
}
</script>

<template>
    <div class="min-h-full">
        <header class="sticky top-0 z-20 border-b border-white/10 bg-slate-950/80 backdrop-blur">
            <div class="mx-auto flex max-w-3xl items-center gap-3 px-4 py-3">
                <!-- `<Link>` DOAR spre zona Inertia. Pentru vizitator, sigla duce în
                     zona publică Blade (§14), care nu răspunde cu anteturi Inertia:
                     un `<Link>` face XHR, primește HTML simplu și clientul deschide
                     modalul de eroare — o copie a paginii Blade într-un iframe
                     `sandbox`, deci cu origine opacă, al cărei `@vite/client` e apoi
                     respins de CORS. Același motiv a impus `Inertia::location()` la
                     `logout`: granița dintre cele două produse se trece numai
                     printr-o navigare reală a browserului.
                     Pe paginile de autentificare `user` e mereu null, deci asta era
                     calea nimerită la fiecare click pe siglă. -->
                <component
                    :is="user ? Link : 'a'"
                    :href="user ? route('dashboard') : route('public.home')"
                    class="font-black tracking-tight"
                >
                    e<span class="text-brand-400">Bac</span>
                </component>

<!-- XP-ul și nivelul sunt PE DISCIPLINĂ, deci nu au ce căuta aici:
                     un total global ar amesteca matematica cu engleza. În antet
                     rămân doar semnalele care chiar sunt globale — streak-ul și
                     progresul spre limita zilnică. -->
                <div v-if="game" class="ml-auto flex items-center gap-3 text-sm">
                    <span class="flex items-center gap-1 tabular-nums" title="Zile consecutive">
                        <Icon name="flame" class="text-amber-400" />{{ game.streak_days }}
                    </span>
                    <span class="flex items-center gap-1 tabular-nums text-slate-400" title="XP câștigat azi">
                        <Icon name="star" class="text-brand-400" />{{ game.xp_today }}<span class="text-slate-600">/{{ game.daily_cap }}</span>
                    </span>
                </div>

                <Link
                    v-if="user"
                    :href="route('gaps.index')"
                    class="relative text-lg leading-none text-slate-400 hover:text-slate-200"
                    title="Ce nu am înțeles"
                >
                    <Icon name="circle-question-mark" />
                    <span
                        v-if="openGaps"
                        class="absolute -right-2 -top-1 grid min-w-4 place-items-center rounded-full bg-amber-500 px-1 text-[10px] font-bold leading-4 text-on-warning"
                    >
                        {{ openGaps > 9 ? '9+' : openGaps }}
                    </span>
                </Link>

                <!-- Setări și deconectare, ca iconițe.
                     Amândouă au `label`, nu doar `title`: un titlu nu se citește
                     la atingere pe telefon, iar aici textul vizibil a dispărut cu
                     totul. Numele elevului rămâne în `title`, ca antetul să spună
                     în continuare cu ce cont ești conectat. -->
                <Link
                    v-if="user"
                    :href="route('settings.edit')"
                    class="text-lg leading-none text-slate-400 hover:text-slate-200"
                    :title="'Setări · ' + user.username"
                >
                    <Icon name="settings" label="Setări" />
                </Link>

                <button
                    v-if="user"
                    ref="logoutTrigger"
                    type="button"
                    class="text-lg leading-none text-slate-400 transition hover:text-slate-200"
                    title="Ieși din cont"
                    @click="confirmingLogout = true"
                >
                    <Icon name="log-out" label="Ieși din cont" />
                </button>
            </div>

            <!-- Bară de progres spre capul zilnic -->
            <div v-if="game" class="h-0.5 bg-white/5">
                <div class="h-full bg-brand-500 transition-all" :style="{ width: dailyPct + '%' }" />
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-6">
            <!-- Erorile trimise de server prin flash (un indiciu refuzat, o
                 simulare care nu se poate porni). Înainte nu le afișa nimeni:
                 butonul părea pur și simplu că nu face nimic. -->
            <p
                v-if="page.props.flash?.error"
                role="alert"
                class="mb-4 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm"
            >
                {{ page.props.flash.error }}
            </p>
            <slot />
        </main>

        <!-- Confirmarea deconectării.
             `@keydown.esc` stă pe overlay, nu pe document: overlay-ul primește
             focusul odată cu butonul din el, deci prinde tasta fără niciun
             ascultător global de curățat. Clicul pe fundal închide, dar `.self`
             e obligatoriu — altfel un clic pe „Renunț" ar trece prin bubbling și
             ar închide de două ori. -->
        <div
            v-if="confirmingLogout"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            aria-labelledby="logout-title"
            tabindex="-1"
            @click.self="loggingOut || (confirmingLogout = false)"
            @keydown.esc="loggingOut || (confirmingLogout = false)"
        >
            <div class="w-full max-w-xs rounded-2xl border border-white/10 bg-slate-900 p-5 shadow-xl">
                <h2 id="logout-title" class="text-base font-semibold">Ieși din cont?</h2>

                <p class="mt-2 text-sm text-slate-400">
                    Progresul tău rămâne salvat. Va trebui să te autentifici din nou.
                </p>

                <div class="mt-5 flex gap-3">
                    <button
                        type="button"
                        class="btn-ghost flex-1 px-3 py-2 text-sm"
                        :disabled="loggingOut"
                        @click="confirmingLogout = false"
                    >
                        Renunț
                    </button>
                    <button
                        ref="confirmButton"
                        type="button"
                        class="btn flex-1 bg-rose-600 px-3 py-2 text-sm text-on-accent hover:bg-rose-500"
                        :disabled="loggingOut"
                        @click="logout"
                    >
                        {{ loggingOut ? 'Se iese…' : 'Ieși' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
