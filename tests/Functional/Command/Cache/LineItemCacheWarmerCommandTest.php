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

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Monolog\Logger;
use OAT\SimpleRoster\Command\Cache\LineItemCacheWarmerCommand;
use OAT\SimpleRoster\Generator\LineItemCacheIdGenerator;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Tests\Traits\CommandDisplayNormalizerTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Uid\UuidV6;

class LineItemCacheWarmerCommandTest extends KernelTestCase
{
    use DatabaseTestingTrait;
    use LoggerTestingTrait;
    use CommandDisplayNormalizerTrait;

    /** @var CommandTester */
    private $commandTester;

    /** @var CacheProvider */
    private $resultCache;

    /** @var LineItemCacheIdGenerator */
    private $lineItemCacheIdGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(LineItemCacheWarmerCommand::NAME));

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $resultCacheImplementation = $entityManager->getConfiguration()->getResultCacheImpl();

        if (!$resultCacheImplementation instanceof CacheProvider) {
            throw new LogicException('Doctrine result cache is not configured.');
        }

        $this->resultCache = $resultCacheImplementation;
        $this->lineItemCacheIdGenerator = self::$container->get(LineItemCacheIdGenerator::class);

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

        self::$container->set('test.line_item_repository', $lineItemRepository);

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

    public function testItCanWarmUpLineItemsCache(): void
    {
        $this->loadFixtureByFilename('3LineItems.yml');

        self::assertFalse(
            $this->resultCache->contains(
                $this->lineItemCacheIdGenerator->generate(new UuidV6('00000001-0000-6000-0000-000000000000'))
            )
        );
        self::assertFalse(
            $this->resultCache->contains(
                $this->lineItemCacheIdGenerator->generate(new UuidV6('00000002-0000-6000-0000-000000000000'))
            )
        );
        self::assertFalse(
            $this->resultCache->contains(
                $this->lineItemCacheIdGenerator->generate(new UuidV6('00000003-0000-6000-0000-000000000000'))
            )
        );

        self::assertSame(0, $this->commandTester->execute([], ['capture_stderr_separately' => true]));

        self::assertStringContainsString('Executing cache warmup...', $this->commandTester->getDisplay());

        self::assertStringContainsString(
            '[OK] Result cache for 3 Line Items have been successfully warmed up. [TTL: 3,600 seconds]',
            $this->normalizeDisplay($this->commandTester->getDisplay())
        );

        self::assertTrue(
            $this->resultCache->contains(
                $this->lineItemCacheIdGenerator->generate(new UuidV6('00000001-0000-6000-0000-000000000000'))
            )
        );
        self::assertTrue(
            $this->resultCache->contains(
                $this->lineItemCacheIdGenerator->generate(new UuidV6('00000002-0000-6000-0000-000000000000'))
            )
        );
        self::assertTrue(
            $this->resultCache->contains(
                $this->lineItemCacheIdGenerator->generate(new UuidV6('00000003-0000-6000-0000-000000000000'))
            )
        );
    }

    public function testItLogsSuccessfulCacheWarmup(): void
    {
        $this->loadFixtureByFilename('3LineItems.yml');

        self::assertSame(0, $this->commandTester->execute([], ['capture_stderr_separately' => true]));

        $this->assertHasLogRecord(
            [
                'message' => "Result cache for Line Item with id = '00000001-0000-6000-0000-000000000000' " .
                    "have been successfully warmed up.",
                'context' => [
                    'cacheKey' => 'lineItem.00000001-0000-6000-0000-000000000000',
                    'cacheTtl' => '3,600',
                ],
            ],
            Logger::INFO
        );

        $this->assertHasLogRecord(
            [
                'message' => "Result cache for Line Item with id = '00000002-0000-6000-0000-000000000000' " .
                    "have been successfully warmed up.",
                'context' => [
                    'cacheKey' => 'lineItem.00000002-0000-6000-0000-000000000000',
                    'cacheTtl' => '3,600',
                ],
            ],
            Logger::INFO
        );

        $this->assertHasLogRecord(
            [
                'message' => "Result cache for Line Item with id = '00000003-0000-6000-0000-000000000000' " .
                    "have been successfully warmed up.",
                'context' => [
                    'cacheKey' => 'lineItem.00000003-0000-6000-0000-000000000000',
                    'cacheTtl' => '3,600',
                ],
            ],
            Logger::INFO
        );
    }
}
