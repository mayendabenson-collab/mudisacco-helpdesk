<?php

declare(strict_types=1);

namespace App\Security;

enum SystemRole: string
{
    case MEMBER = 'ROLE_MEMBER';
    case STAFF = 'ROLE_STAFF';
    case SUPERVISOR = 'ROLE_SUPERVISOR';
    case ADMIN = 'ROLE_ADMIN';
    case SERVICE_ACCOUNT = 'ROLE_SERVICE_ACCOUNT';
}
