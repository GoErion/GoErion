<?php

declare(strict_types=1);

namespace App\Enums;

enum SupplierAddressType: string
{
    case Billing = 'billing';
    case Shipping = 'shipping';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Billing => 'Billing — registered / billing correspondence',
            self::Shipping => 'Shipping — dispatch / collection location',
            self::Other => 'Other — office or another address',
        };
    }
}
