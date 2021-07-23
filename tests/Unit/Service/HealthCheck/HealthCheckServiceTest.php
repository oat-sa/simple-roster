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

use Doctrine\Common\Cache\Cache;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use OAT\SimpleRoster\Exception\DoctrineResultCacheImplementationNotFoundException;
use OAT\SimpleRoster\Service\HealthCheck\HealthCheckService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class HealthCheckServiceTest extends TestCase
{
    private HealthCheckService $subject;

    /** @var Connection|MockObject */
    private $connection;

    /** @var AbstractPlatform|MockObject */
    private $databasePlatform;

    /** @var LoggerInterface|MockObject $logger */
    private $logger;

    /** @var Configuration|MockObject */
    private $ormConfiguration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(Connection::class);
        $this->databasePlatform = $this->createMock(AbstractPlatform::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->ormConfiguration = $this->createMock(Configuration::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->method('getConfiguration')
            ->willReturn($this->ormConfiguration);

        $entityManager
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->connection
            ->method('getDatabasePlatform')
            ->willReturn($this->databasePlatform);

        $this->subject = new HealthCheckService($entityManager, $this->logger);
    }

    public function testItThrowsExceptionIfResultCacheImplementationIsNotConfigured(): void
    {
        $this->expectException(DoctrineResultCacheImplementationNotFoundException::class);

        $this->subject->getHealthCheckResult();
    }

    public function testItCanReturnHealthCheckResult(): void
    {
        $resultCacheImplementation = $this->createMock(Cache::class);
        $resultCacheImplementation
            ->method('getStats')
            ->willReturn(['uptime' => 1]);

        $this->ormConfiguration
            ->method('getResultCacheImpl')
            ->willReturn($resultCacheImplementation);

        $this->databasePlatform
            ->method('getDummySelectSQL')
            ->willReturn('SELECT 1');

        $this->connection
            ->method('fetchOne')
            ->willReturn(true);

        $output = $this->subject->getHealthCheckResult();

        self::assertTrue($output->isDoctrineConnectionAvailable());
        self::assertTrue($output->isDoctrineCacheAvailable());
    }

    public function testItCanReturnIfDatabaseConnectionIsNotAvailable(): void
    {
        $resultCacheImplementation = $this->createMock(Cache::class);
        $resultCacheImplementation
            ->method('getStats')
            ->willReturn(['uptime' => 1]);

        $this->ormConfiguration
            ->method('getResultCacheImpl')
            ->willReturn($resultCacheImplementation);

        $this->databasePlatform
            ->method('getDummySelectSQL')
            ->willThrowException(new Exception('Totally unexpected exception.'));

        $this->connection
            ->method('isConnected')
            ->willReturn(false);

        $this->logger
            ->expects(self::once())
            ->method('critical')
            ->with('DB connection unavailable. Exception: Totally unexpected exception.');

        $output = $this->subject->getHealthCheckResult();

        self::assertFalse($output->isDoctrineConnectionAvailable());
        self::assertTrue($output->isDoctrineCacheAvailable());
    }
}
