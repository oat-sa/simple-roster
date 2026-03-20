<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Request\Validator\RosteringImport;

use OAT\SimpleRoster\Request\Validator\RosteringImport\RosteringImportReferenceIdValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validation;

class RosteringImportReferenceIdValidatorTest extends TestCase
{
    public function testItReturnsTrimmedReferenceIdForValidInput(): void
    {
        $subject = new RosteringImportReferenceIdValidator(Validation::createValidator());

        $result = $subject->validate(' 11111111-1111-1111-1111-111111111111 ');

        self::assertSame('11111111-1111-1111-1111-111111111111', $result);
    }

    public function testItRejectsEmptyReferenceId(): void
    {
        $subject = new RosteringImportReferenceIdValidator(Validation::createValidator());

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Reference ID cannot be empty.');

        $subject->validate('   ');
    }

    public function testItRejectsReferenceIdWithUnsupportedCharacters(): void
    {
        $subject = new RosteringImportReferenceIdValidator(Validation::createValidator());

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Reference ID contains unsupported characters.');

        $subject->validate('ref..invalid');
    }
}
