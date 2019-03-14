<?php declare(strict_types=1);

namespace App\Ingester\Source;

use App\Ingester\Registry\RegistryItemInterface;
use Traversable;

interface IngesterSourceInterface extends RegistryItemInterface
{
    public const DEFAULT_CSV_DELIMITER = ',';
    public const DEFAULT_CSV_CHARSET = 'UTF-8';

    public function getContent(): Traversable;

    public function setPath(string $path): self;

    public function setDelimiter(string $delimiter): self;

    public function setCharset(string $charset): self;
}
