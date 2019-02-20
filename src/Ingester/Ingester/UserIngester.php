<?php declare(strict_types=1);

namespace App\Ingester\Ingester;

use App\Entity\Assignment;
use App\Entity\EntityInterface;
use App\Entity\LineItem;
use App\Entity\User;
use Exception;

class UserIngester extends AbstractIngester
{
    /** @var LineItem[] */
    private $lineItemCollection;

    public function getRegistryItemName(): string
    {
        return 'user';
    }

    /**
     * @throws Exception
     */
    protected function prepare(): void
    {
        /** @var LineItem[] $lineItems */
        $lineItems = $this->managerRegistry->getRepository(LineItem::class)->findAll();

        if (empty($lineItems)) {
            throw new Exception(
                sprintf("Cannot ingest '%s' since line-item table is empty.", $this->getRegistryItemName())
            );
        }

        foreach ($lineItems as $lineItem) {
            $this->lineItemCollection[$lineItem->getSlug()] = $lineItem;
        }
    }

    protected function createEntity(array $data): EntityInterface
    {
        $assignment = new Assignment();
        $assignment
            ->setLineItem($this->lineItemCollection[$data['slug']])
            ->setState(Assignment::STATE_READY);

        return (new User())
            ->setUsername($data['username'])
            ->setPassword($data['password'])
            ->setPlainPassword($data['password'])
            ->addAssignment($assignment);
    }
}
