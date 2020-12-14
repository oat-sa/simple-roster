<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Functional\Command\Activator;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use LogicException;
use Monolog\Logger;
use OAT\SimpleRoster\Command\Activator\LineItemActivatorCommand;
use OAT\SimpleRoster\Generator\LineItemCacheIdGenerator;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Tests\Traits\CommandDisplayNormalizerTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LineItemActivatorCommandTest extends KernelTestCase
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

    /** @var LineItemRepository */
    private $lineItemRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(LineItemActivatorCommand::NAME));

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
        $this->loadFixtureByFilename('100usersWithAssignments.yml');
    }

    /**
     * @dataProvider provideValidArguments
     */
    public function testItDeactivatesLineItem(array $lineItemIds, string $queryField, string $queryValue): void
    {
        foreach ($lineItemIds as $lineItemId) {
            $lineItem = $this->lineItemRepository->findOneById($lineItemId);
            self::assertTrue($lineItem->isActive());

            $lineItemCache = $this->resultCache->fetch($this->lineItemCacheIdGenerator->generate($lineItemId));
            $cache = current(current($lineItemCache));
            self::assertEquals(1, $cache['is_active_3']);
        }

        $commandResult = $this->commandTester->execute(
            [
                'toggle' => 'deactivate',
                'query-field' => $queryField,
                'query-value' => $queryValue
            ],
            ['capture_stderr_separately' => true]
        );
        self::assertEquals(0, $commandResult);

        $this->assertMessageDisplays($lineItemIds, 'deactivate');

        $this->assertLineItems($lineItemIds, 'deactivate', false);
    }

    /**
     * @dataProvider provideValidArguments
     */
    public function testItActivatesLineItem(array $lineItemIds, string $queryField, string $queryValue): void
    {
        $this->commandTester->execute(
            [
                'toggle' => 'deactivate',
                'query-field' => $queryField,
                'query-value' => $queryValue,
            ],
            ['capture_stderr_separately' => true]
        );

        $this->assertLineItems($lineItemIds, 'deactivate', false);

        $commandResult = $this->commandTester->execute(
            [
                'toggle' => 'activate',
                'query-field' => $queryField,
                'query-value' => $queryValue
            ],
            ['capture_stderr_separately' => true]
        );
        self::assertEquals(0, $commandResult);

        $this->assertMessageDisplays($lineItemIds, 'activate');

        $this->assertLineItems($lineItemIds, 'activate', true);
    }

    public function testEnsureOnlyOneLineIsDeactivated(): void
    {
        $secondLineItem = $this->lineItemRepository->findOneById(2);
        self::assertTrue($secondLineItem->isActive());

        $this->commandTester->execute(
            [
                'toggle' => 'deactivate',
                'query-field' => 'id',
                'query-value' => 1,
            ],
            ['capture_stderr_separately' => true]
        );
        $firstLineItem = $this->lineItemRepository->findOneById(1);
        self::assertFalse($firstLineItem->isActive());

        $secondLineItem = $this->lineItemRepository->findOneById(2);
        self::assertTrue($secondLineItem->isActive());
    }

    /**
     * @dataProvider provideInvalidArguments
     */
    public function testItDisplayErrorForInvalidArguments(array $arguments, string $expectedMessage): void
    {
        $this->expectExceptionMessage($expectedMessage);

        $this->commandTester->execute(
            $arguments,
            ['capture_stderr_separately' => true]
        );

        self::assertStringContainsString(
            'Simple Roster - Line Item Activator',
            $this->commandTester->getDisplay()
        );
    }

    public function testItDisplaysErrorInCaseOfUnknownIssue(): void
    {
        $kernel = self::bootKernel();

        $lineItemRepository = $this->createMock(LineItemRepository::class);
        $lineItemRepository->expects($this->once())
            ->method('findBy')
            ->with(['id' => 1])
            ->willThrowException(new Exception('Database Error'));

        self::$container->set('test.line_item_repository', $lineItemRepository);

        $application = new Application($kernel);
        $commandTester = new CommandTester($application->find(LineItemActivatorCommand::NAME));

        $commandTester->execute(
            ['toggle' => 'deactivate', 'query-field' => 'id', 'query-value' => '1'],
            ['capture_stderr_separately' => true]
        );

        self::assertStringContainsString('[ERROR] Database Error', $commandTester->getDisplay());
    }

    public function provideValidArguments(): array
    {
        return [
            'bySlug' => [
                'line-item-ids' => [2],
                'query-field' => 'slug',
                'query-value' => 'lineItemSlug2',
            ],
            'byId' => [
                'line-item-ids' => [1],
                'query-field' => 'id',
                'query-value' => '1',
            ],
            'byUri' => [
                'line-item-ids' => [1, 2, 3],
                'query-field' => 'uri',
                'query-value' => 'http://lineitemuri.com',
            ],
        ];
    }

    public function provideInvalidArguments(): array
    {
        return [
            'invalidToggle' => [
                'arguments' => [
                    'toggle' => 'invalidToggle',
                    'query-field' => 'id',
                    'query-value' => '1',
                ],
                'expectedMessage' => 'Invalid toggle argument. Please use: activate, deactivate',
            ],
            'invalidQueryField' => [
                'arguments' => [
                    'toggle' => 'activate',
                    'query-field' => 'invalid-query-field',
                    'query-value' => '1',
                ],
                'expectedMessage' => 'Invalid query-field argument. Please use: id, slug, uri',
            ],
            'empty' => [
                'arguments' => [],
                'expectedMessage' => 'Not enough arguments (missing: "toggle, query-field, query-value")',
            ],
            'only-toggle' => [
                'arguments' => [
                    'toggle' => 'activate',
                ],
                'expectedMessage' => 'Not enough arguments (missing: "query-field, query-value")',
            ],
            'with-no-query-value' => [
                'arguments' => [
                    'toggle' => 'activate',
                    'query-field' => 'uri',
                ],
                'expectedMessage' => 'Not enough arguments (missing: "query-value")',
            ]
        ];
    }

    private function assertMessageDisplays(array $lineItemIds, string $toggle): void
    {
        self::assertStringContainsString(
            'Simple Roster - Line Item Activator',
            $this->commandTester->getDisplay()
        );
        self::assertStringContainsString(
            sprintf('Executing %s...', ucfirst($toggle)),
            $this->commandTester->getDisplay()
        );
        self::assertStringContainsString(
            sprintf('[OK] The operation: "%s" was executed for "%d" Line Item(s).', $toggle, count($lineItemIds)),
            $this->normalizeDisplay($this->commandTester->getDisplay())
        );
    }

    private function assertLineItems(array $lineItemIds, string $toggle, bool $isActive): void
    {
        foreach ($lineItemIds as $lineItemId) {
            $this->assertHasLogRecord(
                [
                    'message' => sprintf(
                        'The operation: "%s" was executed for Line Item with id: "%d"',
                        $toggle,
                        $lineItemId
                    ),
                    'context' => [
                        'slug' => sprintf('lineItemSlug%d', $lineItemId),
                        'uri' => 'http://lineitemuri.com',
                    ],
                ],
                Logger::INFO
            );

            $lineItem = $this->lineItemRepository->findOneById($lineItemId);

            self::assertEquals($isActive, $lineItem->isActive());

            $cache = current(
                current($this->resultCache->fetch($this->lineItemCacheIdGenerator->generate($lineItemId)))
            );
            self::assertEquals((int)$isActive, $cache['is_active_3']);
        }
    }
}
