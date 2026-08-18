<?php
declare(strict_types=1);

namespace App\Enums;

enum ProductType: string
{
    case PHYSICAL = 'physical';
    case DIGITAL = 'digital';
    case SERVICE = 'service';
}
