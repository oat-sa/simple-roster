<?php declare(strict_types=1);

namespace App\Ingester\Factory;

use App\Ingester\Source\IngesterSourceInterface;
use App\Ingester\Source\LocalCsvIngesterSource;
use InvalidArgumentException;

class IngesterSourceFactory
{
    public function create(string $type, array $options = []): IngesterSourceInterface
    {
        switch ($type) {
            case LocalCsvIngesterSource::NAME:
                return new LocalCsvIngesterSource($options['filename'], $options['delimiter']);
                break;

            default:
                throw new InvalidArgumentException(
                    sprintf("Unsupported source type '%s'.", $type)
                );
        }
    }
}