<?php declare(strict_types=1);

namespace App\Command\Ingesting;

use App\Ingesting\Exception\InputOptionException;
use App\Ingesting\Source\LocalCsvFileSource;
use App\Ingesting\Source\SourceInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class IngestLocalCommand extends AbstractIngestCommand
{
    protected static $defaultName = 'roster:local-ingest';

    protected function configure(): void
    {
        $this->addArgument('filename', InputArgument::REQUIRED, 'The path of CSV file to be imported');

        parent::configure();
    }

    protected function getSource(InputInterface $input): SourceInterface
    {
        return new LocalCsvFileSource($input->getArgument('filename'), $input->getOption('delimiter'));
    }
}