<?php

namespace App\Enums;

enum InstallmentStatusEnum: string
{
    case Pending = 'pending';
    case Paid = 'paid';

    /**
     * Get the label of the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Paid => 'Paid',
        };
    }

    /**
     * Get the badge class.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'badge-warning',
            self::Paid => 'badge-success',
        };
    }

    /**
     * Check if the status is pending.
     */
    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    /**
     * Check if the status is paid.
     */
    public function isPaid(): bool
    {
        return $this === self::Paid;
    }
}
