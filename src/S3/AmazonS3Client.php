<?php declare(strict_types=1);

namespace App\S3;

class AmazonS3Client implements S3ClientInterface
{
    /**
     * @var \Aws\S3\S3Client
     */
    private $client;

    public function __construct(string $region, string $version, string $accessKey, string $secret)
    {
        $this->client = new \Aws\S3\S3Client([
            'region' => $region,
            'version' => $version,
            'credentials' => new \Aws\Credentials\Credentials($accessKey, $secret),
        ]);
    }

    public function getObject(string $bucket, string $object)
    {
        $args = [
            'Bucket' => $bucket,
            'Key' => $object,
        ];

        return $this->client->getObject($args)['Body']->getContents();
    }

    public function putObject(string $bucket, string $name, string $content)
    {
        throw new \Exception('Not implemented');
    }
}