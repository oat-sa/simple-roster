<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Rostering;

use Aws\S3\S3Client;
use OAT\SimpleRoster\Service\Rostering\FileStorageInterface;
use OAT\SimpleRoster\Service\Rostering\RosteringFileKeyResolver;
use OAT\SimpleRoster\Service\Rostering\S3ResultFileUrlProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class S3ResultFileUrlProviderTest extends TestCase
{
    public function testItSignsUrlForConfiguredClientEndpoint(): void
    {
        $fileKey = 'reference-id/output.csv';
        $fileStorage = $this->createMock(FileStorageInterface::class);
        $fileStorage
            ->expects(self::once())
            ->method('exists')
            ->with($fileKey)
            ->willReturn(true);

        $s3Client = new S3Client([
            'version' => 'latest',
            'region' => 'eu-west-1',
            'endpoint' => 'http://localhost:4566',
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => 'test',
                'secret' => 'test',
            ],
        ]);
        $subject = new S3ResultFileUrlProvider(
            $fileStorage,
            new RosteringFileKeyResolver(),
            $s3Client,
            'rostering-files',
            'rostering-files',
            '+15 minutes',
            $this->createMock(LoggerInterface::class)
        );

        $url = $subject->generate($fileKey);

        self::assertIsString($url);
        $urlParts = parse_url($url);
        self::assertIsArray($urlParts);
        self::assertSame('localhost', $urlParts['host']);
        self::assertSame(4566, $urlParts['port']);
        parse_str($urlParts['query'], $query);
        self::assertSame('host', $query['X-Amz-SignedHeaders']);
        self::assertArrayHasKey('X-Amz-Signature', $query);
    }
}
