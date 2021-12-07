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
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Functional\Command\Ingester;

use OAT\SimpleRoster\Command\Ingester\BulkUserCreationCommand;
use OAT\SimpleRoster\Tests\Traits\CommandDisplayNormalizerTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use OAT\SimpleRoster\Tests\Traits\FileRemovalTrait;
use OAT\SimpleRoster\Entity\LtiInstance;
use OAT\SimpleRoster\Entity\LineItem;
use InvalidArgumentException;

class BulkUserCreationCommandTest extends KernelTestCase
{
    use DatabaseTestingTrait;
    use CommandDisplayNormalizerTrait;
    use FileRemovalTrait;
    use LoggerTestingTrait;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(BulkUserCreationCommand::NAME));

        $this->setUpDatabase();
        $this->setUpTestLogHandler();
        $this->removeGeneratedUsersFilePath();
    }

    public function testItCanCreateBulkUserAssignments(): void
    {
        $this->loadFixtureByFilename('lineItemsAndLtiInstances.yml');
        $output = $this->commandTester->execute([
            '-s' => 'slug-qqyw,slug-qwer',
            'user-prefix' => 'QA,LQA',
            '-b' => '4',
            '-g' => 'TestCollege',
        ]);
        self::assertSame(0, $output);
        self::assertStringContainsString(
            'Simple Roster - Bulk User Creation',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );

        self::assertStringContainsString(
            'Executing Bulk user creation',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
        self::assertStringContainsString(
            '[OK] 8 users created for line item slug-qqyw for user prefix QA,LQA',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
        self::assertStringContainsString(
            '8 users created for line item slug-qwer for user prefix QA,LQA',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
    }

    public function testItCanCreateBulkUserAssignmentsWithoutSlugs(): void
    {
        $this->loadFixtureByFilename('lineItemsAndLtiInstances.yml');
        $output = $this->commandTester->execute([
            'user-prefix' => 'QA,LQA',
            '-b' => '4',
            '-g' => 'TestCollege',
        ]);
        self::assertSame(0, $output);

        self::assertStringContainsString(
            '8 users created for line item slug-qqyw for user prefix QA,LQA',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
    }

    public function testItCanCreateBulkUserAssignmentsWithoutBatchSize(): void
    {
        $this->loadFixtureByFilename('lineItemsAndLtiInstances.yml');
        $output = $this->commandTester->execute([
            'user-prefix' => 'QA,LQA',
            '-g' => 'TestCollege',
        ]);
        self::assertSame(0, $output);

        self::assertStringContainsString(
            '200 users created for line item slug-qqyw for user prefix QA,LQA',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
    }

    public function testItCanCreateBulkUserAssignmentsWithLineItemIds(): void
    {
        $this->loadFixtureByFilename('lineItemsAndLtiInstances.yml');
        $output = $this->commandTester->execute([
            '-i' => '1,2',
            'user-prefix' => 'QA,LQA',
            '-b' => '4',
            '-g' => 'TestCollege',
        ]);
        self::assertSame(0, $output);
        self::assertStringContainsString(
            '[OK] 8 users created for line item slug-1 for user prefix QA,LQA',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
    }

    public function testItThrowsExceptionForNonExistSlugs(): void
    {
        $this->loadFixtureByFilename('lineItemsAndLtiInstances.yml');
        $output = $this->commandTester->execute([
            '-s' => 'slug-100,slug-200',
            'user-prefix' => 'QA,LQA',
            '-b' => '4',
            '-g' => 'TestCollege',
        ]);
        self::assertSame(1, $output);
        self::assertStringContainsString(
            '[ERROR] slug-100,slug-200 Line item slug(s) not exist in the system',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
    }

    public function testItThrowsExceptionForNonExistLineItemIds(): void
    {
        $this->loadFixtureByFilename('lineItemsAndLtiInstances.yml');
        $output = $this->commandTester->execute([
            '-i' => '100,1000',
            'user-prefix' => 'QA,LQA',
            '-b' => '4',
            '-g' => 'TestCollege',
        ]);
        self::assertSame(1, $output);
        self::assertStringContainsString(
            '[ERROR] 100,1000 Line item id(s) not exist in the system',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
    }

    public function testItThrowsNoteForNonExistSlug(): void
    {
        $this->loadFixtureByFilename('lineItemsAndLtiInstances.yml');
        $output = $this->commandTester->execute([
            '-s' => 'slug-1,slug-200',
            'user-prefix' => 'QA,LQA',
            '-b' => '4',
            '-g' => 'TestCollege',
        ]);
        self::assertSame(0, $output);
        self::assertStringContainsString(
            '[NOTE] Line Items with slugs/ids \'slug-200\' were not found in the system',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
        self::assertStringContainsString(
            '[OK] 8 users created for line item slug-1 for user prefix QA,LQA',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
    }

    public function testItThrowsExceptionIfNoLtiInstanceAreFound(): void
    {
        $this->loadFixtureByFilename('3LineItems.yml');
        $output = $this->commandTester->execute([
            '-s' => 'slug-1,slug-200',
            'user-prefix' => 'QA,LQA',
            '-b' => '4',
            '-g' => 'TestCollege',
        ]);
        self::assertSame(1, $output);
        self::assertCount(0, $this->getRepository(LtiInstance::class)->findAll());
        self::assertStringContainsString(
            '[ERROR] No Lti instance were found in database.',
            $this->commandTester->getDisplay(true)
        );
    }

    public function testItThrowsExceptionIfNoLineItemsAreFound(): void
    {
        $this->loadFixtureByFilename('5ltiInstances.yml');
        $output = $this->commandTester->execute([
            'user-prefix' => 'QA,LQA',
            '-b' => '4',
            '-g' => 'TestCollege',
        ]);
        self::assertSame(1, $output);
        self::assertCount(0, $this->getRepository(LineItem::class)->findAll());
        self::assertStringContainsString(
            '[ERROR] No line items were found in database.',
            $this->commandTester->getDisplay(true)
        );
    }

    public function testItCreateBulkUserAssignmentsHavingExistingUser(): void
    {
        $this->loadFixtureByFilename('lineItemsAndLtiInstances.yml');
        $output = $this->commandTester->execute([
            '-s' => 'slug-qqyw,slug-qwer',
            'user-prefix' => 'QA,LQA',
            '-b' => '4',
            '-g' => 'TestCollege',
        ]);
        self::assertSame(0, $output);

        self::assertStringContainsString(
            '8 users created for line item slug-qqyw for user prefix QA,LQA',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
        $output = $this->commandTester->execute([
            '-b' => '4',
            'user-prefix' => 'QA,LQA',
            '-g' => 'TestCollege',
        ]);
        self::assertSame(1, $output);
    }

    /**
     * @dataProvider provideInvalidParameters
     */
    public function testItThrowsExceptionForEachInvalidParametersReceived(
        array $parameters,
        string $expectedOutput
    ): void {
        $this->loadFixtureByFilename('lineItemsAndLtiInstances.yml');
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

    public function provideInvalidParameters(): array
    {
        return [
            'invalidLineItemIds' => [
                'parameters' => [
                    '-i' => 'a,b,c',
                ],
                'expectedOutput' => 'Invalid line-item-ids option received.',
            ],
            'invalidLineItemSlugs' => [
                'parameters' => [
                    '-s' => ',',
                ],
                'expectedOutput' => 'Invalid line-item-slugs option received.',
            ],
            'informedBothSlugsAndIds' => [
                'parameters' => [
                    '-i' => '1,2,3',
                    '-s' => 'slug1,slug2,slug3',
                ],
                'expectedOutput' => 'Option line-item-ids and line-item-slugs are exclusive options.',
            ],
            'invalidBatchSize' => [
                'parameters' => [
                    '-b' => '4ab',
                ],
                'expectedOutput' => 'Batch Size should be a valid number',
            ],
            'invalidUserPrefix' => [
                'parameters' => [
                    'user-prefix' => ',',
                ],
                'expectedOutput' => 'User Prefix is a required argument',
            ],
        ];
    }
}
