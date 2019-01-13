<?php declare(strict_types=1);

namespace App\S3;

class InMemoryS3Client implements S3ClientInterface
{
    private $objects = [];

    /**
     * @param string $bucket
     * @param string $object
     * @return mixed
     * @throws \Exception
     */
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