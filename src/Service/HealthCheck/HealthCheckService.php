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

namespace App\Service\HealthCheck;

use App\Exception\DoctrineResultCacheImplementationNotFoundException;
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

        return new HealthCheckResult(
            $this->entityManager->getConnection()->ping(),
            ($cacheStatistics['uptime'] ?? 0) > 0
        );
    }
}
