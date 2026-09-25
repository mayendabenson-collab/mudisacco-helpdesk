<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Entity\Ticket;
use App\Entity\TicketStatusHistory;
use App\Entity\User;
use App\Enum\TicketStatus;
use App\Sla\SlaService;

class TicketLifecycleService
{
    public function __construct(
        private readonly TicketStatusWorkflow $workflow,
        private readonly ?SlaService $slaService = null,
    ) {
    }

    public function transition(
        Ticket $ticket,
        TicketStatus $nextStatus,
        ?User $actor = null,
        ?string $reason = null,
        ?\DateTimeImmutable $occurredAt = null,
    ): TicketStatusHistory {
        $currentStatus = $ticket->getStatus();
        $this->workflow->assertTransition($currentStatus, $nextStatus);

        $timestamp = $occurredAt ?? new \DateTimeImmutable();

        $ticket->setStatus($nextStatus);
        $this->applyLifecycleTimestamps($ticket, $nextStatus, $timestamp);

        if ($nextStatus === TicketStatus::WAITING_FOR_MEMBER) {
            $this->slaService?->pauseIfWaitingForMember($ticket, $timestamp);
        }

        if ($currentStatus === TicketStatus::WAITING_FOR_MEMBER && $nextStatus === TicketStatus::IN_PROGRESS) {
            $this->slaService?->resumeIfMemberResponded($ticket, $timestamp);
        }

        $history = (new TicketStatusHistory($nextStatus, $timestamp))
            ->setFromStatus($currentStatus)
            ->setChangedBy($actor)
            ->setReason($reason);

        $ticket->addStatusHistory($history);

        return $history;
    }

    private function applyLifecycleTimestamps(Ticket $ticket, TicketStatus $status, \DateTimeImmutable $timestamp): void
    {
        if ($status === TicketStatus::RESOLVED) {
            $ticket->setResolvedAt($timestamp);
        }

        if ($status === TicketStatus::CLOSED) {
            $ticket->setClosedAt($timestamp);
        }

        if ($status === TicketStatus::REOPENED) {
            $ticket->setResolvedAt(null);
            $ticket->setClosedAt(null);
        }
    }
}