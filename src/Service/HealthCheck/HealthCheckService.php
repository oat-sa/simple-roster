<?php

/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\HealthCheck;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use OAT\SimpleRoster\Exception\DoctrineResultCacheImplementationNotFoundException;
use OAT\SimpleRoster\HealthCheck\HealthCheckResult;
use Psr\Log\LoggerInterface;

class HealthCheckService
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var LoggerInterface $logger */
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * @throws DoctrineResultCacheImplementationNotFoundException
     */
    public function getHealthCheckResult(): HealthCheckResult
    {
        $resultCacheImplementation = $this->entityManager->getConfiguration()->getResultCacheImpl();
        if (null === $resultCacheImplementation) {
            throw new DoctrineResultCacheImplementationNotFoundException(
                'Doctrine result cache implementation is not configured.'
            );
        }

        $cacheStatistics = $resultCacheImplementation->getStats();

        $connection = $this->entityManager->getConnection();

        try {
            $testQuery = $connection->getDatabasePlatform()->getDummySelectSQL();
            $testQueryResult = (bool)$connection->fetchOne($testQuery);
        } catch (Exception $e) {
            $testQueryResult = $connection->isConnected();

            $this->logger->critical(
                sprintf(
                    'DB connection unavailable. Got `%s` exception from DBAL',
                    $e->getMessage()
                )
            );
        }

        return new HealthCheckResult(
            $testQueryResult,
            ($cacheStatistics['uptime'] ?? 0) > 0
        );
    }
}
