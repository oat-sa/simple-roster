<?php declare(strict_types=1);

namespace App\Ingester\Source;

use Generator;
use Exception;

class LocalCsvIngesterSource extends AbstractIngesterSource
{
    public function getName(): string
    {
        return 'local';
    }

    /**
     * @throws Exception
     */
    public function read(): Generator
    {
        if (!file_exists($this->path)) {
            throw new Exception('Invalid file path ' . $this->path);
        }

        $fileHandle = fopen($this->path, 'r');
        if (false === $fileHandle) {
            throw new Exception('Cannot read file path' . $this->path);
        }

        while (($line = fgetcsv($fileHandle, null, $this->delimiter)) !== false) {
            yield $line;
        }

        fclose($fileHandle);
    }
}
