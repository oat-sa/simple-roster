<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering\Dto;

use OAT\SimpleRoster\Service\Rostering\Validation\RosteringUserRowValidator;

final class RosteringUserEntryDtoFactory
{
    public function __construct(private readonly RosteringUserRowValidator $rowValidator)
    {
    }

    /**
     * @param array<string, string> $values
     */
    public function fromArray(array $values): RosteringUserEntryDto
    {
        $username = $this->fieldValueOrNull($values, RosteringUserRowValidator::FIELD_USER_USERNAME);
        $password = $this->fieldValueOrNull($values, RosteringUserRowValidator::FIELD_USER_PASSWORD);
        $organizationId = $this->fieldValueOrNull($values, RosteringUserRowValidator::FIELD_USER_ORGANIZATION_ID);
        $sessionName = $this->fieldValueOrNull($values, RosteringUserRowValidator::FIELD_SESSION_NAME);
        $userLanguage = $this->fieldValueOrNull($values, RosteringUserRowValidator::FIELD_USER_LANGUAGE);
        $userActiveRaw = $this->fieldValueOrNull($values, RosteringUserRowValidator::FIELD_USER_ACTIVE);
        if (
            null === $username
            && null === $password
            && null === $organizationId
            && null === $sessionName
            && null === $userLanguage
            && null === $userActiveRaw
        ) {
            return new RosteringUserEntryDto(null, null, null, null, null, null);
        }

        $this->rowValidator->validateUsername($username ?? '');

        if (null !== $organizationId) {
            $this->rowValidator->validateOrganizationId($organizationId);
        }

        if (null !== $sessionName) {
            $this->rowValidator->validateSessionName($sessionName);
        }

        $isUserActive = $this->rowValidator->parseUserActive($userActiveRaw);

        return new RosteringUserEntryDto(
            $username,
            $password,
            $organizationId,
            $sessionName,
            $userLanguage,
            $isUserActive
        );
    }

    /**
     * @param array<string, string> $normalizedRow
     */
    private function fieldValueOrNull(array $normalizedRow, string $field): ?string
    {
        if (!array_key_exists($field, $normalizedRow)) {
            return null;
        }

        return $normalizedRow[$field] === '' ? null : $normalizedRow[$field];
    }
}
