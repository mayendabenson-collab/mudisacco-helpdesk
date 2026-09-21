<?php

declare(strict_types=1);

namespace App\Enum;

enum TicketStatus: string
{
    case OPEN = 'OPEN';
    case IN_PROGRESS = 'IN_PROGRESS';
    case WAITING_FOR_MEMBER = 'WAITING_FOR_MEMBER';
    case RESOLVED = 'RESOLVED';
    case CLOSED = 'CLOSED';
    case REOPENED = 'REOPENED';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::IN_PROGRESS => 'In progress',
            self::WAITING_FOR_MEMBER => 'Waiting for member',
            self::RESOLVED => 'Resolved',
            self::CLOSED => 'Closed',
            self::REOPENED => 'Reopened',
        };
    }
}
