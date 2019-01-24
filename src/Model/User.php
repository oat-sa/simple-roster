<?php declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

class User implements ModelInterface
{
    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    private $username;

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
     * @param string $username
     * @param string $password
     * @param Assignment[] $assignments
     */
    public function __construct(string $username, string $password, array $assignments = [])
    {
        $this->username = $username;
        $this->password = $password;

        if (!empty($assignments)) {
            $this->addAssignments(...$assignments);
        }
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

    public function getUsername(): string
    {
        return $this->username;
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
}
