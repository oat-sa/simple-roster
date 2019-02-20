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

    protected function createEntity(array $data): EntityInterface
    {
        return (new Infrastructure())
            ->setLabel($data['label'])
            ->setLtiDirectorLink($data['ltiDirectorLink'])
            ->setLtiKey($data['ltiKey'])
            ->setLtiSecret($data['ltiSecret']);
    }
}
