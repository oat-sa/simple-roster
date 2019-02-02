<?php declare(strict_types=1);

namespace App\Ingester\Ingester;

use App\Entity\EntityInterface;
use App\Ingester\Result\IngesterResult;
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

    public function ingest(IngesterSourceInterface $source): IngesterResult
    {
        $ingestCount = 0;

        foreach ($source->read() as $data) {
            $this->entityManager->persist($this->createEntity($data));
            $ingestCount++;
        }

        $this->entityManager->flush();

        return new IngesterResult($this->getName(), $ingestCount);
    }

    abstract protected function createEntity(array $data): EntityInterface;
}
