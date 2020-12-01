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

use OAT\SimpleRoster\Csv\CsvReaderBuilder;
use OAT\SimpleRoster\DataTransferObject\AssignmentDto;
use OAT\SimpleRoster\DataTransferObject\AssignmentDtoCollection;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Exception\LineItemNotFoundException;
use OAT\SimpleRoster\Ingester\AssignmentIngester;
use OAT\SimpleRoster\Model\LineItemCollection;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Storage\StorageRegistry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class AssignmentIngesterCommand extends AbstractCsvIngesterCommand
{
    public const NAME = 'roster:ingest:assignment';

    /** @var AssignmentIngester */
    private $ingester;

    /** @var LineItemRepository */
    private $lineItemRepository;

    public function __construct(
        CsvReaderBuilder $csvReaderBuilder,
        StorageRegistry $storageRegistry,
        AssignmentIngester $assignmentIngester,
        LineItemRepository $lineItemRepository
    ) {
        $this->csvReaderBuilder = $csvReaderBuilder;
        $this->ingester = $assignmentIngester;
        $this->lineItemRepository = $lineItemRepository;

        parent::__construct($csvReaderBuilder, $storageRegistry);
    }

    protected function getIngesterCommandName(): string
    {
        return self::NAME;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Ingests assignments into the application');
        $this->setHelp(<<<'EOF'
The <info>%command.name%</info> command ingests assignments into the application.

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

        $this->symfonyStyle->title('Simple Roster - Assignment Ingester');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->symfonyStyle->comment('Executing ingestion...');

        try {
            $lineItems = $this->lineItemRepository->findAllAsCollection();

            if ($lineItems->isEmpty()) {
                throw new LineItemNotFoundException("No line items were found in database.");
            }

            $this->progressBar->start();
            $assignmentDtoCollection = new AssignmentDtoCollection();
            $numberOfProcessedRows = 0;
            foreach ($this->csvReader->getRecords() as $rawAssignment) {
                $this->validateRow($rawAssignment, 'username', 'lineItemSlug');

                $numberOfProcessedRows++;

                $assignmentDto = $this->createAssignmentDto(
                    $lineItems,
                    $rawAssignment['lineItemSlug'],
                    $rawAssignment['username']
                );

                $assignmentDtoCollection->add($assignmentDto);

                if ($this->batchProcessable($numberOfProcessedRows)) {
                    if (!$this->isDryRun) {
                        $this->ingester->ingest($assignmentDtoCollection);
                    }

                    $assignmentDtoCollection->clear();
                    $this->progressBar->advance($this->batchSize);
                }
            }

            if (!$this->isDryRun && !$assignmentDtoCollection->isEmpty()) {
                $this->ingester->ingest($assignmentDtoCollection);
            }

            $this->progressBar->finish();

            $this->symfonyStyle->newLine(2);

            $verificationCommentMessage = sprintf(
                'To verify you can run: <options=bold>%s</>',
                'bin/console dbal:run-sql "SELECT COUNT(*) FROM assignments"'
            );

            if ($this->isDryRun) {
                $this->symfonyStyle->warning(
                    sprintf(
                        '[DRY RUN] %s assignments have been successfully ingested.',
                        number_format($numberOfProcessedRows)
                    )
                );

                $this->symfonyStyle->comment($verificationCommentMessage);

                return 0;
            }

            $this->symfonyStyle->success(
                sprintf(
                    '%s assignments have been successfully ingested.',
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

    /**
     * @throws LineItemNotFoundException
     */
    private function createAssignmentDto(
        LineItemCollection $lineItems,
        string $lineItemSlug,
        string $username
    ): AssignmentDto {
        $lineItem = $lineItems->getBySlug($lineItemSlug);

        return new AssignmentDto(Assignment::STATE_READY, (int)$lineItem->getId(), $username);
    }
}
