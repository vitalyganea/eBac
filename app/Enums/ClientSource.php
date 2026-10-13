<?php

namespace App\Enums;

enum ClientSource: string
{
    case Web = 'web';
    case Ios = 'ios';
    case Android = 'android';

    /**
     * Doar clientul web trimite răspunsuri strict online, cu timp măsurat pe
     * server la fiecare item. Clienții mobili pot trimite loturi offline, iar
     * acele răspunsuri nu intră în rating-ul competitiv.
     */
    public function isMobile(): bool
    {
        return $this !== self::Web;
    }
}
