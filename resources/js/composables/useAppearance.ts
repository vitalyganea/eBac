import { ref } from 'vue';

/**
 * Tema și mărimea textului.
 *
 * Preferințele stau în COOKIE, nu în localStorage, dintr-un motiv care nu e
 * negociabil: paginile publice sunt Blade fără niciun JavaScript (§14) — trebuie
 * citite de crawlere fără randare — deci n-au cum să citească localStorage.
 * Cookie-ul e singurul loc pe care îl văd și browserul, și serverul.
 *
 * De aici decurge tot restul:
 *
 *  - serverul randează `data-theme` și `data-font` direct în `<html>`, la ambele
 *    layouturi (`App\Support\Appearance`). Nu mai există niciun script care să
 *    comute tema după primul paint, deci nici clipirea pe care acela o evita;
 *  - starea inițială se citește din DOM, nu din cookie: atributul e deja acolo,
 *    pus de server, deci nu mai trebuie parsat nimic;
 *  - „system" ajunge ca atare în DOM, iar CSS-ul îl rezolvă cu
 *    `prefers-color-scheme`. Funcționează identic pe paginile publice.
 *
 * Compromisul asumat: preferința e legată de browser, nu de cont. Dacă ajunge să
 * conteze pe mai multe dispozitive, se mută într-o coloană din `users` și cookie-ul
 * rămâne doar oglinda ei pentru randarea de pe server.
 */

export type Theme = 'dark' | 'light' | 'system';
export type FontSize = 'sm' | 'md' | 'lg' | 'xl';

/** Numele trebuie să rămână identice cu cele din App\Support\Appearance. */
const THEME_COOKIE = 'ebac_theme';
const FONT_COOKIE = 'ebac_font';

/** Un an: e o preferință de afișare, nu o sesiune. */
const MAX_AGE = 31_536_000;

export const THEMES: { value: Theme; label: string }[] = [
    { value: 'light', label: 'Luminoasă' },
    { value: 'dark', label: 'Întunecată' },
    { value: 'system', label: 'Ca sistemul' },
];

export const FONT_SIZES: { value: FontSize; label: string }[] = [
    { value: 'sm', label: 'Mic' },
    { value: 'md', label: 'Normal' },
    { value: 'lg', label: 'Mare' },
    { value: 'xl', label: 'Foarte mare' },
];

const root = document.documentElement;

// Starea inițială vine din atributele randate de server, nu dintr-o a doua
// sursă care ar putea să difere de ce se vede pe ecran.
const theme = ref<Theme>((root.dataset.theme as Theme) || 'dark');
const fontSize = ref<FontSize>((root.dataset.font as FontSize) || 'md');

function writeCookie(name: string, value: string): void {
    const secure = location.protocol === 'https:' ? '; Secure' : '';

    document.cookie = `${name}=${value}; path=/; max-age=${MAX_AGE}; SameSite=Lax${secure}`;
}

/**
 * Culoarea barei de adrese de pe mobil.
 *
 * Singurul loc unde „system" nu se poate rezolva în CSS: un `<meta>` nu are
 * variante condiționate de temă care să fie suportate peste tot.
 */
function syncThemeColor(): void {
    const light =
        theme.value === 'light' ||
        (theme.value === 'system' && window.matchMedia('(prefers-color-scheme: light)').matches);

    document
        .querySelector('meta[name="theme-color"]')
        ?.setAttribute('content', light ? '#f8fafc' : '#0f172a');
}

export function setTheme(value: Theme): void {
    theme.value = value;
    writeCookie(THEME_COOKIE, value);

    // Atributul păstrează „system" ca atare: rezolvarea e treaba CSS-ului.
    root.dataset.theme = value;
    syncThemeColor();
}

export function setFontSize(value: FontSize): void {
    fontSize.value = value;
    writeCookie(FONT_COOKIE, value);

    // La mărimea normală atributul lipsește cu totul, ca pe server.
    if (value === 'md') {
        delete root.dataset.font;
    } else {
        root.dataset.font = value;
    }
}

/**
 * Cât timp elevul e pe „Ca sistemul", paleta se schimbă singură din CSS. Rămâne
 * de actualizat doar bara de adrese, care nu poate fi condiționată în CSS.
 */
export function watchSystemTheme(): () => void {
    const query = window.matchMedia('(prefers-color-scheme: light)');
    const onChange = () => theme.value === 'system' && syncThemeColor();

    query.addEventListener('change', onChange);

    return () => query.removeEventListener('change', onChange);
}

export function useAppearance() {
    return { theme, fontSize, setTheme, setFontSize };
}
