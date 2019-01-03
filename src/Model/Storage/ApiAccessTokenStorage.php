<?php

namespace App\Model\Storage;

use App\Model\ApiAccessToken;
use App\Model\Model;

class ApiAccessTokenStorage extends ModelStorage
{
    protected function getTable(): string
    {
        return 'api_access_tokens';
    }

    protected function getKeyFieldName(): string
    {
        return 'token';
    }

    /**
     * @param Model $model
     * @return string
     * @throws \Exception
     */
    public function getKey(Model $model): string
    {
        /** @var ApiAccessToken $model */
        $this->assertModelClass($model);

        return $model->getToken();
    }

    protected function getModelClass(): string
    {
        return ApiAccessToken::class;
    }
}