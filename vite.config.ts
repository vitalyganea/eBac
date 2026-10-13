import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
        }),
        vue({
            template: { transformAssetUrls: { base: null, includeAbsolute: false } },
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: { '@': '/resources/js' },
    },
    build: {
        // Publicul e pe Android ieftin, cu date mobile limitate. Un bundle de 2 MB
        // pe 3G rural = utilizator pierdut. Ținta e sub 200 KB pe prima încărcare.
        chunkSizeWarningLimit: 250,
    },
    server: {
        // Vite urmărește recursiv tot ce e sub rădăcina proiectului. Lista lui
        // implicită de excluderi acoperă node_modules și .git, dar nu `vendor/`
        // — 18.000 de fișiere, din care 3.500 doar traducerile Filament. Pe o
        // mașină cu `fs.inotify.max_user_watches` implicit, watch-urile se
        // epuizează și dev server-ul cade cu ENOSPC în plin `composer dev`.
        //
        // Nimic din vendor nu are nevoie de HMR: laravel-vite-plugin reîncarcă
        // din resources/views, routes, app și lang. Lista de aici se ADAUGĂ la
        // cea implicită a lui Vite, nu o înlocuiește.
        watch: {
            ignored: ['**/vendor/**', '**/storage/**', '**/public/build/**'],
        },
    },
});
