<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\AuditLog;
use App\Security\SystemRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/audit')]
class AuditController extends AbstractController
{
    #[Route('', name: 'admin_audit_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(SystemRole::ADMIN->value);

        $action = trim((string) $request->query->get('action'));
        $entityType = trim((string) $request->query->get('entity_type'));

        $qb = $entityManager->createQueryBuilder()
            ->select('a', 'actor')
            ->from(AuditLog::class, 'a')
            ->leftJoin('a.actor', 'actor')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(200);

        if ($action !== '') {
            $qb->andWhere('a.action = :action')->setParameter('action', $action);
        }

        if ($entityType !== '') {
            $qb->andWhere('a.entityType = :entityType')->setParameter('entityType', $entityType);
        }

        return $this->render('admin/audit/index.html.twig', [
            'auditLogs' => $qb->getQuery()->getResult(),
            'actions' => $this->distinctValues($entityManager, 'action'),
            'entityTypes' => $this->distinctValues($entityManager, 'entityType'),
            'selectedAction' => $action,
            'selectedEntityType' => $entityType,
        ]);
    }

    /** @return list<string> */
    private function distinctValues(EntityManagerInterface $entityManager, string $field): array
    {
        $rows = $entityManager->createQueryBuilder()
            ->select('DISTINCT a.' . $field . ' AS value')
            ->from(AuditLog::class, 'a')
            ->orderBy('value', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_map(static fn (array $row): string => (string) $row['value'], $rows)));
    }
}
