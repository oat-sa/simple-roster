<?php declare(strict_types=1);

namespace App\Ingester\Ingester;

use App\Ingester\Registry\RegistryItemInterface;
use App\Ingester\Result\IngesterResult;
use App\Ingester\Source\IngesterSourceInterface;

interface IngesterInterface extends RegistryItemInterface
{
    public function ingest(IngesterSourceInterface $source, bool $dryRun = true): IngesterResult;
}
