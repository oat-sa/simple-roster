<?php declare(strict_types=1);

namespace App\Model\Storage;

use App\Model\LineItem;
use App\Model\AbstractModel;

class LineItemStorage extends AbstractModelStorage
{
    protected function getTable(): string
    {
        return 'line_items';
    }

    protected function getKeyFieldName(): string
    {
        return 'tao_uri';
    }

    /**
     * @param AbstractModel $model
     * @return string
     * @throws \Exception
     */
    public function getKey(AbstractModel $model): string
    {
        /** @var LineItem $model */
        $this->assertModelClass($model);

        return $model->getTaoUri();
    }

    protected function getModelClass(): string
    {
        return LineItem::class;
    }
}