<?php

declare(strict_types=1);

namespace App\Enum;

enum BranchType: string
{
    case BRANCH = 'BRANCH';
    case SATELLITE_CENTRE = 'SATELLITE_CENTRE';

    public function label(): string
    {
        return match ($this) {
            self::BRANCH => 'Branch',
            self::SATELLITE_CENTRE => 'Satellite Centre',
        };
    }
}
