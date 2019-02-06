<?php declare(strict_types=1);

namespace App\Command\Ingester;

use App\Ingester\Registry\IngesterRegistry;
use App\Ingester\Registry\IngesterSourceRegistry;
use App\Ingester\Result\IngesterResult;
use App\Ingester\Result\IngesterResultFailure;
use App\Ingester\Source\IngesterSourceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class IngesterCommand extends Command
{
    const NAME = 'roster:ingest';

    /** @var IngesterRegistry */
    private $ingesterRegistry;

    /** @var IngesterSourceRegistry */
    private $sourceRegistry;

    public function __construct(IngesterRegistry $ingesterRegistry, IngesterSourceRegistry $sourceRegistry)
    {
        $this->ingesterRegistry = $ingesterRegistry;
        $this->sourceRegistry = $sourceRegistry;

        parent::__construct(static::NAME);
    }

    protected function configure()
    {
        $this->addArgument(
            'type',
            InputArgument::REQUIRED,
            sprintf(
                'Type of data to be ingested, possible values: ["%s"]',
                implode('", "', array_keys($this->ingesterRegistry->all()))
            )
        );

        $this->addArgument(
            'source',
            InputArgument::REQUIRED,
            sprintf(
                'Source type to ingest from, possible values: ["%s"]',
                implode('", "', array_keys($this->sourceRegistry->all()))
            )
        );

        $this->addArgument(
            'path',
            InputArgument::REQUIRED,
            'Source path to ingest from'
        );

        $this->addOption(
            'delimiter',
            'd',
            InputOption::VALUE_REQUIRED,
            'CSV delimiter',
            IngesterSourceInterface::DEFAULT_CSV_DELIMITER
        );

        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Causes data ingestion to be applied into storage'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        try {
            $ingester = $this->ingesterRegistry->get($input->getArgument('type'));
            $source = $this->sourceRegistry
                ->get($input->getArgument('source'))
                ->setPath($input->getArgument('path'))
                ->setDelimiter($input->getOption('delimiter'));

            $result = $ingester->ingest($source, !(bool)$input->getOption('force'));

            $this->displayIngestionResult($result, $style);

        } catch (Throwable $exception) {
            $style->error($exception->getMessage());

            return 1;
        }

        return 0;
    }

    private function displayIngestionResult(IngesterResult $result, SymfonyStyle $style): void
    {
        if (!$result->hasFailures()) {
            $style->success($result);

            return;
        }

        $style->warning($result);
        $style->table(
            ['Line', 'Data', 'Reason'],
            array_map(function (IngesterResultFailure $failure): array {
                return [
                    $failure->getLineNumber(),
                    implode(', ', $failure->getData()),
                    $failure->getReason()
                ];
            }, $result->getFailures())
        );
    }
}
