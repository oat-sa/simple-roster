<?php declare(strict_types=1);

namespace App\ModelManager;

use App\Model\ModelInterface;
use App\Model\Infrastructure;

class InfrastructureManager extends AbstractModelManager
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
     * @param ModelInterface $model
     * @return string
     * @throws \Exception
     */
    public function getKey(ModelInterface $model): string
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