<?php

namespace App\Enums;

/**
 * Niciun item generat nu ajunge la elevi nevalidat: Draft → Review → Published.
 */
enum ItemStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Published = 'published';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Ciornă',
            self::Review => 'În validare',
            self::Published => 'Publicat',
            self::Retired => 'Retras',
        };
    }

    public function isServable(): bool
    {
        return $this === self::Published;
    }
}
