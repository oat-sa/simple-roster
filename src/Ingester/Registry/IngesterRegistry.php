<?php declare(strict_types=1);

namespace App\Ingester\Registry;

use App\Ingester\Ingester\IngesterInterface;
use InvalidArgumentException;

class IngesterRegistry
{
    /** @var IngesterInterface[] */
    private $ingesters = [];

    public function add(IngesterInterface $ingester): self
    {
        $this->ingesters[$ingester->getName()] = $ingester;

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

    public function has(string $ingesterName): bool
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
