<?php
declare(strict_types=1);

namespace App\Enums;

enum FiscalYearStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
}
