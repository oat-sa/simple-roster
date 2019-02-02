<?php declare(strict_types=1);

namespace App\Ingester\Registry;

use App\Ingester\Source\IngesterSourceInterface;
use InvalidArgumentException;

class IngesterSourceRegistry
{
    /** @var IngesterSourceInterface[] */
    private $sources = [];

    public function add(IngesterSourceInterface $source): self
    {
        $this->sources[$source->getName()] = $source;

        return $this;
    }

    public function get(string $sourceName): IngesterSourceInterface
    {
        if (!$this->has($sourceName)) {
            throw new InvalidArgumentException(
                sprintf("Ingester source named '%s' cannot be found.", $sourceName)
            );
        }

        return $this->sources[$sourceName];
    }

    public function has(string $sourceName): bool
    {
        return isset($this->sources[$sourceName]);
    }

    /**
     * @return IngesterSourceInterface[]
     */
    public function all(): array
    {
        return $this->sources;
    }
}
