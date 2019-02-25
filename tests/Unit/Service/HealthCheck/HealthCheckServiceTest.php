<?php declare(strict_types=1);

namespace App\Tests\Unit\Service\HealthCheck;

use App\HealthCheck\HealthCheckResult;
use App\Service\HealthCheck\HealthCheckService;
use Doctrine\Common\Cache\Cache;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

class HealthCheckServiceTest extends TestCase
{
    /** @var HealthCheckService */
    private $subject;

    /** @var EntityManagerInterface|PHPUnit_Framework_MockObject_MockObject */
    private $entityManager;

    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->subject = new HealthCheckService($this->entityManager);
    }

    public function testGetHealthCheckResultSuccess(): void
    {
        $resultCacheImplMock = $this->createMock(Cache::class);
        $resultCacheImplMock
            ->expects($this->once())
            ->method('getStats')
            ->willReturn(['uptime' => 999]);

        $configurationMock = $this->createMock(Configuration::class);
        $configurationMock
            ->expects($this->once())
            ->method('getResultCacheImpl')
            ->willReturn($resultCacheImplMock);

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock
            ->expects($this->once())
            ->method('ping')
            ->willReturn(true);

        $this->entityManager
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($connectionMock);
        $this->entityManager
            ->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configurationMock);

        $output = $this->subject->getHealthCheckResult();

        $this->assertTrue($output->isDoctrineConnectionAvailable());
        $this->assertTrue($output->isDoctrineCacheAvailable());
    }

    public function testGetHealthCheckResultFailure(): void
    {
        $resultCacheImplMock = $this->createMock(Cache::class);
        $resultCacheImplMock
            ->expects($this->once())
            ->method('getStats')
            ->willReturn([]);

        $configurationMock = $this->createMock(Configuration::class);
        $configurationMock
            ->expects($this->once())
            ->method('getResultCacheImpl')
            ->willReturn($resultCacheImplMock);

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock
            ->expects($this->once())
            ->method('ping')
            ->willReturn(false);

        $this->entityManager
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($connectionMock);
        $this->entityManager
            ->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configurationMock);

        $output = $this->subject->getHealthCheckResult();

        $this->assertFalse($output->isDoctrineConnectionAvailable());
        $this->assertFalse($output->isDoctrineCacheAvailable());
    }
}
