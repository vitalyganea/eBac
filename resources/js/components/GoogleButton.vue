<script setup lang="ts">
/**
 * „Continuă cu Google".
 *
 * E un `<a>` obișnuit, nu un `<Link>` Inertia: destinația e serverul nostru,
 * care redirecționează imediat spre accounts.google.com. O cerere XHR n-ar putea
 * urma redirectul spre alt domeniu (§14) — ar ajunge în modalul de eroare, exact
 * ca deconectarea.
 *
 * Sigla e desenată inline, ca restul iconițelor (§10i): un `<img>` de la Google
 * ar însemna o cerere către un domeniu terț pe pagina de autentificare, adică
 * exact ce încearcă §9 să evite pentru minori.
 */
defineProps<{ label?: string }>();
</script>

<template>
    <a
        :href="route('google.redirect')"
        class="btn w-full border border-white/15 bg-white/[0.03] text-slate-100 hover:bg-white/[0.07]"
    >
        <svg viewBox="0 0 24 24" width="1.15em" height="1.15em" aria-hidden="true" class="shrink-0">
            <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.4a5.5 5.5 0 0 1-2.4 3.6v3h3.9c2.3-2.1 3.6-5.2 3.6-8.8z"/>
            <path fill="#34A853" d="M12 24c3.2 0 6-1.1 8-2.9l-3.9-3a7.2 7.2 0 0 1-10.7-3.8h-4v3.1A12 12 0 0 0 12 24z"/>
            <path fill="#FBBC05" d="M5.3 14.3a7.1 7.1 0 0 1 0-4.6v-3.1h-4a12 12 0 0 0 0 10.8l4-3.1z"/>
            <path fill="#EA4335" d="M12 4.8c1.8 0 3.4.6 4.6 1.8l3.4-3.4A12 12 0 0 0 1.3 6.6l4 3.1A7.2 7.2 0 0 1 12 4.8z"/>
        </svg>
        {{ label ?? 'Continuă cu Google' }}
    </a>
</template>
