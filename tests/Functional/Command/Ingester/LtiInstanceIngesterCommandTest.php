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

namespace OAT\SimpleRoster\Tests\Functional\Command\Ingester;

use OAT\SimpleRoster\Command\Ingester\LtiInstanceIngesterCommand;
use OAT\SimpleRoster\Entity\LtiInstance;
use OAT\SimpleRoster\Tests\Traits\CommandDisplayNormalizerTrait;
use OAT\SimpleRoster\Tests\Traits\CsvIngestionTestingTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use ReflectionException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LtiInstanceIngesterCommandTest extends KernelTestCase
{
    use DatabaseTestingTrait;
    use CsvIngestionTestingTrait;
    use CommandDisplayNormalizerTrait;

    /** @var CommandTester */
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(LtiInstanceIngesterCommand::NAME));

        $this->setUpDatabase();
    }

    /**
     * @throws ReflectionException
     */
    public function testItDoesNotIngestInDryRun(): void
    {
        $ltiInstancesCsvContent = [
            ['label', 'ltiLink', 'ltiKey', 'ltiSecret'],
            ['infra_1', 'http://infra_1.com', 'key1', 'secret1'],
            ['infra_2', 'http://infra_2.com', 'key2', 'secret2'],
            ['infra_3', 'http://infra_3.com', 'key3', 'secret3'],
            ['infra_4', 'http://infra_4.com', 'key4', 'secret4'],
            ['infra_5', 'http://infra_5.com', 'key5', 'secret5'],
        ];

        $this->writeCsv('lti-instances.csv', $ltiInstancesCsvContent);

        $output = $this->commandTester->execute(
            [
                'path' => 'lti-instances.csv',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(0, $output);
        self::assertCount(0, $this->getRepository(LtiInstance::class)->findAll());

        self::assertStringContainsString(
            'Simple Roster - LTI Instance Ingester',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
        self::assertStringContainsString(
            'Executing ingestion...',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
        self::assertStringContainsString(
            '[WARNING] [DRY RUN] 5 LTI instances have been successfully ingested.',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testSuccessfulBatchedIngestion(): void
    {
        $ltiInstancesCsvContent = [
            ['label', 'ltiLink', 'ltiKey', 'ltiSecret'],
            ['infra_1', 'http://infra_1.com', 'key1', 'secret1'],
            ['infra_2', 'http://infra_2.com', 'key2', 'secret2'],
            ['infra_3', 'http://infra_3.com', 'key3', 'secret3'],
            ['infra_4', 'http://infra_4.com', 'key4', 'secret4'],
            ['infra_5', 'http://infra_5.com', 'key5', 'secret5'],
        ];

        $this->writeCsv('lti-instances.csv', $ltiInstancesCsvContent);

        $output = $this->commandTester->execute(
            [
                'path' => 'lti-instances.csv',
                '--batch' => 2,
                '--force' => true,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(0, $output);
        self::assertCount(5, $this->getRepository(LtiInstance::class)->findAll());
        self::assertStringContainsString(
            '[OK] 5 LTI instances have been successfully ingested.',
            $this->commandTester->getDisplay(true)
        );
    }

    /**
     * @dataProvider provideInvalidSourceFiles
     */
    public function testSourceFileValidation(string $filename, array $csvContent, string $expectedOutput): void
    {
        $this->writeCsv($filename, $csvContent);

        $output = $this->commandTester->execute(
            [
                'path' => $filename,
                '--batch' => 2,
                '--force' => true,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(1, $output);
        self::assertStringContainsString($expectedOutput, $this->commandTester->getDisplay(true));
    }

    public function provideInvalidSourceFiles(): array
    {
        return [
            'labelColumnIsMissing' => [
                'filename' => 'lti-instances-without-label-column.csv',
                'csvContent' => [
                    ['ltiLink', 'ltiKey', 'ltiSecret'],
                    ['http://infra_1.com', 'key1', 'secret1'],
                    ['http://infra_2.com', 'key2', 'secret2'],
                    ['http://infra_3.com', 'key3', 'secret3'],
                    ['http://infra_4.com', 'key4', 'secret4'],
                    ['http://infra_5.com', 'key5', 'secret5'],
                ],
                'expectedOutput' => "[ERROR] Column 'label' is not set in source file.",
            ],
            'ltiLinkColumnIsMissing' => [
                'filename' => 'lti-instances-without-ltiLink-column.csv',
                'csvContent' => [
                    ['label', 'ltiKey', 'ltiSecret'],
                    ['infra_1', 'key1', 'secret1'],
                    ['infra_2', 'key2', 'secret2'],
                    ['infra_3', 'key3', 'secret3'],
                    ['infra_4', 'key4', 'secret4'],
                    ['infra_5', 'key5', 'secret5'],
                ],
                'expectedOutput' => "[ERROR] Column 'ltiLink' is not set in source file.",
            ],
            'ltiKeyColumnIsMissing' => [
                'filename' => 'lti-instances-without-ltiKey-column.csv',
                'csvContent' => [
                    ['label', 'ltiLink', 'ltiSecret'],
                    ['infra_1', 'http://infra_1.com', 'secret1'],
                    ['infra_2', 'http://infra_2.com', 'secret2'],
                    ['infra_3', 'http://infra_3.com', 'secret3'],
                    ['infra_4', 'http://infra_4.com', 'secret4'],
                    ['infra_5', 'http://infra_5.com', 'secret5'],
                ],
                'expectedOutput' => "[ERROR] Column 'ltiKey' is not set in source file.",
            ],
            'ltiSecretColumnIsMissing' => [
                'filename' => 'lti-instances-without-ltiSecret-column.csv',
                'csvContent' => [
                    ['label', 'ltiLink', 'ltiKey'],
                    ['infra_1', 'http://infra_1.com', 'key1'],
                    ['infra_2', 'http://infra_2.com', 'key2'],
                    ['infra_3', 'http://infra_3.com', 'key3'],
                    ['infra_4', 'http://infra_4.com', 'key4'],
                    ['infra_5', 'http://infra_5.com', 'key5'],
                ],
                'expectedOutput' => "[ERROR] Column 'ltiSecret' is not set in source file.",
            ],
        ];
    }
}
