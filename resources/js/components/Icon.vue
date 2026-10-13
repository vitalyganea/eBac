<script setup lang="ts">
import { computed } from 'vue';
import icons from '../../icons/icons.json';

/**
 * Iconiță SVG desenată în linie, din setul generat în `resources/icons`.
 *
 * De ce nu o bibliotecă de iconițe: publicul e pe Android ieftin cu date
 * limitate, iar bundle-ul paginii e deja la plafonul de 200 KB. Un pachet npm
 * ar aduce un runtime și un index de peste 2000 de forme pentru câteva zeci de
 * `path`-uri. Aici tot setul costă ~2,5 KB comprimat, iar aceleași forme sunt
 * citite și de Blade (`<x-ebac-icon>`), pentru paginile publice fără JavaScript.
 *
 * Dimensiunea e 1em, deci iconița crește cu textul din jur, ca emoji-ul pe care
 * îl înlocuiește: clasele de mărime existente rămân valabile.
 *
 * `v-html` primește exclusiv conținut din fișierul generat, niciodată text din
 * baza de date sau din request. Din DB vine doar NUMELE iconiței, folosit ca
 * cheie de căutare — un nume necunoscut nu randează nimic, nu injectează nimic.
 */

/**
 * Dă autocomplete pe numele existente, dar acceptă și un `string` oarecare:
 * numele disciplinelor și al realizărilor vin din baza de date, deci tipul nu
 * le poate cunoaște. Pentru ele, `Subject::iconName()` garantează pe server că
 * numele există în set.
 */
type IconName = keyof typeof icons | (string & {});

const props = withDefaults(defineProps<{
    name: IconName;
    /** Etichetă pentru cititoarele de ecran. Fără ea, iconița e decorativă. */
    label?: string;
    strokeWidth?: number;
}>(), { label: undefined, strokeWidth: 2 });

const body = computed<string>(() => (icons as Record<string, string>)[props.name] ?? '');
</script>

<template>
    <svg
        v-if="body"
        class="inline-block shrink-0 align-[-0.125em]"
        width="1em"
        height="1em"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        :stroke-width="strokeWidth"
        stroke-linecap="round"
        stroke-linejoin="round"
        :role="label ? 'img' : undefined"
        :aria-label="label"
        :aria-hidden="label ? undefined : true"
        :focusable="label ? undefined : 'false'"
        v-html="body"
    />
</template>
