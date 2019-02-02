<?php declare(strict_types=1);

namespace App\Ingester\Source;

use Generator;

interface IngesterSourceInterface
{
    const DEFAULT_CSV_DELIMITER = ',';

    public function getName(): string;

    public function read(): Generator;

    public function setPath(string $path): self;

    public function setDelimiter(string $delimiter): self;
}
