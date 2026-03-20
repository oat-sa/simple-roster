<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Rostering;

use League\Flysystem\FilesystemOperator;
use OAT\SimpleRoster\Service\Rostering\OperationalFileStorage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class OperationalFileStorageTest extends TestCase
{
    private FilesystemOperator&MockObject $filesystem;
    private OperationalFileStorage $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = $this->createMock(FilesystemOperator::class);
        $this->subject = new OperationalFileStorage($this->filesystem);
    }

    public function testExistsReturnsTrueWhenFileIsPresent(): void
    {
        $this->filesystem
            ->expects(self::once())
            ->method('fileExists')
            ->with('processing/ref.csv')
            ->willReturn(true);

        self::assertTrue($this->subject->exists('processing/ref.csv'));
    }

    public function testExistsReturnsFalseWhenStorageThrows(): void
    {
        $this->filesystem
            ->expects(self::once())
            ->method('fileExists')
            ->with('processing/ref.csv')
            ->willThrowException(new RuntimeException('cannot check'));

        self::assertFalse($this->subject->exists('processing/ref.csv'));
    }

    public function testReadReturnsStream(): void
    {
        $stream = fopen('php://temp', 'rb+');
        self::assertNotFalse($stream);

        $this->filesystem
            ->expects(self::once())
            ->method('readStream')
            ->with('processing/ref.csv')
            ->willReturn($stream);

        $result = $this->subject->read('processing/ref.csv');
        self::assertTrue(is_resource($result));
        fclose($result);
    }

    public function testReadThrowsWhenStorageReturnsInvalidStream(): void
    {
        $this->filesystem
            ->expects(self::once())
            ->method('readStream')
            ->with('processing/ref.csv')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to read file from storage key "processing/ref.csv".');

        $this->subject->read('processing/ref.csv');
    }

    public function testStoreWritesAndClosesStream(): void
    {
        $stream = fopen('php://temp', 'rb+');
        self::assertNotFalse($stream);

        $this->filesystem
            ->expects(self::once())
            ->method('writeStream')
            ->with('processing/ref.csv', $stream, ['Metadata' => ['referenceId' => 'ref']]);

        $result = $this->subject->store($stream, 'processing/ref.csv', ['Metadata' => ['referenceId' => 'ref']]);

        self::assertSame('File uploaded to processing/ref.csv', $result);
        self::assertFalse(is_resource($stream));
    }

    public function testStoreThrowsWhenStreamIsInvalid(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to open uploaded file stream.');

        $this->subject->store(false, 'processing/ref.csv');
    }
}
