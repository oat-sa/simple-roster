<?php

declare(strict_types=1);

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

namespace App\Command\Bulk;

use App\Bulk\Operation\BulkOperation;
use App\Bulk\Operation\BulkOperationCollection;
use App\Ingester\Registry\IngesterSourceRegistry;
use App\Service\Bulk\BulkCreateUsersAssignmentsService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class BulkCreateUsersAssignmentsCommand extends AbstractBulkUsersAssignmentsCommand
{
    public const NAME = 'roster:assignments:bulk-create';

    public function __construct(
        IngesterSourceRegistry $ingesterSourceRegistry,
        BulkCreateUsersAssignmentsService $bulkCreateUsersAssignmentService
    ) {
        parent::__construct(self::NAME, $ingesterSourceRegistry, $bulkCreateUsersAssignmentService);
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Responsible for creating user assignments based on user list (Local file, S3 bucket)');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->symfonyStyle->title('Simple Roster - Bulk Assignment Creation');
    }

    protected function process(OutputInterface $output): int
    {
        $this->symfonyStyle->note('Starting assignment creation...');

        try {
            $bulkOperationCollection = new BulkOperationCollection();

            $progressBar = $this->createNewFormattedProgressBar($output);

            $progressBar->setMaxSteps($this->ingesterSource->count());
            $progressBar->start();

            $numberOfProcessedAssignments = 0;
            foreach ($this->ingesterSource->getContent() as $row) {
                $this->validateRow($row);

                $operation = new BulkOperation($row['username'], BulkOperation::TYPE_CREATE);

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
        } catch (Throwable $exception) {
            $this->symfonyStyle->error($exception->getMessage());

            return 1;
        }

        $this->displayResult($numberOfProcessedAssignments);

        return 0;
    }
}
