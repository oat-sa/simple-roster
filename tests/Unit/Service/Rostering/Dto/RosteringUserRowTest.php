<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Rostering\Dto;

use OAT\SimpleRoster\Service\Rostering\Dto\RosteringUserRow;
use PHPUnit\Framework\TestCase;

class RosteringUserRowTest extends TestCase
{
    public function testItMapsKnownColumnsFromNormalizedRow(): void
    {
        $row = RosteringUserRow::fromNormalizedRow(
            [
                'user_username' => 'user_1',
                'user_password' => 'Pass123',
                'user_organizationId' => 'SCHOOL_1',
                'session_name' => 'session-1',
                'user_language' => 'en',
                'user_active' => 'true',
            ]
        );

        $this->assertSame('user_1', $row->getUserUsername());
        $this->assertSame('Pass123', $row->getUserPassword());
        $this->assertSame('SCHOOL_1', $row->getUserOrganizationId());
        $this->assertSame('session-1', $row->getSessionName());
        $this->assertSame('en', $row->getUserLanguage());
        $this->assertSame('true', $row->getUserActive());
        $this->assertTrue($row->isImportable());
    }

    public function testItTreatsMissingAndEmptyValuesAsNull(): void
    {
        $row = RosteringUserRow::fromNormalizedRow(
            [
                'user_username' => '',
                'session_name' => '',
            ]
        );

        $this->assertNull($row->getUserUsername());
        $this->assertNull($row->getSessionName());
        $this->assertFalse($row->isImportable());
    }
}
