<?php declare(strict_types=1);

namespace App\Ingester\Source;

use League\Csv\Exception;
use League\Csv\Reader;
use Traversable;

class LocalCsvIngesterSource extends AbstractIngesterSource
{
    public function getRegistryItemName(): string
    {
        return 'local';
    }

    /**
     * @throws Exception
     */
    public function getContent(): Traversable
    {
        $reader = Reader::createFromPath($this->path);

        $reader
            ->setDelimiter($this->delimiter)
            ->setHeaderOffset(0);

        if ($this->charset !== self::DEFAULT_CSV_CHARSET) {
            $reader->addStreamFilter(sprintf('convert.iconv.%s/UTF-8', $this->charset));
        }

        return $reader;
    }
}
