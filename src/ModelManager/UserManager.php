<?php declare(strict_types=1);

namespace App\ModelManager;

use App\Model\ModelInterface;
use App\Model\User;

class UserManager extends AbstractModelManager
{
    protected function getTable(): string
    {
        return 'users';
    }

    protected function getKeyFieldName(): string
    {
        return 'username';
    }

    /**
     * @param ModelInterface $model
     * @return string
     * @throws \Exception
     */
    public function getKey(ModelInterface $model): string
    {
        /** @var User $model */
        $this->assertModelClass($model);

        return $model->getUsername();
    }

    protected function getModelClass(): string
    {
        return User::class;
    }
}