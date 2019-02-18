<?php declare(strict_types=1);

namespace App\Service\HealthCheck;

use App\HealthCheck\HealthCheckResult;
use Doctrine\ORM\EntityManagerInterface;

class HealthCheckService
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getHealthCheckResult(): HealthCheckResult
    {
        $cacheStatistics = $this->entityManager->getConfiguration()->getResultCacheImpl()->getStats();

        return new HealthCheckResult(
            $this->entityManager->getConnection()->ping(),
            ($cacheStatistics['uptime'] ?? 0) > 0
        );
    }
}
