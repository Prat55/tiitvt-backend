<?php


namespace App\Enums;

enum RolesEnum: string
{
    case Admin = 'admin';
    case Center = 'center';
    case Student = 'student';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Center => 'Center',
            self::Student => 'Student',
        };
    }
}
