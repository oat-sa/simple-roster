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

namespace OAT\SimpleRoster\Command\Bulk;

use OAT\SimpleRoster\Bulk\Operation\BulkOperation;
use OAT\SimpleRoster\Bulk\Operation\BulkOperationCollection;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Ingester\Registry\IngesterSourceRegistry;
use OAT\SimpleRoster\Service\Bulk\BulkUpdateUsersAssignmentsStateService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class BulkCancelUsersAssignmentsCommand extends AbstractBulkUsersAssignmentsCommand
{
    public const NAME = 'roster:assignments:bulk-cancel';

    public function __construct(
        IngesterSourceRegistry $ingesterSourceRegistry,
        BulkUpdateUsersAssignmentsStateService $bulkAssignmentsUpdateService
    ) {
        parent::__construct(self::NAME, $ingesterSourceRegistry, $bulkAssignmentsUpdateService);
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Responsible for cancelling user assignments based on user list (Local file, S3 bucket)');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->symfonyStyle->title('Simple Roster - Bulk Assignment Cancellation');
    }


    protected function process(OutputInterface $output): int
    {
        $this->symfonyStyle->note('Starting assignment cancellation...');

        try {
            $bulkOperationCollection = new BulkOperationCollection();

            $progressBar = $this->createNewFormattedProgressBar($output);

            $progressBar->setMaxSteps($this->ingesterSource->count());
            $progressBar->start();

            $numberOfProcessedAssignments = 0;
            foreach ($this->ingesterSource->getContent() as $row) {
                $this->validateRow($row);

                $operation = new BulkOperation(
                    $row['username'],
                    BulkOperation::TYPE_UPDATE,
                    ['state' => Assignment::STATE_CANCELLED]
                );

                $operation->setIsDryRun($this->isDryRun);

                $bulkOperationCollection->add($operation);
                $operationCount = count($bulkOperationCollection);

                if ($operationCount % $this->batchSize !== 0) {
                    continue;
                }

                $numberOfProcessedAssignments += $operationCount;
                $this->processOperationCollection($bulkOperationCollection);

                $progressBar->advance($this->batchSize);
            }

            // Process remaining operations
            if (!$bulkOperationCollection->isEmpty()) {
                $remainingAssignmentsToProcess = count($bulkOperationCollection);
                $numberOfProcessedAssignments += $remainingAssignmentsToProcess;

                $this->processOperationCollection($bulkOperationCollection);
                $progressBar->advance($remainingAssignmentsToProcess);
            }

            $progressBar->finish();
        } catch (Throwable $exception) {
            $this->symfonyStyle->error($exception->getMessage());

            return 1;
        }

        $this->displayResult($numberOfProcessedAssignments);

        return 0;
    }
}
