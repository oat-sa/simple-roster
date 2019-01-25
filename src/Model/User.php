<?php declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

class User implements UserInterface, ModelInterface
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
     * @param $login
     * @param $password
     * @param Assignment[] $assignments
     */
    public function __construct(string $login, string $password, array $assignments = [])
    {
        $this->login = $login;
        $this->password = $password;
        $this->assignments = $assignments;
    }

    /**
     * @param Assignment ...$assignments
     * @return int amount of actually added assignments
     */
    public function addAssignments(Assignment ...$assignments): int
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

    /**
     * Returns the roles granted to the user.
     *
     * @return (Role|string)[] The user roles
     */
    public function getRoles()
    {
        return ['ROLE_USER'];
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->login;
    }

    public function eraseCredentials()
    {
        // do nothing
    }
}
