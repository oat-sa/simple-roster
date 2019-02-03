<?php declare(strict_types=1);

namespace App\Ingester\Source;

use App\S3\S3ClientInterface;
use Iterator;

class S3CsvIngesterSource extends AbstractIngesterSource
{
    /** @var S3ClientInterface  */
    private $client;

    /** @var string  */
    private $bucket;

    public function __construct(S3ClientInterface $client, string $bucket)
    {
        $this->client = $client;
        $this->bucket = $bucket;
    }

    public function getRegistryItemName(): string
    {
        return 's3';
    }

    public function read(): Iterator
    {
        $response = $this->client->getObject($this->bucket, $this->path);

        foreach (explode(PHP_EOL, $response) as $line) {
            yield str_getcsv($line, $this->delimiter);
        }
    }
}
