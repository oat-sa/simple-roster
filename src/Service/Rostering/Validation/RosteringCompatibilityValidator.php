<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering\Validation;

use OAT\SimpleRoster\Service\Rostering\Exception\RosteringValidationException;

final class RosteringCompatibilityValidator
{
    public const FIELD_HIERARCHY_ORGANIZATION_ID = 'hierarchy_organizationId';

    private const MAX_ORGANIZATION_ID_LENGTH = 255;
    private const MAX_USER_USERNAME_LENGTH = 100;

    public function __construct(private readonly bool $isCompatibilityValidationEnabled)
    {
    }

    /**
     * @param array<string, string> $values
     */
    public function validate(array $values): void
    {
        if (!$this->isCompatibilityValidationEnabled || !$this->isStudentRow($values)) {
            return;
        }

        $organizationId = $this->fieldValue($values, self::FIELD_HIERARCHY_ORGANIZATION_ID);
        $parentOrganizationId = $this->fieldValue(
            $values,
            RosteringUserRowValidator::FIELD_HIERARCHY_PARENT_ORGANIZATION_ID
        );

        $this->validateHierarchyFieldsAreProvidedTogether($organizationId, $parentOrganizationId);

        $this->validateOrganizationId($organizationId, self::FIELD_HIERARCHY_ORGANIZATION_ID);
        $this->validateOrganizationId(
            $parentOrganizationId,
            RosteringUserRowValidator::FIELD_HIERARCHY_PARENT_ORGANIZATION_ID
        );

        $username = $this->fieldValue($values, RosteringUserRowValidator::FIELD_USER_USERNAME);
        if (strlen($username) > self::MAX_USER_USERNAME_LENGTH) {
            throw new RosteringValidationException(
                sprintf(
                    'Field "%s" exceeds max length (%d).',
                    RosteringUserRowValidator::FIELD_USER_USERNAME,
                    self::MAX_USER_USERNAME_LENGTH
                )
            );
        }
    }

    /**
     * @param array<string, string> $values
     */
    private function isStudentRow(array $values): bool
    {
        foreach (
            [
                RosteringUserRowValidator::FIELD_USER_USERNAME,
                RosteringUserRowValidator::FIELD_USER_PASSWORD,
                RosteringUserRowValidator::FIELD_SESSION_NAME,
            ] as $field
        ) {
            if ($this->fieldValue($values, $field) !== '') {
                return true;
            }
        }

        return false;
    }

    private function validateHierarchyFieldsAreProvidedTogether(
        string $organizationId,
        string $parentOrganizationId
    ): void {
        if (($organizationId === '') === ($parentOrganizationId === '')) {
            return;
        }

        throw new RosteringValidationException(
            sprintf(
                'Fields "%s" and "%s" must be provided together.',
                self::FIELD_HIERARCHY_ORGANIZATION_ID,
                RosteringUserRowValidator::FIELD_HIERARCHY_PARENT_ORGANIZATION_ID
            )
        );
    }

    private function validateOrganizationId(string $organizationId, string $fieldName): void
    {
        if ($organizationId === '') {
            throw new RosteringValidationException(sprintf('Field "%s" is required.', $fieldName));
        }

        if (strlen($organizationId) <= self::MAX_ORGANIZATION_ID_LENGTH) {
            return;
        }

        throw new RosteringValidationException(
            sprintf(
                'Field "%s" exceeds max length (%d).',
                $fieldName,
                self::MAX_ORGANIZATION_ID_LENGTH
            )
        );
    }

    /**
     * @param array<string, string> $values
     */
    private function fieldValue(array $values, string $field): string
    {
        return $values[$field] ?? '';
    }
}
