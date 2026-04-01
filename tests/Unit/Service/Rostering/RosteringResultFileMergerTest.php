<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Rostering;

use League\Csv\Reader;
use OAT\SimpleRoster\Service\Rostering\FileStorageInterface;
use OAT\SimpleRoster\Service\Rostering\Exception\RosteringStatusException;
use OAT\SimpleRoster\Service\Rostering\RosteringFileKeyResolver;
use OAT\SimpleRoster\Service\Rostering\RosteringResultFileMerger;
use OAT\SimpleRoster\Service\Rostering\SeekableStreamFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RosteringResultFileMergerTest extends TestCase
{
    public function testItReturnsExistingMergedFileWhenAlreadyPresent(): void
    {
        $storage = new TestInMemoryFileStorage();
        $resolver = new RosteringFileKeyResolver();
        $referenceId = 'ref-1';
        $mergedKey = $resolver->mergedOutputFileKey($referenceId);
        $storage->write($mergedKey, "id,status,errorType,errorCode,errorMessage\nu1,processed,,,\n");

        $subject = new RosteringResultFileMerger($storage, $resolver, new SeekableStreamFactory());

        self::assertSame($mergedKey, $subject->getOrCreateMergedOutputFileKey($referenceId));
    }

    public function testItThrowsExceptionWhenOneOfWorkerOutputFilesIsMissing(): void
    {
        $storage = new TestInMemoryFileStorage();
        $resolver = new RosteringFileKeyResolver();
        $referenceId = 'ref-2';
        $storage->write(
            $resolver->outputFileKey($referenceId),
            "id,status,errorType,errorCode,errorMessage\nu1,processed,,,\n"
        );

        $subject = new RosteringResultFileMerger($storage, $resolver, new SeekableStreamFactory());

        $this->expectException(RosteringStatusException::class);
        $subject->getOrCreateMergedOutputFileKey($referenceId);
    }

    public function testItThrowsExceptionWhenHeadersDoNotMatch(): void
    {
        $storage = new TestInMemoryFileStorage();
        $resolver = new RosteringFileKeyResolver();
        $referenceId = 'ref-headers';

        $storage->write($resolver->outputFileKey($referenceId), "id,status,errorType,errorCode,errorMessage\nu1,processed,,,\n");
        $storage->write($resolver->externalReportingSystemOutputFileKey($referenceId), "id,status,errorType,errorCode\nu1,processed,,\n");

        $subject = new RosteringResultFileMerger($storage, $resolver, new SeekableStreamFactory());

        $this->expectException(RosteringStatusException::class);
        $subject->getOrCreateMergedOutputFileKey($referenceId);
    }

    public function testItMergesRowsWithoutDuplicatingSuccessfulOnes(): void
    {
        $storage = new TestInMemoryFileStorage();
        $resolver = new RosteringFileKeyResolver();
        $referenceId = 'ref-3';

        $storage->write(
            $resolver->outputFileKey($referenceId),
            implode(
                "\n",
                [
                    'id,status,errorType,errorCode,errorMessage',
                    'u1,processed,,,',
                    'u2,400,error,validation.fieldError,SR error',
                    ',,,,',
                    'u3,400,error,validation.fieldError,SR error 2',
                ]
            ) . "\n"
        );

        $storage->write(
            $resolver->externalReportingSystemOutputFileKey($referenceId),
            implode(
                "\n",
                [
                    'id,status,errorType,errorCode,errorMessage',
                    'u1,processed,,,',
                    'u2,processed,,,',
                    ',,,,',
                    'u3,500,error,csv.import.internalError,External Reporting System error',
                ]
            ) . "\n"
        );

        $subject = new RosteringResultFileMerger($storage, $resolver, new SeekableStreamFactory());
        $mergedKey = $subject->getOrCreateMergedOutputFileKey($referenceId);

        self::assertSame($resolver->mergedOutputFileKey($referenceId), $mergedKey);
        self::assertNotNull($mergedKey);

        $mergedRows = $this->readCsvRecords($storage->read($mergedKey));

        self::assertCount(4, $mergedRows);
        self::assertSame('u1', $mergedRows[0]['id']);
        self::assertSame('processed', $mergedRows[0]['status']);
        self::assertSame('u2', $mergedRows[1]['id']);
        self::assertSame('400', $mergedRows[1]['status']);
        self::assertSame('u3', $mergedRows[2]['id']);
        self::assertSame('400', $mergedRows[2]['status']);
        self::assertSame('u3', $mergedRows[3]['id']);
        self::assertSame('500', $mergedRows[3]['status']);
    }

    /**
     * @param resource $stream
     *
     * @return array<int, array<string, string>>
     */
    private function readCsvRecords($stream): array
    {
        $reader = Reader::from($stream);
        $reader->setHeaderOffset(0);

        return iterator_to_array($reader->getRecords(), false);
    }
}

final class TestInMemoryFileStorage implements FileStorageInterface
{
    /**
     * @var array<string, string>
     */
    private array $files = [];

    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->files);
    }

    public function read(string $key)
    {
        if (!$this->exists($key)) {
            throw new RuntimeException(sprintf('File "%s" not found.', $key));
        }

        $stream = fopen('php://temp', 'rb+');
        if ($stream === false) {
            throw new RuntimeException('Unable to open memory stream.');
        }

        fwrite($stream, $this->files[$key]);
        rewind($stream);

        return $stream;
    }

    public function store($stream, string $key, array $config = []): string
    {
        if (!is_resource($stream)) {
            throw new RuntimeException('Invalid stream.');
        }

        $contents = stream_get_contents($stream);
        if ($contents === false) {
            $contents = '';
        }

        $this->files[$key] = $contents;
        fclose($stream);

        return $key;
    }

    public function write(string $key, string $content): void
    {
        $this->files[$key] = $content;
    }
}
