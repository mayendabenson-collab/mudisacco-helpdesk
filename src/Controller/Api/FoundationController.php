<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Enum\NotificationChannel;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use App\Security\Permission;
use App\Security\SystemRole;
use App\Ticket\TicketStatusWorkflow;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class FoundationController extends AbstractController
{
    #[Route('/api/foundation', name: 'api_foundation', methods: ['GET'])]
    public function __invoke(TicketStatusWorkflow $workflow): JsonResponse
    {
        return $this->json([
            'roles' => array_map(static fn (SystemRole $role): string => $role->value, SystemRole::cases()),
            'permissions' => array_map(static fn (Permission $permission): string => $permission->value, Permission::cases()),
            'ticket_priorities' => array_map(static fn (TicketPriority $priority): string => $priority->value, TicketPriority::cases()),
            'ticket_statuses' => array_map(static fn (TicketStatus $status): string => $status->value, TicketStatus::cases()),
            'ticket_status_transitions' => $workflow->transitionMap(),
            'notification_channels' => array_map(static fn (NotificationChannel $channel): string => $channel->value, NotificationChannel::cases()),
        ]);
    }
}
