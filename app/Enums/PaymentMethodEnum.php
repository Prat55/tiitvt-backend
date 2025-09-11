<?php

namespace App\Enums;

enum PaymentMethodEnum: string
{
    case CASH = 'cash';
    case CHEQUE = 'cheque';

    /**
     * Get the label for the payment method.
     */
    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::CHEQUE => 'Cheque',
        };
    }

    /**
     * Get all payment method options.
     */
    public static function options(): array
    {
        return [
            self::CASH->value => self::CASH->label(),
            self::CHEQUE->value => self::CHEQUE->label(),
        ];
    }
}
