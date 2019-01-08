<?php

namespace App\Ingesting\Source;

use App\Ingesting\Exception\FileNotFoundException;

class LocalFileAbstractSource extends AbstractSource
{
    protected $accessParameters = ['filename' => null, 'delimiter' => null];

    /**
     * @return \Generator
     * @throws \Exception
     */
    public function iterateThroughLines(): \Generator
    {
        $fileName = $this->accessParameters['filename'];
        if (!file_exists($fileName)) {
            throw new FileNotFoundException($fileName);
        }
        $fileHandle = fopen($fileName, 'r');
        if (false === $fileHandle) {
            throw new FileNotFoundException($fileName);
        }
        while (($line = fgetcsv($fileHandle, null, $this->accessParameters['delimiter'])) !== false) {
            yield $line;
        }
    }
}