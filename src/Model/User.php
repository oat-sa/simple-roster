<?php

namespace App\Model;

class User extends AbstractModel
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
     * @var Assignment[]
     */
    private $assignments = [];

    /**
     * @inheritdoc
     */
    public static function createFromArray(array $data): AbstractModel
    {
        $model = new self();
        $model->login = $data['login'] ?? null;
        $model->password = $data['password'] ?? null;

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
}
