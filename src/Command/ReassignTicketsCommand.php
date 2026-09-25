<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Department;
use App\Entity\Ticket;
use App\Security\SystemRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:reassign-tickets', description: 'Bulk move tickets from one department code to another')]
class ReassignTicketsCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('from', InputArgument::REQUIRED, 'Source department code (e.g. ICT)')
            ->addArgument('to', InputArgument::REQUIRED, 'Target department code (e.g. CUSTOMER_SERVICE)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fromCode = (string) $input->getArgument('from');
        $toCode = (string) $input->getArgument('to');

        $repo = $this->entityManager->getRepository(Department::class);
        $from = $repo->findOneBy(['code' => strtoupper($fromCode)]);
        $to = $repo->findOneBy(['code' => strtoupper($toCode)]);

        if (!$from instanceof Department) {
            $output->writeln(sprintf('<error>Source department "%s" not found.</error>', $fromCode));
            return Command::FAILURE;
        }

        if (!$to instanceof Department) {
            $output->writeln(sprintf('<error>Target department "%s" not found.</error>', $toCode));
            return Command::FAILURE;
        }

        $qb = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(Ticket::class, 't')
            ->where('t.department = :from')
            ->setParameter('from', $from);

        $tickets = $qb->getQuery()->getResult();
        $count = count($tickets);

        if ($count === 0) {
            $output->writeln('<comment>No tickets found for the source department.</comment>');
            return Command::SUCCESS;
        }

        foreach ($tickets as $ticket) {
            /** @var Ticket $ticket */
            $ticket->setDepartment($to);
            $this->entityManager->persist($ticket);
        }

        $this->entityManager->flush();

        $output->writeln(sprintf('<info>Moved %d ticket(s) from %s to %s.</info>', $count, $fromCode, $toCode));

        return Command::SUCCESS;
    }
}
