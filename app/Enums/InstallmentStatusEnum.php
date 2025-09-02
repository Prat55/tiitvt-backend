<?php

namespace App\Enums;

enum InstallmentStatusEnum: string
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Partial => 'Partial',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Paid => 'badge-success',
            self::Partial => 'badge-info',
            self::Overdue => 'badge-error',
            self::Pending => 'badge-warning',
        };
    }

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isPartial(): bool
    {
        return $this === self::Partial;
    }

    public function isOverdue(): bool
    {
        return $this === self::Overdue;
    }

    /**
     * Get all available statuses.
     */
    public static function all(): array
    {
        return [
            self::Pending,
            self::Partial,
            self::Paid,
            self::Overdue,
        ];
    }

    /**
     * Get all status values as strings.
     */
    public static function values(): array
    {
        return array_map(fn($status) => $status->value, self::all());
    }

    /**
     * Get all status labels.
     */
    public static function labels(): array
    {
        return array_map(fn($status) => $status->label(), self::all());
    }
}
