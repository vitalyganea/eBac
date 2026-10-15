<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import GoogleButton from '@/components/GoogleButton.vue';

const form = useForm({ email: '', password: '', remember: true });
</script>

<template>
    <Head title="Intră în cont" />

    <AppLayout>
        <div class="mx-auto max-w-sm space-y-6">
            <h1 class="text-2xl font-bold">Intră în cont</h1>

            <form class="space-y-4" @submit.prevent="form.post(route('login'))">
                <div>
                    <label class="mb-1 block text-sm text-slate-400">Email</label>
                    <input v-model="form.email" type="email" required autofocus
                        class="w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 outline-none focus:border-brand-500" />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-rose-400">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-400">Parolă</label>
                    <input v-model="form.password" type="password" required
                        class="w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 outline-none focus:border-brand-500" />
                </div>

                <button type="submit" class="btn-primary w-full" :disabled="form.processing">
                    Intră
                </button>
            </form>

            <div class="flex items-center gap-3 text-xs text-slate-500">
                <span class="h-px flex-1 bg-white/10" />
                sau
                <span class="h-px flex-1 bg-white/10" />
            </div>

            <GoogleButton />

            <!-- Pereche cu „Intră în cont" de pe înregistrare: o țintă de deget,
                 nu un cuvânt într-o propoziție. -->
            <div class="space-y-2 border-t border-white/10 pt-6 text-center">
                <p class="text-sm text-slate-400">Nu ai cont?</p>
                <Link :href="route('register')" class="btn-ghost min-h-14 w-full text-base">
                    Creează cont
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
