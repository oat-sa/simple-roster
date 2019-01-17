<?php declare(strict_types=1);

namespace App\Ingesting\Source;

use App\Ingesting\Exception\S3AccessException;
use App\S3\S3ClientInterface;

class S3CsvSource implements SourceInterface
{
    private $s3Client;
    private $bucket;
    private $object;
    private $delimiter;

    public function __construct(S3ClientInterface $s3Client, string $bucket, string $object, string $delimiter)
    {
        $this->s3Client = $s3Client;
        $this->bucket = $bucket;
        $this->object = $object;
        $this->delimiter = $delimiter;
    }

    /**
     * @return \Generator
     * @throws S3AccessException
     */
    public function iterateThroughLines(): \Generator
    {
        try {
            $response = $this->s3Client->getObject($this->bucket, $this->object);
        } catch (\Exception $e) {
            throw new S3AccessException();
        }
        foreach (explode(PHP_EOL, $response) as $line) {
            yield str_getcsv($line, $this->delimiter);
        }
    }
}