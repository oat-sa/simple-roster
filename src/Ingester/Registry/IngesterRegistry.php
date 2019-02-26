<?php declare(strict_types=1);

namespace App\Ingester\Registry;

use App\Ingester\Ingester\IngesterInterface;
use InvalidArgumentException;

class IngesterRegistry
{
    /** @var IngesterInterface[] */
    private $ingesters = [];

    public function __construct(iterable $ingesters = [])
    {
        foreach ($ingesters as $ingester) {
            $this->add($ingester);
        }
    }

    public function add(IngesterInterface $ingester): self
    {
        $this->ingesters[$ingester->getRegistryItemName()] = $ingester;

        return $this;
    }

    public function get(string $ingesterName): IngesterInterface
    {
        if (!$this->has($ingesterName)) {
            throw new InvalidArgumentException(
                sprintf("Ingester named '%s' cannot be found.", $ingesterName)
            );
        }

        return $this->ingesters[$ingesterName];
    }

    private function has(string $ingesterName): bool
    {
        return isset($this->ingesters[$ingesterName]);
    }

    /**
     * @return IngesterInterface[]
     */
    public function all(): array
    {
        return $this->ingesters;
    }
}
