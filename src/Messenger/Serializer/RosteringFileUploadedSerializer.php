<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Messenger\Serializer;

use JsonException;
use OAT\SimpleRoster\Message\RosteringFileUploadedMessage;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Transport\Serialization\Serializer as TransportSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class RosteringFileUploadedSerializer implements SerializerInterface
{
    private SerializerInterface $transportSerializer;

    public function __construct()
    {
        $this->transportSerializer = TransportSerializer::create();
    }

    public function decode(array $encodedEnvelope): Envelope
    {
        $referenceId = $this->extractReferenceId($encodedEnvelope);
        $normalizedBody = $this->encodeReferenceId($referenceId);
        $headers = $this->normalizeHeaders($encodedEnvelope['headers'] ?? []);

        $headers['type'] = RosteringFileUploadedMessage::class;

        return $this->transportSerializer->decode(
            [
                'body' => $normalizedBody,
                'headers' => $headers,
            ]
        );
    }

    public function encode(Envelope $envelope): array
    {
        $message = $envelope->getMessage();
        if (!$message instanceof RosteringFileUploadedMessage) {
            throw new RuntimeException('Unsupported message type for RosteringFileUploadedSerializer.');
        }

        $body = $this->encodeReferenceId($message->referenceId, 'Unable to encode rostering uploaded message: %s');

        $encoded = $this->transportSerializer->encode($envelope->withoutAll(ErrorDetailsStamp::class));
        $encoded['body'] = $body;

        return [
            'body' => $encoded['body'],
            'headers' => $encoded['headers'],
        ];
    }

    private function extractReferenceId(array $encodedEnvelope): string
    {
        $body = (string) ($encodedEnvelope['body'] ?? '');
        $payload = $this->decodeJsonObject($body);

        if (isset($payload['Message']) && is_string($payload['Message'])) {
            $payload = $this->decodeJsonObject($payload['Message']);
        }

        if (!isset($payload['referenceId']) || !is_string($payload['referenceId'])) {
            throw new MessageDecodingFailedException('Reference ID missing.');
        }

        $referenceId = trim($payload['referenceId']);
        if ('' === $referenceId) {
            throw new MessageDecodingFailedException('Reference ID missing.');
        }

        return $referenceId;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonObject(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new MessageDecodingFailedException(
                sprintf('json_decode error: %s', $exception->getMessage()),
                0,
                $exception
            );
        }

        if (!is_array($decoded)) {
            throw new MessageDecodingFailedException('Decoded payload must be a JSON object.');
        }

        return $decoded;
    }

    private function encodeReferenceId(string $referenceId, string $errorTemplate = 'json_encode error: %s'): string
    {
        try {
            return json_encode(['referenceId' => $referenceId], JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(sprintf($errorTemplate, $exception->getMessage()), 0, $exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeHeaders(mixed $headers): array
    {
        return is_array($headers) ? $headers : [];
    }
}
