<?php

namespace App\Model\Storage;

use App\Model\AbstractModel;
use App\Model\User;

class UserStorage extends AbstractModelStorage
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
     * @param AbstractModel $model
     * @return string
     * @throws \Exception
     */
    public function getKey(AbstractModel $model): string
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