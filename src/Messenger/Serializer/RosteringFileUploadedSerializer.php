<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Messenger\Serializer;

use OAT\SimpleRoster\Message\RosteringFileUploadedMessage;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class RosteringFileUploadedSerializer implements SerializerInterface
{
    public function decode(array $encodedEnvelope): Envelope
    {
        throw new RuntimeException('Transport & serializer not meant for receiving messages');
    }

    public function encode(Envelope $envelope): array
    {
        $message = $envelope->getMessage();
        if (!$message instanceof RosteringFileUploadedMessage) {
            throw new RuntimeException('Unsupported message type for RosteringFileUploadedSerializer.');
        }

        $body = json_encode([
            'referenceId' => $message->referenceId,
        ]);

        if ($body === false) {
            throw new RuntimeException(sprintf('json_encode error: %s', json_last_error_msg()));
        }

        return [
            'body' => $body,
            'headers' => [],
        ];
    }
}
