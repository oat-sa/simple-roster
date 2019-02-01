<?php declare(strict_types=1);

namespace App\Ingester\Ingester;

use App\Entity\EntityInterface;
use App\Entity\LineItem;

class LineItemIngester extends AbstractIngester
{
    public function getName(): string
    {
        return 'lineItem';
    }

    protected function createEntity(array $data): EntityInterface
    {
        $lineItem = new LineItem();

        return $lineItem->setLabel($data[0] ?? '');
    }
}
