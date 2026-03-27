<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Rostering\Dto;

use OAT\SimpleRoster\Service\Rostering\Dto\RosteringUserEntryDtoFactory;
use OAT\SimpleRoster\Service\Rostering\Exception\RosteringValidationException;
use OAT\SimpleRoster\Service\Rostering\Validation\RosteringUserRowValidator;
use PHPUnit\Framework\TestCase;

class RosteringUserEntryDtoFactoryTest extends TestCase
{
    public function testItMapsKnownColumnsFromNormalizedRowAndParsesUserActive(): void
    {
        $subject = new RosteringUserEntryDtoFactory(new RosteringUserRowValidator());

        $entryDto = $subject->fromArray(
            [
                RosteringUserRowValidator::FIELD_USER_USERNAME => 'user_1',
                RosteringUserRowValidator::FIELD_USER_PASSWORD => 'Pass123',
                RosteringUserRowValidator::FIELD_USER_ORGANIZATION_ID => 'SCHOOL_1',
                RosteringUserRowValidator::FIELD_SESSION_NAME => 'session-1',
                RosteringUserRowValidator::FIELD_USER_LANGUAGE => 'en',
                RosteringUserRowValidator::FIELD_USER_ACTIVE => 'true',
            ]
        );

        $this->assertSame('user_1', $entryDto->getUserUsername());
        $this->assertSame('Pass123', $entryDto->getUserPassword());
        $this->assertSame('SCHOOL_1', $entryDto->getUserOrganizationId());
        $this->assertSame('session-1', $entryDto->getSessionName());
        $this->assertSame('en', $entryDto->getUserLanguage());
        $this->assertTrue($entryDto->getUserActive());
        $this->assertTrue($entryDto->isImportable());
    }

    public function testItTreatsMissingAndEmptyValuesAsNullForNonImportableRows(): void
    {
        $subject = new RosteringUserEntryDtoFactory(new RosteringUserRowValidator());

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
        $subject = new RosteringUserEntryDtoFactory(new RosteringUserRowValidator());

        $this->expectException(RosteringValidationException::class);
        $this->expectExceptionMessage('Field "user_username" is required.');

        $subject->fromArray(
            [
                RosteringUserRowValidator::FIELD_USER_ORGANIZATION_ID => 'SCHOOL_1',
            ]
        );
    }
}
