<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

use Aws\S3\S3Client;
use Psr\Log\LoggerInterface;
use Throwable;

class S3ResultFileUrlProvider implements ResultFileUrlProviderInterface
{
    public function __construct(
        private readonly FileStorageInterface $fileStorage,
        private readonly RosteringFileKeyResolver $fileKeyResolver,
        private readonly S3Client $s3Client,
        private readonly string $bucket,
        private readonly string $prefix,
        private readonly string $signedUrlTtl,
        private readonly LoggerInterface $logger,
        private readonly string $publicEndpoint = ''
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

            return $this->withPublicEndpoint((string) $request->getUri());
        } catch (Throwable $exception) {
            $this->logger->error(
                sprintf('Unable to generate signed result file URL for file key "%s".', $fileKey),
                [
                    'fileKey' => $fileKey,
                    'objectKey' => $objectKey,
                    'bucket' => $this->bucket,
                    'exception' => $exception,
                ]
            );

            return null;
        }
    }

    private function withPublicEndpoint(string $signedUrl): string
    {
        $publicEndpoint = trim($this->publicEndpoint);
        if ($publicEndpoint === '') {
            return $signedUrl;
        }

        $publicParts = parse_url($publicEndpoint);
        $signedUrlParts = parse_url($signedUrl);
        if (
            $publicParts === false
            || $signedUrlParts === false
            || !isset($publicParts['scheme'], $publicParts['host'])
        ) {
            return $signedUrl;
        }

        $host = sprintf(
            '%s://%s%s',
            $publicParts['scheme'],
            $publicParts['host'],
            isset($publicParts['port']) ? sprintf(':%d', (int) $publicParts['port']) : ''
        );

        return sprintf(
            '%s%s%s',
            $host,
            $signedUrlParts['path'] ?? '',
            isset($signedUrlParts['query']) ? sprintf('?%s', $signedUrlParts['query']) : ''
        );
    }
}
