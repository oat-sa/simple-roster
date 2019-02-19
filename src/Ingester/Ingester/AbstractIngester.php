<?php declare(strict_types=1);

namespace App\Ingester\Ingester;

use App\Entity\EntityInterface;
use App\Ingester\Result\IngesterResult;
use App\Ingester\Result\IngesterResultFailure;
use App\Ingester\Source\IngesterSourceInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Throwable;

abstract class AbstractIngester implements IngesterInterface
{
    private const BATCH_SIZE = 3000;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function ingest(IngesterSourceInterface $source, bool $dryRun = true): IngesterResult
    {
        $result = new IngesterResult(
            $this->getRegistryItemName(),
            $source->getRegistryItemName(),
            $dryRun
        );

        if (!$dryRun) {
            $this->prepare();
        }

        $lineNumber = 1;
        foreach ($source->getContent() as $data) {
            try {
                if (!$dryRun) {
                    $this->managerRegistry->getManager()->persist($this->createEntity($data));


                    if (($lineNumber % self::BATCH_SIZE) === 0) {
                        $this->managerRegistry->getManager()->flush();
                        //$this->managerRegistry->getManager()->clear();
                        //$this->prepare();
                        echo 'processed : ' . $lineNumber . PHP_EOL;
                    }
                }

                $result->addSuccess();
            } catch (Throwable $exception) {
                if (!$dryRun) {
                    $this->managerRegistry->resetManager();
                }

                $result->addFailure(
                    new IngesterResultFailure($lineNumber, $data, $exception->getMessage())
                );
            }

            $lineNumber++;
        }

        $this->managerRegistry->getManager()->flush();
        $this->managerRegistry->getManager()->clear();

        return $result;
    }

    protected function prepare(): void
    {
        return;
    }

    abstract protected function createEntity(array $data): EntityInterface;
}
