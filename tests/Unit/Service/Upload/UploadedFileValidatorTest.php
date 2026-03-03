<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Upload;

use OAT\SimpleRoster\Service\Upload\Exception\UploadedFileValidationException;
use OAT\SimpleRoster\Service\Upload\UploadedFileValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadedFileValidatorTest extends TestCase
{
    private const CSV_DELIMITER = ',';
    private const CSV_ENCLOSURE = '"';
    private const CSV_ESCAPE = '\\';

    public function testItRejectsDisallowedExtension(): void
    {
        $validator = $this->createValidator(1024);
        $file = $this->createUploadedFile('test.xlsx', 'content');

        $this->expectException(UploadedFileValidationException::class);
        $this->expectExceptionMessage('File extension "xlsx" is not allowed. Allowed extensions are: csv');

        $validator->validate($file);
    }

    public function testItRejectsTooLargeFile(): void
    {
        $validator = $this->createValidator(3);
        $file = $this->createUploadedFile('test.csv', '1234');

        $this->expectException(UploadedFileValidationException::class);
        $this->expectExceptionMessage('File size "4" exceeds maximum allowed size of "3".');

        $validator->validate($file);
    }

    public function testItAcceptsValidFile(): void
    {
        $validator = $this->createValidator(1024);
        $file = $this->createUploadedFile('test.csv', 'ok');

        $validator->validate($file);

        $this->assertTrue(true);
    }

    public function testItRejectsInvalidCsvStructure(): void
    {
        $validator = $this->createValidator(1024);
        $file = $this->createUploadedFile('test.csv', "col1,col2\nvalue1,value2,value3");

        $this->expectException(UploadedFileValidationException::class);
        $this->expectExceptionMessage('Invalid CSV structure detected at row "2". Expected "2" columns, got "3".');

        $validator->validate($file);
    }

    public function testItAcceptsCsvWithConfiguredDelimiter(): void
    {
        $validator = new UploadedFileValidator(1024, ';', self::CSV_ENCLOSURE, self::CSV_ESCAPE);
        $file = $this->createUploadedFile('test.csv', "col1;col2\nvalue1;value2");

        $validator->validate($file);

        $this->assertTrue(true);
    }

    private function createValidator(int $maxSize): UploadedFileValidator
    {
        return new UploadedFileValidator(
            $maxSize,
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
}
