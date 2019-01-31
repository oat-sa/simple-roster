<?php

namespace App\Model;

class ApiAccessToken extends Model
{
    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $userLogin;

    /**
     * @inheritdoc
     */
    public static function createFromArray(array $data): Model
    {
        $model = new self();
        $model->token = $data['token'] ?? null;
        $model->userLogin = $data['user_login'] ?? null;
        return $model;
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'userLogin' => $this->userLogin,
        ];
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function setUser(User $user): void
    {
        $this->userLogin = $user->getLogin();
    }

    public function validate(): void
    {
    }
}
