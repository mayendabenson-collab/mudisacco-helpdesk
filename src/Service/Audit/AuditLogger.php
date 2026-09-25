<?php

declare(strict_types=1);

namespace App\Service\Audit;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class AuditLogger
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function record(?User $actor, string $action, string $entityType, ?Uuid $entityId = null, array $context = [], ?string $ipAddress = null): AuditLog
    {
        $auditLog = (new AuditLog())
            ->setActor($actor)
            ->setAction($action)
            ->setEntityType($entityType)
            ->setEntityId($entityId?->toRfc4122())
            ->setIpAddress($ipAddress)
            ->setContext($context);

        $this->entityManager->persist($auditLog);

        $this->logger->info('Audit event recorded.', [
            'actor' => $actor?->getUserIdentifier(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId?->toRfc4122(),
            'context' => $context,
        ]);

        return $auditLog;
    }
}
