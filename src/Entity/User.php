<?php

/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace App\Entity;

use App\Exception\AssignmentNotFoundException;
use Carbon\Carbon;
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

    /** @var string|null */
    private $plainPassword;

    /** @var string|null */
    private $groupId;

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

    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    public function hasGroupId(): bool
    {
        return null !== $this->groupId;
    }

    public function setGroupId(string $groupId): self
    {
        $this->groupId = $groupId;

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

    public function getLastAssignment(): Assignment
    {
        return $this->assignments->last();
    }

    public function removeAssignment(Assignment $assignment): self
    {
        if ($this->assignments->contains($assignment)) {
            $this->assignments->removeElement($assignment);
        }

        return $this;
    }

    /**
     * @return Assignment[]
     */
    public function getAvailableAssignments(): array
    {
        $availableAssignments = [];
        $now = Carbon::now()->toDateTime();

        foreach ($this->getAssignments() as $assignment) {
            if ($assignment->isCancelled()) {
                continue;
            }

            if (!$assignment->getLineItem()->isAvailableForDate($now)) {
                continue;
            }

            $availableAssignments[] = $assignment;
        }

        return $availableAssignments;
    }

    /**
     * @throws AssignmentNotFoundException
     */
    public function getAvailableAssignmentById(int $assignmentId): Assignment
    {
        foreach ($this->getAvailableAssignments() as $assignment) {
            if ($assignment->getId() === $assignmentId) {
                return $assignment;
            }
        }

        throw new AssignmentNotFoundException(
            sprintf("Assignment id '%s' not found for user '%s'.", $assignmentId, $this->getUsername())
        );
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
    public function getSalt(): ?string
    {
        // not needed when using the "argon2i" algorithm in security.yaml
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }
}
