<?php declare(strict_types=1);

namespace App\S3;

interface S3ClientInterface
{
    public function connect(string $accessKey, string $secret): void ;

    public function getObject(string $bucket, string $object): string ;

    public function putObject(string $bucket, string $name, string $content): void ;
}