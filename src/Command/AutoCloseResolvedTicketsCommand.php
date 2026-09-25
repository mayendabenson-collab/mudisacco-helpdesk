<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketStatus;
use App\Service\Audit\AuditLogger;
use App\Service\Notification\NotificationRouter;
use App\Ticket\TicketLifecycleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:tickets:auto-close-resolved', description: 'Closes resolved tickets after the member confirmation window expires.')]
class AutoCloseResolvedTicketsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TicketLifecycleService $lifecycleService,
        private readonly AuditLogger $auditLogger,
        private readonly NotificationRouter $notificationRouter,
        #[Autowire('%mudi.auto_close_days%')]
        private readonly int $autoCloseDays,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cutoff = (new \DateTimeImmutable())->modify('-' . $this->autoCloseDays . ' days');
        $tickets = $this->entityManager->createQueryBuilder()
            ->select('t', 'm', 'u', 'a')
            ->from(Ticket::class, 't')
            ->join('t.member', 'm')
            ->leftJoin('m.user', 'u')
            ->leftJoin('t.assignedTo', 'a')
            ->where('t.status = :status')
            ->andWhere('t.resolvedAt IS NOT NULL')
            ->andWhere('t.resolvedAt <= :cutoff')
            ->setParameter('status', TicketStatus::RESOLVED)
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getResult();

        $closed = 0;
        foreach ($tickets as $ticket) {
            if (!$ticket instanceof Ticket) {
                continue;
            }

            $this->lifecycleService->transition($ticket, TicketStatus::CLOSED, null, 'Automatically closed after member confirmation window expired.');
            $this->auditLogger->record(null, 'ticket.auto_closed', 'ticket', $ticket->getId(), [
                'reference' => $ticket->getReference(),
                'auto_close_days' => $this->autoCloseDays,
            ]);

            $memberUser = $ticket->getMember()->getUser();
            if ($memberUser instanceof User) {
                $this->notificationRouter->notify(
                    $memberUser,
                    'Ticket closed: ' . $ticket->getReference(),
                    'Your resolved ticket was automatically closed after the confirmation period expired.',
                    [
                        'event' => 'TICKET_CLOSED',
                        'ticket_id' => $ticket->getId()->toRfc4122(),
                        'reference' => $ticket->getReference(),
                    ]
                );
            }

            $closed++;
        }

        $this->entityManager->flush();
        $output->writeln(sprintf('Auto-closed %d resolved ticket%s.', $closed, $closed === 1 ? '' : 's'));

        return Command::SUCCESS;
    }
}