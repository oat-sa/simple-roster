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

namespace OAT\SimpleRoster\Command\Ingester;

use DateTime;
use OAT\SimpleRoster\Csv\CsvReaderBuilder;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Storage\StorageRegistry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Uid\UuidV6;
use Throwable;

class LineItemIngesterCommand extends AbstractCsvIngesterCommand
{
    public const NAME = 'roster:ingest:line-item';

    /** @var LineItemRepository */
    private LineItemRepository $lineItemRepository;

    public function __construct(
        CsvReaderBuilder $csvReaderBuilder,
        StorageRegistry $storageRegistry,
        LineItemRepository $lineItemRepository
    ) {
        parent::__construct($csvReaderBuilder, $storageRegistry);

        $this->lineItemRepository = $lineItemRepository;
    }

    protected function getIngesterCommandName(): string
    {
        return self::NAME;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Ingests line items into the application');
        $this->setHelp(<<<'EOF'
The <info>%command.name%</info> command ingests line items into the application.

    <info>php %command.full_name% <path></info>

To ingest from a local csv file:

    <info>php %command.full_name% relative/path/to/csv --force</info>

Use the --batch option to use custom batch size for ingestion:

    <info>php %command.full_name% relative/path/to/csv --batch=10000 --force</info>

Use the --storage option to ingest from custom sources other than local filesystem (e.g. S3 bucket)):

    <info>php %command.full_name% relative/path/to/csv --storage=customStorage --force</info>
    <comment>(Documentation: https://github.com/oat-sa/simple-roster/blob/develop/docs/storage-registry.md)</comment>

Use the --delimiter option to define custom csv column delimiter:

    <info>php %command.full_name% relative/path/to/csv --delimiter=| --force</info>
EOF
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->symfonyStyle->title('Simple Roster - Line Item Ingester');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->symfonyStyle->comment('Executing ingestion...');

        try {
            $this->progressBar->start();

            $numberOfProcessedRows = 0;
            foreach ($this->csvReader->getRecords() as $rawLineItem) {
                // Status column in optional for backward compatibility
                $this->validateRow(
                    $rawLineItem,
                    'uri',
                    'label',
                    'slug',
                    'startTimestamp',
                    'endTimestamp',
                    'maxAttempts'
                );

                $numberOfProcessedRows++;

                $this->lineItemRepository->persist($this->createLineItem($rawLineItem));

                if ($this->batchProcessable($numberOfProcessedRows)) {
                    if (!$this->isDryRun) {
                        $this->lineItemRepository->flush();
                    }

                    $this->progressBar->advance($this->batchSize);
                }
            }

            if (!$this->isDryRun) {
                $this->lineItemRepository->flush();
            }

            $this->progressBar->finish();

            $this->symfonyStyle->newLine(2);

            $verificationCommentMessage = sprintf(
                'To verify you can run: <options=bold>%s</>',
                'bin/console dbal:run-sql "SELECT COUNT(*) FROM line_items"'
            );

            if ($this->isDryRun) {
                $this->symfonyStyle->warning(
                    sprintf(
                        '[DRY RUN] %s line items have been successfully ingested.',
                        number_format($numberOfProcessedRows)
                    )
                );

                $this->symfonyStyle->comment($verificationCommentMessage);

                return 0;
            }

            $this->symfonyStyle->success(
                sprintf(
                    '%s line items have been successfully ingested.',
                    number_format($numberOfProcessedRows)
                )
            );

            $this->symfonyStyle->comment($verificationCommentMessage);
        } catch (Throwable $exception) {
            $this->symfonyStyle->error($exception->getMessage());

            return 1;
        }

        return 0;
    }

    private function createLineItem(array $rawLineItem): LineItem
    {
        $startAt = isset($rawLineItem['startTimestamp']) && !(empty($rawLineItem['startTimestamp']))
            ? (new DateTime())->setTimestamp((int)$rawLineItem['startTimestamp'])
            : null;

        $endAt = isset($rawLineItem['endTimestamp']) && !(empty($rawLineItem['endTimestamp']))
            ? (new DateTime())->setTimestamp((int)$rawLineItem['endTimestamp'])
            : null;

        return new LineItem(
            new UuidV6(),
            $rawLineItem['label'],
            $rawLineItem['uri'],
            $rawLineItem['slug'],
            $rawLineItem['status'] ?? LineItem::STATUS_ENABLED,
            (int)$rawLineItem['maxAttempts'],
            $rawLineItem['groupId'] ?? null,
            $startAt,
            $endAt
        );
    }
}
