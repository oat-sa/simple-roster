<?php declare(strict_types=1);

namespace App\Command\Ingesting\SourceSpecific;

use App\Ingesting\Exception\InputOptionException;
use App\Ingesting\Source\S3CsvSource;
use App\Ingesting\Source\SourceInterface;
use Symfony\Component\Console\Input\InputOption;

trait S3SourceSpecificTrait
{
    use SourceSpecificTrait;

    protected function addSourceOptions(): void
    {
        $this
            ->addOption('delimiter', null, InputOption::VALUE_OPTIONAL, 'CSV delimiter used in file ("," or "; normally)', ',')
            ->addOption('s3_bucket', null, InputOption::VALUE_OPTIONAL, 'Name of a S3 bucket')
            ->addOption('s3_object', null, InputOption::VALUE_OPTIONAL, 'Key of a S3 object')
            ->addOption('s3_region', null, InputOption::VALUE_OPTIONAL, 'Region specified for S3 bucket')
            ->addOption('s3_access_key', null, InputOption::VALUE_OPTIONAL, 'AWS access key')
            ->addOption('s3_secret', null, InputOption::VALUE_OPTIONAL, 'AWS secret key');
    }

    /**
     * @param array $options
     * @return SourceInterface
     * @throws InputOptionException
     */
    protected function getSource(array $options): SourceInterface
    {
        $requiredOptions = ['delimiter', 's3_bucket', 's3_object', 's3_region', 's3_access_key', 's3_secret'];

        foreach ($requiredOptions as $requiredOption) {
            if (!array_key_exists($requiredOption, $options) || $options[$requiredOption] === null) {
                throw new InputOptionException(sprintf('Option "%s" is not provided', $requiredOption));
            }
        }

        return new S3CsvSource($this->s3ClientFactory, $options['s3_bucket'], $options['s3_object'],
            $options['s3_region'], $options['s3_access_key'], $options['s3_secret'], $options['delimiter']);
    }
}