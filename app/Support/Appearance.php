<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Tema și mărimea textului, citite pe SERVER.
 *
 * Preferințele stau în cookie, nu în localStorage, dintr-un motiv precis:
 * paginile publice sunt Blade fără niciun JavaScript (§14) — trebuie citite de
 * crawlere fără randare — deci nu au cum să citească localStorage. Un cookie e
 * singurul loc pe care îl văd și browserul, și serverul.
 *
 * Consecința bună: atributele se randează direct în `<html>`, la ambele layouturi.
 * Nu mai există niciun script care să comute tema după primul paint, deci nici
 * clipirea pe care acela o evita.
 *
 * Cookie-urile sunt exceptate de la criptare în `bootstrap/app.php`: le scrie
 * JavaScript-ul din Setări, deci nu pot fi criptate de Laravel.
 */
final class Appearance
{
    public const THEME_COOKIE = 'ebac_theme';

    public const FONT_COOKIE = 'ebac_font';

    /** Un an: e o preferință de afișare, nu o sesiune. */
    public const MAX_AGE = 31_536_000;

    private const THEMES = ['light', 'dark', 'system'];

    private const FONTS = ['sm', 'md', 'lg', 'xl'];

    /**
     * Tema aleasă, ca atribut pentru `<html>`.
     *
     * „system" ajunge ca atare în DOM: serverul nu are de unde ști setarea
     * sistemului de operare, iar CSS-ul o rezolvă singur cu `prefers-color-scheme`.
     * Așa funcționează și pe paginile publice, fără o linie de JavaScript.
     */
    public static function theme(?Request $request = null): string
    {
        return self::pick($request, self::THEME_COOKIE, self::THEMES, 'dark');
    }

    /**
     * Mărimea textului, sau null la cea normală.
     *
     * Null înseamnă că atributul lipsește cu totul din `<html>`: cazul obișnuit
     * nu plătește nici măcar un atribut în plus.
     */
    public static function font(?Request $request = null): ?string
    {
        $font = self::pick($request, self::FONT_COOKIE, self::FONTS, 'md');

        return $font === 'md' ? null : $font;
    }

    /**
     * Culoarea barei de adrese, pentru meta `theme-color`.
     *
     * La „system" rămâne cea întunecată: un singur meta nu poate exprima „depinde
     * de setarea telefonului", iar varianta cu două metauri și `media` e suportată
     * inegal. E singurul detaliu pe care „system" nu-l nimerește întotdeauna.
     */
    public static function themeColor(?Request $request = null): string
    {
        return self::theme($request) === 'light' ? '#f8fafc' : '#0f172a';
    }

    /**
     * @param  list<string>  $allowed
     */
    private static function pick(?Request $request, string $cookie, array $allowed, string $fallback): string
    {
        $value = ($request ?? request())->cookie($cookie);

        // Valoarea vine dintr-un cookie scris de client, deci e text arbitrar:
        // se acceptă doar din lista albă. Altfel ar ajunge direct într-un atribut.
        return is_string($value) && in_array($value, $allowed, true) ? $value : $fallback;
    }
}
