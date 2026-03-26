<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering\Dto;

final class RosteringUserEntryDto
{
    public function __construct(
        private readonly ?string $userUsername,
        private readonly ?string $userPassword,
        private readonly ?string $userOrganizationId,
        private readonly ?string $sessionName,
        private readonly ?string $userLanguage,
        private readonly ?bool $userActive
    ) {
    }

    public function isImportable(): bool
    {
        return $this->userUsername !== null
            || $this->userPassword !== null
            || $this->userOrganizationId !== null
            || $this->sessionName !== null
            || $this->userLanguage !== null
            || $this->userActive !== null;
    }

    public function getUserUsername(): ?string
    {
        return $this->userUsername;
    }

    public function getUserPassword(): ?string
    {
        return $this->userPassword;
    }

    public function getUserOrganizationId(): ?string
    {
        return $this->userOrganizationId;
    }

    public function getSessionName(): ?string
    {
        return $this->sessionName;
    }

    public function getUserLanguage(): ?string
    {
        return $this->userLanguage;
    }

    public function getUserActive(): ?bool
    {
        return $this->userActive;
    }
}
