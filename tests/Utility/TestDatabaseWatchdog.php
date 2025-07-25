<?php

namespace App\Tests\Utility;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TestDatabaseWatchdog
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private string $dbName = 'dividend_test'
    ) {}

    public function ping(): void
    {
        $conn = $this->em->getConnection();

        if (!$conn->isConnected()) {
            try {
                $conn->connect();
                $this->logger->info('[Watchdog] DB connection re-established.');
            } catch (\Throwable $e) {
                $this->logger->error('[Watchdog] Unable to reconnect to DB: ' . $e->getMessage());
                throw $e;
            }
        }

        try {
            $result = $conn->executeQuery('SELECT 1')->fetchOne();
            $this->logger->info('[Watchdog] DB ping successful: ' . $result);
        } catch (\Throwable $e) {
            $this->logger->critical('[Watchdog] Failed to ping DB: ' . $e->getMessage());
            throw $e;
        }
    }
}
