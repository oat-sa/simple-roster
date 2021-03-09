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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Functional\Command\ModifyEntity\LineItem;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use InvalidArgumentException;
use LogicException;
use Monolog\Logger;
use OAT\SimpleRoster\Command\ModifyEntity\LineItem\LineItemChangeDatesCommand;
use OAT\SimpleRoster\Generator\LineItemCacheIdGenerator;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Tests\Traits\CommandDisplayNormalizerTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Uid\UuidV6;

class LineItemChangeDatesCommandTest extends KernelTestCase
{
    use CommandDisplayNormalizerTrait;
    use DatabaseTestingTrait;
    use LoggerTestingTrait;

    /** @var CommandTester */
    private $commandTester;

    /** @var CacheProvider */
    private $resultCache;

    /** @var LineItemCacheIdGenerator */
    private $lineItemCacheIdGenerator;

    /** @var LineItemRepository */
    private $lineItemRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(LineItemChangeDatesCommand::NAME));

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $resultCacheImplementation = $entityManager->getConfiguration()->getResultCacheImpl();

        if (!$resultCacheImplementation instanceof CacheProvider) {
            throw new LogicException('Doctrine result cache is not configured.');
        }

        $this->resultCache = $resultCacheImplementation;
        $this->lineItemCacheIdGenerator = self::$container->get(LineItemCacheIdGenerator::class);
        $this->lineItemRepository = self::$container->get(LineItemRepository::class);

        $this->setUpDatabase();
        $this->setUpTestLogHandler();
        $this->loadFixtureByFilename('3LineItems.yml');
    }

    /**
     * @dataProvider provideInvalidParameters
     */
    public function testItThrowsExceptionForEachInvalidParameters(array $parameters, string $expectedOutput): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedOutput);

        self::assertSame(0, $this->commandTester->execute(
            $parameters,
            [
                'capture_stderr_separately' => true,
            ]
        ));

        self::assertStringContainsString($expectedOutput, $this->commandTester->getDisplay());
    }

    /**
     * @dataProvider provideValidParametersWithExistingLineItems
     */
    public function testItEncapsulatesAnyUnexpectedExceptions(array $parameters): void
    {
        $kernel = self::bootKernel();

        $lineItemRepository = $this->createMock(LineItemRepository::class);
        $lineItemRepository
            ->method('flush')
            ->willThrowException(new ORMException('ErrorMessage'));

        self::$container->set('test.line_item_repository', $lineItemRepository);

        $application = new Application($kernel);
        $commandTester = new CommandTester($application->find(LineItemChangeDatesCommand::NAME));

        $parameters['-f'] = null;

        self::assertSame(1, $commandTester->execute($parameters, ['capture_stderr_separately' => true]));
        self::assertStringContainsString(
            '[ERROR] An unexpected error occurred: ErrorMessage',
            $this->normalizeDisplay($commandTester->getDisplay())
        );
    }

    /**
     * @dataProvider provideValidParametersWithExistingLineItems
     */
    public function testDryRunModeAndCacheStillTheSame(array $parameters, array $persistedData): void
    {
        $lineItemIds = $persistedData['lineItemIds'];

        $this->assertCacheDoesNotExist($lineItemIds);

        self::assertSame(0, $this->commandTester->execute($parameters, ['capture_stderr_separately' => true]));

        $display = $this->normalizeDisplay($this->commandTester->getDisplay());

        self::assertStringContainsString('[NOTE] Checking line items to be updated...', $display);
        self::assertStringContainsString(
            sprintf('[OK] [DRY RUN] %d line item(s) have been updated.', count($lineItemIds)),
            $display
        );

        $this->assertCacheDoesNotExist($lineItemIds);
    }

    /**
     * @dataProvider provideValidParametersWithExistingLineItems
     */
    public function testItUpdateLineItemsWithRealUpdatesAndCacheIsWarmup(array $parameters, array $persistedData): void
    {
        $lineItemIds = $persistedData['lineItemIds'];
        $this->assertCacheDoesNotExist($lineItemIds);

        $parameters['-f'] = null;
        self::assertSame(0, $this->commandTester->execute($parameters, ['capture_stderr_separately' => true]));

        $display = $this->normalizeDisplay($this->commandTester->getDisplay());

        self::assertStringContainsString('[NOTE] Checking line items to be updated...', $display);
        self::assertStringContainsString(
            sprintf('[OK] %d line item(s) have been updated.', count($lineItemIds)),
            $display
        );

        $this->assertLineItems($persistedData);
    }

    /**
     * @dataProvider provideValidParametersWithNonExistingLineItems
     */
    public function testItWarnsIfNoLineItemWasFound(array $parameters): void
    {
        self::assertSame(0, $this->commandTester->execute($parameters, ['capture_stderr_separately' => true]));

        $display = $this->normalizeDisplay($this->commandTester->getDisplay());

        self::assertStringContainsString('[NOTE] Checking line items to be updated...', $display);
        self::assertStringContainsString('[WARNING] No line items found with specified criteria.', $display);
    }

    private function assertLineItems(array $persistedData): void
    {
        foreach ($persistedData['lineItemIds'] as $lineItemId) {
            $lineItem = $this->lineItemRepository->findOneById($lineItemId);

            $this->assertHasLogRecord(
                [
                    'message' => sprintf(
                        'New dates were set for line item with: "%s"',
                        (string)$lineItemId
                    ),
                    'context' => $lineItem->jsonSerialize(),
                ],
                Logger::INFO
            );

            $lineItemCacheId = $this->lineItemCacheIdGenerator->generate($lineItemId);
            $lineItemCache = current(current($this->resultCache->fetch($lineItemCacheId)));

            self::assertTrue($this->resultCache->contains($lineItemCacheId));
            self::assertSame($persistedData['start_at'], $lineItemCache['start_at_4']);
            self::assertSame($persistedData['end_at'], $lineItemCache['end_at_5']);
        }
    }

    public function provideInvalidParameters(): array
    {
        return [
            'noLineItemIdsOrSlugs' => [
                'parameters' => [
                    '--start-date' => '2020-01-01T00:00:00+0000',
                    '--end-date' => '2020-01-10T00:00:00+0000',
                ],
                'expectedOutput' => 'You need to specify line-item-ids or line-item-slugs option.',
            ],
            'invalidLineItemIds' => [
                'parameters' => [
                    '-i' => 'a,b,c',
                ],
                'expectedOutput' => 'Invalid \'line-item-ids\' option received.',
            ],
            'invalidLineItemSlugs' => [
                'parameters' => [
                    '-s' => ',',
                ],
                'expectedOutput' => 'Invalid \'line-item-slugs\' option received.',
            ],
            'invalidDate' => [
                'parameters' => [
                    '-i' => '00000001-0000-6000-0000-000000000000,00000002-0000-6000-0000-000000000000,' .
                        '00000003-0000-6000-0000-000000000000',
                    '--start-date' => '2020-13-01',
                ],
                'expectedOutput' => '2020-13-01 is an invalid date. Expected format: 2020-01-01T00:00:00+0000',
            ],
            'invalidEndDate' => [
                'parameters' => [
                    '-i' => '00000001-0000-6000-0000-000000000000,00000002-0000-6000-0000-000000000000,' .
                        '00000003-0000-6000-0000-000000000000',
                    '--start-date' => '2020-01-01T00:00:00+0000',
                    '--end-date' => '2019-12-31T23:59:00+0000',
                ],
                'expectedOutput' => 'End date should be later than start date.',
            ],
            'endDateBeforeStartDate' => [
                'parameters' => [
                    '-i' => '00000001-0000-6000-0000-000000000000,00000002-0000-6000-0000-000000000000,' .
                        '00000003-0000-6000-0000-000000000000',
                    '--start-date' => '2020-01-02T00:00:00+0000',
                    '--end-date' => '2020-01-01T00:00:00+0000',
                ],
                'expectedOutput' => 'End date should be later than start date.',
            ],
            'informedBothSlugsAndIds' => [
                'parameters' => [
                    '-i' => '00000001-0000-6000-0000-000000000000,00000002-0000-6000-0000-000000000000,' .
                        '00000003-0000-6000-0000-000000000000',
                    '-s' => 'slug1,slug2,slug3',
                ],
                'expectedOutput' => 'Option \'line-item-ids\' and \'line-item-slugs\' are exclusive options.',
            ],
        ];
    }

    public function provideValidParametersWithNonExistingLineItems(): array
    {
        return [
            'usingShortIdsParameterAndDates' => [
                'parameters' => [
                    '-i' => '00000004-0000-6000-0000-000000000000,00000005-0000-6000-0000-000000000000',
                    '--start-date' => '2020-01-01T00:00:00+0000',
                    '--end-date' => '2020-01-10T00:00:00+0000',
                ],
            ],
            'usingLongIdParameterAndDates' => [
                'parameters' => [
                    '--line-item-ids' => '00000004-0000-6000-0000-000000000000,00000005-0000-6000-0000-000000000000',
                    '--start-date' => '2020-01-01T00:00:00+0000',
                    '--end-date' => '2020-01-10T00:00:00+0000',
                ],
            ],
            'usingShortSlugsParameterAndDates' => [
                'parameters' => [
                    '-s' => 'slug1,slug2,slug3',
                    '--start-date' => '2020-01-01T00:00:00+0000',
                    '--end-date' => '2020-01-10T00:00:00+0000',
                ],
            ],
            'usingLongSlugsParameterAndDates' => [
                'parameters' => [
                    '--line-item-slugs' => 'slug1,slug2,slug3',
                    '--start-date' => '2020-01-01T00:00:00+0000',
                    '--end-date' => '2020-01-10T00:00:00+0000',
                ],
            ],
        ];
    }

    private function assertCacheDoesNotExist(array $lineItemIds): void
    {
        foreach ($lineItemIds as $lineItemId) {
            $lineItemCacheId = $this->lineItemCacheIdGenerator->generate($lineItemId);

            self::assertFalse($this->resultCache->fetch($lineItemCacheId));
        }
    }

    /**
     * * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function provideValidParametersWithExistingLineItems(): array
    {
        return [
            'usingSingleIdAndDates' => [
                'parameters' => [
                    '-i' => '00000003-0000-6000-0000-000000000000',
                    '--start-date' => '2020-01-01T00:00:00+0000',
                    '--end-date' => '2020-01-10T00:00:00+0000',
                ],
                'persistedData' => [
                    'lineItemIds' => [new UuidV6('00000003-0000-6000-0000-000000000000')],
                    'start_at' => '2020-01-01 00:00:00',
                    'end_at' => '2020-01-10 00:00:00',
                ],
            ],
            'usingSingleIdsWithoutDates' => [
                'parameters' => [
                    '-i' => '00000003-0000-6000-0000-000000000000',
                ],
                'persistedData' => [
                    'lineItemIds' => [new UuidV6('00000003-0000-6000-0000-000000000000')],
                    'start_at' => null,
                    'end_at' => null,
                ],
            ],
            'usingMultipleIdsAndDates' => [
                'parameters' => [
                    '-i' => '00000001-0000-6000-0000-000000000000,00000002-0000-6000-0000-000000000000,' .
                        '00000003-0000-6000-0000-000000000000',
                    '--start-date' => '2020-01-01T00:00:00+0000',
                    '--end-date' => '2020-01-10T00:00:00+0000',
                ],
                'persistedData' => [
                    'lineItemIds' => [
                        new UuidV6('00000001-0000-6000-0000-000000000000'),
                        new UuidV6('00000002-0000-6000-0000-000000000000'),
                        new UuidV6('00000003-0000-6000-0000-000000000000'),
                    ],
                    'start_at' => '2020-01-01 00:00:00',
                    'end_at' => '2020-01-10 00:00:00',
                ],
            ],
            'usingMultipleIdsWithoutDates' => [
                'parameters' => [
                    '-i' => '00000001-0000-6000-0000-000000000000,00000002-0000-6000-0000-000000000000,' .
                        '00000003-0000-6000-0000-000000000000',
                ],
                'persistedData' => [
                    'lineItemIds' => [
                        new UuidV6('00000001-0000-6000-0000-000000000000'),
                        new UuidV6('00000002-0000-6000-0000-000000000000'),
                        new UuidV6('00000003-0000-6000-0000-000000000000'),
                    ],
                    'start_at' => null,
                    'end_at' => null,
                ],
            ],
            'usingIdsAndStartDateOnly' => [
                'parameters' => [
                    '-i' => '00000001-0000-6000-0000-000000000000,00000002-0000-6000-0000-000000000000,' .
                        '00000003-0000-6000-0000-000000000000',
                    '--start-date' => '2020-01-01T00:00:00+0000',
                ],
                'persistedData' => [
                    'lineItemIds' => [
                        new UuidV6('00000001-0000-6000-0000-000000000000'),
                        new UuidV6('00000002-0000-6000-0000-000000000000'),
                        new UuidV6('00000003-0000-6000-0000-000000000000'),
                    ],
                    'start_at' => '2020-01-01 00:00:00',
                    'end_at' => null,
                ],
            ],
            'usingIdsAndEndDateOnly' => [
                'parameters' => [
                    '-i' => '00000001-0000-6000-0000-000000000000,00000002-0000-6000-0000-000000000000,' .
                        '00000003-0000-6000-0000-000000000000',
                    '--end-date' => '2020-01-01T00:00:00+0000',
                ],
                'persistedData' => [
                    'lineItemIds' => [
                        new UuidV6('00000001-0000-6000-0000-000000000000'),
                        new UuidV6('00000002-0000-6000-0000-000000000000'),
                        new UuidV6('00000003-0000-6000-0000-000000000000'),
                    ],
                    'start_at' => null,
                    'end_at' => '2020-01-01 00:00:00',
                ],
            ],
            'usingSingleSlugAndDates' => [
                'parameters' => [
                    '-s' => 'slug-1',
                    '--start-date' => '2020-01-01T00:00:00+0000',
                    '--end-date' => '2020-01-10T00:00:00+0000',
                ],
                'persistedData' => [
                    'lineItemIds' => [new UuidV6('00000001-0000-6000-0000-000000000000')],
                    'start_at' => '2020-01-01 00:00:00',
                    'end_at' => '2020-01-10 00:00:00',
                ],
            ],
            'usingSingleSlugWithoutDates' => [
                'parameters' => [
                    '-s' => 'slug-1',
                ],
                'persistedData' => [
                    'lineItemIds' => [new UuidV6('00000001-0000-6000-0000-000000000000')],
                    'start_at' => null,
                    'end_at' => null,
                ],
            ],
            'usingMultipleSlugsAndDates' => [
                'parameters' => [
                    '-s' => 'slug-1,slug-2,slug-qqy',
                    '--start-date' => '2020-01-01T00:00:00+0000',
                    '--end-date' => '2020-01-10T00:00:00+0000',
                ],
                'persistedData' => [
                    'lineItemIds' => [
                        new UuidV6('00000001-0000-6000-0000-000000000000'),
                        new UuidV6('00000002-0000-6000-0000-000000000000'),
                        new UuidV6('00000003-0000-6000-0000-000000000000'),
                    ],
                    'start_at' => '2020-01-01 00:00:00',
                    'end_at' => '2020-01-10 00:00:00',
                ],
            ],
            'usingMultipleSlugsWithoutDates' => [
                'parameters' => [
                    '-s' => 'slug-1,slug-2,slug-qqy',
                ],
                'persistedData' => [
                    'lineItemIds' => [
                        new UuidV6('00000001-0000-6000-0000-000000000000'),
                        new UuidV6('00000002-0000-6000-0000-000000000000'),
                        new UuidV6('00000003-0000-6000-0000-000000000000'),
                    ],
                    'start_at' => null,
                    'end_at' => null,
                ],
            ],
            'usingSlugsAndStartDateOnly' => [
                'parameters' => [
                    '-s' => 'slug-1,slug-2,slug-qqy',
                    '--start-date' => '2020-01-01T00:00:00+0000',
                ],
                'persistedData' => [
                    'lineItemIds' => [
                        new UuidV6('00000001-0000-6000-0000-000000000000'),
                        new UuidV6('00000002-0000-6000-0000-000000000000'),
                        new UuidV6('00000003-0000-6000-0000-000000000000'),
                    ],
                    'start_at' => '2020-01-01 00:00:00',
                    'end_at' => null,
                ],
            ],
            'usingSlugsAndEndDateOnly' => [
                'parameters' => [
                    '-s' => 'slug-1,slug-2,slug-qqy',
                    '--end-date' => '2020-01-01T00:00:00+0000',
                ],
                'persistedData' => [
                    'lineItemIds' => [
                        new UuidV6('00000001-0000-6000-0000-000000000000'),
                        new UuidV6('00000002-0000-6000-0000-000000000000'),
                        new UuidV6('00000003-0000-6000-0000-000000000000'),
                    ],
                    'start_at' => null,
                    'end_at' => '2020-01-01 00:00:00',
                ],
            ],
        ];
    }
}
