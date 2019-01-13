<?php declare(strict_types=1);

namespace App\Command\Ingesting\SourceSpecific;

use App\Ingesting\Exception\InputOptionException;
use App\Ingesting\Source\LocalFileSource;
use App\Ingesting\Source\SourceInterface;
use Symfony\Component\Console\Input\InputOption;

trait LocalFileSourceSpecificTrait
{
    use SourceSpecificTrait;

    protected function addSourceOptions(): void
    {
        $this
            ->addOption('filename', null, InputOption::VALUE_OPTIONAL, 'The filename with CSV data')
            ->addOption('delimiter', null, InputOption::VALUE_OPTIONAL, 'CSV delimiter used in file ("," or "; normally)', ',');
    }

    /**
     * @param array $options
     * @return SourceInterface
     * @throws InputOptionException
     */
    protected function getSource(array $options): SourceInterface
    {
        $requiredOptions = ['delimiter', 'filename'];

        foreach ($requiredOptions as $requiredOption) {
            if (!array_key_exists($requiredOption, $options) || $options[$requiredOption] === null) {
                throw new InputOptionException(sprintf('Option "%s" is not provided', $requiredOption));
            }
        }

        return new LocalFileSource($options['filename'], $options['delimiter']);
    }
}