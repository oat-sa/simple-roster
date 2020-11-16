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

use InvalidArgumentException;
use OAT\SimpleRoster\Csv\CsvReaderBuilder;
use OAT\SimpleRoster\Entity\LtiInstance;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use OAT\SimpleRoster\Storage\StorageRegistry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class LtiInstanceIngesterCommand extends AbstractCsvIngesterCommand
{
    public const NAME = 'roster:ingest:lti-instance';

    /** @var LtiInstanceRepository */
    private $ltiInstanceRepository;

    public function __construct(
        CsvReaderBuilder $csvReaderBuilder,
        StorageRegistry $storageRegistry,
        LtiInstanceRepository $ltiInstanceRepository
    ) {
        parent::__construct($csvReaderBuilder, $storageRegistry);

        $this->ltiInstanceRepository = $ltiInstanceRepository;
    }

    protected function getIngesterCommandName(): string
    {
        return self::NAME;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('LTI instance ingestion from various sources');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->symfonyStyle->title('Simple Roster - LTI Instance Ingester');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->symfonyStyle->text('Executing ingestion...');
        $this->symfonyStyle->newLine();

        try {
            $this->progressBar->start();

            $numberOfProcessedRows = 0;
            $persisted = false;
            foreach ($this->csvReader->getRecords() as $rawLtiInstance) {
                $this->validateRawLtiInstance($rawLtiInstance);

                $numberOfProcessedRows++;
                $persisted = false;

                $ltiInstance = new LtiInstance(
                    0,
                    $rawLtiInstance['label'],
                    $rawLtiInstance['ltiLink'],
                    $rawLtiInstance['ltiKey'],
                    $rawLtiInstance['ltiSecret']
                );

                $this->ltiInstanceRepository->persist($ltiInstance);

                if ($numberOfProcessedRows % $this->batchSize === 0) {
                    if (!$this->isDryRun) {
                        $this->ltiInstanceRepository->flush();
                    }

                    $persisted = true;

                    $this->progressBar->advance($this->batchSize);
                }
            }

            if (!$this->isDryRun && !$persisted) {
                $this->ltiInstanceRepository->flush();
            }

            $this->progressBar->finish();

            $this->symfonyStyle->newLine(2);

            $verificationCommentMessage = sprintf(
                'To verify you can run: <options=bold>%s</>',
                'bin/console dbal:run-sql "SELECT COUNT(*) FROM lti_instances"'
            );

            if ($this->isDryRun) {
                $this->symfonyStyle->warning(
                    sprintf(
                        '[DRY RUN] %s LTI instances have been successfully ingested.',
                        number_format($numberOfProcessedRows)
                    )
                );

                $this->symfonyStyle->comment($verificationCommentMessage);

                return 0;
            }

            $this->symfonyStyle->success(
                sprintf(
                    '%s LTI instances have been successfully ingested.',
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
     * @throws InvalidArgumentException
     */
    private function validateRawLtiInstance(array $rawUser): void
    {
        if (!isset($rawUser['label'])) {
            throw new InvalidArgumentException("Column 'label' is not set in source file.");
        }

        if (!isset($rawUser['ltiLink'])) {
            throw new InvalidArgumentException("Column 'ltiLink' is not set in source file.");
        }

        if (!isset($rawUser['ltiKey'])) {
            throw new InvalidArgumentException("Column 'ltiKey' is not set in source file.");
        }

        if (!isset($rawUser['ltiSecret'])) {
            throw new InvalidArgumentException("Column 'ltiSecret' is not set in source file.");
        }
    }
}