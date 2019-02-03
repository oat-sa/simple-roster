<?php declare(strict_types=1);

namespace App\Registry;

use App\Ingester\Ingester\IngesterInterface;
use InvalidArgumentException;

class AbstractRegistry
{
    /** @var RegistryItemInterface[] */
    protected $items = [];

    protected function addItem(RegistryItemInterface $item): self
    {
        $this->items[$item->getItemName()] = $item;

        return $this;
    }

    protected function getItem(string $itemName): ?RegistryItemInterface
    {
        return $this->items[$itemName] ?? null;
    }

    protected function hasItem(string $itemName): bool
    {
        return isset($this->items[$itemName]);
    }

    /**
     * @return RegistryItemInterface[]
     */
    protected function getAllItems(): array
    {
        return $this->items;
    }
}
