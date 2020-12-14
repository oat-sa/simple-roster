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

namespace OAT\SimpleRoster\Tests\Functional\Command\Update;

use Carbon\Carbon;
use DateTimeZone;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use InvalidArgumentException;
use LogicException;
use OAT\SimpleRoster\Command\Update\LineItemUpdateDatesCommand;
use OAT\SimpleRoster\Generator\LineItemCacheIdGenerator;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Tests\Traits\CommandDisplayNormalizerTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LineItemUpdateDatesCommandTest extends KernelTestCase
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
        $this->commandTester = new CommandTester($application->find(LineItemUpdateDatesCommand::NAME));

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
    public function testItThrowsExceptionForIfInvalidParametersReceived(array $input, string $expectedOutput): void
    {
        Carbon::setTestNow(Carbon::create(2020, 1, 1, 0, 0, 0, new DateTimeZone('UTC')));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedOutput);

        self::assertSame(0, $this->commandTester->execute(
            $input,
            [
                'capture_stderr_separately' => true,
            ]
        ));

        self::assertStringContainsString($expectedOutput, $this->commandTester->getDisplay());
    }

    public function testItEncapsulatesAnyUnexpectedExceptions(): void
    {
        $kernel = self::bootKernel();

        $lineItemRepository = $this->createMock(LineItemRepository::class);
        $lineItemRepository
            ->method('flush')
            ->willThrowException(new ORMException('ErrorMessage'));

        self::$container->set('test.line_item_repository', $lineItemRepository);

        $application = new Application($kernel);
        $commandTester = new CommandTester($application->find(LineItemUpdateDatesCommand::NAME));
        $input = [
            '-i' => '4,5,6',
            '-f' => null,
        ];

        self::assertSame(1, $commandTester->execute($input, ['capture_stderr_separately' => true]));
        self::assertStringContainsString(
            '[ERROR] An unexpected error occurred: ErrorMessage',
            $commandTester->getDisplay()
        );
    }

    /**
     * @dataProvider getValidParametersWithLineItems
     */
    public function testItUpdateLineItemsWithoutRealUpdatesAndCacheStillTheSame(array $input): void
    {
        $this->loadFixtureByFilename('3LineItems.yml');

        self::assertFalse($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(4)));
        self::assertFalse($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(5)));
        self::assertFalse($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(6)));

        self::assertSame(0, $this->commandTester->execute($input, ['capture_stderr_separately' => true]));

        self::assertStringContainsString(
            '[NOTE] Checking line items to be updated...',
            $this->commandTester->getDisplay()
        );
        self::assertStringContainsString(
            '[OK] [DRY RUN] 3 line item(s) have been updated.',
            $this->commandTester->getDisplay()
        );

        self::assertFalse($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(4)));
        self::assertFalse($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(5)));
        self::assertFalse($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(6)));
    }

    /**
     * @dataProvider getValidParametersWithLineItems
     */
    public function testItUpdateLineItemsWithRealUpdatesAndCacheIsWarmup(array $input): void
    {
        $this->loadFixtureByFilename('3LineItems.yml');

        self::assertFalse($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(4)));
        self::assertFalse($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(5)));
        self::assertFalse($this->resultCache->contains($this->lineItemCacheIdGenerator->generate(6)));

        $input['-f'] = null;
        self::assertSame(0, $this->commandTester->execute($input, ['capture_stderr_separately' => true]));

        $display = $this->commandTester->getDisplay();

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
     * @dataProvider getValidParametersWithoutLineItems
     */
    public function testItWarnsIfNoLineItemWasFound(array $input): void
    {
        $this->loadFixtureByFilename('3LineItems.yml');

        self::assertSame(0, $this->commandTester->execute($input, ['capture_stderr_separately' => true]));

        self::assertStringContainsString(
            '[NOTE] Checking line items to be updated...',
            $this->commandTester->getDisplay()
        );
        self::assertStringContainsString(
            '[WARNING] No line items found with specified criteria.',
            $this->commandTester->getDisplay()
        );
    }

    public function provideInvalidParameters(): array
    {
        $longText = 'End date should be later than start date. Start Date: 2020-01-02 End Date: 2020-01-01.';

        return [
            'noLineItemIdsOrSlugs' => [
                'input' => [
                    '--start-date' => '2020-01-01',
                    '--end-date' => '2020-01-10',
                ],
                'expectedOutput' => 'You need to specify line-item-ids or line-item-slugs option.',
            ],
            'invalidLineItemIds' => [
                'input' => [
                    '-i' => 'a,b,c',
                ],
                'expectedOutput' => 'Invalid \'line-item-ids\' option received.',
            ],
            'invalidLineItemSlugs' => [
                'input' => [
                    '-s' => '1,2,3',
                ],
                'expectedOutput' => 'Invalid \'line-item-slugs\' option received.',
            ],
            'invalidStartDate' => [
                'input' => [
                    '-i' => '1,2,3',
                    '--start-date' => '2020-13-01',
                ],
                'expectedOutput' => '2020-13-01 is an invalid start date. Expected format: 2020-01-01T00:00:00+0000',
            ],
            'invalidEndDate' => [
                'input' => [
                    '-i' => '1,2,3',
                    '--end-date' => '2020-13-01',
                ],
                'expectedOutput' => '2020-13-01 is an invalid end date. Expected format: 2020-01-01T00:00:00+0000',
            ],
            'endDateBeforeStartDate' => [
                'input' => [
                    '-i' => '1,2,3',
                    '--start-date' => '2020-01-02',
                    '--end-date' => '2020-01-01',
                ],
                'expectedOutput' => $longText,
            ],
            'informedBothSlugsAndIds' => [
                'input' => [
                    '-i' => '1,2,3',
                    '-s' => 'slug1,slug2,slug3',
                ],
                'expectedOutput' => 'Option \'line-item-ids\' and \'line-item-slugs\' are exclusive options.',
            ],
        ];
    }

    public function getValidParametersWithoutLineItems(): array
    {
        return [
            'usingIdsAndDates' => [
                'input' => [
                    '-i' => '1,2,3',
                    '--start-date' => '2020-01-01',
                    '--end-date' => '2020-01-10',
                ],
            ],
            'usingSlugsAndDates' => [
                'input' => [
                    '-s' => 'slug1,slug2,slug3',
                    '--start-date' => '2020-01-01',
                    '--end-date' => '2020-01-10',
                ],
            ],
        ];
    }

    public function getValidParametersWithLineItems(): array
    {
        return [
            'usingIdsAndDates' => [
                'input' => [
                    '-i' => '4,5,6',
                    '--start-date' => '2020-01-01',
                    '--end-date' => '2020-01-10',
                ],
            ],
            'usingIdsWithoutDates' => [
                'input' => [
                    '-i' => '4,5,6'
                ],
            ],
            'usingIdsAndStartDateOnly' => [
                'input' => [
                    '-i' => '4,5,6',
                    '--start-date' => '2020-01-01',
                ],
            ],
            'usingIdsAndEndDateOnly' => [
                'input' => [
                    '-i' => '4,5,6',
                    '--end-date' => '2020-01-01',
                ],
            ],
            'usingSlugsAndDates' => [
                'input' => [
                    '-s' => 'slug-1,slug-2,slug-qqy',
                    '--start-date' => '2020-01-01',
                    '--end-date' => '2020-01-10',
                ],
            ],
            'usingSlugsWithoutDates' => [
                'input' => [
                    '-s' => 'slug-1,slug-2,slug-qqy',
                ],
            ],
            'usingSlugsAndStartDateOnly' => [
                'input' => [
                    '-s' => 'slug-1,slug-2,slug-qqy',
                    '--start-date' => '2020-01-01',
                ],
            ],
            'usingSlugsAndEndDateOnly' => [
                'input' => [
                    '-s' => 'slug-1,slug-2,slug-qqy',
                    '--end-date' => '2020-01-01',
                ],
            ],
        ];
    }
}
