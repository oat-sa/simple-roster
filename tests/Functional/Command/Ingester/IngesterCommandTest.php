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

namespace OAT\SimpleRoster\Tests\Functional\Command\Ingester;

use OAT\SimpleRoster\Command\Ingester\IngesterCommand;
use OAT\SimpleRoster\Entity\LtiInstance;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
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
            'type' => 'lti-instance',
            'source' => 'local',
            'path' => __DIR__ . '/../../../Resources/Ingester/Valid/lti-instances.csv',
        ]);

        self::assertSame(0, $output);
        self::assertStringContainsString(
            "[OK] [DRY_RUN] Ingestion (type='lti-instance', source='local'): 3 successes, 0 failures.",
            $this->normalizeDisplay($this->commandTester->getDisplay())
        );

        self::assertEmpty($this->getRepository(LtiInstance::class)->findAll());
    }

    public function testLocalIngestionSuccess(): void
    {
        $output = $this->commandTester->execute([
            'type' => 'lti-instance',
            'source' => 'local',
            'path' => __DIR__ . '/../../../Resources/Ingester/Valid/lti-instances.csv',
            '--force' => 'true' // Test if it gets casted properly
        ]);

        self::assertSame(0, $output);
        self::assertStringContainsString(
            "[OK] Ingestion (type='lti-instance', source='local'): 3 successes, 0 failures.",
            $this->normalizeDisplay($this->commandTester->getDisplay())
        );

        self::assertCount(3, $this->getRepository(LtiInstance::class)->findAll());

        $user1 = $this->getRepository(LtiInstance::class)->find(1);
        self::assertSame('infra_1', $user1->getLabel());

        $user2 = $this->getRepository(LtiInstance::class)->find(2);
        self::assertSame('infra_2', $user2->getLabel());

        $user3 = $this->getRepository(LtiInstance::class)->find(3);
        self::assertSame('infra_3', $user3->getLabel());
    }

    public function testLocalIngestionFailure(): void
    {
        $output = $this->commandTester->execute([
            'type' => 'lti-instance',
            'source' => 'local',
            'path' => __DIR__ . '/../../../Resources/Ingester/Invalid/lti-instances.csv',
            '--force' => true,
        ]);

        self::assertSame(0, $output);
        self::assertStringContainsString(
            "[WARNING] Ingestion (type='lti-instance', source='local'): 1 successes, 1 failures.",
            $this->normalizeDisplay($this->commandTester->getDisplay())
        );

        $errorMessage = 'Argument 5 passed to OAT\SimpleRoster\Entity\LtiInstance::__construct() '
            . 'must be of the type string, null given';

        self::assertStringContainsString(
            $errorMessage,
            $this->commandTester->getDisplay()
        );

        self::assertCount(1, $this->getRepository(LtiInstance::class)->findAll());

        $user1 = $this->getRepository(LtiInstance::class)->find(1);
        self::assertSame('infra_1', $user1->getLabel());
    }

    public function testInvalidIngesterFailure(): void
    {
        $output = $this->commandTester->execute([
            'type' => 'invalid',
            'source' => 'invalid',
            'path' => __DIR__ . '/../../../Resources/Ingester/Invalid/lti-instances.csv',
            '--force' => true,
        ]);

        self::assertSame(1, $output);
        self::assertStringContainsString(
            "[ERROR] Ingester named 'invalid' cannot be found.",
            $this->commandTester->getDisplay()
        );
    }

    public function testInvalidSourceFailure(): void
    {
        $output = $this->commandTester->execute([
            'type' => 'lti-instance',
            'source' => 'invalid',
            'path' => __DIR__ . '/../../../Resources/Ingester/Invalid/lti-instances.csv',
            '--force' => true,
        ]);

        self::assertSame(1, $output);
        self::assertStringContainsString(
            "[ERROR] Ingester source named 'invalid' cannot be found.",
            $this->commandTester->getDisplay()
        );
    }

    /**
     * Without this tests asserting the command display are failing with plain phpunit (so NOT with bin/phpunit)
     * due to new line/tab characters. This modification does NOT affect bin/phpunit usage.
     */
    private function normalizeDisplay(string $commandDisplay): string
    {
        return trim((string)preg_replace('/\s+/', ' ', $commandDisplay));
    }
}
