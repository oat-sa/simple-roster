<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\MessageHandler;

use OAT\SimpleRoster\Message\RosteringFileUploadedMessage;
use OAT\SimpleRoster\Service\Rostering\RosteringFileProcessor;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(fromTransport: 'rostering-file-uploaded-sr')]
class RosteringFileUploadedMessageHandler
{
    public function __construct(private readonly RosteringFileProcessor $rosteringFileProcessor)
    {
    }

    public function __invoke(RosteringFileUploadedMessage $message): void
    {
        $this->rosteringFileProcessor->process($message->referenceId);
    }
}
