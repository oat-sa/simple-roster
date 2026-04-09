<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\MessageHandler;

use OAT\SimpleRoster\Message\RosteringFileUploadedMessage;
use OAT\SimpleRoster\MessageHandler\RosteringFileUploadedMessageHandler;
use OAT\SimpleRoster\Service\Rostering\Exception\RosteringValidationException;
use OAT\SimpleRoster\Service\Rostering\RosteringFileProcessor;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

class RosteringFileUploadedMessageHandlerTest extends TestCase
{
    private RosteringFileProcessor&MockObject $rosteringFileProcessor;
    private LoggerInterface&MockObject $logger;
    private RosteringFileUploadedMessageHandler $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rosteringFileProcessor = $this->createMock(RosteringFileProcessor::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subject = new RosteringFileUploadedMessageHandler($this->rosteringFileProcessor, $this->logger);
    }

    public function testItIsInvokableAndTaggedAsMessageHandler(): void
    {
        self::assertTrue(is_callable($this->subject), 'Handler should be callable (__invoke).');

        $ref = new ReflectionClass($this->subject);
        self::assertTrue($ref->hasMethod('__invoke'));

        $attrs = $ref->getAttributes(AsMessageHandler::class);
        self::assertNotEmpty($attrs, 'Handler should have #[AsMessageHandler] attribute.');
    }

    public function testItProcessesUploadedMessage(): void
    {
        $this->rosteringFileProcessor
            ->expects(self::once())
            ->method('process')
            ->with('ref-123');

        $this->subject->__invoke(new RosteringFileUploadedMessage('ref-123'));
    }

    public function testItBubblesUpExceptionFromProcessor(): void
    {
        $this->rosteringFileProcessor
            ->expects(self::once())
            ->method('process')
            ->with('ref-500')
            ->willThrowException(new RuntimeException('Processor failed.'));

        $this->logger
            ->expects(self::once())
            ->method('error');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Processor failed.');

        $this->subject->__invoke(new RosteringFileUploadedMessage('ref-500'));
    }

    public function testItConvertsValidationExceptionToUnrecoverableMessageHandlingException(): void
    {
        $this->rosteringFileProcessor
            ->expects(self::once())
            ->method('process')
            ->with('ref-invalid')
            ->willThrowException(new RosteringValidationException('Reference ID missing.'));

        $this->logger
            ->expects(self::once())
            ->method('warning');

        $this->expectException(UnrecoverableMessageHandlingException::class);
        $this->expectExceptionMessage('Reference ID missing.');

        $this->subject->__invoke(new RosteringFileUploadedMessage('ref-invalid'));
    }
}
