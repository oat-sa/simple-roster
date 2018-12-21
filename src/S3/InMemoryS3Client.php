<?php

namespace App\S3;

class InMemoryS3Client implements S3Client
{
    private $objects = [];

    public function getObject(string $bucket, string $object)
    {
        $hash = $bucket . $object;

        if (!array_key_exists($hash, $this->objects)) {
            throw new \Exception('No such object inside given bucket');
        }
        return $this->objects[$hash];
    }

    /**
     * @param array $args
     * @throws \Exception
     */
    public function putObject(string $bucket, string $name, string $content)
    {
        $hash = $bucket . $name;

        $this->objects[$hash] = $content;
    }
}