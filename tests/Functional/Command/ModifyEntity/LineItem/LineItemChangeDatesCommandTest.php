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
use OAT\SimpleRoster\Command\ModifyEntity\LineItem\LineItemChangeDatesCommand;
use OAT\SimpleRoster\Generator\LineItemCacheIdGenerator;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Tests\Traits\CommandDisplayNormalizerTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LineItemChangeDatesCommandTest extends KernelTestCase
{
    use DatabaseTestingTrait;
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
        $this->commandTester = new CommandTester($application->find(LineItemChangeDatesCommand::NAME));

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $resultCacheImplementation = $entityManager->getConfiguration()->getResultCacheImpl();

        if (!$resultCacheImplementation instanceof CacheProvider) {
            throw new LogicException('Doctrine result cache is not configured.');
        }

        $this->resultCache = $resultCacheImplementation;
        $this->lineItemCacheIdGenerator = self::$container->get(LineItemCacheIdGenerator::class);

        $this->setUpDatabase();
        $this->loadFixtureByFilename('3LineItems.yml');
    }

    /**
     * @dataProvider provideInvalidParameters
     */
    public function testItThrowsExceptionForEachInvalidParametersReceived(
        array $parameters,
        string $expectedOutput
    ): void {
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
    public function testItUpdateLineItemsWithoutRealUpdatesAndCacheStillTheSame(array $parameters): void
    {
        $this->loadFixtureByFilename('3LineItems.yml');

        self::assertFalse($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(4)));
        self::assertFalse($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(5)));
        self::assertFalse($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(6)));

        self::assertSame(0, $this->commandTester->execute($parameters, ['capture_stderr_separately' => true]));

        $display = $this->normalizeDisplay($this->commandTester->getDisplay());

        self::assertStringContainsString('[NOTE] Checking line items to be updated...', $display);
        self::assertStringContainsString('[OK] [DRY RUN] 3 line item(s) have been updated.', $display);

        self::assertFalse($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(4)));
        self::assertFalse($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(5)));
        self::assertFalse($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(6)));
    }

    /**
     * @dataProvider provideValidParametersWithExistingLineItems
     */
    public function testItUpdateLineItemsWithRealUpdatesAndCacheIsWarmup(array $parameters): void
    {
        $this->loadFixtureByFilename('3LineItems.yml');

        self::assertFalse($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(4)));
        self::assertFalse($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(5)));
        self::assertFalse($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(6)));

        $parameters['-f'] = null;
        self::assertSame(0, $this->commandTester->execute($parameters, ['capture_stderr_separately' => true]));

        $display = $this->normalizeDisplay($this->commandTester->getDisplay());

        self::assertStringContainsString('[NOTE] Checking line items to be updated...', $display);
        self::assertStringContainsString('[OK] 3 line item(s) have been updated.', $display);
        self::assertStringContainsString('Executing cache warmup...', $display);
        self::assertStringContainsString(
            '[OK] Result cache for 3 Line Items have been successfully warmed up. [TTL: 3,600 seconds]',
            $display
        );

        self::assertTrue($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(4)));
        self::assertTrue($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(5)));
        self::assertTrue($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(6)));
    }

    /**
     * @dataProvider provideValidParametersWithNonExistingLineItems
     */
    public function testItWarnsIfNoLineItemWasFound(array $parameters): void
    {
        $this->loadFixtureByFilename('3LineItems.yml');

        self::assertSame(0, $this->commandTester->execute($parameters, ['capture_stderr_separately' => true]));

        $display = $this->normalizeDisplay($this->commandTester->getDisplay());

        self::assertStringContainsString('[NOTE] Checking line items to be updated...', $display);
        self::assertStringContainsString('[WARNING] No line items found with specified criteria.', $display);
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
                    '-s' => '1,2,3',
                ],
                'expectedOutput' => 'Invalid \'line-item-slugs\' option received.',
            ],
            'invalidStartDate' => [
                'parameters' => [
                    '-i' => '1,2,3',
                    '--start-date' => '2020-13-01',
                ],
                'expectedOutput' => '2020-13-01 is an invalid start date.',
            ],
            'invalidEndDate' => [
                'parameters' => [
                    '-i' => '1,2,3',
                    '--end-date' => '2020-13-01',
                ],
                'expectedOutput' => '2020-13-01 is an invalid end date.',
            ],
            'endDateBeforeStartDate' => [
                'parameters' => [
                    '-i' => '1,2,3',
                    '--start-date' => '2020-01-02T00:00:00+0000',
                    '--end-date' => '2020-01-01T00:00:00+0000',
                ],
                'expectedOutput' => 'End date should be later than start date.',
            ],
            'informedBothSlugsAndIds' => [
                'parameters' => [
                    '-i' => '1,2,3',
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
                    '-i' => '1,2,3',
                    '--start-date' => '2020-01-01T00:00:00+0000',
                    '--end-date' => '2020-01-10T00:00:00+0000',
                ],
            ],
            'usingLongIdParameterAndDates' => [
                'parameters' => [
                    '--line-item-ids' => '1,2,3',
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

    public function provideValidParametersWithExistingLineItems(): array
    {
        return [
            'usingIdsAndDates' => [
                'parameters' => [
                    '-i' => '4,5,6',
                    '--start-date' => '2020-01-01T00:00:00+0000',
                    '--end-date' => '2020-01-10T00:00:00+0000',
                ],
            ],
            'usingIdsWithoutDates' => [
                'parameters' => [
                    '-i' => '4,5,6'
                ],
            ],
            'usingIdsAndStartDateOnly' => [
                'parameters' => [
                    '-i' => '4,5,6',
                    '--start-date' => '2020-01-01T00:00:00+0000',
                ],
            ],
            'usingIdsAndEndDateOnly' => [
                'parameters' => [
                    '-i' => '4,5,6',
                    '--end-date' => '2020-01-01T00:00:00+0000',
                ],
            ],
            'usingSlugsAndDates' => [
                'parameters' => [
                    '-s' => 'slug-1,slug-2,slug-qqy',
                    '--start-date' => '2020-01-01T00:00:00+0000',
                    '--end-date' => '2020-01-10T00:00:00+0000',
                ],
            ],
            'usingSlugsWithoutDates' => [
                'parameters' => [
                    '-s' => 'slug-1,slug-2,slug-qqy',
                ],
            ],
            'usingSlugsAndStartDateOnly' => [
                'parameters' => [
                    '-s' => 'slug-1,slug-2,slug-qqy',
                    '--start-date' => '2020-01-01T00:00:00+0000',
                ],
            ],
            'usingSlugsAndEndDateOnly' => [
                'parameters' => [
                    '-s' => 'slug-1,slug-2,slug-qqy',
                    '--end-date' => '2020-01-01T00:00:00+0000',
                ],
            ],
        ];
    }
}
