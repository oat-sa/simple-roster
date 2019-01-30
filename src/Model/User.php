<?php declare(strict_types=1);

namespace App\Model;

use App\ODM\Annotations\Item;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Item(table="users", primaryKey="username")
 */
class User implements ModelInterface, UserInterface, \Serializable
{
    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    private $username;

    /**
     * @var string The hashed password
     *
     * @Assert\NotBlank
     */
    private $password;

    /**
     * @var Assignment[]
     *
     * @Assert\Valid
     */
    private $assignments = [];

    private $roles = [];

    /**
     * @param string $username
     * @param string $password
     * @param string $salt
     * @param Assignment[] $assignments
     */
    public function __construct(string $username, array $assignments = [])
    {
        $this->username = $username;

        if (!empty($assignments)) {
            $this->addAssignment(...$assignments);
        }
    }

    /**
     * @param Assignment ...$assignments
     * @return int amount of actually added assignments
     */
    public function addAssignment(Assignment ...$assignments): int
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

    /**
     * Setter method is required for the serializer to be able to denormalize
     * the nested raw data from DynamoDB.
     *
     * Only setter denormalization works for nested data at the moment:
     * @see https://github.com/symfony/symfony/issues/28081
     *
     * @param Assignment[] $assignments
     * @return User
     */
    public function setAssignments(array $assignments): self
    {
        $this->assignments = [];

        $this->addAssignment(...$assignments);

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return User
     */
    public function setPassword(string $password): User
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return Assignment[]
     */
    public function getAssignments(): array
    {
        return $this->assignments;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "argon2i" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here like
        // $this->plainPassword = null;
    }

    public function serialize(): string
    {
        return serialize([
            $this->username,
            $this->password
        ]);
    }

    public function unserialize($serialized): void
    {
        list (
            $this->username,
            $this->password,
            ) = unserialize($serialized);
    }
}
