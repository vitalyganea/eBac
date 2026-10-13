<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import Avatar from '@/components/Avatar.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { FONT_SIZES, THEMES, useAppearance } from '@/composables/useAppearance';

// Aspectul se salvează local și se aplică pe loc — nu trece prin formularul de
// mai jos și nu așteaptă butonul „Salvează setările".
const { theme, fontSize, setTheme, setFontSize } = useAppearance();

interface Option { value: string; label: string }

const props = defineProps<{
    settings: {
        username: string;
        avatar: string | null;
        avatar_url: string | null;
        public_alias: string | null;
        username_pending: boolean;
        avatar_pending: boolean;
        profile: string | null;
        exam_target: string | null;
        timezone: string;
        leaderboard_opt_in: boolean;
        has_password: boolean;
        google_email: string | null;
        email: string;
    };
    profiles: Option[];
    seasons: Option[];
    timezones: string[];
}>();

const page = usePage();
const flash = computed(() => page.props.flash?.success);

const AVATARS = ['🦊', '🐼', '🦉', '🐝', '🦁', '🐙', '🦖', '🚀', '⚡', '🔥', '🌊', '🧠'];

// Încărcarea imaginii are formularul ei: se trimite imediat la alegerea
// fișierului, separat de restul setărilor.
const avatarForm = useForm<{ avatar: File | null }>({ avatar: null });
const fileInput = ref<HTMLInputElement | null>(null);

function pickFile() {
    fileInput.value?.click();
}

function onFileChosen(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;

    avatarForm.avatar = file;
    avatarForm.post(route('settings.avatar'), {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            avatarForm.reset();
            if (fileInput.value) fileInput.value.value = '';
        },
    });
}

function removeAvatar() {
    router.delete(route('settings.avatar.destroy'), { preserveScroll: true });
}

