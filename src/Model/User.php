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
        $model->salt = $data['salt'] ?? null;

        if (!empty($data['assignments'])) {
            foreach ($data['assignments'] as $assignmentArray) {
                $model->assignments[] = Assignment::createFromArray($assignmentArray);
            }
        }

        return $model;
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        $assignmentArrays = [];
        foreach ($this->assignments as $assignment) {
            $assignmentArrays[] = $assignment->toArray();
        }

        return [
            'login' => $this->login,
            'password' => $this->password,
            'salt' => $this->salt,
            'assignments' => $assignmentArrays,
        ];
    }

    /**
     * @param array $uris
     * @return int amount of actually added assignments
     */
    public function addAssignments(array $uris): int
    {
        $addedCount = 0;

        foreach ($uris as $uri) {
            $alreadyExists = false;
            foreach ($this->assignments as $assignment) {
                if ($assignment->getLineItemTaoUri() === $uri && $assignment->getState() === Assignment::STATE_READY) {
                    $alreadyExists = true;
                }
            }

            if (!$alreadyExists) {
                $newAssignment = new Assignment();
                $newAssignment->setLineItemTaoUri($uri);
                $this->assignments[] = $newAssignment;
                $addedCount++;
            }
        }

        return $addedCount;
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
        foreach ($this->assignments as $assignment) {
            $assignment->validate();
        }
    }

    /**
     * @return Assignment[]
     */
    public function getAssignments(): array
    {
        return $this->assignments;
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
