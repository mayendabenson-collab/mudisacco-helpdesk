<?php

declare(strict_types=1);

namespace App\Enum;

enum MemberStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case SUSPENDED = 'SUSPENDED';
}
