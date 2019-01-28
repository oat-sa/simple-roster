<?php declare(strict_types=1);

namespace App\Ingesting\Source;

use App\Ingesting\Exception\FileNotFoundException;

class LocalCsvFileSource implements SourceInterface
{
    private $filename;
    private $delimiter;

    public function __construct(string $filename, string $delimiter)
    {
        $this->filename = $filename;
        $this->delimiter = $delimiter;
    }

    /**
     * @return \Generator
     * @throws \Exception
     */
    public function iterateThroughLines(): \Generator
    {
        if (!file_exists($this->filename)) {
            throw new FileNotFoundException($this->filename);
        }

        $fileHandle = fopen($this->filename, 'r');
        if (false === $fileHandle) {
            throw new FileNotFoundException($this->filename);
        }

        while (($line = fgetcsv($fileHandle, null, $this->delimiter)) !== false) {
            yield $line;
        }

        fclose($fileHandle);
    }
}