<?php declare(strict_types=1);

namespace App\Ingesting\Ingester;

use App\Ingesting\RowToModelMapper\LineItemRowToModelMapper;
use App\Model\ModelInterface;
use App\Model\Storage\LineItemManager;
use App\Model\Validation\LineItemValidator;

class LineItemsIngester extends AbstractIngester
{
    public function __construct(LineItemManager $modelStorage, LineItemRowToModelMapper $rowToModelMapper, LineItemValidator $lineItemValidator)
    {
        parent::__construct($modelStorage, $rowToModelMapper, $lineItemValidator);
    }

    /**
     * {@inheritdoc}
     */
    protected function convertRowToModel(array $row): ModelInterface
    {
        return $this->rowToModelMapper->map($row,
            ['tao_uri', 'title', 'infrastructure_id', 'start_date_time', 'end_date_time']
        );
    }
}