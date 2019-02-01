<?php declare(strict_types=1);

namespace App\Ingester\Source;

use Generator;
use Exception;

class LocalCsvIngesterSource implements IngesterSourceInterface
{
    const NAME = 'local';

    /** @var string */
    private $filename;

    private $delimiter;

    public function __construct(string $filename, $delimiter)
    {
        $this->filename = $filename;
        $this->delimiter = $delimiter;
    }

    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * @throws Exception
     */
    public function read(): Generator
    {
        if (!file_exists($this->filename)) {
            throw new Exception('Invalid file ' . $this->filename);
        }

        $fileHandle = fopen($this->filename, 'r');
        if (false === $fileHandle) {
            throw new Exception('Cannor read' . $this->filename);
        }

        while (($line = fgetcsv($fileHandle, null, $this->delimiter)) !== false) {
            yield $line;
        }

        fclose($fileHandle);
    }
}