const form = useForm({
    username: props.settings.username,
    avatar: props.settings.avatar,
    profile: props.settings.profile ?? '',
    exam_target: props.settings.exam_target ?? '',
    timezone: props.settings.timezone,
    leaderboard_opt_in: props.settings.leaderboard_opt_in,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const deleteForm = useForm({ password: '', code: '' });
const codeSent = ref(false);

// Contul creat prin Google n-are parolă, deci ștergerea se confirmă cu un cod
// trimis pe email — altfel `Hash::check` ar întoarce mereu fals și contul n-ar
// putea fi șters niciodată.
function requestDeletionCode() {
    router.post(route('settings.destroy.code'), {}, {
        preserveScroll: true,
        onSuccess: () => (codeSent.value = true),
    });
}
const confirmingDelete = ref(false);

function save() {
    form.put(route('settings.update'), { preserveScroll: true });
}

function changePassword() {
    passwordForm.put(route('settings.password'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}

function deleteAccount() {
    deleteForm.delete(route('settings.destroy'), { preserveScroll: true });
}
</script>

<template>
    <Head title="Setări" />

    <AppLayout>
        <div class="space-y-6">
            <h1 class="text-2xl font-bold">Setări</h1>

            <p v-if="flash" class="rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm">
                {{ flash }}
            </p>

            <!-- Elevul trebuie să înțeleagă de ce ceilalți îl văd altfel. Fără
                 explicație, ar arăta ca o defecțiune. -->
            <div v-if="settings.username_pending || settings.avatar_pending"
                 class="rounded-xl border border-amber-500/30 bg-amber-500/[0.08] px-4 py-3 text-sm">
                <p class="font-semibold text-amber-400">În curs de verificare</p>
                <p class="mt-1 text-slate-300">
                    <template v-if="settings.username_pending">
                        Până la verificare, ceilalți elevi te văd ca
                        <strong class="text-slate-100">{{ settings.public_alias }}</strong>.
                    </template>
                    <template v-if="settings.avatar_pending">
                        Imaginea ta de profil e vizibilă deocamdată doar pentru tine.
                    </template>
                </p>
                <p class="mt-2 text-xs text-slate-400">
                    Poți folosi platforma normal — progresul și clasamentele funcționează.
                </p>
            </div>

            <!-- ================= IDENTITATE PUBLICĂ ================= -->
            <section class="card space-y-4">
                <div>
                    <h2 class="font-bold">Identitatea ta publică</h2>
                    <p class="mt-1 text-sm text-slate-400">
                        Doar acestea sunt vizibile altor elevi. Numele tău real și adresa de
                        email nu apar niciodată public.
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-400">Nume public</label>
                    <input v-model="form.username" type="text"
                        class="w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 outline-none focus:border-brand-500" />
                    <p v-if="form.errors.username" class="mt-1 text-sm text-rose-400">{{ form.errors.username }}</p>
                </div>

                <div>
                    <label class="mb-2 block text-sm text-slate-400">Imagine de profil</label>

                    <div class="flex items-center gap-4">
                        <span class="relative">
                            <Avatar :url="settings.avatar_url" :emoji="form.avatar" size="xl" :alt="form.username" />
                            <span v-if="settings.avatar_pending"
                                  class="absolute -bottom-1 -right-1 rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold text-on-warning"
                                  title="În curs de verificare">
                                ⏳
                            </span>
                        </span>

                        <div class="flex-1 space-y-2">
                            <input ref="fileInput" type="file" class="hidden"
                                accept="image/jpeg,image/png,image/webp" @change="onFileChosen" />

                            <button type="button" class="btn-ghost !py-2 !text-sm"
                                :disabled="avatarForm.processing" @click="pickFile">
                                {{ avatarForm.processing ? 'Se încarcă…' : 'Încarcă o imagine' }}
                            </button>

                            <button v-if="settings.avatar_url" type="button"
                                class="btn-ghost !py-2 !text-sm text-rose-400 hover:bg-rose-500/10"
                                @click="removeAvatar">
                                Șterge imaginea
                            </button>

                            <p class="text-xs text-slate-500">
                                JPEG, PNG sau WebP, maximum 2 MB. Imaginea se decupează la pătrat.
                            </p>
                        </div>
                    </div>

                    <div v-if="avatarForm.progress" class="mt-3 h-1 overflow-hidden rounded-full bg-white/5">
                        <div class="h-full bg-brand-500 transition-all"
                            :style="{ width: avatarForm.progress.percentage + '%' }" />
                    </div>

                    <p v-if="avatarForm.errors.avatar" class="mt-2 text-sm text-rose-400">
                        {{ avatarForm.errors.avatar }}
                    </p>
                </div>

                <div>
                    <label class="mb-2 block text-sm text-slate-400">
                        Sau alege un emoji
                        <span v-if="settings.avatar_url" class="text-slate-500">
                            — se folosește doar dacă ștergi imaginea
                        </span>
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="emoji in AVATARS"
                            :key="emoji"
                            type="button"
                            class="grid size-11 place-items-center rounded-xl border-2 text-xl transition"
                            :class="form.avatar === emoji
                                ? 'border-brand-500 bg-brand-500/15'
                                : 'border-white/10 bg-white/[0.03] hover:border-white/25'"
                            @click="form.avatar = form.avatar === emoji ? null : emoji"
                        >
                            {{ emoji }}
                        </button>
                    </div>
                </div>
            </section>

            <!-- ================= PROFIL BAC ================= -->
            <section class="card space-y-4">
                <div>
                    <h2 class="font-bold">Profilul tău de BAC</h2>
                    <p class="mt-1 text-sm text-slate-400">
                        Profilul doar îți sugerează ce discipline sunt obligatorii. Poți exersa
                        oricare disciplină, indiferent de el.
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-400">Profil</label>
                    <select v-model="form.profile"
                        class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 outline-none focus:border-brand-500">
                        <option value="">Nu știu / prefer să nu spun</option>
                        <option v-for="p in profiles" :key="p.value" :value="p.value">{{ p.label }}</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-400">Sesiunea de examen</label>
                    <select v-model="form.exam_target"
                        class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 outline-none focus:border-brand-500">
                        <option v-for="s in seasons" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">
                        Determină numărătoarea inversă și planul de pregătire.
                    </p>
                </div>

            </section>

            <!-- ================= CONFIDENȚIALITATE ================= -->
            <section class="card space-y-4">
                <div>
                    <h2 class="font-bold">Clasamente</h2>
                    <p class="mt-1 text-sm text-slate-400">
                        Ești inclus implicit. Te poți retrage oricând.
                    </p>
                </div>

                <label class="flex cursor-pointer items-start gap-3 rounded-xl border-2 p-4 transition"
                       :class="form.leaderboard_opt_in
                           ? 'border-brand-500 bg-brand-500/10'
                           : 'border-white/10 bg-white/[0.03]'">
                    <input v-model="form.leaderboard_opt_in" type="checkbox" class="mt-1 size-4 accent-current" />
                    <span class="flex-1 text-sm">
                        <span class="block font-medium">Apar în clasamente</span>
                        <!-- Textul descrie clasamentul care EXISTĂ: cel pe
                             disciplină. Varianta anterioară promitea și unul „pe
                             liceu", rămasă dinainte ca tabelul `schools` să fie
                             șters — nu cerem apartenența la o instituție (§9),
                             deci acela nu poate exista. -->
                        <span class="mt-1 block text-slate-400">
                            Vei apărea în clasamentul fiecărei discipline pe care o exersezi,
                            alături de toți elevii din țară, cu numele public
                            <strong class="text-slate-200">{{ form.username }}</strong> —
                            niciodată cu numele real și niciodată cu adresa de email.
                        </span>
                    </span>
                </label>
            </section>

            <!-- ================= PREFERINȚE ================= -->
            <section class="card space-y-4">
                <h2 class="font-bold">Fus orar</h2>

                <select v-model="form.timezone"
                    class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 outline-none focus:border-brand-500">
                    <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
                </select>
                <p class="text-xs text-slate-500">
                    Decide când se schimbă ziua pentru streak și pentru limita zilnică de XP.
                    Schimbă-l doar dacă ești în altă țară.
                </p>
            </section>

            <section class="card space-y-5">
                <div>
                    <h2 class="font-bold">Aspect</h2>
                    <p class="mt-1 text-sm text-slate-400">
                        Se aplică imediat și rămâne pe acest dispozitiv — nu ai nevoie
                        de butonul de salvare.
                    </p>
                </div>

                <div class="space-y-2">
                    <span class="block text-sm font-medium">Temă</span>

                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="option in THEMES"
                            :key="option.value"
                            type="button"
                            class="rounded-xl border-2 px-3 py-3 text-sm transition"
                            :class="theme === option.value
                                ? 'border-brand-500 bg-brand-500/10 font-semibold'
                                : 'border-white/10 bg-white/[0.03] hover:border-white/25'"
                            :aria-pressed="theme === option.value"
                            @click="setTheme(option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>

                    <p class="text-xs text-slate-500">
                        „Ca sistemul" urmează setarea telefonului, inclusiv trecerea
                        automată în modul de noapte.
                    </p>
                </div>

                <div class="space-y-2">
                    <span class="block text-sm font-medium">Mărimea textului</span>

                    <div class="grid grid-cols-4 gap-2">
                        <button
                            v-for="option in FONT_SIZES"
                            :key="option.value"
                            type="button"
                            class="rounded-xl border-2 px-2 py-3 transition"
                            :class="[
                                fontSize === option.value
                                    ? 'border-brand-500 bg-brand-500/10 font-semibold'
                                    : 'border-white/10 bg-white/[0.03] hover:border-white/25',
                                // Fiecare buton e scris la mărimea pe care o
                                // alege: diferența se vede înainte de a apăsa.
                                { sm: 'text-xs', md: 'text-sm', lg: 'text-base', xl: 'text-lg' }[option.value],
                            ]"
                            :aria-pressed="fontSize === option.value"
                            @click="setFontSize(option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>

                    <p class="text-xs text-slate-500">
                        Scalează toată interfața, nu doar literele — și butoanele devin
                        mai ușor de atins.
                    </p>
                </div>
            </section>

            <div class="sticky bottom-0 -mx-4 border-t border-white/10 bg-slate-950/90 px-4 py-4 backdrop-blur">
                <button type="button" class="btn-primary w-full" :disabled="form.processing" @click="save">
                    {{ form.processing ? 'Se salvează…' : 'Salvează setările' }}
                </button>
            </div>

            <!-- ================= PAROLĂ ================= -->
            <section class="card space-y-4">
                <h2 class="font-bold">
                    {{ settings.has_password ? 'Schimbă parola' : 'Setează o parolă' }}
                </h2>

                <p v-if="settings.google_email" class="text-sm text-slate-400">
                    Contul e legat de Google ({{ settings.google_email }}).
                    <template v-if="!settings.has_password">
                        O parolă îți dă o a doua cale de intrare, dacă pierzi accesul la Google.
                    </template>
                </p>

                <div v-if="settings.has_password">
                    <label class="mb-1 block text-sm text-slate-400">Parola actuală</label>
                    <input v-model="passwordForm.current_password" type="password"
                        class="w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 outline-none focus:border-brand-500" />
                    <p v-if="passwordForm.errors.current_password" class="mt-1 text-sm text-rose-400">
                        {{ passwordForm.errors.current_password }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-400">Parola nouă</label>
                    <input v-model="passwordForm.password" type="password"
                        class="w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 outline-none focus:border-brand-500" />
                    <p v-if="passwordForm.errors.password" class="mt-1 text-sm text-rose-400">
                        {{ passwordForm.errors.password }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-400">Confirmă parola nouă</label>
                    <input v-model="passwordForm.password_confirmation" type="password"
                        class="w-full rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 outline-none focus:border-brand-500" />
                </div>

                <button type="button" class="btn-ghost" :disabled="passwordForm.processing" @click="changePassword">
                    {{ settings.has_password ? 'Schimbă parola' : 'Setează parola' }}
                </button>
            </section>

            <!-- ================= ȘTERGERE CONT ================= -->
            <section class="card space-y-4 border-rose-500/30">
                <div>
                    <h2 class="font-bold text-rose-400">Șterge contul</h2>
                    <p class="mt-1 text-sm text-slate-400">
                        Se șterg definitiv contul, tot progresul, răspunsurile și statisticile.
                        Acțiunea nu poate fi anulată.
                    </p>
                </div>

                <button v-if="!confirmingDelete" type="button"
                    class="btn-ghost text-rose-400 hover:bg-rose-500/10"
                    @click="confirmingDelete = true">
                    Vreau să-mi șterg contul
                </button>

                <div v-else class="space-y-3">
                    <template v-if="settings.has_password">
                        <label class="block text-sm text-slate-400">
                            Confirmă cu parola ta
                        </label>
                        <input v-model="deleteForm.password" type="password"
                            class="w-full rounded-xl border border-rose-500/40 bg-white/[0.03] px-4 py-3 outline-none focus:border-rose-500" />
                        <p v-if="deleteForm.errors.password" class="text-sm text-rose-400">{{ deleteForm.errors.password }}</p>
                    </template>

                    <template v-else>
                        <p class="text-sm text-slate-400">
                            Contul tău nu are parolă, deci confirmarea se face cu un cod
                            trimis pe <strong class="text-slate-200">{{ settings.email }}</strong>.
                        </p>

                        <button v-if="!codeSent" type="button" class="btn-ghost w-full"
                            @click="requestDeletionCode">
                            Trimite-mi codul
                        </button>

                        <template v-else>
                            <label class="block text-sm text-slate-400">Codul din email</label>
                            <input v-model="deleteForm.code" type="text" inputmode="numeric"
                                autocomplete="one-time-code" maxlength="6" placeholder="000000"
                                class="w-full rounded-xl border border-rose-500/40 bg-white/[0.03] px-4 py-3 text-center text-lg tracking-[0.4em] outline-none focus:border-rose-500" />
                            <p v-if="deleteForm.errors.code" class="text-sm text-rose-400">{{ deleteForm.errors.code }}</p>
                            <button type="button" class="text-xs text-slate-500 underline"
                                @click="requestDeletionCode">
                                Trimite alt cod
                            </button>
                        </template>
                    </template>

                    <div class="flex gap-3">
                        <button type="button" class="btn-ghost flex-1" @click="confirmingDelete = false">
                            Renunț
                        </button>
                        <button type="button"
                            class="btn flex-1 bg-rose-600 text-on-accent hover:bg-rose-500"
                            :disabled="deleteForm.processing"
                            @click="deleteAccount">
                            Șterge definitiv
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
