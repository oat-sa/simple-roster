<?php declare(strict_types=1);

namespace App\Ingester\Source;

use App\Ingester\Registry\RegistryItemInterface;

interface IngesterSourceInterface extends RegistryItemInterface
{
    public const DEFAULT_CSV_DELIMITER = ',';

    public function getContent();

    public function setPath(string $path): self;

    public function setDelimiter(string $delimiter): self;
}
