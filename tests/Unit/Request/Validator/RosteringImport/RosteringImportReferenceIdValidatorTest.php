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

        $result = $subject->validate(' 76091d1a-3ef5-438d-a88f-8df73bb5f919 ');

        self::assertSame('76091d1a-3ef5-438d-a88f-8df73bb5f919', $result);
    }

    public function testItRejectsEmptyReferenceId(): void
    {
        $subject = new RosteringImportReferenceIdValidator(Validation::createValidator());

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Reference ID cannot be empty.');

        $subject->validate('   ');
    }

    public function testItRejectsNonUuidReferenceId(): void
    {
        $subject = new RosteringImportReferenceIdValidator(Validation::createValidator());

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Reference ID must be a valid UUID.');

        $subject->validate('ref..invalid');
    }
}
