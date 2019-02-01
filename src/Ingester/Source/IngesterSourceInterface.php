<?php declare(strict_types=1);

namespace App\Ingester\Source;

use Generator;

interface IngesterSourceInterface
{
    public function read(): Generator;
}
