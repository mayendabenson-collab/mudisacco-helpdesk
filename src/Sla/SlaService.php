<?php

declare(strict_types=1);

namespace App\Sla;

use App\Entity\Category;
use App\Entity\SlaRule;
use App\Entity\Ticket;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use Doctrine\ORM\EntityManagerInterface;

class SlaService
{
    /**
     * System configuration fallback targets (used ONLY if neither category-specific nor global database SLA rules exist).
     * Note: These values serve as technical system defaults to prevent null deadlines, not confirmed official Mudi SACCO policy.
     */
    public const SYSTEM_DEFAULT_RESOLUTION_MINUTES = [
        'URGENT' => 240,   // 4 hours
        'HIGH' => 480,     // 8 hours
        'MEDIUM' => 1440,  // 24 hours
        'LOW' => 2880,     // 48 hours
    ];

    public const SYSTEM_DEFAULT_RESPONSE_MINUTES = [
        'URGENT' => 30,    // 30 mins
        'HIGH' => 60,      // 1 hour
        'MEDIUM' => 240,   // 4 hours
        'LOW' => 480,      // 8 hours
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function applyInitialSla(Ticket $ticket, ?\DateTimeImmutable $startedAt = null): void
    {
        $startedAt ??= new \DateTimeImmutable();
        $category = $ticket->getCategory();
        $priority = $ticket->getPriority();

        $rule = $category !== null ? $this->resolveRule($category, $priority) : null;
        $resolutionMinutes = $rule instanceof SlaRule
            ? $rule->getResolutionMinutes()
            : $this->fallbackResolutionMinutes($priority);

        $dueAt = $startedAt->modify('+' . $resolutionMinutes . ' minutes');
        $ticket->startSla($startedAt, $dueAt);
    }

    public function calculateDueAt(Category $category, TicketPriority $priority, ?\DateTimeImmutable $from = null): \DateTimeImmutable
    {
        $from ??= new \DateTimeImmutable();
        $rule = $this->resolveRule($category, $priority);
        $minutes = $rule instanceof SlaRule ? $rule->getResolutionMinutes() : $this->fallbackResolutionMinutes($priority);

        return $from->modify('+' . $minutes . ' minutes');
    }

    public function fallbackResolutionMinutes(TicketPriority $priority): int
    {
        return self::SYSTEM_DEFAULT_RESOLUTION_MINUTES[$priority->value] ?? 1440;
    }

    public function fallbackResponseMinutes(TicketPriority $priority): int
    {
        return self::SYSTEM_DEFAULT_RESPONSE_MINUTES[$priority->value] ?? 240;
    }

    public function pauseIfWaitingForMember(Ticket $ticket, ?\DateTimeImmutable $pausedAt = null): void
    {
        if (!$ticket->isSlaPaused()) {
            $ticket->pauseSla($pausedAt);
        }
    }

    public function resumeIfMemberResponded(Ticket $ticket, ?\DateTimeImmutable $resumedAt = null): void
    {
        if ($ticket->isSlaPaused()) {
            $ticket->resumeSla($resumedAt);
        }
    }

    /**
     * Resolves the applicable SLA rule in priority order:
     * 1. Exact Category + Priority rule from database
     * 2. Database global Priority fallback (where category IS NULL)
     * 3. Null if neither exists (caller falls back to system configuration default)
     */
    public function resolveRule(Category $category, TicketPriority $priority): ?SlaRule
    {
        $repository = $this->entityManager->getRepository(SlaRule::class);

        // 1. Exact Category + Priority
        $rule = $repository->findOneBy([
            'category' => $category,
            'priority' => $priority,
            'active' => true,
        ]);

        if ($rule instanceof SlaRule) {
            return $rule;
        }

        // 2. Global Priority fallback configured in database
        $fallback = $repository->findOneBy([
            'category' => null,
            'priority' => $priority,
            'active' => true,
        ]);

        return $fallback instanceof SlaRule ? $fallback : null;
    }
}