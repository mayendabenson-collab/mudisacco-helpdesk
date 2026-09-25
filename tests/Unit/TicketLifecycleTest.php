<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Ticket;
use App\Enum\TicketStatus;
use App\Ticket\TicketLifecycleService;
use App\Ticket\TicketStatusWorkflow;
use DomainException;
use PHPUnit\Framework\TestCase;

class TicketLifecycleTest extends TestCase
{
    private TicketStatusWorkflow $workflow;
    private TicketLifecycleService $lifecycle;

    protected function setUp(): void
    {
        $this->workflow = new TicketStatusWorkflow();
        $this->lifecycle = new TicketLifecycleService($this->workflow);
    }

    public function testValidLifecycleFlow(): void
    {
        $ticket = new Ticket();
        $this->assertEquals(TicketStatus::OPEN, $ticket->getStatus());

        // OPEN -> IN_PROGRESS
        $this->lifecycle->transition($ticket, TicketStatus::IN_PROGRESS, reason: 'Staff picked up');
        $this->assertEquals(TicketStatus::IN_PROGRESS, $ticket->getStatus());

        // IN_PROGRESS -> WAITING_FOR_MEMBER
        $this->lifecycle->transition($ticket, TicketStatus::WAITING_FOR_MEMBER, reason: 'Need documents');
        $this->assertEquals(TicketStatus::WAITING_FOR_MEMBER, $ticket->getStatus());

        // WAITING_FOR_MEMBER -> IN_PROGRESS
        $this->lifecycle->transition($ticket, TicketStatus::IN_PROGRESS, reason: 'Member replied');
        $this->assertEquals(TicketStatus::IN_PROGRESS, $ticket->getStatus());

        // IN_PROGRESS -> RESOLVED
        $resolvedAt = new \DateTimeImmutable('2026-09-15 15:00:00');
        $this->lifecycle->transition($ticket, TicketStatus::RESOLVED, occurredAt: $resolvedAt);
        $this->assertEquals(TicketStatus::RESOLVED, $ticket->getStatus());
        $this->assertEquals($resolvedAt, $ticket->getResolvedAt());

        // RESOLVED -> CLOSED
        $this->lifecycle->transition($ticket, TicketStatus::CLOSED);
        $this->assertEquals(TicketStatus::CLOSED, $ticket->getStatus());
        $this->assertNotNull($ticket->getClosedAt());
    }

    public function testIllegalSkipToClosedThrowsDomainException(): void
    {
        $ticket = new Ticket();
        $this->expectException(DomainException::class);

        // Cannot jump directly from OPEN to CLOSED
        $this->lifecycle->transition($ticket, TicketStatus::CLOSED);
    }

    public function testReopenClearsResolutionTimestamp(): void
    {
        $ticket = new Ticket();
        $this->lifecycle->transition($ticket, TicketStatus::IN_PROGRESS);
        $this->lifecycle->transition($ticket, TicketStatus::RESOLVED);

        $this->assertNotNull($ticket->getResolvedAt());

        // Reopening ticket must reset resolvedAt
        $this->lifecycle->transition($ticket, TicketStatus::REOPENED);
        $this->assertEquals(TicketStatus::REOPENED, $ticket->getStatus());
        $this->assertNull($ticket->getResolvedAt());
    }
}
