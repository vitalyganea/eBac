<?php

namespace App\Enums;

enum SessionMode: string
{
    case Practice = 'practice';
    case Duel = 'duel';
    case Daily = 'daily';
    case Mock = 'mock';         // simulare BAC
    case Boss = 'boss';
    case Flashcards = 'flashcards';

    public function label(): string
    {
        return match ($this) {
            self::Practice => 'Antrenament',
            self::Duel => 'Duel',
            self::Daily => 'Provocarea zilei',
            self::Mock => 'Simulare BAC',
            self::Boss => 'Boss',
            self::Flashcards => 'Flashcards',
        };
    }

    /** Doar modurile competitive mișcă rating-ul. */
    public function isRanked(): bool
    {
        return in_array($this, [self::Duel, self::Daily], true);
    }

    /** Simulările consumă itemi de holdout, ținuți deoparte de antrenament. */
    public function usesExamHoldout(): bool
    {
        return $this === self::Mock;
    }

    /** În simulare și în duel nu se dau indicii și nu se arată explicații pe parcurs. */
    public function allowsHints(): bool
    {
        return in_array($this, [self::Practice, self::Flashcards, self::Boss], true);
    }
}
