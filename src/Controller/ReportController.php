<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Branch;
use App\Entity\Department;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketStatus;
use App\Security\SystemRole;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reports')]
class ReportController extends AbstractController
{
    #[Route('', name: 'report_overview', methods: ['GET'])]
    public function overview(EntityManagerInterface $entityManager): Response
    {
        $user = $this->requireReportAccess();

        $departmentRows = $this->departmentRows($entityManager, $user);
        $branchRows = $this->branchRows($entityManager, $user);
        $slaRows = $this->slaRows($entityManager, $user);

        return $this->render('reports/index.html.twig', [
            'departmentRows' => $departmentRows,
            'branchRows' => $branchRows,
            'slaRows' => $slaRows,
        ]);
    }

    #[Route('/management-summary', name: 'report_management_summary', methods: ['GET'])]
    public function managementSummary(EntityManagerInterface $entityManager): Response
    {
        $user = $this->requireReportAccess();

        return $this->render('reports/management_summary.html.twig', $this->managementSummaryData($entityManager, $user));
    }

    #[Route('/department-performance', name: 'report_department_performance', methods: ['GET'])]
    public function departmentPerformance(EntityManagerInterface $entityManager): Response
    {
        $user = $this->requireReportAccess();

        return $this->render('reports/department_performance.html.twig', [
            'rows' => $this->departmentRows($entityManager, $user),
        ]);
    }

    #[Route('/branch-performance', name: 'report_branch_performance', methods: ['GET'])]
    public function branchPerformance(EntityManagerInterface $entityManager): Response
    {
        $user = $this->requireReportAccess();

        return $this->render('reports/branch_performance.html.twig', [
            'rows' => $this->branchRows($entityManager, $user),
        ]);
    }

    #[Route('/sla-overview', name: 'report_sla_overview', methods: ['GET'])]
    public function slaOverview(EntityManagerInterface $entityManager): Response
    {
        $user = $this->requireReportAccess();

        return $this->render('reports/sla_overview.html.twig', [
            'rows' => $this->slaRows($entityManager, $user),
        ]);
    }

    #[Route('/department-performance.csv', name: 'report_department_performance_csv', methods: ['GET'])]
    public function departmentPerformanceCsv(EntityManagerInterface $entityManager): StreamedResponse
    {
        $user = $this->requireReportAccess();

        return $this->csvResponse('department-performance.csv', ['Department', 'Total', 'Open', 'In Progress', 'Waiting', 'Resolved', 'Closed', 'Reopened', 'Overdue'], $this->departmentRows($entityManager, $user));
    }

    #[Route('/branch-performance.csv', name: 'report_branch_performance_csv', methods: ['GET'])]
    public function branchPerformanceCsv(EntityManagerInterface $entityManager): StreamedResponse
    {
        $user = $this->requireReportAccess();

        return $this->csvResponse('branch-performance.csv', ['Branch', 'Total', 'Open', 'In Progress', 'Waiting', 'Resolved', 'Closed', 'Reopened', 'Overdue'], $this->branchRows($entityManager, $user));
    }

    #[Route('/sla-overview.csv', name: 'report_sla_overview_csv', methods: ['GET'])]
    public function slaOverviewCsv(EntityManagerInterface $entityManager): StreamedResponse
    {
        $user = $this->requireReportAccess();

        return $this->csvResponse('sla-overview.csv', ['Status', 'Tickets'], $this->slaRows($entityManager, $user));
    }

