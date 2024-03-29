<?php

/*
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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Functional\Command\Cache;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Monolog\Logger;
use OAT\SimpleRoster\Command\Cache\LtiInstanceCacheWarmerCommand;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use OAT\SimpleRoster\Tests\Traits\CommandDisplayNormalizerTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LtiInstanceCacheWarmerCommandTest extends KernelTestCase
{
    use DatabaseTestingTrait;
    use LoggerTestingTrait;
    use CommandDisplayNormalizerTrait;

    /** @var CommandTester */
    private CommandTester $commandTester;

    /** @var CacheItemPoolInterface */
    private $resultCache;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(LtiInstanceCacheWarmerCommand::NAME));

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $resultCacheImplementation = $entityManager->getConfiguration()->getResultCache();

        if (!$resultCacheImplementation instanceof CacheItemPoolInterface) {
            throw new LogicException('Doctrine result cache is not configured.');
        }

        $this->resultCache = $resultCacheImplementation;

        $this->setUpDatabase();
        $this->setUpTestLogHandler('cache_warmup');
    }

    public function testItEncapsulatesAnyUnexpectedExceptions(): void
    {
        $kernel = self::bootKernel();

        $ltiInstanceRepository = $this->createMock(LtiInstanceRepository::class);
        $ltiInstanceRepository
            ->method('findAllAsCollection')
            ->willThrowException(new LogicException('Yaaay'));

        self::getContainer()->set('test.lti_repository', $ltiInstanceRepository);

        $application = new Application($kernel);
        $commandTester = new CommandTester($application->find(LtiInstanceCacheWarmerCommand::NAME));

        self::assertSame(1, $commandTester->execute([], ['capture_stderr_separately' => true]));
        self::assertStringContainsString(
            '[ERROR] An unexpected error occurred: Yaaay',
            $commandTester->getDisplay()
        );
    }

    public function testItDisplaysWarningIfThereAreNoLtiInstancesIngested(): void
    {
        self::assertSame(0, $this->commandTester->execute([], ['capture_stderr_separately' => true]));

        self::assertStringContainsString(
            '[WARNING] There are no LTI instances found in the database.',
            $this->commandTester->getDisplay()
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testItCanWarmUpLtiInstanceCache(): void
    {
        $this->loadFixtureByFilename('5ltiInstances.yml');

        self::assertFalse($this->resultCache->hasItem(LtiInstanceRepository::CACHE_ID_ALL_LTI_INSTANCES));

        self::assertSame(0, $this->commandTester->execute([], ['capture_stderr_separately' => true]));

        self::assertStringContainsString('Executing cache warmup...', $this->commandTester->getDisplay());

        self::assertStringContainsString(
            '[OK] Result cache for 5 LTI instances have been successfully warmed up. [TTL: 3,600 seconds]',
            $this->normalizeDisplay($this->commandTester->getDisplay())
        );

        self::assertTrue($this->resultCache->hasItem(LtiInstanceRepository::CACHE_ID_ALL_LTI_INSTANCES));
    }

    public function testItLogsSuccessfulCacheWarmup(): void
    {
        $this->loadFixtureByFilename('5ltiInstances.yml');

        self::assertSame(0, $this->commandTester->execute([], ['capture_stderr_separately' => true]));

        $this->assertHasLogRecord([
            'message' => 'Result cache for 5 LTI instances have been successfully warmed up.',
            'context' => [
                'cacheKey' => 'lti_instances.all',
                'cacheTtl' => '3,600',
            ],
        ], Logger::INFO);
    }
}
