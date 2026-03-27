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
        $body = (string) ($encodedEnvelope['body'] ?? '');

        try {
            $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(sprintf('json_decode error: %s', $exception->getMessage()), 0, $exception);
        }

        if (!is_array($payload) || !isset($payload['referenceId']) || !is_string($payload['referenceId'])) {
            throw new RuntimeException('Reference ID missing.');
        }

        $referenceId = trim($payload['referenceId']);
        if ('' === $referenceId) {
            throw new RuntimeException('Reference ID missing.');
        }

        return new Envelope(new RosteringFileUploadedMessage($referenceId));
    }

    public function encode(Envelope $envelope): array
    {
        $message = $envelope->getMessage();
        if (!$message instanceof RosteringFileUploadedMessage) {
            throw new RuntimeException('Unsupported message type for RosteringFileUploadedSerializer.');
        }

        try {
            $body = json_encode(
                [
                    'referenceId' => $message->referenceId,
                ],
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
