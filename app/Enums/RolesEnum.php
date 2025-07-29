<?php


namespace App\Enums;

enum RolesEnum: string
{
    case ADMIN = 'admin';
    case CENTER = 'center';
    case STUDENT = 'student';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::CENTER => 'Center',
            self::STUDENT => 'Student',
        };
    }
}
