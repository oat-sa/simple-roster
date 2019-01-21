<?php declare(strict_types=1);

namespace App\Model;

use Doctrine\Common\Collections\ArrayCollection;
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
     * @var ArrayCollection
     */
    private $assignments;

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
        $this->salt = $salt;
        $this->assignments = new ArrayCollection($assignments);
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
                /** @var Assignment $assignment */
                if ($assignment->getLineItemTaoUri() === $assignmentToAdd->getLineItemTaoUri() && $assignment->getState() === Assignment::STATE_READY) {
                    $alreadyExists = true;
                }
            }

            if (!$alreadyExists) {
                $this->assignments->add($assignmentToAdd);
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

    public function getAssignments(): array
    {
        return $this->assignments->toArray();
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
