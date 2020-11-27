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

use OAT\SimpleRoster\Command\Ingester\LineItemIngesterCommand;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Tests\Traits\CommandDisplayNormalizerTrait;
use OAT\SimpleRoster\Tests\Traits\CsvIngestionTestingTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use ReflectionException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LineItemIngesterCommandTest extends KernelTestCase
{
    use DatabaseTestingTrait;
    use CsvIngestionTestingTrait;
    use CommandDisplayNormalizerTrait;

    /** @var CommandTester */
    private $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(LineItemIngesterCommand::NAME));

        $this->setUpDatabase();
    }

    /**
     * @throws ReflectionException
     */
    public function testItDoesNotIngestInDryRun(): void
    {
        $lineItemsCsvContent = [
            ['uri', 'label', 'slug', 'startTimestamp', 'endTimestamp', 'maxAttempts'],
            ['http://taoplatform.loc/delivery_1.rdf', 'label1', 'gra13_ita_1', 1546682400, 1546713000, 1],
            ['http://taoplatform.loc/delivery_2.rdf', 'label2', 'gra13_ita_2', 1546682400, 1546713000, 2],
            ['http://taoplatform.loc/delivery_3.rdf', 'label2', 'gra13_ita_3', 1546682400, 1546713000, 1],
            ['http://taoplatform.loc/delivery_4.rdf', 'label4', 'gra13_ita_4', 1546682400, 1546713000, 2],
            ['http://taoplatform.loc/delivery_5.rdf', 'label5', 'gra13_ita_5', 1546682400, 1546713000, 1],
            ['http://taoplatform.loc/delivery_6.rdf', 'label6', 'gra13_ita_6', 1546682400, 1546713000, 2],
        ];

        $this->writeCsv('line-items.csv', $lineItemsCsvContent);

        $output = $this->commandTester->execute(
            [
                'path' => 'line-items.csv',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(0, $output);
        self::assertCount(0, $this->getRepository(LineItem::class)->findAll());

        self::assertStringContainsString(
            'Simple Roster - Line Item Ingester',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
        self::assertStringContainsString(
            'Executing ingestion...',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
        self::assertStringContainsString(
            '[WARNING] [DRY RUN] 6 line items have been successfully ingested.',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
        self::assertStringContainsString(
            'To verify you can run: bin/console dbal:run-sql "SELECT COUNT(*) FROM line_items"',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testSuccessfulBatchedIngestion(): void
    {
        $lineItemsCsvContent = [
            ['uri', 'label', 'slug', 'startTimestamp', 'endTimestamp', 'maxAttempts'],
            ['http://taoplatform.loc/delivery_1.rdf', 'label1', 'gra13_ita_1', 1546682400, 1546713000, 1],
            ['http://taoplatform.loc/delivery_2.rdf', 'label2', 'gra13_ita_2', 1546682400, 1546713000, 2],
            ['http://taoplatform.loc/delivery_3.rdf', 'label2', 'gra13_ita_3', 1546682400, 1546713000, 1],
            ['http://taoplatform.loc/delivery_4.rdf', 'label4', 'gra13_ita_4', 1546682400, 1546713000, 2],
            ['http://taoplatform.loc/delivery_5.rdf', 'label5', 'gra13_ita_5', 1546682400, 1546713000, 1],
            ['http://taoplatform.loc/delivery_6.rdf', 'label6', 'gra13_ita_6', 1546682400, 1546713000, 2],
        ];

        $this->writeCsv('line-items.csv', $lineItemsCsvContent);

        $output = $this->commandTester->execute(
            [
                'path' => 'line-items.csv',
                '--batch' => 4,
                '--force' => true,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(0, $output);
        self::assertCount(6, $this->getRepository(LineItem::class)->findAll());
        self::assertStringContainsString(
            '[OK] 6 line items have been successfully ingested.',
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
                '--batch' => 3,
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
            'uriColumnIsMissing' => [
                'filename' => 'line-items-without-uri-column.csv',
                'csvContent' => [
                    ['label', 'slug', 'startTimestamp', 'endTimestamp', 'maxAttempts'],
                    ['label1', 'gra13_ita_1', 1546682400, 1546713000, 1],
                    ['label2', 'gra13_ita_2', 1546682400, 1546713000, 2],
                    ['label2', 'gra13_ita_3', 1546682400, 1546713000, 1],
                    ['label4', 'gra13_ita_4', 1546682400, 1546713000, 2],
                    ['label5', 'gra13_ita_5', 1546682400, 1546713000, 1],
                    ['label6', 'gra13_ita_6', 1546682400, 1546713000, 2],
                ],
                'expectedOutput' => "[ERROR] Column 'uri' is not set in source file.",
            ],
            'labelColumnIsMissing' => [
                'filename' => 'line-items-without-label-column.csv',
                'csvContent' => [
                    ['uri', 'slug', 'startTimestamp', 'endTimestamp', 'maxAttempts'],
                    ['http://taoplatform.loc/delivery_1.rdf', 'gra13_ita_1', 1546682400, 1546713000, 1],
                    ['http://taoplatform.loc/delivery_2.rdf', 'gra13_ita_2', 1546682400, 1546713000, 2],
                    ['http://taoplatform.loc/delivery_3.rdf', 'gra13_ita_3', 1546682400, 1546713000, 1],
                    ['http://taoplatform.loc/delivery_4.rdf', 'gra13_ita_4', 1546682400, 1546713000, 2],
                    ['http://taoplatform.loc/delivery_5.rdf', 'gra13_ita_5', 1546682400, 1546713000, 1],
                    ['http://taoplatform.loc/delivery_6.rdf', 'gra13_ita_6', 1546682400, 1546713000, 2],
                ],
                'expectedOutput' => "[ERROR] Column 'label' is not set in source file.",
            ],
            'slugColumnIsMissing' => [
                'filename' => 'line-items-without-slug-column.csv',
                'csvContent' => [
                    ['uri', 'label', 'startTimestamp', 'endTimestamp', 'maxAttempts'],
                    ['http://taoplatform.loc/delivery_1.rdf', 'label1', 1546682400, 1546713000, 1],
                    ['http://taoplatform.loc/delivery_2.rdf', 'label2', 1546682400, 1546713000, 2],
                    ['http://taoplatform.loc/delivery_3.rdf', 'label2', 1546682400, 1546713000, 1],
                    ['http://taoplatform.loc/delivery_4.rdf', 'label4', 1546682400, 1546713000, 2],
                    ['http://taoplatform.loc/delivery_5.rdf', 'label5', 1546682400, 1546713000, 1],
                    ['http://taoplatform.loc/delivery_6.rdf', 'label6', 1546682400, 1546713000, 2],
                ],
                'expectedOutput' => "[ERROR] Column 'slug' is not set in source file.",
            ],
            'startTimestampColumnIsMissing' => [
                'filename' => 'line-items-without-startTimestamp-column.csv',
                'csvContent' => [
                    ['uri', 'label', 'slug', 'endTimestamp', 'maxAttempts'],
                    ['http://taoplatform.loc/delivery_1.rdf', 'label1', 'gra13_ita_1', 1546713000, 1],
                    ['http://taoplatform.loc/delivery_2.rdf', 'label2', 'gra13_ita_2', 1546713000, 2],
                    ['http://taoplatform.loc/delivery_3.rdf', 'label2', 'gra13_ita_3', 1546713000, 1],
                    ['http://taoplatform.loc/delivery_4.rdf', 'label4', 'gra13_ita_4', 1546713000, 2],
                    ['http://taoplatform.loc/delivery_5.rdf', 'label5', 'gra13_ita_5', 1546713000, 1],
                    ['http://taoplatform.loc/delivery_6.rdf', 'label6', 'gra13_ita_6', 1546713000, 2],
                ],
                'expectedOutput' => "[ERROR] Column 'startTimestamp' is not set in source file.",
            ],
            'endTimestampColumnIsMissing' => [
                'filename' => 'line-items-without-endTimestamp-column.csv',
                'csvContent' => [
                    ['uri', 'label', 'slug', 'startTimestamp', 'maxAttempts'],
                    ['http://taoplatform.loc/delivery_1.rdf', 'label1', 'gra13_ita_1', 1546682400, 1],
                    ['http://taoplatform.loc/delivery_2.rdf', 'label2', 'gra13_ita_2', 1546682400, 2],
                    ['http://taoplatform.loc/delivery_3.rdf', 'label2', 'gra13_ita_3', 1546682400, 1],
                    ['http://taoplatform.loc/delivery_4.rdf', 'label4', 'gra13_ita_4', 1546682400, 2],
                    ['http://taoplatform.loc/delivery_5.rdf', 'label5', 'gra13_ita_5', 1546682400, 1],
                    ['http://taoplatform.loc/delivery_6.rdf', 'label6', 'gra13_ita_6', 1546682400, 2],
                ],
                'expectedOutput' => "[ERROR] Column 'endTimestamp' is not set in source file.",
            ],
            'maxAttemptsColumnIsMissing' => [
                'filename' => 'line-items-without-maxAttempts-column.csv',
                'csvContent' => [
                    ['uri', 'label', 'slug', 'startTimestamp', 'endTimestamp'],
                    ['http://taoplatform.loc/delivery_1.rdf', 'label1', 'gra13_ita_1', 1546682400, 1546713000],
                    ['http://taoplatform.loc/delivery_2.rdf', 'label2', 'gra13_ita_2', 1546682400, 1546713000],
                    ['http://taoplatform.loc/delivery_3.rdf', 'label2', 'gra13_ita_3', 1546682400, 1546713000],
                    ['http://taoplatform.loc/delivery_4.rdf', 'label4', 'gra13_ita_4', 1546682400, 1546713000],
                    ['http://taoplatform.loc/delivery_5.rdf', 'label5', 'gra13_ita_5', 1546682400, 1546713000],
                    ['http://taoplatform.loc/delivery_6.rdf', 'label6', 'gra13_ita_6', 1546682400, 1546713000],
                ],
                'expectedOutput' => "[ERROR] Column 'maxAttempts' is not set in source file.",
            ],
        ];
    }
}
