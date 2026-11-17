<?php

declare(strict_types=1);

namespace App\Enum;

enum PositionStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case PENDING = 'pending';
}
