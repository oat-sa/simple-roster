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
use App\Ingester\Registry\IngesterSourceRegistry;
use App\Service\Bulk\BulkCreateUsersAssignmentsService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
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

    protected function configure()
    {
        parent::configure();

        $this->setDescription('Responsible for creating user assignments based on user list (Local file, S3 bucket)');
    }

    protected function process(
        InputInterface $input,
        ConsoleOutputInterface $consoleOutput,
        int $batchSize,
        bool $isDryRun
    ): int {
        $style = new SymfonyStyle($input, $consoleOutput);
        $style->title('Simple Roster - Bulk Assignment Creation');

        $section = $consoleOutput->section();
        $section->writeln('Starting assignment creation...');

        try {
            $source = $this->getIngesterSource($input);
            $bulkOperationCollection = new BulkOperationCollection();

            $numberOfProcessedAssignments = 0;
            foreach ($source->getContent() as $row) {
                $this->validateRow($row);

                $operation = new BulkOperation($row['username'], BulkOperation::TYPE_CREATE);

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
