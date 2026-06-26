<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CreditCard = 'credit_card';
    case PayPal = 'paypal';
    // case BankTransfer = 'bank_transfer';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
