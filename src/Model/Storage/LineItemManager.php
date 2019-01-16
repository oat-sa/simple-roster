<?php declare(strict_types=1);

namespace App\Model\Storage;

use App\Model\LineItem;
use App\Model\ModelInterface;

class LineItemManager extends AbstractModelManager
{
    protected function getTable(): string
    {
        return 'line_items';
    }

    protected function getKeyFieldName(): string
    {
        return 'taoUri';
    }

    /**
     * @param ModelInterface $model
     * @return string
     * @throws \Exception
     */
    public function getKey(ModelInterface $model): string
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