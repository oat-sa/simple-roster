<?php declare(strict_types=1);

namespace App\S3;

use Aws\S3\S3Client;

class AmazonS3Client implements S3ClientInterface
{
    /**
     * @var \Aws\S3\S3Client
     */
    private $client;
    private $awsVersion;
    private $awsRegion;

    public function __construct(string $awsVersion, string $awsRegion, S3Client $client)
    {
        $this->awsVersion = $awsVersion;
        $this->awsRegion = $awsRegion;
        $this->client = $client;
    }

    /**
     * @param null|string $accessKey Not needed on prod
     * @param null|string $secret Not needed on prod
     */
    public function connect(string $accessKey, string $secret): void
    {
        $this->client = new S3Client([
            'region' => $this->awsRegion,
            'version' => $this->awsVersion,
            'credentials' =>  new \Aws\Credentials\Credentials($accessKey, $secret)
        ]);
    }

    /**
     * @param string $bucket
     * @param string $object
     * @return string
     * @throws \RuntimeException
     */
    public function getObject(string $bucket, string $object): string
    {
        if (!$this->client) {
            throw new \RuntimeException();
        }
        $args = [
            'Bucket' => $bucket,
            'Key' => $object,
        ];

        return $this->client->getObject($args)['Body']->getContents();
    }

    /**
     * @param string $bucket
     * @param string $name
     * @param string $content
     * @throws \RuntimeException
     */
    public function putObject(string $bucket, string $name, string $content): void
    {
        throw new \RuntimeException('Not implemented');
    }
}