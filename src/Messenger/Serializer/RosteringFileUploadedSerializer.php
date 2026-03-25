<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Messenger\Serializer;

use JsonException;
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

        try {
            $body = json_encode(
                ['referenceId' => $message->referenceId],
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                sprintf('Unable to encode rostering uploaded message: %s', $exception->getMessage()),
                0,
                $exception
            );
        }

        return [
            'body' => $body,
            'headers' => [],
        ];
    }
}
