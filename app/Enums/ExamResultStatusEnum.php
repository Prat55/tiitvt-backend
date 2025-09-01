<?php

namespace App\Enums;

enum ExamResultStatusEnum: string
{
    case Passed = 'passed';
    case Failed = 'failed';
    case NotDeclared = 'not_declared';

    public function label(): string
    {
        return match ($this) {
            self::Passed => 'Passed',
            self::Failed => 'Failed',
            self::NotDeclared => 'Not Declared',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Passed => 'badge-success',
            self::Failed => 'badge-error',
            self::NotDeclared => 'badge-error',
        };
    }
}
