<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Upload;

use OAT\SimpleRoster\Service\Upload\Exception\UploadedFileValidationException;
use OAT\SimpleRoster\Service\Upload\UploadedFileValidator;
use OAT\SimpleRoster\Service\Upload\ZippedCsvExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

class ZippedCsvExtractorTest extends TestCase
{
    private const CSV_DELIMITER = ',';
    private const CSV_ENCLOSURE = '"';
    private const CSV_ESCAPE = '\\';

    public function testItReturnsOriginalFileWhenFileIsNotZip(): void
    {
        $validator = $this->createValidator(1024);
        $extractor = new ZippedCsvExtractor($validator);
        $file = $this->createUploadedFile('test.csv', 'a,b');

        $preparedFile = $extractor->prepare($file);

        $this->assertSame($file, $preparedFile->file());
    }

    public function testItExtractsSingleCsvFromZip(): void
    {
        if (!class_exists(ZipArchive::class)) {
            $this->markTestSkipped('The zip extension is not installed.');
        }

        $validator = $this->createValidator(1024);
        $extractor = new ZippedCsvExtractor($validator);
        $file = $this->createUploadedZip('test.zip', ['roster.csv' => "a,b\nc,d"]);

        $preparedFile = $extractor->prepare($file);

        try {
            $this->assertNotSame($file, $preparedFile->file());
            $this->assertSame('roster.csv', $preparedFile->file()->getClientOriginalName());
            $this->assertSame("a,b\nc,d", file_get_contents($preparedFile->file()->getPathname()));
        } finally {
            $preparedFile->cleanup();
        }
    }

    public function testItRejectsZipWithSeveralFiles(): void
    {
        if (!class_exists(ZipArchive::class)) {
            $this->markTestSkipped('The zip extension is not installed.');
        }

        $extractor = new ZippedCsvExtractor($this->createValidator(1024));
        $file = $this->createUploadedZip('test.zip', [
            'one.csv' => 'a,b',
            'two.csv' => 'c,d',
        ]);

        $this->expectException(UploadedFileValidationException::class);
        $this->expectExceptionMessage('ZIP archive must contain exactly one file.');

        $extractor->prepare($file);
    }

    public function testItRejectsUnreadableZipArchive(): void
    {
        if (!class_exists(ZipArchive::class)) {
            $this->markTestSkipped('The zip extension is not installed.');
        }

        $extractor = new ZippedCsvExtractor($this->createValidator(1024));
        $file = $this->createUploadedFile('broken.zip', 'not a zip archive');

        $this->expectException(UploadedFileValidationException::class);
        $this->expectExceptionMessage('Unable to read uploaded ZIP file.');

        $extractor->prepare($file);
    }

    public function testItRejectsZipEntryThatExceedsConfiguredMaxSize(): void
    {
        if (!class_exists(ZipArchive::class)) {
            $this->markTestSkipped('The zip extension is not installed.');
        }

        $extractor = new ZippedCsvExtractor($this->createValidator(3));
        $file = $this->createUploadedZip('oversized.zip', ['roster.csv' => '1234']);

        $this->expectException(UploadedFileValidationException::class);
        $this->expectExceptionMessage('File size "4" exceeds maximum allowed size of "3".');

        $extractor->prepare($file);
    }

    private function createValidator(int $maxSize): UploadedFileValidator
    {
        return new UploadedFileValidator(
            $maxSize,
            100,
            self::CSV_DELIMITER,
            self::CSV_ENCLOSURE,
            self::CSV_ESCAPE
        );
    }

    private function createUploadedFile(string $originalName, string $content): UploadedFile
    {
        $tmpDir = sys_get_temp_dir();
        $path = $tmpDir . '/' . uniqid('upload_', true) . '_' . $originalName;
        file_put_contents($path, $content);

        return new UploadedFile($path, $originalName, null, null, true);
    }

    /**
     * @param array<string, string> $files
     */
    private function createUploadedZip(string $originalName, array $files): UploadedFile
    {
        $tmpDir = sys_get_temp_dir();
        $path = $tmpDir . '/' . uniqid('upload_', true) . '_' . $originalName;
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);

        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();

        return new UploadedFile($path, $originalName, null, null, true);
    }
}
