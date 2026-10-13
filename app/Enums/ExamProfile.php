<?php

namespace App\Enums;

/** Profilurile de bacalaureat din Republica Moldova. */
enum ExamProfile: string
{
    case Real = 'real';
    case Umanist = 'umanist';
    case Arte = 'arte';
    case Sport = 'sport';

    public function label(): string
    {
        return match ($this) {
            self::Real => 'Real',
            self::Umanist => 'Umanist',
            self::Arte => 'Arte',
            self::Sport => 'Sport',
        };
    }
}
