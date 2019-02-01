<?php declare(strict_types=1);

namespace App\Ingester\Source;

use App\S3\S3ClientInterface;
use Generator;

class S3CsvIngesterSource implements IngesterSourceInterface
{
    const NAME = 's3';

    /** @var S3ClientInterface  */
    private $client;

    /** @var string  */
    private $bucket;

    /** @var string  */
    private $object;

    /** @var string  */
    private $delimiter;

    public function __construct(S3ClientInterface $client, string $bucket, string $object, string $delimiter)
    {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->object = $object;
        $this->delimiter = $delimiter;
    }

    public function read(): Generator
    {
        $response = $this->client->getObject($this->bucket, $this->object);

        foreach (explode(PHP_EOL, $response) as $line) {
            yield str_getcsv($line, $this->delimiter);
        }
    }
}