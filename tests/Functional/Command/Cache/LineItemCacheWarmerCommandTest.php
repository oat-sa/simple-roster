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
use OAT\SimpleRoster\Command\Cache\LineItemCacheWarmerCommand;
use OAT\SimpleRoster\Generator\LineItemCacheIdGenerator;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Tests\Traits\CommandDisplayNormalizerTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LineItemCacheWarmerCommandTest extends KernelTestCase
{
    use DatabaseTestingTrait;
    use LoggerTestingTrait;
    use CommandDisplayNormalizerTrait;

    /** @var CommandTester */
    private CommandTester $commandTester;

    private CacheItemPoolInterface $resultCache;

    /** @var LineItemCacheIdGenerator */
    private $lineItemCacheIdGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(LineItemCacheWarmerCommand::NAME));

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $resultCacheImplementation = $entityManager->getConfiguration()->getResultCache();

        if (!$resultCacheImplementation instanceof CacheItemPoolInterface) {
            throw new LogicException('Doctrine result cache is not configured.');
        }

        $this->resultCache = $resultCacheImplementation;
        $this->lineItemCacheIdGenerator = self::getContainer()->get(LineItemCacheIdGenerator::class);

        $this->setUpDatabase();
        $this->setUpTestLogHandler('cache_warmup');
    }

    public function testItEncapsulatesAnyUnexpectedExceptions(): void
    {
        $kernel = self::bootKernel();

        $lineItemRepository = $this->createMock(LineItemRepository::class);
        $lineItemRepository
            ->method('findAllAsCollection')
            ->willThrowException(new LogicException('Yaaay'));

        self::getContainer()->set('test.line_item_repository', $lineItemRepository);

        $application = new Application($kernel);
        $commandTester = new CommandTester($application->find(LineItemCacheWarmerCommand::NAME));

        self::assertSame(1, $commandTester->execute([], ['capture_stderr_separately' => true]));
        self::assertStringContainsString(
            '[ERROR] An unexpected error occurred: Yaaay',
            $commandTester->getDisplay()
        );
    }

    public function testItDisplaysWarningIfThereAreNoLineItemsIngested(): void
    {
        self::assertSame(0, $this->commandTester->execute([], ['capture_stderr_separately' => true]));

        self::assertStringContainsString(
            '[WARNING] There are no Line Items found in the database.',
            $this->commandTester->getDisplay()
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testItCanWarmUpLineItemsCache(): void
    {
        $this->loadFixtureByFilename('3LineItems.yml');

        self::assertFalse($this->resultCache->hasItem($this->lineItemCacheIdGenerator->generate(1)));
        self::assertFalse($this->resultCache->hasItem($this->lineItemCacheIdGenerator->generate(2)));
        self::assertFalse($this->resultCache->hasItem($this->lineItemCacheIdGenerator->generate(3)));

        self::assertSame(0, $this->commandTester->execute([], ['capture_stderr_separately' => true]));

        self::assertStringContainsString('Executing cache warmup...', $this->commandTester->getDisplay());

        self::assertStringContainsString(
            '[OK] Result cache for 3 Line Items have been successfully warmed up. [TTL: 3,600 seconds]',
            $this->normalizeDisplay($this->commandTester->getDisplay())
        );

        self::assertTrue($this->resultCache->hasItem($this->lineItemCacheIdGenerator->generate(1)));
        self::assertTrue($this->resultCache->hasItem($this->lineItemCacheIdGenerator->generate(2)));
        self::assertTrue($this->resultCache->hasItem($this->lineItemCacheIdGenerator->generate(3)));
    }

    public function testItLogsSuccessfulCacheWarmup(): void
    {
        $this->loadFixtureByFilename('3LineItems.yml');

        self::assertSame(0, $this->commandTester->execute([], ['capture_stderr_separately' => true]));

        $this->assertHasLogRecord(
            [
                'message' => 'Result cache for Line Item Id 1 have been successfully warmed up.',
                'context' => [
                    'cacheKey' => 'lineItem.1',
                    'cacheTtl' => '3,600',
                ],
            ],
            Logger::INFO
        );

        $this->assertHasLogRecord(
            [
                'message' => 'Result cache for Line Item Id 2 have been successfully warmed up.',
                'context' => [
                    'cacheKey' => 'lineItem.2',
                    'cacheTtl' => '3,600',
                ],
            ],
            Logger::INFO
        );

        $this->assertHasLogRecord(
            [
                'message' => 'Result cache for Line Item Id 3 have been successfully warmed up.',
                'context' => [
                    'cacheKey' => 'lineItem.3',
                    'cacheTtl' => '3,600',
                ],
            ],
            Logger::INFO
        );
    }
}
