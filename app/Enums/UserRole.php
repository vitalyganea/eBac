<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Teacher = 'teacher';
    case Editor = 'editor';     // validează itemi
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Elev',
            self::Teacher => 'Profesor',
            self::Editor => 'Editor',
            self::Admin => 'Administrator',
        };
    }

    public function canAccessAdmin(): bool
    {
        return in_array($this, [self::Editor, self::Admin], true);
    }
}
