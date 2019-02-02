<?php declare(strict_types=1);

namespace App\Command\Ingester;

use App\Ingester\Registry\IngesterRegistry;
use App\Ingester\Registry\IngesterSourceRegistry;
use App\Ingester\Source\IngesterSourceInterface;
use Psr\Log\LoggerInterface;
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

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        IngesterRegistry $ingesterRegistry,
        IngesterSourceRegistry $sourceRegistry,
        LoggerInterface $logger
    ) {
        $this->ingesterRegistry = $ingesterRegistry;
        $this->sourceRegistry = $sourceRegistry;
        $this->logger = $logger;

        parent::__construct(static::NAME);
    }

    protected function configure()
    {
        $this->addArgument('type', InputArgument::REQUIRED, sprintf(
            'Type of the items needed to be ingested. Possible values: ["%s"]',
            implode('", "', array_keys($this->ingesterRegistry->all()))
        ));

        $this->addArgument('source', InputArgument::REQUIRED, sprintf(
            'Source to ingest from. Possible values: ["%s"]',
            implode('", "', array_keys($this->sourceRegistry->all()))
        ));

        $this->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Path to import');

        $this->addOption('delimiter', 'd', InputOption::VALUE_REQUIRED, 'CSV delimiter', IngesterSourceInterface::DEFAULT_CSV_DELIMITER);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        try {
            $ingester = $this->ingesterRegistry->get($input->getArgument('type'));
            $source = $this->sourceRegistry->get($input->getArgument('source'));

            $source
                ->setPath($input->getOption('path'))
                ->setDelimiter($input->getOption('delimiter'));

            $result = $ingester->ingest($source);

            $this->logger->info($result->getFeedback());
            $style->success($result->getFeedback());

        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage());
            $style->error($exception->getMessage());

            return 1;
        }

        return 0;
    }
}