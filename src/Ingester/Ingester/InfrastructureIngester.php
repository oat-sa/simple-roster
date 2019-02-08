<?php declare(strict_types=1);

namespace App\Ingester\Ingester;

use App\Entity\EntityInterface;
use App\Entity\Infrastructure;

class InfrastructureIngester extends AbstractIngester
{
    public function getRegistryItemName(): string
    {
        return 'infrastructure';
    }

    protected function prepare(): void
    {
    }

    protected function createEntity(array $data): EntityInterface
    {
        return (new Infrastructure())
            ->setLabel($data[0])
            ->setLtiDirectorLink($data[1])
            ->setLtiKey($data[2])
            ->setLtiSecret($data[3]);
    }
}
