<?php

namespace App\Enums;

enum ExamStatusEnum: string
{
    case SCHEDULED = 'scheduled';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Scheduled',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::SCHEDULED => 'badge badge-primary',
            self::CANCELLED => 'badge badge-error',
            self::COMPLETED => 'badge badge-success',
        };
    }
}
