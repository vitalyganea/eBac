import type { route as routeFn } from 'ziggy-js';

/**
 * `route()` e injectat global de directiva Blade @routes plus plugin-ul ZiggyVue.
 * Declarația de aici îl face vizibil atât în `<script setup>`, cât și în template.
 */
declare global {
    const route: typeof routeFn;
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof routeFn;
    }
}
