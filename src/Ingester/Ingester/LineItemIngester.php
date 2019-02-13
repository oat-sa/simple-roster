<?php declare(strict_types=1);

namespace App\Ingester\Ingester;

use App\Entity\EntityInterface;
use App\Entity\Infrastructure;
use App\Entity\LineItem;
use DateTime;
use Exception;

class LineItemIngester extends AbstractIngester
{
    /** @var Infrastructure[] */
    private $infrastructureCollection;

    public function getRegistryItemName(): string
    {
        return 'line-item';
    }

    /**
     * @throws Exception
     */
    protected function prepare(): void
    {
        /** @var Infrastructure[] $infrastructures */
        $infrastructures = $this->managerRegistry->getRepository(Infrastructure::class)->findAll();

        if (empty($infrastructures)) {
            throw new Exception(
                sprintf("Cannot ingest '%s' since infrastructure table is empty.", $this->getRegistryItemName())
            );
        }

        foreach ($infrastructures as $infrastructure) {
            $this->infrastructureCollection[$infrastructure->getLabel()] = $infrastructure;
        }
    }

    /**
     * @throws Exception
     */
    protected function createEntity(array $data): EntityInterface
    {
        $lineItem = new LineItem();

        $lineItem
            ->setUri($data[0])
            ->setLabel($data[1])
            ->setSlug($data[2])
            ->setInfrastructure($this->infrastructureCollection[$data[3]]);

        if (isset($data[4]) && $data[5]) {
            $lineItem
                ->setStartAt($this->createDateTime($data[4]))
                ->setEndAt($this->createDateTime($data[5]));
        }

        return $lineItem;
    }

    /**
     * @throws Exception
     */
    private function createDateTime(string $value): DateTime
    {
        return (new DateTime())->setTimestamp((int)$value);
    }
}
