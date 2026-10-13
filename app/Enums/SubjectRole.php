<?php

namespace App\Enums;

/** Rolul unei discipline într-un profil de bacalaureat. */
enum SubjectRole: string
{
    case Obligatoriu = 'obligatoriu';   // pentru toate profilurile
    case Profil = 'profil';             // disciplina de profil
    case LaAlegere = 'la_alegere';

    public function label(): string
    {
        return match ($this) {
            self::Obligatoriu => 'Obligatoriu',
            self::Profil => 'Disciplină de profil',
            self::LaAlegere => 'La alegere',
        };
    }
}
