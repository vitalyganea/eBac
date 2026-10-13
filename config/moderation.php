<?php

return [
    /*
     * Nume rezervate: impersonarea platformei sau a personalului.
     *
     * Un elev care se numește „moderator" sau „ebac_oficial" poate păcăli alți
     * elevi într-un clasament public. Se compară pe forma NORMALIZATĂ, deci
     * „adm1n" și „a-d-m-i-n" cad tot aici.
     */
    'reserved_exact' => [
        'mod', 'root', 'system', 'sistem', 'staff', 'echipa', 'suport', 'support',
        'ajutor', 'test', 'null', 'undefined', 'anonim', 'mec', 'ministerul',
        'profesor', 'teacher', 'official', 'ance',
    ],

    /*
     * Termeni care nu au voie să apară NICĂIERI în nume, nu doar ca nume întreg.
     *
     * Impersonarea arată în practică a compunere: „ebac_oficial", „admin_ebac",
     * „suport-ebac". Compararea exactă le ratează pe toate. Aici sunt doar
     * termenii pentru care riscul de fals-pozitiv e neglijabil — „mod" sau
     * „test" NU pot sta în lista asta, ar bloca „model" și „modest".
     */
    'reserved_contains' => [
        'ebac', 'admin', 'moderator', 'oficial',
    ],

    /*
     * Termeni interziși.
     *
     * ATENȚIE: lista de mai jos e un PUNCT DE PLECARE, nu una completă. Trebuie
     * extinsă de cineva care cunoaște argoul local — româna din Moldova și rusa.
     * Un filtru de cuvinte nu prinde niciodată tot; el reduce volumul care ajunge
     * la moderare umană, nu îl elimină.
     *
     * Comparația se face pe forma normalizată (fără diacritice, fără separatoare,
     * cu leetspeak tradus), deci nu e nevoie de variante ortografice aici.
     */
    'blocked' => [
        // Româna
        'pula', 'pizda', 'muie', 'futu', 'cacat', 'curva', 'jegos',
        // Rusa (transliterat și chirilic)
        'suka', 'blyat', 'blyad', 'pizdec', 'hui', 'сука', 'блять', 'хуй', 'пизда',
        // Engleza
        'fuck', 'shit', 'bitch', 'cunt', 'nigger', 'faggot', 'rape',
        // Simboluri de ură
        'hitler', 'nazi', 'swastika',
    ],

    /*
     * Câte raportări deschise pe un utilizator îl scot automat din afișarea
     * publică, până la revizuire.
     *
     * Nu e o pedeapsă, e o măsură de siguranță: dacă mai mulți elevi raportează
     * independent același avatar, probabilitatea să fie o problemă reală e mare,
     * iar el rămâne vizibil până când un om are timp să se uite.
     */
    'auto_hide_threshold' => 3,
];
