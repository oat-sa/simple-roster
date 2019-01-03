<?php

namespace App\Model;

use Symfony\Component\Security\Core\Encoder\EncoderAwareInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User extends Model implements UserInterface, EncoderAwareInterface
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
     * @var string
     */
    private $salt;

    /**
     * @var Assignment[]
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

    public function setPasswordAndSalt(string $password, string $salt): void
    {
        $this->password = $password;
        $this->salt = $salt;
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

    public function getRoles()
    {
        return [];
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function getUsername()
    {
        return $this->login;
    }

    public function eraseCredentials()
    {
        $this->password = '***';
        $this->salt = '***';
    }

    public function getEncoderName()
    {
        return 'harsh';
    }
}
