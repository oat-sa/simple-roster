<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Rostering\Dto;

use DateTimeImmutable;
use OAT\SimpleRoster\Service\Rostering\Dto\RosteringFileProcessingContext;
use OAT\SimpleRoster\Service\Rostering\Exception\RosteringValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RosteringFileProcessingContextTest extends TestCase
{
    public function testItCreatesValidatedImmutableContext(): void
    {
        $context = RosteringFileProcessingContext::fromPayload(
            '  ref-123  ',
            '2026-08-31T12:00:00+00:00'
        );

        self::assertSame('ref-123', $context->getReferenceId());
        self::assertInstanceOf(DateTimeImmutable::class, $context->getUploadedAt());
        self::assertSame('2026-08-31T12:00:00+00:00', $context->getUploadedAt()?->format(DATE_ATOM));
    }

    public function testItSupportsMissingUploadedAtForBackwardCompatibility(): void
    {
        $context = RosteringFileProcessingContext::fromPayload('ref-123');

        self::assertNull($context->getUploadedAt());
    }

    #[DataProvider('provideInvalidReferenceIds')]
    public function testItRejectsInvalidReferenceId(string $referenceId, string $expectedMessage): void
    {
        $this->expectException(RosteringValidationException::class);
        $this->expectExceptionMessage($expectedMessage);

        RosteringFileProcessingContext::fromPayload($referenceId);
    }

    public static function provideInvalidReferenceIds(): iterable
    {
        yield 'empty' => ['', 'Reference ID cannot be empty.'];
        yield 'only whitespace' => ['   ', 'Reference ID cannot be empty.'];
        yield 'too long' => [str_repeat('a', 256), 'Reference ID exceeds max length (255).'];
        yield 'unsupported characters' => ['ref/123', 'Reference ID contains unsupported characters.'];
        yield 'path traversal' => ['ref..123', 'Reference ID contains unsupported characters.'];
    }

    #[DataProvider('provideInvalidUploadedAtValues')]
    public function testItRejectsInvalidUploadedAt(string $uploadedAt): void
    {
        $this->expectException(RosteringValidationException::class);
        $this->expectExceptionMessage('Uploaded at must be a valid DATE_ATOM timestamp.');

        RosteringFileProcessingContext::fromPayload('ref-123', $uploadedAt);
    }

    public static function provideInvalidUploadedAtValues(): iterable
    {
        yield 'invalid text' => ['not-a-date'];
        yield 'invalid calendar date' => ['2026-02-31T12:00:00+00:00'];
        yield 'missing timezone' => ['2026-08-31T12:00:00'];
    }
}
