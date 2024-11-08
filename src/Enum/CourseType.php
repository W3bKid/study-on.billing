<?php

namespace App\Enum;

enum CourseType: int {
    case RENTAL = 0;
    case FULL_PAYMENT = 1;
    case FREE = 3;

    public function getName(): string {
        return match ($this) {
            self::RENTAL => 'rent',
            self::FULL_PAYMENT => 'full',
            self::FREE => 'free',
        };
    }
}