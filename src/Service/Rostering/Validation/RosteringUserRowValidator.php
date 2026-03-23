<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering\Validation;

use OAT\SimpleRoster\Service\Rostering\Exception\RosteringValidationException;

final class RosteringUserRowValidator
{
    public const FIELD_USER_USERNAME = 'user_username';
    public const FIELD_USER_PASSWORD = 'user_password';
    public const FIELD_USER_ORGANIZATION_ID = 'user_organizationId';
    public const FIELD_SESSION_NAME = 'session_name';
    public const FIELD_USER_ACTIVE = 'user_active';
    public const FIELD_USER_LANGUAGE = 'user_language';

    private const MAX_USER_USERNAME_LENGTH = 255;
    private const MAX_USER_ORGANIZATION_ID_LENGTH = 255;
    private const MAX_SESSION_NAME_LENGTH = 255;

    public function validateUsername(string $username): void
    {
        if ($username === '') {
            throw new RosteringValidationException(
                sprintf('Field "%s" is required.', self::FIELD_USER_USERNAME)
            );
        }

        if (preg_match('/\s/', $username) === 1) {
            throw new RosteringValidationException(
                sprintf('Field "%s" cannot contain whitespaces.', self::FIELD_USER_USERNAME)
            );
        }

        if (strlen($username) > self::MAX_USER_USERNAME_LENGTH) {
            throw new RosteringValidationException(
                sprintf(
                    'Field "%s" exceeds max length (%d).',
                    self::FIELD_USER_USERNAME,
                    self::MAX_USER_USERNAME_LENGTH
                )
            );
        }
    }

    public function validateOrganizationId(string $organizationId): void
    {
        if (preg_match('/\s/', $organizationId) === 1) {
            throw new RosteringValidationException(
                sprintf('Field "%s" cannot contain whitespaces.', self::FIELD_USER_ORGANIZATION_ID)
            );
        }

        if (strlen($organizationId) > self::MAX_USER_ORGANIZATION_ID_LENGTH) {
            throw new RosteringValidationException(
                sprintf(
                    'Field "%s" exceeds max length (%d).',
                    self::FIELD_USER_ORGANIZATION_ID,
                    self::MAX_USER_ORGANIZATION_ID_LENGTH
                )
            );
        }
    }

    public function validateSessionName(string $sessionName): void
    {
        if (preg_match('/\s/', $sessionName) === 1) {
            throw new RosteringValidationException(
                sprintf('Field "%s" cannot contain whitespaces.', self::FIELD_SESSION_NAME)
            );
        }

        if (strlen($sessionName) > self::MAX_SESSION_NAME_LENGTH) {
            throw new RosteringValidationException(
                sprintf(
                    'Field "%s" exceeds max length (%d).',
                    self::FIELD_SESSION_NAME,
                    self::MAX_SESSION_NAME_LENGTH
                )
            );
        }
    }

    public function parseUserActive(?string $userActive): ?bool
    {
        if ($userActive === null) {
            return null;
        }

        $normalized = strtolower($userActive);

        if ($normalized === '1' || $normalized === 'true') {
            return true;
        }

        if ($normalized === '0' || $normalized === 'false') {
            return false;
        }

        throw new RosteringValidationException(
            sprintf(
                'Field "%s" must be one of: true, false, 1, 0.',
                self::FIELD_USER_ACTIVE
            )
        );
    }
}
