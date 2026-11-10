<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Rostering\Dto;

use OAT\SimpleRoster\Service\Rostering\Dto\RosteringUserEntryDtoFactory;
use OAT\SimpleRoster\Service\Rostering\Exception\RosteringValidationException;
use OAT\SimpleRoster\Service\Rostering\Validation\RosteringCompatibilityValidator;
use OAT\SimpleRoster\Service\Rostering\Validation\RosteringUserRowValidator;
use PHPUnit\Framework\TestCase;

class RosteringUserEntryDtoFactoryTest extends TestCase
{
    public function testItMapsKnownColumnsFromNormalizedRowAndParsesUserActive(): void
    {
        $subject = $this->createSubject();

        $entryDto = $subject->fromArray(
            [
                RosteringUserRowValidator::FIELD_USER_USERNAME => 'user_1',
                RosteringUserRowValidator::FIELD_USER_PASSWORD => 'Pass123',
                RosteringUserRowValidator::FIELD_HIERARCHY_PARENT_ORGANIZATION_ID => 'COLLEGE_1',
                RosteringUserRowValidator::FIELD_SESSION_NAME => 'session-1',
                RosteringUserRowValidator::FIELD_USER_LANGUAGE => 'en',
                RosteringUserRowValidator::FIELD_USER_ACTIVE => 'true',
            ]
        );

        $this->assertSame('user_1', $entryDto->getUserUsername());
        $this->assertSame('Pass123', $entryDto->getUserPassword());
        $this->assertSame('COLLEGE_1', $entryDto->getParentOrganizationId());
        $this->assertSame('session-1', $entryDto->getSessionName());
        $this->assertSame('en', $entryDto->getUserLanguage());
        $this->assertTrue($entryDto->getUserActive());
        $this->assertTrue($entryDto->isImportable());
    }

    public function testItTreatsMissingAndEmptyValuesAsNullForNonImportableRows(): void
    {
        $subject = $this->createSubject();

        $entryDto = $subject->fromArray(
            [
                RosteringUserRowValidator::FIELD_USER_USERNAME => '',
                RosteringUserRowValidator::FIELD_SESSION_NAME => '',
            ]
        );

        $this->assertNull($entryDto->getUserUsername());
        $this->assertNull($entryDto->getSessionName());
        $this->assertFalse($entryDto->isImportable());
    }

    public function testItValidatesImportableRowsDuringCreation(): void
    {
        $subject = $this->createSubject();

        $this->expectException(RosteringValidationException::class);
        $this->expectExceptionMessage('Field "user_username" is required.');

        $subject->fromArray(
            [
                RosteringUserRowValidator::FIELD_USER_PASSWORD => 'Pass123',
            ]
        );
    }

    public function testItKeepsTheStandaloneUsernameLimitWhenCompatibilityValidationIsDisabled(): void
    {
        $subject = $this->createSubject();
        $username = str_repeat('u', 101);

        $entryDto = $subject->fromArray(
            [
                RosteringUserRowValidator::FIELD_USER_USERNAME => $username,
            ]
        );

        $this->assertSame($username, $entryDto->getUserUsername());
    }

    private function createSubject(): RosteringUserEntryDtoFactory
    {
        return new RosteringUserEntryDtoFactory(
            new RosteringUserRowValidator(),
            new RosteringCompatibilityValidator(false)
        );
    }
}
