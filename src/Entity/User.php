<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, EntityInterface
{
    /** @var int */
    private $id;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var ArrayCollection|Assignment[] */
    private $assignments;

    /** @var string[] */
    private $roles = [];

    /** @var string */
    private $plainPassword;

    public function __construct()
    {
        $this->assignments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function setPlainPassword(string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @return Collection|Assignment[]
     */
    public function getAssignments(): Collection
    {
        return $this->assignments;
    }

    public function addAssignment(Assignment $assignment): self
    {
        if (!$this->assignments->contains($assignment)) {
            $this->assignments[] = $assignment;
            $assignment->setUser($this);
        }

        return $this;
    }

    public function getLastAssignment(): ?Assignment
    {
        return $this->assignments->last() ?: null;
    }

    public function removeAssignment(Assignment $assignment): self
    {
        if ($this->assignments->contains($assignment)) {
            $this->assignments->removeElement($assignment);
            // set the owning side to null (unless already changed)
            if ($assignment->getUser() === $this) {
                $assignment->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Assignment[]
     */
    public function getAvailableAssignments(): array
    {
        $availableAssignments = [];
        foreach ($this->getAssignments() as $assignment) {
            // cancelled assignment cannot be listed
            if ($assignment->isCancelled()) {
                continue;
            }

            $availableAssignments[] = $assignment;
        }

        return $availableAssignments;
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
        $this->plainPassword = null;
    }
}
