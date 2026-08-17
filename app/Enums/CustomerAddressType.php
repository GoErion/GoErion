<?php
declare(strict_types=1);

namespace App\Enums;

enum CustomerAddressType: string
{
    case BILLING = 'billing';
    case SHIPPING = 'shipping';
    case OTHER = 'other';
}
