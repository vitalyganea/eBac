<?php

namespace App\Support;

/**
 * Setul de iconițe al platformei, citit din `resources/icons/icons.json`.
 *
 * Același fișier alimentează și componentul Vue `Icon.vue`. Paginile publice
 * sunt Blade fără niciun JavaScript (vezi invariante §14), deci un set de
 * iconițe care ar trăi doar în bundle-ul Vue ar lăsa jumătate din produs
 * descoperită — și, în practică, ar duce la un al doilea set care ajunge să
 * difere de primul.
 *
 * Fișierul se generează cu `node bin/icons.mjs`; nu se editează manual.
 */
final class Icons
{
    /** @var array<string, string>|null */
    private static ?array $set = null;

    public static function path(): string
    {
        return resource_path('icons/icons.json');
    }

    /** @return array<string, string> */
    public static function all(): array
    {
        if (self::$set === null) {
            $json = @file_get_contents(self::path());

            self::$set = $json === false
                ? []
                : (json_decode($json, true) ?: []);
        }

        return self::$set;
    }

    /** Markup-ul din interiorul `<svg>` pentru o iconiță, sau null dacă nu există. */
    public static function body(string $name): ?string
    {
        return self::all()[$name] ?? null;
    }

    public static function has(string $name): bool
    {
        return isset(self::all()[$name]);
    }

    /** @return list<string> */
    public static function names(): array
    {
        return array_keys(self::all());
    }

    /** Doar pentru teste: golește memoizarea. */
    public static function flush(): void
    {
        self::$set = null;
    }
}
