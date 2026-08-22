<?php
declare(strict_types=1);

namespace App\Enums;

enum BarcodeType: string
{
    case EAN = 'EAN';
    case UPC = 'UPC';
    case CODE128 = 'CODE128';
    case GTIN = 'GTIN';
}
