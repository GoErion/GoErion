<?php
declare(strict_types=1);

namespace App\Enums;

enum PriceType: string
{
    case COST = 'cost';
    case RETAIL = 'retail';
    case WHOLESALE = 'wholesale';
    case DEALER = 'dealer';
}
