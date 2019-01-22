<?php declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Encoder\EncoderAwareInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements ModelInterface, UserInterface, EncoderAwareInterface
{
    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    private $login;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    private $password;

    /**
     * @var Assignment[]
     */
    private $assignments = [];

    /**
     * @var string
     */
    private $salt;

    /**
     * @param $login
     * @param $password
     * @param Assignment[] $assignments
     */
    public function __construct(string $login, string $password, ?string $salt = null, ?array $assignments = [])
    {
        $this->login = $login;
        $this->password = $password;
        $this->assignments = $assignments;
        $this->salt = $salt;
    }

    /**
     * @param Assignment[] $assignments
     * @return int amount of actually added assignments
     */
    public function addAssignments(array $assignments): int
    {
        $addedCount = 0;

        foreach ($assignments as $assignmentToAdd) {
            $alreadyExists = false;
            foreach ($this->assignments as $assignment) {
                if ($assignment->getLineItemTaoUri() === $assignmentToAdd->getLineItemTaoUri() && $assignment->getState() === Assignment::STATE_READY) {
                    $alreadyExists = true;
                }
            }

            if (!$alreadyExists) {
                $this->assignments[] = $assignmentToAdd;
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
     * @return Assignment[]
     */
    public function getAssignments(): array
    {
        return $this->assignments;
    }

    public function getRoles()
    {
        return ['ROLE_USER'];
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

    public function setPasswordAndSalt(string $password, string $salt): void
    {
        $this->password = $password;
        $this->salt = $salt;
    }
}
