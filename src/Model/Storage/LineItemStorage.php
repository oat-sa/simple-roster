<?php

namespace App\Model\Storage;

use App\Model\LineItem;
use App\Model\Model;

class LineItemStorage extends ModelStorage
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
     * @param Model $model
     * @return string
     * @throws \Exception
     */
    public function getKey(Model $model): string
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