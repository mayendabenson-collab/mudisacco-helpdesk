<?php

declare(strict_types=1);

namespace App\Enum;

enum NotificationChannel: string
{
    case IN_APP = 'IN_APP';
    case EMAIL = 'EMAIL';
    case SMS = 'SMS';
    case WHATSAPP = 'WHATSAPP';
}
