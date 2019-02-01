<?php declare(strict_types=1);

namespace App\Ingester\Ingester;

use App\Entity\EntityInterface;
use App\Ingester\Source\IngesterSourceInterface;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractIngester implements IngesterInterface
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function ingest(IngesterSourceInterface $source)
    {
        foreach ($source->read() as $data) {
            $this->entityManager->persist($this->createEntity($data));
        }

        $this->entityManager->flush();
    }

    abstract protected function createEntity(array $data): EntityInterface;
}
