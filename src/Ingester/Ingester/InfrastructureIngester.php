<?php declare(strict_types=1);

namespace App\Ingester\Ingester;

use App\Entity\EntityInterface;
use App\Entity\Infrastructure;

class InfrastructureIngester extends AbstractIngester
{
    public function getName(): string
    {
        return 'infrastructure';
    }

    protected function createEntity(array $data): EntityInterface
    {
        $infrastructure = new Infrastructure();

        return $infrastructure
            ->setLabel($data[0] ?? '')
            ->setLtiDirectorLink($data[1] ?? '')
            ->setLtiKey($data[2] ?? '')
            ->setLtiSecret($data[3] ?? '');
    }
}
