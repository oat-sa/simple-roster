<?php declare(strict_types=1);

namespace App\S3;

class AmazonS3Client implements S3ClientInterface
{
    /**
     * @var string
     */
    private $apiVersion;

    /**
     * @var \Aws\S3\S3Client
     */
    private $client;

    public function __construct(string $apiVersion)
    {
        $this->apiVersion = $apiVersion;
    }

    public function connect(string $region, string $accessKey, string $secret): void
    {
        $this->client = new \Aws\S3\S3Client([
            'region' => $region,
            'version' => $this->apiVersion,
            'credentials' => new \Aws\Credentials\Credentials($accessKey, $secret),
        ]);
    }

    /**
     * @param string $bucket
     * @param string $object
     * @return string
     * @throws \Exception
     */
    public function getObject(string $bucket, string $object)
    {
        if (!$this->client) {
            throw new \Exception();
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
     * @throws \Exception
     */
    public function putObject(string $bucket, string $name, string $content)
    {
        throw new \Exception('Not implemented');
    }
}