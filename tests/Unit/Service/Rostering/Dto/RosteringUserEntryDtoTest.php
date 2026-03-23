<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Rostering\Dto;

use OAT\SimpleRoster\Service\Rostering\Dto\RosteringUserEntryDto;
use PHPUnit\Framework\TestCase;

class RosteringUserEntryDtoTest extends TestCase
{
    public function testItExposesAssignedValues(): void
    {
        $entryDto = new RosteringUserEntryDto(
            'user_1',
            'Pass123',
            'SCHOOL_1',
            'session-1',
            'en',
            true
        );

        $this->assertSame('user_1', $entryDto->getUserUsername());
        $this->assertSame('Pass123', $entryDto->getUserPassword());
        $this->assertSame('SCHOOL_1', $entryDto->getUserOrganizationId());
        $this->assertSame('session-1', $entryDto->getSessionName());
        $this->assertSame('en', $entryDto->getUserLanguage());
        $this->assertTrue($entryDto->getUserActive());
        $this->assertTrue($entryDto->isImportable());
    }

    public function testItIsNotImportableWhenAllFieldsAreNull(): void
    {
        $entryDto = new RosteringUserEntryDto(null, null, null, null, null, null);

        $this->assertFalse($entryDto->isImportable());
    }
}
