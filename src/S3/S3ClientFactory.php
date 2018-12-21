<?php

namespace App\S3;

class S3ClientFactory
{
    /**
     * @var string
     */
    private $clientClass;

    /**
     * @var string
     */
    private $apiVersion;

    public function __construct(string $clientClass, ?string $apiVersion = null)
    {
        $this->clientClass = $clientClass;
        $this->apiVersion = $apiVersion;
    }

    public function createClient(?string $region = null, ?string $accessKey = null, ?string $secret = null): S3Client
    {
        if ($this->clientClass === AmazonS3Client::class) {
            return new $this->clientClass($region, $this->apiVersion, $accessKey, $secret);
        }

        if ($this->clientClass === InMemoryS3Client::class) {
            return new $this->clientClass();
        }

        throw new \OutOfRangeException();
    }
}