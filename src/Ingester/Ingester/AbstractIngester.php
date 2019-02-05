<?php declare(strict_types=1);

namespace App\Ingester\Ingester;

use App\Entity\EntityInterface;
use App\Ingester\Result\IngesterResult;
use App\Ingester\Source\IngesterSourceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

abstract class AbstractIngester implements IngesterInterface
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function ingest(IngesterSourceInterface $source, bool $dryRun = true): IngesterResult
    {
        $result = new IngesterResult(
            $this->getRegistryItemName(),
            $source->getRegistryItemName(),
            $dryRun
        );

        foreach ($source->getContent() as $data) {
            try {
                if (!$dryRun) {
                    $this->entityManager->persist($this->createEntity($data));
                    $this->entityManager->flush();
                }

                $result->addSuccess($data);
            } catch (Throwable $exception) {
                $result->addFailure($data);
            }
        }

        return $result;
    }

    abstract protected function createEntity(array $data): EntityInterface;
}
