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

namespace OAT\SimpleRoster\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use OAT\SimpleRoster\Exception\AssignmentNotFoundException;
use OAT\SimpleRoster\Exception\AssignmentUnavailableException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\UuidV6;

class User implements UserInterface, EntityInterface, PasswordAuthenticatedUserInterface
{
    private UuidV6 $id;
    private string $username;
    /** @var string|null */
    private ?string $plainPassword;
    private string $password;
    /** @var string[] */
    private array $roles;
    private ?string $groupId;
    /** @var Collection|Assignment[] */
    private Collection $assignments;

    public function __construct(
        UuidV6 $id,
        string $username,
        string $plainPassword,
        string $groupId = null,
        array $roles = []
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->plainPassword = $plainPassword;
        $this->groupId = $groupId;

        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }

        $this->roles = $roles;
        $this->assignments = new ArrayCollection();
    }

    public function getId(): UuidV6
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    public function hasGroupId(): bool
    {
        return null !== $this->groupId;
    }

    public function setGroupId(?string $groupId): self
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

    /**
     * @return Collection|Assignment[]
     */
    public function getCancellableAssignments(): Collection
    {
        $cancellableAssignments = new ArrayCollection();
        foreach ($this->getAssignments() as $assignment) {
            if ($assignment->isCancellable()) {
                $cancellableAssignments->add($assignment);
            }
        }

        return $cancellableAssignments;
    }

    public function addAssignment(Assignment $assignment): self
    {
        if (!$this->assignments->contains($assignment)) {
            $this->assignments[] = $assignment;
            $assignment->setUser($this);
        }

        return $this;
    }

    /**
     * @throws AssignmentNotFoundException
     */
    public function getLastAssignment(): Assignment
    {
        $lastAssignment = $this->assignments->last();

        if (!$lastAssignment instanceof Assignment) {
            throw new AssignmentNotFoundException(
                sprintf("User '%s' does not have any assignments.", $this->username)
            );
        }

        return $lastAssignment;
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

        foreach ($this->getAssignments() as $assignment) {
            if (!$assignment->isAvailable()) {
                continue;
            }

            $availableAssignments[] = $assignment;
        }

        return $availableAssignments;
    }

    /**
     * @throws AssignmentNotFoundException
     * @throws AssignmentUnavailableException
     */
    public function getAvailableAssignmentById(string $assignmentId): Assignment
    {
        $assignment = $this->getAssignmentById(new UuidV6($assignmentId));

        if ($assignment->isAvailable()) {
            return $assignment;
        }

        throw new AssignmentUnavailableException(
            sprintf("Assignment with id '%s' for user '%s' is unavailable.", $assignmentId, $this->getUsername())
        );
    }

    public function getRoles(): array
    {
        return $this->roles;
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

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    /**
     * @throws AssignmentNotFoundException
     */
    private function getAssignmentById(UuidV6 $assignmentId): Assignment
    {
        foreach ($this->getAssignments() as $assignment) {
            if ($assignment->getId()->equals($assignmentId)) {
                return $assignment;
            }
        }

        throw new AssignmentNotFoundException(
            sprintf("Assignment id '%s' not found for user '%s'.", $assignmentId, $this->getUsername())
        );
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }
}
