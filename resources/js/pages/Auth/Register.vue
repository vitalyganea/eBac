<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import GoogleButton from '@/components/GoogleButton.vue';

defineProps<{ profiles: { value: string; label: string }[] }>();

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
    profile: '',
});
</script>

<template>
    <Head title="Înregistrare" />

    <AppLayout>
        <div class="mx-auto max-w-sm space-y-6">
            <h1 class="text-2xl font-bold">Cont nou</h1>

            <form class="space-y-4" @submit.prevent="form.post(route('register'))">
                <div>
                    <label class="mb-1 block text-sm text-slate-400">Nume</label>
                    <input v-model="form.name" type="text" required
                        class="w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 outline-none focus:border-brand-500" />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-rose-400">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-400">Nume public (în clasamente)</label>
                    <input v-model="form.username" type="text" required
                        class="w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 outline-none focus:border-brand-500" />
                    <p class="mt-1 text-xs text-slate-500">
                        Doar acesta e vizibil altor elevi. Numele tău real nu apare niciodată public.
                    </p>
                    <p v-if="form.errors.username" class="mt-1 text-sm text-rose-400">{{ form.errors.username }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-400">
                        Profil <span class="text-slate-500">(opțional)</span>
                    </label>
                    <select v-model="form.profile"
                        class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 outline-none focus:border-brand-500">
                        <option value="">Nu știu / prefer să nu spun</option>
                        <option v-for="p in profiles" :key="p.value" :value="p.value">{{ p.label }}</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">
                        Ne ajută doar să-ți sugerăm disciplinele. Poți exersa oricare,
                        indiferent de profil.
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-400">Email</label>
                    <input v-model="form.email" type="email" required
                        class="w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 outline-none focus:border-brand-500" />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-rose-400">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-400">Parolă</label>
                    <input v-model="form.password" type="password" required
                        class="w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 outline-none focus:border-brand-500" />
                    <p v-if="form.errors.password" class="mt-1 text-sm text-rose-400">{{ form.errors.password }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-400">Confirmă parola</label>
                    <input v-model="form.password_confirmation" type="password" required
                        class="w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 outline-none focus:border-brand-500" />
                </div>

                <button type="submit" class="btn-primary w-full" :disabled="form.processing">
                    Creează cont
                </button>
            </form>

            <div class="flex items-center gap-3 text-xs text-slate-500">
                <span class="h-px flex-1 bg-white/10" />
                sau
                <span class="h-px flex-1 bg-white/10" />
            </div>

            <GoogleButton label="Înscrie-te cu Google" />

            <!-- Cine a ajuns aici din greșeală are deja cont: ieșirea spre logare
                 trebuie să fie o țintă de deget, nu un cuvânt într-o propoziție. -->
            <div class="space-y-2 border-t border-white/10 pt-6 text-center">
                <p class="text-sm text-slate-400">Ai deja cont?</p>
                <Link :href="route('login')" class="btn-ghost min-h-14 w-full text-base">
                    Intră în cont
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
