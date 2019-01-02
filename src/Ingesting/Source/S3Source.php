<?php

namespace App\Ingesting\Source;

use App\Command\Ingesting\Exception\S3AccessException;
use App\S3\S3ClientFactory;

class S3Source extends Source
{
    protected $accessParameters = ['s3_bucket' => null, 's3_object' => null, 's3_region' => null,
        's3_access_key' => null, 's3_secret' => null, 's3_client_factory' => null, 'delimiter' => null];

    /**
     * @return \Generator
     * @throws S3AccessException
     */
    public function iterateThroughLines(): \Generator
    {
        /** @var S3ClientFactory $s3ClientFactory */
        $s3ClientFactory = $this->accessParameters['s3_client_factory'];

        $s3Client = $s3ClientFactory->createClient($this->accessParameters['s3_region'],
            $this->accessParameters['s3_access_key'], $this->accessParameters['s3_secret']);

        try {
            $s3Response = $s3Client->getObject($this->accessParameters['s3_bucket'], $this->accessParameters['s3_object']);
        } catch (\Exception $e) {
            throw new S3AccessException();
        }
        foreach (explode(PHP_EOL, $s3Response) as $line) {
            yield str_getcsv($line, $this->accessParameters['delimiter']);
        }
    }
}