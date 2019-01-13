<?php declare(strict_types=1);

namespace App\S3;

interface S3ClientInterface
{
    public function connect(string $region, string $accessKey, string $secret);

    public function getObject(string $bucket, string $object);

    public function putObject(string $bucket, string $name, string $content);
}