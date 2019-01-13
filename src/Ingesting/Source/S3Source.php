<?php declare(strict_types=1);

namespace App\Ingesting\Source;

use App\Ingesting\Exception\S3AccessException;
use App\S3\S3ClientFactory;

class S3Source implements SourceInterface
{
    private $clientFactory;
    private $bucket;
    private $object;
    private $region;
    private $accessKey;
    private $secret;
    private $delimiter;

    public function __construct(S3ClientFactory $clientFactory, string $bucket, string $object, string $region, string $accessKey, string $secret, string $delimiter)
    {
        $this->clientFactory = $clientFactory;
        $this->bucket = $bucket;
        $this->object = $object;
        $this->region = $region;
        $this->accessKey = $accessKey;
        $this->secret = $secret;
        $this->delimiter = $delimiter;
    }

    /**
     * @return \Generator
     * @throws S3AccessException
     */
    public function iterateThroughLines(): \Generator
    {
        $client = $this->clientFactory->createClient($this->region,
            $this->accessKey, $this->secret);

        try {
            $response = $client->getObject($this->bucket, $this->object);
        } catch (\Exception $e) {
            throw new S3AccessException();
        }
        foreach (explode(PHP_EOL, $response) as $line) {
            yield str_getcsv($line, $this->delimiter);
        }
    }
}