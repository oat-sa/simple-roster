<?php declare(strict_types=1);

namespace App\Ingester\Source;

use App\Ingester\Registry\RegistryItemInterface;
use Iterator;

interface IngesterSourceInterface extends RegistryItemInterface
{
    const DEFAULT_CSV_DELIMITER = ',';

    public function read(): Iterator;

    public function setPath(string $path): self;

    public function setDelimiter(string $delimiter): self;
}