    private function managementSummaryData(EntityManagerInterface $entityManager, User $user): array
    {
        $departmentRows = $this->departmentRows($entityManager, $user);
        $branchRows = $this->branchRows($entityManager, $user);
        $slaRows = $this->slaRows($entityManager, $user);

        $summary = [
            'total_tickets' => array_sum(array_map(static fn (array $row): int => (int) $row['total'], $departmentRows)),
            'open_tickets' => array_sum(array_map(static fn (array $row): int => (int) $row['open'], $departmentRows)),
            'in_progress_tickets' => array_sum(array_map(static fn (array $row): int => (int) $row['in_progress'], $departmentRows)),
            'waiting_tickets' => array_sum(array_map(static fn (array $row): int => (int) $row['waiting'], $departmentRows)),
            'resolved_tickets' => array_sum(array_map(static fn (array $row): int => (int) $row['resolved'], $departmentRows)),
            'closed_tickets' => array_sum(array_map(static fn (array $row): int => (int) $row['closed'], $departmentRows)),
            'reopened_tickets' => array_sum(array_map(static fn (array $row): int => (int) $row['reopened'], $departmentRows)),
            'overdue_tickets' => array_sum(array_map(static fn (array $row): int => (int) $row['overdue'], $departmentRows)),
            'escalated_tickets' => $this->countSlaStatus($slaRows, 'ESCALATED'),
            'breached_tickets' => $this->countSlaStatus($slaRows, 'BREACHED'),
            'departmentRows' => $departmentRows,
            'branchRows' => $branchRows,
            'slaRows' => $slaRows,
        ];

        $topDepartments = array_slice($departmentRows, 0, 5);
        $topBranches = array_slice($branchRows, 0, 5);

        return [
            'summary' => $summary,
            'topDepartments' => $topDepartments,
            'topBranches' => $topBranches,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function departmentRows(EntityManagerInterface $entityManager, User $user): array
    {
        $departments = $entityManager->getRepository(Department::class)->findBy(['active' => true], ['name' => 'ASC']);
        $rows = [];

        foreach ($departments as $department) {
            if (!$this->isAllowedDepartment($user, $department)) {
                continue;
            }

            $counts = $this->ticketCounts($entityManager, 'department', $department);
            $total = (int) $counts['total'];
            $overdue = (int) $counts['overdue'];
            $rows[] = [
                'department' => $department->getName(),
                'total' => $total,
                'open' => (int) $counts['open'],
                'in_progress' => (int) $counts['in_progress'],
                'waiting' => (int) $counts['waiting'],
                'resolved' => (int) $counts['resolved'],
                'closed' => (int) $counts['closed'],
                'reopened' => (int) $counts['reopened'],
                'overdue' => $overdue,
            ];
        }

        usort($rows, static fn (array $left, array $right): int => $right['total'] <=> $left['total']);

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    private function branchRows(EntityManagerInterface $entityManager, User $user): array
    {
        $branches = $entityManager->getRepository(Branch::class)->findBy(['active' => true], ['name' => 'ASC']);
        $rows = [];

        foreach ($branches as $branch) {
            $counts = $this->ticketCounts($entityManager, 'branch', $branch);
            $total = (int) $counts['total'];

            if ($total === 0 && !$this->isAdminOrSupervisor($user)) {
                continue;
            }

            $rows[] = [
                'branch' => $branch->getName(),
                'total' => $total,
                'open' => (int) $counts['open'],
                'in_progress' => (int) $counts['in_progress'],
                'waiting' => (int) $counts['waiting'],
                'resolved' => (int) $counts['resolved'],
                'closed' => (int) $counts['closed'],
                'reopened' => (int) $counts['reopened'],
                'overdue' => (int) $counts['overdue'],
            ];
        }

        usort($rows, static fn (array $left, array $right): int => $right['total'] <=> $left['total']);

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    private function slaRows(EntityManagerInterface $entityManager, User $user): array
    {
        $qb = $entityManager->createQueryBuilder()
            ->select('t', 'd', 'b', 'a')
            ->from(Ticket::class, 't')
            ->leftJoin('t.department', 'd')
            ->leftJoin('t.branch', 'b')
            ->leftJoin('t.assignedTo', 'a')
            ->orderBy('t.updatedAt', 'DESC');
        $this->scopeByUser($qb, $user);

        $tickets = $qb->getQuery()->getResult();
        $counts = [
            'ON_TRACK' => 0,
            'DUE_SOON' => 0,
            'PAUSED' => 0,
            'BREACHED' => 0,
            'ESCALATED' => 0,
            'MET' => 0,
            'NOT_SET' => 0,
        ];

        foreach ($tickets as $ticket) {
            if (!$ticket instanceof Ticket) {
                continue;
            }

            $status = $ticket->getSlaStatus();
            if (!isset($counts[$status])) {
                $counts[$status] = 0;
            }
            $counts[$status]++;
        }

        $rows = [];
        foreach ($counts as $label => $count) {
            $rows[] = ['label' => $label, 'count' => $count];
        }

        usort($rows, static fn (array $left, array $right): int => $right['count'] <=> $left['count']);

        return $rows;
    }

    private function countSlaStatus(array $rows, string $status): int
    {
        foreach ($rows as $row) {
            if (($row['label'] ?? '') === $status) {
                return (int) ($row['count'] ?? 0);
            }
        }

        return 0;
    }

    private function csvResponse(string $filename, array $headers, array $rows): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                $values = [];
                foreach ($headers as $key => $header) {
                    $label = is_string($key) ? $key : strtolower(str_replace(' ', '_', $header));
                    $values[] = $row[$label] ?? $row[lcfirst(str_replace(' ', '', $header))] ?? '';
                }

                fputcsv($handle, $values);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    /** @param Department|Branch $scope */
    private function ticketCounts(EntityManagerInterface $entityManager, string $scopeType, object $scope): array
    {
        $qb = $entityManager->createQueryBuilder()
            ->select('COUNT(t.id) AS total')
            ->from(Ticket::class, 't');

        if ($scopeType === 'department') {
            $qb->where('t.department = :scope')
                ->setParameter('scope', $scope);
        } else {
            $qb->where('t.branch = :scope')
                ->setParameter('scope', $scope);
        }

        $counts = [
            'total' => (int) $qb->getQuery()->getSingleScalarResult(),
            'open' => 0,
            'in_progress' => 0,
            'waiting' => 0,
            'resolved' => 0,
            'closed' => 0,
            'reopened' => 0,
            'overdue' => 0,
        ];

        $statusCounts = $entityManager->createQueryBuilder()
            ->select('t.status AS status, COUNT(t.id) AS count')
            ->from(Ticket::class, 't');

        if ($scopeType === 'department') {
            $statusCounts->where('t.department = :scope')
                ->setParameter('scope', $scope);
        } else {
            $statusCounts->where('t.branch = :scope')
                ->setParameter('scope', $scope);
        }

        $statusCounts->groupBy('t.status');

        foreach ($statusCounts->getQuery()->getResult() as $row) {
            $status = $row['status'] instanceof TicketStatus ? $row['status']->value : (string) $row['status'];
            $count = (int) $row['count'];

            match ($status) {
                TicketStatus::OPEN->value => $counts['open'] = $count,
                TicketStatus::IN_PROGRESS->value => $counts['in_progress'] = $count,
                TicketStatus::WAITING_FOR_MEMBER->value => $counts['waiting'] = $count,
                TicketStatus::RESOLVED->value => $counts['resolved'] = $count,
                TicketStatus::CLOSED->value => $counts['closed'] = $count,
                TicketStatus::REOPENED->value => $counts['reopened'] = $count,
                default => null,
            };
        }

        $overdueQb = $entityManager->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(Ticket::class, 't');

        if ($scopeType === 'department') {
            $overdueQb->where('t.department = :scope')
                ->andWhere('t.status NOT IN (:closedStatuses)')
                ->andWhere('t.slaDueAt IS NOT NULL AND t.slaDueAt < :now')
                ->setParameter('scope', $scope)
                ->setParameter('closedStatuses', [TicketStatus::RESOLVED, TicketStatus::CLOSED])
                ->setParameter('now', new \DateTimeImmutable());
        } else {
            $overdueQb->where('t.branch = :scope')
                ->andWhere('t.status NOT IN (:closedStatuses)')
                ->andWhere('t.slaDueAt IS NOT NULL AND t.slaDueAt < :now')
                ->setParameter('scope', $scope)
                ->setParameter('closedStatuses', [TicketStatus::RESOLVED, TicketStatus::CLOSED])
                ->setParameter('now', new \DateTimeImmutable());
        }

        $counts['overdue'] = (int) $overdueQb->getQuery()->getSingleScalarResult();

        return $counts;
    }

    private function requireReportAccess(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('You must sign in to view reports.');
        }

        $roles = $user->getRoles();
        if (in_array(SystemRole::ADMIN->value, $roles, true)
            || in_array(SystemRole::SUPERVISOR->value, $roles, true)
            || $user->hasPermission('view_reports')) {
            return $user;
        }

        throw $this->createAccessDeniedException('You do not have permission to view reports.');
    }

    private function isAdminOrSupervisor(User $user): bool
    {
        $roles = $user->getRoles();

        return in_array(SystemRole::ADMIN->value, $roles, true)
            || in_array(SystemRole::SUPERVISOR->value, $roles, true);
    }

    private function isAllowedDepartment(User $user, Department $department): bool
    {
        if ($this->isAdminOrSupervisor($user)) {
            return true;
        }

        return $user->getDepartment() !== null && $user->getDepartment()->getId()->equals($department->getId());
    }

    private function scopeByUser(QueryBuilder $qb, User $user): void
    {
        $roles = $user->getRoles();
        if (in_array(SystemRole::ADMIN->value, $roles, true)) {
            return;
        }

        $department = $user->getDepartment();
        if ($department === null) {
            $qb->andWhere('1 = 0');
            return;
        }

        $qb->andWhere('t.department = :department')
            ->setParameter('department', $department);
    }
}
