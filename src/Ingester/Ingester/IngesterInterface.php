<?php declare(strict_types=1);

namespace App\Ingester\Ingester;

use App\Ingester\Result\IngesterResult;
use App\Ingester\Source\IngesterSourceInterface;

interface IngesterInterface
{
    public function getName(): string;

    public function ingest(IngesterSourceInterface $source): IngesterResult;
}
