<?php

namespace App\Enum;

enum TransactionType: int
{
    case Payment = 1;
    case Deposit = 2;

    public function getName(): string
    {
        return match ($this) {
            self::Payment => 'payment',
            self::Deposit => 'deposit',
        };
    }

    public static function valueFromName(string $name)
    {
        return constant("self::$name");
    }
}