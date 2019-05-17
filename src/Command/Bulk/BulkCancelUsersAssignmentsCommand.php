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

namespace App\Command\Bulk;

use App\Bulk\Operation\BulkOperation;
use App\Bulk\Operation\BulkOperationCollection;
use App\Entity\Assignment;
use App\Ingester\Registry\IngesterSourceRegistry;
use App\Service\Bulk\BulkUpdateUsersAssignmentsStateService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
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

    protected function configure()
    {
        parent::configure();

        $this->setDescription('Responsible for cancelling user assignments based on user list (Local file, S3 bucket)');
    }

    protected function process(
        InputInterface $input,
        ConsoleOutputInterface $consoleOutput,
        int $batchSize,
        bool $isDryRun
    ): int {
        $style = new SymfonyStyle($input, $consoleOutput);
        $style->title('Simple Roster - Bulk Assignment Cancellation');

        $section = $consoleOutput->section();
        $section->writeln('Starting assignment cancellation...');

        try {
            $source = $this->getIngesterSource($input);
            $bulkOperationCollection = new BulkOperationCollection();

            $numberOfProcessedAssignments = 0;
            foreach ($source->getContent() as $row) {
                $this->validateRow($row);

                $operation = new BulkOperation(
                    $row['username'],
                    BulkOperation::TYPE_UPDATE,
                    ['state' => Assignment::STATE_CANCELLED]
                );

                $operation->setIsDryRun($isDryRun);

                $bulkOperationCollection->add($operation);

                if (count($bulkOperationCollection) % $batchSize !== 0) {
                    continue;
                }

                $numberOfProcessedAssignments += count($bulkOperationCollection);
                $this->processOperationCollection($bulkOperationCollection);

                $this->overwriteSection($section, $numberOfProcessedAssignments);
            }

            // Process remaining operations
            if (!$bulkOperationCollection->isEmpty()) {
                $numberOfProcessedAssignments += count($bulkOperationCollection);

                $this->processOperationCollection($bulkOperationCollection);

                $this->overwriteSection($section, $numberOfProcessedAssignments);
            }
        } catch (Throwable $exception) {
            $style->error($exception->getMessage());

            return 1;
        }

        $this->displayResult($style, $numberOfProcessedAssignments, $batchSize);

        return 0;
    }
}
