<?php

declare(strict_types=1);

namespace App\Enum;

enum StaffPermission: string
{
    // Ticket creation and resolution
    case CREATE_TICKETS = 'create_tickets';
    case RESOLVE_TICKETS = 'resolve_tickets';
    case CLOSE_TICKETS = 'close_tickets';
    
    // Assignment and management
    case ASSIGN_TICKETS = 'assign_tickets';
    case REASSIGN_TICKETS = 'reassign_tickets';
    
    // Escalation
    case ESCALATE_TICKETS = 'escalate_tickets';
    
    // Viewing
    case VIEW_ALL_DEPARTMENT_TICKETS = 'view_all_department_tickets';
    case VIEW_MEMBER_HISTORY = 'view_member_history';
    
    // Reporting
    case VIEW_REPORTS = 'view_reports';
    case VIEW_AUDIT_LOG = 'view_audit_log';
    
    // Customization
    case EDIT_TICKET_PRIORITY = 'edit_ticket_priority';
    case EDIT_TICKET_CATEGORY = 'edit_ticket_category';
    
    public static function defaultForStaff(): array
    {
        return [
            self::CREATE_TICKETS->value,
            self::RESOLVE_TICKETS->value,
            self::VIEW_MEMBER_HISTORY->value,
            self::ASSIGN_TICKETS->value,
            self::REASSIGN_TICKETS->value,
        ];
    }

    public static function defaultForSupervisor(): array
    {
        return [
            self::CREATE_TICKETS->value,
            self::RESOLVE_TICKETS->value,
            self::CLOSE_TICKETS->value,
            self::ASSIGN_TICKETS->value,
            self::REASSIGN_TICKETS->value,
            self::ESCALATE_TICKETS->value,
            self::VIEW_ALL_DEPARTMENT_TICKETS->value,
            self::VIEW_MEMBER_HISTORY->value,
            self::VIEW_REPORTS->value,
            self::EDIT_TICKET_PRIORITY->value,
            self::EDIT_TICKET_CATEGORY->value,
        ];
    }

    public static function defaultForAdmin(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::CREATE_TICKETS => 'Create Tickets',
            self::RESOLVE_TICKETS => 'Resolve Tickets',
            self::CLOSE_TICKETS => 'Close Tickets',
            self::ASSIGN_TICKETS => 'Assign Tickets to Others',
            self::REASSIGN_TICKETS => 'Reassign Tickets',
            self::ESCALATE_TICKETS => 'Escalate Tickets',
            self::VIEW_ALL_DEPARTMENT_TICKETS => 'View All Department Tickets',
            self::VIEW_MEMBER_HISTORY => 'View Member History',
            self::VIEW_REPORTS => 'View Reports',
            self::VIEW_AUDIT_LOG => 'View Audit Log',
            self::EDIT_TICKET_PRIORITY => 'Edit Ticket Priority',
            self::EDIT_TICKET_CATEGORY => 'Edit Ticket Category',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::CREATE_TICKETS => 'Can create new tickets when members call',
            self::RESOLVE_TICKETS => 'Can mark tickets as resolved/fixed',
            self::CLOSE_TICKETS => 'Can close resolved tickets',
            self::ASSIGN_TICKETS => 'Can assign tickets to team members',
            self::REASSIGN_TICKETS => 'Can reassign tickets to different staff',
            self::ESCALATE_TICKETS => 'Can escalate tickets to supervisor',
            self::VIEW_ALL_DEPARTMENT_TICKETS => 'Can see all tickets in department (not just assigned)',
            self::VIEW_MEMBER_HISTORY => 'Can view member complaint history and previous tickets',
            self::VIEW_REPORTS => 'Can access performance and SLA reports',
            self::VIEW_AUDIT_LOG => 'Can view system audit logs',
            self::EDIT_TICKET_PRIORITY => 'Can change ticket priority after creation',
            self::EDIT_TICKET_CATEGORY => 'Can change ticket category after creation',
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::CREATE_TICKETS, self::RESOLVE_TICKETS, self::CLOSE_TICKETS => 'Ticket Management',
            self::ASSIGN_TICKETS, self::REASSIGN_TICKETS, self::ESCALATE_TICKETS => 'Ticket Assignment',
            self::VIEW_ALL_DEPARTMENT_TICKETS, self::VIEW_MEMBER_HISTORY => 'Viewing Permissions',
            self::VIEW_REPORTS, self::VIEW_AUDIT_LOG => 'Reporting',
            self::EDIT_TICKET_PRIORITY, self::EDIT_TICKET_CATEGORY => 'Customization',
        };
    }
}
