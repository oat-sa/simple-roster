<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\MessageHandler;

use OAT\SimpleRoster\Message\RosteringFileUploadedMessage;
use OAT\SimpleRoster\Service\Rostering\Exception\RosteringValidationException;
use OAT\SimpleRoster\Service\Rostering\RosteringFileProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Throwable;

#[AsMessageHandler(fromTransport: 'rostering-file-uploaded-sr')]
class RosteringFileUploadedMessageHandler
{
    public function __construct(
        private readonly RosteringFileProcessor $rosteringFileProcessor,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(RosteringFileUploadedMessage $message): void
    {
        try {
            $this->rosteringFileProcessor->process($message->referenceId);
        } catch (RosteringValidationException $exception) {
            $this->logger->warning(
                $exception->getMessage(),
                [
                    'messageClass' => RosteringFileUploadedMessage::class,
                    'referenceId' => $message->referenceId,
                ]
            );

            throw new UnrecoverableMessageHandlingException($exception->getMessage(), 0, $exception);
        } catch (Throwable $exception) {
            $this->logger->error(
                $exception->getMessage(),
                [
                    'messageClass' => RosteringFileUploadedMessage::class,
                    'referenceId' => $message->referenceId,
                    'trace' => $exception->getTraceAsString(),
                ]
            );

            throw $exception;
        }
    }
}
