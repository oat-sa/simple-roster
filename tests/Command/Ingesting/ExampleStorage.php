<?php

namespace App\Tests\Command\Ingesting;

use App\Model\AbstractModel;
use App\Model\Storage\AbstractModelStorage;
use App\Model\User;

class ExampleStorage extends AbstractModelStorage
{
    protected function getTable(): string
    {
        return 'example_table';
    }

    protected function getKeyFieldName(): string
    {
        return 'name';
    }

    /**
     * @param AbstractModel $model
     * @return string
     * @throws \Exception
     */
    public function getKey(AbstractModel $model): string
    {
        /** @var ExampleModel $model */
        $this->assertModelClass($model);

        return $model->getName();
    }

    protected function getModelClass(): string
    {
        return ExampleModel::class;
    }
}