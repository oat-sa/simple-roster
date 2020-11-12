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

namespace OAT\SimpleRoster\Command\Ingester;

use OAT\SimpleRoster\Ingester\Registry\IngesterRegistry;
use OAT\SimpleRoster\Ingester\Registry\IngesterSourceRegistry;
use OAT\SimpleRoster\Ingester\Result\IngesterResult;
use OAT\SimpleRoster\Ingester\Result\IngesterResultFailure;
use OAT\SimpleRoster\Ingester\Source\IngesterSourceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class IngesterCommand extends Command
{
    public const NAME = 'roster:ingest';

    /** @var IngesterRegistry */
    private $ingesterRegistry;

    /** @var IngesterSourceRegistry */
    private $sourceRegistry;

    /** @var SymfonyStyle */
    private $symfonyStyle;

    /** @var bool */
    private $isDryRun;

    public function __construct(IngesterRegistry $ingesterRegistry, IngesterSourceRegistry $sourceRegistry)
    {
        $this->ingesterRegistry = $ingesterRegistry;
        $this->sourceRegistry = $sourceRegistry;

        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->setDescription(
            'Responsible for LTI instance and Line item ingestion from various sources (Local file, S3 bucket)'
        );

        $this->addArgument(
            'type',
            InputArgument::REQUIRED,
            sprintf(
                'Type of data to be ingested, possible values: ["%s"]',
                implode('", "', array_keys($this->ingesterRegistry->all()))
            )
        );

        $this->addArgument(
            'path',
            InputArgument::REQUIRED,
            'Source path to ingest from'
        );

        $this->addArgument(
            'source',
            InputArgument::OPTIONAL,
            sprintf(
                'Source type to ingest from, possible values: ["%s"]',
                implode('", "', array_keys($this->sourceRegistry->all()))
            ),
            'local'
        );

        $this->addOption(
            'delimiter',
            'd',
            InputOption::VALUE_REQUIRED,
            'CSV delimiter',
            IngesterSourceInterface::DEFAULT_CSV_DELIMITER
        );

        $this->addOption(
            'charset',
            'c',
            InputOption::VALUE_REQUIRED,
            'CSV source charset',
            IngesterSourceInterface::DEFAULT_CSV_CHARSET
        );

        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Causes data ingestion to be applied into storage'
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->symfonyStyle = new SymfonyStyle($input, $output);
        $this->symfonyStyle->title('Simple Roster - Ingester');

        $this->isDryRun = !(bool)$input->getOption('force');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $ingester = $this->ingesterRegistry->get((string)$input->getArgument('type'));
            $source = $this->sourceRegistry
                ->get((string)$input->getArgument('source'))
                ->setPath((string)$input->getArgument('path'))
                ->setDelimiter((string)$input->getOption('delimiter'))
                ->setCharset((string)$input->getOption('charset'));

            $result = $ingester->ingest($source, !(bool)$input->getOption('force'));

            $this->displayIngestionResult($result);
        } catch (Throwable $exception) {
            $this->symfonyStyle->error($exception->getMessage());

            return 1;
        } finally {
            $this->symfonyStyle->note('Done.');
        }

        return 0;
    }

    private function displayIngestionResult(IngesterResult $result): void
    {
        if (!$result->hasFailures()) {
            $this->symfonyStyle->success((string)$result);

            return;
        }

        $this->symfonyStyle->warning((string)$result);
        $this->symfonyStyle->table(
            ['Line', 'Data', 'Reason'],
            array_map(static function (IngesterResultFailure $failure): array {
                return [
                    $failure->getLineNumber(),
                    implode(', ', $failure->getData()),
                    $failure->getReason(),
                ];
            }, $result->getFailures())
        );
    }
}
