<?php

declare(strict_types=1);

namespace App\Security;

enum Permission: string
{
    case MANAGE_USERS = 'users.manage';
    case MANAGE_DEPARTMENTS = 'departments.manage';
    case MANAGE_CATEGORIES = 'categories.manage';
    case HANDLE_TICKETS = 'tickets.handle';
    case ASSIGN_TICKETS = 'tickets.assign';
    case ESCALATE_TICKETS = 'tickets.escalate';
    case VIEW_REPORTS = 'reports.view';
    case VIEW_AUDIT_LOG = 'audit.view';
    case MANAGE_SYSTEM = 'system.manage';

    public function label(): string
    {
        return match ($this) {
            self::MANAGE_USERS => 'Manage users',
            self::MANAGE_DEPARTMENTS => 'Manage departments',
            self::MANAGE_CATEGORIES => 'Manage ticket categories',
            self::HANDLE_TICKETS => 'Handle tickets',
            self::ASSIGN_TICKETS => 'Assign tickets',
            self::ESCALATE_TICKETS => 'Escalate tickets',
            self::VIEW_REPORTS => 'View reports',
            self::VIEW_AUDIT_LOG => 'View audit log',
            self::MANAGE_SYSTEM => 'Manage system configuration',
        };
    }
}
