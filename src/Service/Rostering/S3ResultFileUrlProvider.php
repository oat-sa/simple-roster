<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

use Aws\S3\S3Client;
use Throwable;

class S3ResultFileUrlProvider implements ResultFileUrlProviderInterface
{
    public function __construct(
        private readonly FileStorageInterface $fileStorage,
        private readonly RosteringFileKeyResolver $fileKeyResolver,
        private readonly S3Client $s3Client,
        private readonly string $bucket,
        private readonly string $prefix,
        private readonly string $signedUrlTtl
    ) {
    }

    public function generate(string $fileKey): ?string
    {
        if (trim($this->bucket) === '') {
            return null;
        }

        if (!$this->fileStorage->exists($fileKey)) {
            return null;
        }

        $objectKey = $this->fileKeyResolver->objectKey($fileKey, $this->prefix);

        try {
            $command = $this->s3Client->getCommand(
                'GetObject',
                [
                    'Bucket' => $this->bucket,
                    'Key' => $objectKey,
                ]
            );
            $request = $this->s3Client->createPresignedRequest($command, $this->signedUrlTtl);

            return (string) $request->getUri();
        } catch (Throwable $exception) {
            return null;
        }
    }
}
