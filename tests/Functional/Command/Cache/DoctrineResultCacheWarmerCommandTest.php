<?php declare(strict_types=1);
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

namespace App\Tests\Functional\Command\Cache;

use App\Command\Cache\DoctrineResultCacheWarmerCommand;
use App\Generator\UserCacheIdGenerator;
use App\Tests\Traits\DatabaseManualFixturesTrait;
use Doctrine\Common\Cache\Cache;
use InvalidArgumentException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DoctrineResultCacheWarmerCommandTest extends KernelTestCase
{
    use DatabaseManualFixturesTrait;

    /** @var CommandTester */
    private $commandTester;

    /** @var UserCacheIdGenerator */
    private $userCacheIdGenerator;

    /** @var Cache */
    private $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = $this->setUpDatabase();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(DoctrineResultCacheWarmerCommand::NAME));

        $this->loadFixtures([
            __DIR__ . '/../../../../fixtures/100usersWithAssignments.yml',
        ]);

        $this->userCacheIdGenerator = new UserCacheIdGenerator();
        $this->cache = $this->getEntityManager()->getConfiguration()->getResultCacheImpl();
    }

    public function testItThrowsExceptionIfNoConsoleOutputWasFound(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            "Output must be instance of 'Symfony\Component\Console\Output\ConsoleOutputInterface' because of section usage."
        );

        $this->commandTester->execute([]);
    }

    public function testItIteratesThroughAllUsers(): void
    {
        $this->checkCache(false);

        $this->assertEquals(0, $this->commandTester->execute(
            [
                '--batch-size' => '1',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));
        $this->assertStringContainsString(
            '[OK] 100 result cache entries have been successfully warmed up.',
            $this->commandTester->getDisplay()
        );

        $this->assertStringContainsString(
            'Number of warmed up cache entries: 100',
            $this->commandTester->getDisplay()
        );

        $this->checkCache(true);
    }

    public function testIteratesOneUser(): void
    {
        $this->checkCache(false);

        $this->assertEquals(0, $this->commandTester->execute(
            [
                '--batch-size' => '3',
                '--user-ids' => '1,99',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        $this->assertStringContainsString(
            '[OK] 2 result cache entries have been successfully warmed up.',
            $this->commandTester->getDisplay()
        );

        $this->assertStringContainsString(
            'Number of warmed up cache entries: 2',
            $this->commandTester->getDisplay()
        );

        $this->checkCache(true);
    }

    public function testIteratesLineItems(): void
    {
        $this->checkCache(false);

        $this->assertEquals(0, $this->commandTester->execute(
            [
                '--batch-size' => '10',
                '--line-item-ids' => '1,3',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        $display = $this->commandTester->getDisplay();

        $this->assertStringContainsString(
            '[OK] 90 result cache entries have been successfully warmed up.',
            $display
        );

        $this->assertStringContainsString(
            'Number of warmed up cache entries: 90',
            $display
        );

        $this->checkCache(true);
    }

    private function checkCache(bool $empty): void
    {
        $userNames = ['user_1', 'user_99'];
        $keys = array_map(
            function ($userName) {
                return $this->userCacheIdGenerator->generate($userName);
            },
            $userNames
        );

        foreach ($keys as $key) {
            $this->assertEquals($empty, $this->cache->contains($key));
        }
    }

    public function testOutputInCaseOfException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid 'batch-size' argument received.");

        $this->commandTester->execute(
            [
                '--batch-size' => 0,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );
    }

    public function testWrongEmptyUserIdsParameters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Option user-ids is empty. Should contain at least one value');

        $this->commandTester->execute(
            [
                '--user-ids' => '',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );
    }

    public function testWrongEmptyLineItemIdsParameters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Option line-item-ids is empty. Should contain at least one value');

        $this->commandTester->execute(
            [
                '--line-item-ids' => '',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );
    }
}
