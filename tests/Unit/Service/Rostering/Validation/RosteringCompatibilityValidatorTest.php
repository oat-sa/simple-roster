<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Rostering\Validation;

use OAT\SimpleRoster\Service\Rostering\Exception\RosteringValidationException;
use OAT\SimpleRoster\Service\Rostering\Validation\RosteringCompatibilityValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RosteringCompatibilityValidatorTest extends TestCase
{
    public function testItDoesNotApplyAdditionalValidationWhenDisabled(): void
    {
        $subject = new RosteringCompatibilityValidator(false);

        $subject->validate(
            [
                'user_username' => str_repeat('u', 256),
                'user_password' => 'Password123',
                'session_name' => 'SESSION_1',
            ]
        );

        $this->addToAssertionCount(1);
    }

    public function testItAcceptsAStudentRowWithACompleteHierarchy(): void
    {
        $subject = new RosteringCompatibilityValidator(true);

        $subject->validate(
            [
                'hierarchy_organizationId' => 'CLASS_1',
                'hierarchy_parentOrganizationId' => 'SCHOOL_1',
                'user_username' => 'student_1',
            ]
        );

        $this->addToAssertionCount(1);
    }

    /**
     * @param array<string, string> $row
     */
    #[DataProvider('invalidStudentRowProvider')]
    public function testItRejectsAnInvalidStudentRow(array $row, string $expectedMessage): void
    {
        $subject = new RosteringCompatibilityValidator(true);

        $this->expectException(RosteringValidationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $subject->validate($row);
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function invalidStudentRowProvider(): iterable
    {
        yield 'missing hierarchy' => [
            ['user_username' => 'student_1'],
            'Field "hierarchy_organizationId" is required.',
        ];

        yield 'missing classroom' => [
            [
                'hierarchy_parentOrganizationId' => 'SCHOOL_1',
                'user_username' => 'student_1',
            ],
            'Fields "hierarchy_organizationId" and "hierarchy_parentOrganizationId" must be provided together.',
        ];

        yield 'missing school' => [
            [
                'hierarchy_organizationId' => 'CLASS_1',
                'user_username' => 'student_1',
            ],
            'Fields "hierarchy_organizationId" and "hierarchy_parentOrganizationId" must be provided together.',
        ];

        yield 'classroom too long' => [
            [
                'hierarchy_organizationId' => str_repeat('c', 256),
                'hierarchy_parentOrganizationId' => 'SCHOOL_1',
                'user_username' => 'student_1',
            ],
            'Field "hierarchy_organizationId" exceeds max length (255).',
        ];

        yield 'school too long' => [
            [
                'hierarchy_organizationId' => 'CLASS_1',
                'hierarchy_parentOrganizationId' => str_repeat('s', 256),
                'user_username' => 'student_1',
            ],
            'Field "hierarchy_parentOrganizationId" exceeds max length (255).',
        ];

        yield 'username too long' => [
            [
                'hierarchy_organizationId' => 'CLASS_1',
                'hierarchy_parentOrganizationId' => 'SCHOOL_1',
                'user_username' => str_repeat('u', 101),
            ],
            'Field "user_username" exceeds max length (100).',
        ];
    }

    /**
     * @param array<string, string> $row
     */
    #[DataProvider('nonStudentRowProvider')]
    public function testItDoesNotTreatPassThroughRowsAsStudents(array $row): void
    {
        $subject = new RosteringCompatibilityValidator(true);

        $subject->validate($row);

        $this->addToAssertionCount(1);
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function nonStudentRowProvider(): iterable
    {
        yield 'principal-only row' => [
            [
                'hierarchy_parentOrganizationId' => 'Root',
                'principal_username' => 'principal_1',
            ],
        ];

        yield 'unrelated row' => [
            [
                'user_organizationId' => 'SCHOOL_1',
                'hierarchy_organizationName' => 'Class 1',
            ],
        ];
    }
}
