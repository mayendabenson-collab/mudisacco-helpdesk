<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Entity\Department;
use App\Entity\Ticket;
use App\Entity\TicketAssignment;
use App\Entity\Team;
use App\Entity\User;
use App\Enum\TicketStatus;
use App\Security\SystemRole;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;

class TicketAssignmentService
{
    private const ACTIVE_WORK_STATUSES = [
        TicketStatus::OPEN,
        TicketStatus::IN_PROGRESS,
        TicketStatus::WAITING_FOR_MEMBER,
        TicketStatus::REOPENED,
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function assignInitialStaff(Ticket $ticket): ?User
    {
        $candidates = $this->assignmentCandidates($ticket->getDepartment());

        if ($candidates === []) {
            return null;
        }

        usort($candidates, fn (array $left, array $right): int => $this->openWorkload($left[0]) <=> $this->openWorkload($right[0]));
        [$selectedStaff, $selectedTeam] = $candidates[0];

        $this->assignTo($ticket, $selectedStaff, null, 'Automatically assigned to the lowest current workload in department.', $selectedTeam);

        return $selectedStaff;
    }

    public function assignTo(Ticket $ticket, ?User $assignedTo, ?User $assignedBy, ?string $note = null, ?Team $assignedTeam = null): TicketAssignment
    {
        if ($assignedTo instanceof User && !$assignedTo->belongsToDepartment($ticket->getDepartment())) {
            throw new DomainException('Assigned staff must belong to the ticket department.');
        }

        if ($assignedTeam instanceof Team && !$assignedTeam->getDepartment()->getId()->equals($ticket->getDepartment()->getId())) {
            throw new DomainException('Assigned team must belong to the ticket department.');
        }

        if ($assignedTeam instanceof Team && !$assignedTeam->isActive()) {
            throw new DomainException('Tickets can only be assigned to an active team.');
        }

        if ($assignedTo instanceof User && $assignedTeam instanceof Team && !$assignedTo->belongsToTeam($assignedTeam)) {
            throw new DomainException('Assigned staff must belong to the assigned team.');
        }

        $ticket->setAssignedTo($assignedTo);
        $ticket->setAssignedTeam($assignedTeam);

        $assignment = (new TicketAssignment())
            ->setTicket($ticket)
            ->setDepartment($ticket->getDepartment())
            ->setAssignedTeam($assignedTeam)
            ->setAssignedTo($assignedTo)
            ->setAssignedBy($assignedBy)
            ->setNote($note);

        $this->entityManager->persist($assignment);

        return $assignment;
    }

    /** @return list<array{0: User, 1: ?Team}> */
    private function assignmentCandidates(Department $department): array
    {
        $staffMembers = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->join('u.roles', 'r')
            ->where('u.active = true')
            ->andWhere('u.department = :department')
            ->andWhere('r.code = :staffRole')
            ->setParameter('department', $department)
            ->setParameter('staffRole', SystemRole::STAFF->value)
            ->getQuery()
            ->getResult();

        $candidates = [];
        foreach ($this->activeTeamsForDepartment($department) as $team) {
            foreach ($team->getMembers() as $staffMember) {
                if (!$staffMember instanceof User || !$staffMember->isActive() || !in_array(SystemRole::STAFF->value, $staffMember->getRoles(), true)) {
                    continue;
                }

                $candidates[$staffMember->getId()->toRfc4122()] = [$staffMember, $team];
            }
        }

        foreach ($staffMembers as $staffMember) {
            if (!$staffMember instanceof User || isset($candidates[$staffMember->getId()->toRfc4122()])) {
                continue;
            }

            $candidates[$staffMember->getId()->toRfc4122()] = [$staffMember, null];
        }

        return array_values($candidates);
    }

    /** @return list<Team> */
    private function activeTeamsForDepartment(Department $department): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('team')
            ->from(Team::class, 'team')
            ->where('team.active = true')
            ->andWhere('team.department = :department')
            ->setParameter('department', $department)
            ->getQuery()
            ->getResult();
    }

    private function openWorkload(User $user): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(Ticket::class, 't')
            ->where('t.assignedTo = :user')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', self::ACTIVE_WORK_STATUSES)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
