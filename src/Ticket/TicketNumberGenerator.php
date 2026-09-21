<?php

declare(strict_types=1);

namespace App\Ticket;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

class TicketNumberGenerator
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function nextReference(?\DateTimeImmutable $date = null): string
    {
        $date ??= new \DateTimeImmutable();
        $dateKey = $date->format('Ymd');
        $prefix = 'MUD-' . $dateKey . '-';
        $connection = $this->entityManager->getConnection();

        $sequence = $connection->transactional(function (Connection $connection) use ($dateKey): int {
            try {
                $connection->executeStatement(
                    'INSERT INTO ticket_counters (date_key, next_number, updated_at) VALUES (:date_key, 0, CURRENT_TIMESTAMP)',
                    ['date_key' => $dateKey]
                );
            } catch (UniqueConstraintViolationException) {
                // Existing counter row is expected after the first ticket of the day.
            }

            $connection->executeStatement(
                'UPDATE ticket_counters SET next_number = next_number + 1, updated_at = CURRENT_TIMESTAMP WHERE date_key = :date_key',
                ['date_key' => $dateKey]
            );

            return (int) $connection->fetchOne('SELECT next_number FROM ticket_counters WHERE date_key = :date_key', ['date_key' => $dateKey]);
        });

        return $prefix . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}