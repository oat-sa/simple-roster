<?php declare(strict_types=1);

namespace App\Ingesting\Ingester;

use App\Model\ModelInterface;

class LineItemsIngester extends AbstractIngester
{
    public function getType(): string
    {
        return self::TYPE_LINE_ITEM;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertRowToModel(array $row): ModelInterface
    {
        return $this->rowToModelMapper->map(
            $row,
            ['tao_uri', 'label', 'infrastructure_id', 'start_date_time', 'end_date_time']
        );
    }
}