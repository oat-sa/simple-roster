<?php declare(strict_types=1);

namespace App\Ingester\Source;

use League\Csv\Reader;
use Traversable;

class LocalCsvIngesterSource extends AbstractIngesterSource
{
    public function getRegistryItemName(): string
    {
        return 'local';
    }

    public function getContent(): Traversable
    {
        $reader = Reader::createFromPath($this->path, 'r');

        return $reader->setDelimiter($this->delimiter);
    }
}
