<?php

declare(strict_types=1);

namespace App\Enum;

enum TicketMessageVisibility: string
{
    case PUBLIC = 'PUBLIC';
    case INTERNAL = 'INTERNAL';
}
