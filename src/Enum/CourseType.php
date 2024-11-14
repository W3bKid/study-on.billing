<?php

namespace App\Enum;

enum CourseType: int {
    case RENTAL = 0;
    case FULL_PAYMENT = 1;
    case FREE = 3;

    public const VALUES = [
        'Free' => self::FREE,
        'Rental' => self::RENTAL,
        'Full Payment' => self::FULL_PAYMENT,
    ];

    public function getName(): string {
        return match ($this) {
            self::RENTAL => 'Rental',
            self::FULL_PAYMENT => 'Full Payment',
            self::FREE => 'Free',
        };
    }

    public static function textValues(): array
    {
        return array_keys(self::VALUES);
    }

}
