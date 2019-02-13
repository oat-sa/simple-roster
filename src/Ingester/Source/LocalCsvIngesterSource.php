<?php declare(strict_types=1);

namespace App\Ingester\Source;

use League\Csv\Reader;

class LocalCsvIngesterSource extends AbstractIngesterSource
{
    public function getRegistryItemName(): string
    {
        return 'local';
    }

    public function getContent(): array
    {
        $reader = Reader::createFromPath($this->path, 'r');

        return $reader
            ->setDelimiter($this->delimiter)
            ->setOffset(1)
            ->fetchAll();
    }
}
