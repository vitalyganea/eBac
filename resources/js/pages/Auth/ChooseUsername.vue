<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

/**
 * Ultimul pas al înscrierii cu Google.
 *
 * Numele NU vine de la furnizor, deși Google îl are: ar fi numele real, iar în
 * clasamente apare doar `username` (§9). În plus, ar ocoli filtrul din
 * `AcceptableUsername`, care trebuie să fie prezent în TOATE locurile unde se
 * poate seta un nume (§10g).
 */
defineProps<{ suggested: string | null }>();

const form = useForm({ username: '' });
</script>

<template>
    <Head title="Alege-ți numele" />

    <AppLayout>
        <div class="mx-auto max-w-sm space-y-6">
            <div>
                <h1 class="text-2xl font-bold">Alege-ți numele</h1>
                <p class="mt-2 text-sm text-slate-400">
                    Așa te vor vedea ceilalți în clasamente. Numele tău real nu apare
                    niciodată — nici aici, nici altundeva.
                </p>
            </div>

            <form class="space-y-4" @submit.prevent="form.post(route('auth.username.store'))">
                <div>
                    <label class="mb-1 block text-sm text-slate-400">Nume public</label>
                    <input v-model="form.username" type="text" required autofocus
                        minlength="3" maxlength="32" :placeholder="suggested ?? 'ion_p'"
                        class="w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 outline-none focus:border-brand-500" />
                    <p v-if="form.errors.username" class="mt-1 text-sm text-rose-400">
                        {{ form.errors.username }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        Litere, cifre, liniuță și underscore. Între 3 și 32 de caractere.
                    </p>
                </div>

                <button type="submit" class="btn-primary w-full" :disabled="form.processing">
                    {{ form.processing ? 'Se salvează…' : 'Continuă' }}
                </button>
            </form>

            <p class="text-center text-xs text-slate-500">
                Până la aprobarea numelui, ceilalți te văd ca
                <strong class="text-slate-400">{{ suggested }}</strong>.
            </p>
        </div>
    </AppLayout>
</template>
