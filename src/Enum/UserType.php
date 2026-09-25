<?php

declare(strict_types=1);

namespace App\Enum;

enum UserType: string
{
    case MEMBER = 'MEMBER';
    case STAFF = 'STAFF';
    case SUPERVISOR = 'SUPERVISOR';
    case ADMINISTRATOR = 'ADMINISTRATOR';
    case SERVICE_ACCOUNT = 'SERVICE_ACCOUNT';
}
