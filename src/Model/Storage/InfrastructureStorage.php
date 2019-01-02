<?php

namespace App\Model\Storage;

use App\Model\Model;
use App\Model\Infrastructure;

class InfrastructureStorage extends ModelStorage
{
    protected function getTable(): string
    {
        return 'infrastructures';
    }

    protected function getKeyFieldName(): string
    {
        return 'id';
    }

    /**
     * @param Model $model
     * @return string
     * @throws \Exception
     */
    public function getKey(Model $model): string
    {
        /** @var Infrastructure $model */
        $this->assertModelClass($model);

        return $model->getId();
    }

    protected function getModelClass(): string
    {
        return Infrastructure::class;
    }
}