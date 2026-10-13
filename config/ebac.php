<?php

return [
    /*
     * Versiunea minimă de aplicație mobilă acceptată de API.
     * Vezi App\Http\Middleware\EnsureMinimumAppVersion.
     */
    'min_mobile_version' => env('APP_MIN_MOBILE_VERSION'),

    /*
     * Ținta implicită de examen pentru utilizatorii noi.
     * Sesiunea 2026 s-a încheiat pe 19 iunie 2026; cohorta curentă dă BAC-ul în 2027.
     */
    'default_exam_target' => env('EBAC_DEFAULT_EXAM_TARGET', 'bac-2027'),

    /*
     * Fusul implicit al elevului.
     *
     * Aplicația și baza de date rulează în UTC (config/app.php) — corect, pentru
     * că `answered_at` e un moment absolut. Dar granițele de ZI (streak, capul
     * zilnic de XP, provocarea zilei) se calculează în fusul ELEVULUI.
     *
     * Fallback-ul NU trebuie să fie UTC: Chișinăul e UTC+2/+3, deci un elev care
     * răspunde la 01:00 local ar fi numărat în ziua precedentă și și-ar pierde
     * streak-ul pe nedrept.
     */
    'default_timezone' => env('EBAC_DEFAULT_TIMEZONE', 'Europe/Chisinau'),

    /*
     * Numărul implicit de itemi pe sesiune, per mod de joc.
     */
    'session_sizes' => [
        'practice' => 10,
        'duel' => 10,
        'daily' => 5,
        'boss' => 10,
        'mock' => 10,
    ],
];
