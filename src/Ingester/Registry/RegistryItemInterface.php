<?php declare(strict_types=1);

namespace App\Ingester\Registry;

interface RegistryItemInterface
{
    public function getRegistryItemName(): string;
}
