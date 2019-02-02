<?php declare(strict_types=1);

namespace App\Ingester\Source;

abstract class AbstractIngesterSource implements IngesterSourceInterface
{
    /** @var string */
    protected $path = '';

    /** @var string */
    protected $delimiter = self::DEFAULT_CSV_DELIMITER;

    public function setPath(string $path): IngesterSourceInterface
    {
        $this->path = $path;

        return $this;
    }

    public function setDelimiter(string $delimiter): IngesterSourceInterface
    {
        $this->delimiter = $delimiter;

        return $this;
    }
}
