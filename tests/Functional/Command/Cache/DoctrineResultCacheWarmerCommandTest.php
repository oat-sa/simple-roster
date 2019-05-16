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
use App\Tests\Traits\DatabaseManualFixturesTrait;
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

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = $this->setUpDatabase();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(DoctrineResultCacheWarmerCommand::NAME));

        $this->loadFixtures([
            __DIR__ . '/../../../../fixtures/100usersWithAssignments.yml',
        ]);
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
}
