<?php

return [
    /*
     * Identitatea publică. Platforma se adresează în primul rând elevilor din
     * Republica Moldova, iar semnalele de mai jos o spun explicit motoarelor de
     * căutare: limbă ro-MD, zonă deservită MD, referință la ANCE.
     */
    'site_name' => 'eBac',
    'domain' => env('SEO_DOMAIN', 'ebac.md'),

    'locale' => 'ro_MD',
    'country' => 'MD',
    'region' => 'Republica Moldova',
    'city' => 'Chișinău',

    /*
     * Variantele lingvistice, pentru hreflang.
     *
     * `ru-MD` țintește segmentul alolingv — elevii din școlile cu predare în
     * rusă, care au probe distincte și aproape niciun material de pregătire.
     * Deocamdată doar declarat; paginile în rusă se adaugă odată cu conținutul.
     */
    'locales' => [
        'ro' => 'ro-MD',
        // 'ru' => 'ru-MD',
    ],

    'default_title' => 'Pregătire pentru BAC — Republica Moldova',
    'default_description' => 'Pregătire pentru examenul național de bacalaureat din Republica Moldova: exerciții rezolvate, indicii pas cu pas și antrenament interactiv, pe programele oficiale ANCE.',

    /*
     * Câți itemi rezolvați se publică pe o pagină de competență.
     *
     * Publicarea unui item pe web îl arde: oricine îl găsește cu răspuns cu tot.
     * De aceea publicăm un SUBSET deliberat din bazinul de antrenament, iar
     * itemii de holdout NU apar niciodată public — altfel simulările și
     * estimarea de notă își pierd validitatea.
     */
    'public_items_per_skill' => 4,
];
