<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\StaffPermission;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use App\Security\SystemRole;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard', methods: ['GET'])]
    public function __invoke(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $metrics = $this->calculateMetrics($entityManager, $user);
        $recentTickets = $this->recentTickets($entityManager, $user, 6);

        return $this->render('dashboard/index.html.twig', [
            'metrics' => $metrics,
            'recentTickets' => $recentTickets,
        ]);
    }

    /** @return list<Ticket> */
    private function recentTickets(EntityManagerInterface $entityManager, User $user, int $limit = 6): array
    {
        $qb = $entityManager->createQueryBuilder()
            ->select('t', 'm', 'd', 'c', 'a')
            ->from(Ticket::class, 't')
            ->join('t.member', 'm')
            ->join('t.department', 'd')
            ->join('t.category', 'c')
            ->leftJoin('t.assignedTo', 'a')
            ->orderBy('t.updatedAt', 'DESC')
            ->setMaxResults($limit);

        $this->applyScope($qb, $user);

        return $qb->getQuery()->getResult();
    }

    /** @return array<string, mixed> */
    private function calculateMetrics(EntityManagerInterface $entityManager, User $user): array
    {
        $now = new \DateTimeImmutable();

        // 1. Status breakdown counts
        $statusQb = $entityManager->createQueryBuilder()
            ->select('t.status, COUNT(t.id) AS cnt')
            ->from(Ticket::class, 't')
            ->groupBy('t.status');
        $this->applyScope($statusQb, $user);
        $statusResults = $statusQb->getQuery()->getResult();

        $byStatus = array_fill_keys(array_map(static fn (TicketStatus $s): string => $s->value, TicketStatus::cases()), 0);
        $total = 0;
        foreach ($statusResults as $row) {
            $statusVal = $row['status'] instanceof TicketStatus ? $row['status']->value : (string) $row['status'];
            $cnt = (int) $row['cnt'];
            $byStatus[$statusVal] = $cnt;
            $total += $cnt;
        }

        // 2. Overdue count (open/in-progress/waiting with slaDueAt < now)
        $overdueQb = $entityManager->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(Ticket::class, 't')
            ->where('t.status NOT IN (:closedStatuses)')
            ->andWhere('t.slaDueAt IS NOT NULL AND t.slaDueAt < :now')
            ->setParameter('closedStatuses', [TicketStatus::RESOLVED, TicketStatus::CLOSED])
            ->setParameter('now', $now);
        $this->applyScope($overdueQb, $user);
        $overdue = (int) $overdueQb->getQuery()->getSingleScalarResult();

        // 3. Escalated count
        $escalatedQb = $entityManager->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(Ticket::class, 't')
            ->where('t.escalatedAt IS NOT NULL');
        $this->applyScope($escalatedQb, $user);
        $escalated = (int) $escalatedQb->getQuery()->getSingleScalarResult();

        // 4. Critical / Urgent count
        $criticalQb = $entityManager->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(Ticket::class, 't')
            ->where('t.priority = :urgent')
            ->setParameter('urgent', TicketPriority::URGENT);
        $this->applyScope($criticalQb, $user);
        $critical = (int) $criticalQb->getQuery()->getSingleScalarResult();

        // 5. Department grouping breakdown
        $deptQb = $entityManager->createQueryBuilder()
            ->select('d.name AS label, COUNT(t.id) AS cnt')
            ->from(Ticket::class, 't')
            ->join('t.department', 'd')
            ->groupBy('d.name')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults(10);
        $this->applyScope($deptQb, $user);
        $deptBreakdown = [];
        foreach ($deptQb->getQuery()->getResult() as $row) {
            $deptBreakdown[$row['label']] = (int) $row['cnt'];
        }

        // 6. Category grouping breakdown
        $catQb = $entityManager->createQueryBuilder()
            ->select('c.name AS label, COUNT(t.id) AS cnt')
            ->from(Ticket::class, 't')
            ->join('t.category', 'c')
            ->groupBy('c.name')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults(10);
        $this->applyScope($catQb, $user);
        $catBreakdown = [];
        foreach ($catQb->getQuery()->getResult() as $row) {
            $catBreakdown[$row['label']] = (int) $row['cnt'];
        }

        // 7. Priority grouping breakdown
        $prioQb = $entityManager->createQueryBuilder()
            ->select('t.priority AS prio, COUNT(t.id) AS cnt')
            ->from(Ticket::class, 't')
            ->groupBy('t.priority');
        $this->applyScope($prioQb, $user);
        $prioBreakdown = [];
        foreach ($prioQb->getQuery()->getResult() as $row) {
            $prioObj = $row['prio'] instanceof TicketPriority ? $row['prio'] : TicketPriority::tryFrom((string) $row['prio']);
            $label = $prioObj?->label() ?? (string) $row['prio'];
            $prioBreakdown[$label] = (int) $row['cnt'];
        }

        // 8. Staff assignment breakdown
        $staffQb = $entityManager->createQueryBuilder()
            ->select('COALESCE(a.fullName, \'Unassigned\') AS staffName, COUNT(t.id) AS cnt')
            ->from(Ticket::class, 't')
            ->leftJoin('t.assignedTo', 'a')
            ->groupBy('staffName')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults(10);
        $this->applyScope($staffQb, $user);
        $staffBreakdown = [];
        foreach ($staffQb->getQuery()->getResult() as $row) {
            $staffBreakdown[$row['staffName']] = (int) $row['cnt'];
        }

        return [
            'total' => $total,
            'open' => $byStatus[TicketStatus::OPEN->value],
            'in_progress' => $byStatus[TicketStatus::IN_PROGRESS->value],
            'waiting' => $byStatus[TicketStatus::WAITING_FOR_MEMBER->value],
            'resolved' => $byStatus[TicketStatus::RESOLVED->value],
            'closed' => $byStatus[TicketStatus::CLOSED->value],
            'reopened' => $byStatus[TicketStatus::REOPENED->value],
            'overdue' => $overdue,
            'escalated' => $escalated,
            'critical' => $critical,
            'avg_first_response' => null,
            'avg_resolution' => null,
            'sla_compliance' => null,
            'groups' => [
                'department' => $deptBreakdown,
                'category' => $catBreakdown,
                'priority' => $prioBreakdown,
                'staff' => $staffBreakdown,
                'date' => [],
            ],
        ];
    }

    private function applyScope(QueryBuilder $qb, User $user): void
    {
        $roles = $user->getRoles();
        if (in_array(SystemRole::ADMIN->value, $roles, true)) {
            return;
        }

        if (in_array(SystemRole::STAFF->value, $roles, true)) {
            $department = $user->getDepartment();
            if ($department !== null) {
                $qb->join('t.department', 'scopeDepartment');
                $canSeeDept = $user->hasPermission(StaffPermission::VIEW_ALL_DEPARTMENT_TICKETS)
                    || $user->hasPermission(StaffPermission::RESOLVE_TICKETS)
                    || $user->hasPermission(StaffPermission::CLOSE_TICKETS)
                    || $user->hasPermission(StaffPermission::ASSIGN_TICKETS)
                    || $user->hasPermission(StaffPermission::REASSIGN_TICKETS)
                    || $user->hasPermission(StaffPermission::EDIT_TICKET_PRIORITY)
                    || $user->hasPermission(StaffPermission::EDIT_TICKET_CATEGORY);

                if ($canSeeDept) {
                    $qb->andWhere('(t.assignedTo = :user OR t.createdBy = :user OR scopeDepartment.code = :departmentCode)')
                        ->setParameter('user', $user)
                        ->setParameter('departmentCode', $department->getCode());
                } else {
                    $qb->andWhere('(t.assignedTo = :user OR t.createdBy = :user)')
                        ->setParameter('user', $user);
                }
            } else {
                $qb->andWhere('(t.assignedTo = :user OR t.createdBy = :user)')
                    ->setParameter('user', $user);
            }

            return;
        }

        $qb->join('t.member', 'mScope')
            ->join('mScope.user', 'memberUser')
            ->andWhere('memberUser = :user')
            ->setParameter('user', $user);
    }
}