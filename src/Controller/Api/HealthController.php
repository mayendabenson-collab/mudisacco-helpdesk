<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/health', name: 'health', methods: ['GET'])]
    #[Route('/health/', name: 'health_slash', methods: ['GET'])]
    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function __invoke(Connection $connection): JsonResponse
    {
        $database = 'ok';

        try {
            $connection->executeQuery('SELECT 1')->fetchOne();
        } catch (\Throwable) {
            $database = 'unavailable';
        }

        return $this->json([
            'service' => 'mudi-sacco-support',
            'status' => $database === 'ok' ? 'ok' : 'degraded',
            'checks' => [
                'database' => $database,
            ],
            'timestamp' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ], $database === 'ok' ? 200 : 503);
    }
}
