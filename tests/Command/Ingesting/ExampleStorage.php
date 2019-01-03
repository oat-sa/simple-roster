<?php

namespace App\Tests\Command\Ingesting;

use App\Model\Model;
use App\Model\Storage\ModelStorage;
use App\Model\User;

class ExampleStorage extends ModelStorage
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
     * @param Model $model
     * @return string
     * @throws \Exception
     */
    public function getKey(Model $model): string
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