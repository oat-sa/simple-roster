<?php

namespace App\Model;

class User extends Model
{
    /**
     * @var string
     */
    private $login;

    /**
     * @var string
     */
    private $password;

    /**
     * @var array
     */
    private $assignments = [];

    /**
     * @inheritdoc
     */
    public static function createFromArray(array $data): Model
    {
        $model = new self();
        $model->login = $data['login'] ?? null;
        $model->password = $data['password'] ?? null;
        $model->assignments = $data['assignments'] ?? [];
        return $model;
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return [
            'login' => $this->login,
            'password' => $this->password,
            'assignments' => $this->assignments,
        ];
    }

    /**
     * @param String[] $uris
     */
    public function addAssignments(array $uris): void
    {
        foreach ($uris as $uri) {
            if (!array_key_exists($uri, $this->assignments)) {
                $this->assignments[] = [
                    'line_item_tao_uri' => $uri,
                ];
            }
        }
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @inheritdoc
     */
    public function validate(): void
    {
        if (!$this->login) {
            $this->throwExceptionRequiredFieldEmpty('login');
        }
        if (!$this->password) {
            $this->throwExceptionRequiredFieldEmpty('password');
        }
    }
}
