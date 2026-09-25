<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Category;
use App\Entity\Department;
use App\Entity\SlaRule;
use App\Entity\Ticket;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use App\Sla\SlaService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class SlaServiceTest extends TestCase
{
    public function testApplyInitialSlaUsesResolutionMinutesForSlaDueAt(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(EntityRepository::class);

        $rule = new SlaRule();
        $rule->setTargets(responseMinutes: 60, resolutionMinutes: 1440, escalationMinutes: 1200);

        $repo->method('findOneBy')->willReturn($rule);
        $em->method('getRepository')->willReturn($repo);

        $slaService = new SlaService($em);

        $ticket = new Ticket();
        $category = new Category();
        $ticket->setCategory($category);
        $ticket->setPriority(TicketPriority::MEDIUM);

        $now = new \DateTimeImmutable('2026-09-15 09:00:00');
        $slaService->applyInitialSla($ticket, $now);

        // slaDueAt must be 1440 minutes (24 hours) from start, NOT 60 minutes
        $expectedDue = $now->modify('+1440 minutes');
        $this->assertEquals($expectedDue, $ticket->getSlaDueAt());
    }

    public function testPauseAndResumeSlaExtendsDueAtByPauseDuration(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $slaService = new SlaService($em);

        $ticket = new Ticket();
        $ticket->setPriority(TicketPriority::HIGH);

        $startedAt = new \DateTimeImmutable('2026-09-15 08:00:00');
        $originalDue = $startedAt->modify('+480 minutes'); // 8 hours later: 16:00:00
        $ticket->startSla($startedAt, $originalDue);

        // Pause SLA 2 hours in: 10:00:00
        $pausedAt = new \DateTimeImmutable('2026-09-15 10:00:00');
        $slaService->pauseIfWaitingForMember($ticket, $pausedAt);

        $this->assertTrue($ticket->isSlaPaused());
        $this->assertEquals($pausedAt, $ticket->getSlaPausedAt());

        // Resume SLA 3 hours later: 13:00:00 (pause duration was 3 hours = 180 min)
        $resumedAt = new \DateTimeImmutable('2026-09-15 13:00:00');
        $slaService->resumeIfMemberResponded($ticket, $resumedAt);

        $this->assertFalse($ticket->isSlaPaused());
        $this->assertNull($ticket->getSlaPausedAt());

        // Due date should now be extended by 3 hours (16:00 + 3h = 19:00)
        $expectedNewDue = $originalDue->modify('+3 hours');
        $this->assertEquals($expectedNewDue, $ticket->getSlaDueAt());
    }
}
