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
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace App\Tests\Functional\Command\Ingester;

use App\Command\Ingester\IngesterCommand;
use App\Entity\Infrastructure;
use App\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class IngesterCommandTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    /** @var CommandTester */
    private $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $this->setUpDatabase();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(IngesterCommand::NAME));
    }

    public function testDryRunLocalIngestion(): void
    {
        $output = $this->commandTester->execute([
            'type' => 'infrastructure',
            'source' => 'local',
            'path' => __DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv',
        ]);

        $this->assertEquals(0, $output);
        $this->assertStringContainsString(
            "[OK] [DRY_RUN] Ingestion (type='infrastructure', source='local'): 3 successes, 0 failures.",
            $this->commandTester->getDisplay()
        );

        $this->assertEmpty($this->getRepository(Infrastructure::class)->findAll());
    }

    public function testLocalIngestionSuccess(): void
    {
        $output = $this->commandTester->execute([
            'type' => 'infrastructure',
            'source' => 'local',
            'path' => __DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv',
            '--force' => 'true' // Test if it gets casted properly
        ]);

        $this->assertEquals(0, $output);
        $this->assertStringContainsString(
            "[OK] Ingestion (type='infrastructure', source='local'): 3 successes, 0 failures.",
            $this->commandTester->getDisplay()
        );

        $this->assertCount(3, $this->getRepository(Infrastructure::class)->findAll());

        $user1 = $this->getRepository(Infrastructure::class)->find(1);
        $this->assertEquals('infra_1', $user1->getLabel());

        $user2 = $this->getRepository(Infrastructure::class)->find(2);
        $this->assertEquals('infra_2', $user2->getLabel());

        $user3 = $this->getRepository(Infrastructure::class)->find(3);
        $this->assertEquals('infra_3', $user3->getLabel());
    }

    public function testLocalIngestionFailure(): void
    {
        $output = $this->commandTester->execute([
            'type' => 'infrastructure',
            'source' => 'local',
            'path' => __DIR__ . '/../../../Resources/Ingester/Invalid/infrastructures.csv',
            '--force' => true,
        ]);

        $this->assertEquals(0, $output);
        $this->assertStringContainsString(
            "[WARNING] Ingestion (type='infrastructure', source='local'): 1 successes, 1 failures.",
            $this->commandTester->getDisplay()
        );

        $this->assertStringContainsString(
            'Argument 1 passed to App\Entity\Infrastructure::setLtiSecret() must be of the type string, null given',
            $this->commandTester->getDisplay()
        );

        $this->assertCount(1, $this->getRepository(Infrastructure::class)->findAll());

        $user1 = $this->getRepository(Infrastructure::class)->find(1);
        $this->assertEquals('infra_1', $user1->getLabel());
    }

    public function testInvalidIngesterFailure(): void
    {
        $output = $this->commandTester->execute([
            'type' => 'invalid',
            'source' => 'invalid',
            'path' => __DIR__ . '/../../../Resources/Ingester/Invalid/infrastructures.csv',
            '--force' => true,
        ]);

        $this->assertEquals(1, $output);
        $this->assertStringContainsString(
            "[ERROR] Ingester named 'invalid' cannot be found.",
            $this->commandTester->getDisplay()
        );
    }

    public function testInvalidSourceFailure(): void
    {
        $output = $this->commandTester->execute([
            'type' => 'infrastructure',
            'source' => 'invalid',
            'path' => __DIR__ . '/../../../Resources/Ingester/Invalid/infrastructures.csv',
            '--force' => true,
        ]);

        $this->assertEquals(1, $output);
        $this->assertStringContainsString(
            "[ERROR] Ingester source named 'invalid' cannot be found.",
            $this->commandTester->getDisplay()
        );
    }
}
