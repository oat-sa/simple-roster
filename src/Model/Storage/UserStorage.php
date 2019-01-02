<?php

namespace App\Model\Storage;

use App\Model\Model;
use App\Model\User;

class UserStorage extends ModelStorage
{
    protected function getTable(): string
    {
        return 'users';
    }

    protected function getKeyFieldName(): string
    {
        return 'login';
    }

    /**
     * @param Model $model
     * @return string
     * @throws \Exception
     */
    public function getKey(Model $model): string
    {
        /** @var User $model */
        $this->assertModelClass($model);

        return $model->getLogin();
    }

    protected function getModelClass(): string
    {
        return User::class;
    }
}