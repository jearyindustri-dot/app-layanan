<?php

namespace App\Enums;

enum HandlingType: string
{
    case DIRECT = 'direct';
    case REFERRAL = 'referral';
    case BOTH = 'both';

    public function label(): string
    {
        return match ($this) {
            self::DIRECT => 'Pelayanan Langsung',
            self::REFERRAL => 'Rujukan',
            self::BOTH => 'Pelayanan Langsung & Rujukan',
        };
    }
}
