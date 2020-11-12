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

namespace OAT\SimpleRoster\Tests\Unit\Service\HealthCheck;

use OAT\SimpleRoster\Exception\DoctrineResultCacheImplementationNotFoundException;
use OAT\SimpleRoster\Service\HealthCheck\HealthCheckService;
use Doctrine\Common\Cache\Cache;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HealthCheckServiceTest extends TestCase
{
    /** @var HealthCheckService */
    private $subject;

    /** @var EntityManagerInterface|MockObject */
    private $entityManager;

    /** @var Configuration|MockObject */
    private $ormConfiguration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->ormConfiguration = $this->createMock(Configuration::class);

        $this->entityManager
            ->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($this->ormConfiguration);

        $this->subject = new HealthCheckService($this->entityManager);
    }

    public function testItThrowsExceptionIfResultCacheImplementationIsNotConfigured(): void
    {
        $this->expectException(DoctrineResultCacheImplementationNotFoundException::class);

        $this->subject->getHealthCheckResult();
    }

    public function testGetHealthCheckResultSuccess(): void
    {
        $resultCacheImplMock = $this->createMock(Cache::class);
        $resultCacheImplMock
            ->expects(self::once())
            ->method('getStats')
            ->willReturn(['uptime' => 1]);

        $this->ormConfiguration
            ->expects(self::once())
            ->method('getResultCacheImpl')
            ->willReturn($resultCacheImplMock);

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock
            ->expects(self::once())
            ->method('ping')
            ->willReturn(true);

        $this->entityManager
            ->expects(self::once())
            ->method('getConnection')
            ->willReturn($connectionMock);

        $output = $this->subject->getHealthCheckResult();

        self::assertTrue($output->isDoctrineConnectionAvailable());
        self::assertTrue($output->isDoctrineCacheAvailable());
    }

    public function testGetHealthCheckResultFailure(): void
    {
        $resultCacheImplMock = $this->createMock(Cache::class);
        $resultCacheImplMock
            ->expects(self::once())
            ->method('getStats')
            ->willReturn(false);

        $this->ormConfiguration
            ->expects(self::once())
            ->method('getResultCacheImpl')
            ->willReturn($resultCacheImplMock);

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock
            ->expects(self::once())
            ->method('ping')
            ->willReturn(false);

        $this->entityManager
            ->expects(self::once())
            ->method('getConnection')
            ->willReturn($connectionMock);

        $output = $this->subject->getHealthCheckResult();

        self::assertFalse($output->isDoctrineConnectionAvailable());
        self::assertFalse($output->isDoctrineCacheAvailable());
    }
}
