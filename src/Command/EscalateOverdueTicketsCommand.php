<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Ticket;
use App\Enum\TicketStatus;
use App\Ticket\TicketService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:tickets:escalate-overdue', description: 'Escalates active tickets that have breached their SLA.')] 
class EscalateOverdueTicketsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TicketService $ticketService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new \DateTimeImmutable();
        $tickets = $this->entityManager->createQueryBuilder()
            ->select('t', 'd', 'm', 'a')
            ->from(Ticket::class, 't')
            ->join('t.department', 'd')
            ->join('t.member', 'm')
            ->leftJoin('t.assignedTo', 'a')
            ->where('t.escalatedAt IS NULL')
            ->andWhere('t.slaDueAt IS NOT NULL')
            ->andWhere('t.slaDueAt < :now')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('now', $now)
            ->setParameter('statuses', [TicketStatus::OPEN, TicketStatus::IN_PROGRESS, TicketStatus::WAITING_FOR_MEMBER, TicketStatus::REOPENED])
            ->getQuery()
            ->getResult();

        $escalated = 0;
        foreach ($tickets as $ticket) {
            if ($ticket instanceof Ticket && $ticket->isSlaBreached($now) && $this->ticketService->escalate($ticket, null, 'Automatic SLA breach escalation.')) {
                $escalated++;
            }
        }

        $output->writeln(sprintf('Escalated %d overdue ticket%s.', $escalated, $escalated === 1 ? '' : 's'));

        return Command::SUCCESS;
    }
}