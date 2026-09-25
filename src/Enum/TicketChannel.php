<?php

declare(strict_types=1);

namespace App\Enum;

enum TicketChannel: string
{
    case PHONE = 'PHONE';
    case WALK_IN = 'WALK_IN';
    case EMAIL = 'EMAIL';
    case WHATSAPP = 'WHATSAPP';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::PHONE => 'Phone Call',
            self::WALK_IN => 'In-Person / Walk-in',
            self::EMAIL => 'Email',
            self::WHATSAPP => 'WhatsApp',
            self::OTHER => 'Other',
        };
    }
}
