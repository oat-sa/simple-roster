<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Rostering\Validation;

use OAT\SimpleRoster\Service\Rostering\Exception\RosteringValidationException;
use OAT\SimpleRoster\Service\Rostering\Validation\RosteringUserRowValidator;
use PHPUnit\Framework\TestCase;

class RosteringUserRowValidatorTest extends TestCase
{
    public function testItRejectsInvalidUserActiveValue(): void
    {
        $subject = new RosteringUserRowValidator();

        $this->expectException(RosteringValidationException::class);
        $this->expectExceptionMessage('must be one of: true, false, 1, 0');

        $subject->parseUserActive('yes');
    }

    public function testItParsesBooleanUserActiveValues(): void
    {
        $subject = new RosteringUserRowValidator();

        $this->assertNull($subject->parseUserActive(null));
        $this->assertTrue($subject->parseUserActive('true'));
        $this->assertTrue($subject->parseUserActive('1'));
        $this->assertFalse($subject->parseUserActive('false'));
        $this->assertFalse($subject->parseUserActive('0'));
    }

    public function testItRejectsUsernameWithWhitespaces(): void
    {
        $subject = new RosteringUserRowValidator();

        $this->expectException(RosteringValidationException::class);
        $this->expectExceptionMessage('cannot contain whitespaces');

        $subject->validateUsername('bad username');
    }

    public function testItRejectsOrganizationIdWithWhitespaces(): void
    {
        $subject = new RosteringUserRowValidator();

        $this->expectException(RosteringValidationException::class);
        $this->expectExceptionMessage('cannot contain whitespaces');

        $subject->validateOrganizationId('BAD ORG');
    }
}
