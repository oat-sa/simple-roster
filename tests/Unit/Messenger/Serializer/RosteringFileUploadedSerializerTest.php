<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Messenger\Serializer;

use OAT\SimpleRoster\Message\RosteringFileUploadedMessage;
use OAT\SimpleRoster\Messenger\Serializer\RosteringFileUploadedSerializer;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

class RosteringFileUploadedSerializerTest extends TestCase
{
    public function testEncode(): void
    {
        $serializer = new RosteringFileUploadedSerializer();

        $encoded = $serializer->encode(new Envelope(new RosteringFileUploadedMessage('ref-123')));

        self::assertArrayHasKey('body', $encoded);
        self::assertArrayHasKey('headers', $encoded);
        self::assertIsArray($encoded['headers']);
        self::assertSame(RosteringFileUploadedMessage::class, $encoded['headers']['type'] ?? null);
        self::assertSame(
            ['referenceId' => 'ref-123'],
            json_decode($encoded['body'], true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function testDecode(): void
    {
        $serializer = new RosteringFileUploadedSerializer();

        $decoded = $serializer->decode(['body' => '{"referenceId":"ref-456"}']);

        self::assertInstanceOf(Envelope::class, $decoded);
        self::assertInstanceOf(RosteringFileUploadedMessage::class, $decoded->getMessage());
        self::assertSame('ref-456', $decoded->getMessage()->referenceId);
    }

    public function testDecodeSnsWrappedPayload(): void
    {
        $serializer = new RosteringFileUploadedSerializer();

        $decoded = $serializer->decode(['body' => '{"Message":"{\"referenceId\":\"ref-789\"}"}']);

        self::assertInstanceOf(Envelope::class, $decoded);
        self::assertInstanceOf(RosteringFileUploadedMessage::class, $decoded->getMessage());
        self::assertSame('ref-789', $decoded->getMessage()->referenceId);
    }

    public function testItKeepsRedeliveryStampBetweenEncodeAndDecode(): void
    {
        $serializer = new RosteringFileUploadedSerializer();

        $envelope = (new Envelope(new RosteringFileUploadedMessage('ref-123')))
            ->with(new RedeliveryStamp(2));

        $decoded = $serializer->decode($serializer->encode($envelope));

        $stamp = $decoded->last(RedeliveryStamp::class);
        self::assertInstanceOf(RedeliveryStamp::class, $stamp);
        self::assertSame(2, $stamp->getRetryCount());
    }

    public function testItKeepsRetryStampAndDropsErrorDetailsStamp(): void
    {
        $serializer = new RosteringFileUploadedSerializer();

        $envelope = (new Envelope(new RosteringFileUploadedMessage('ref-123')))
            ->with(new RedeliveryStamp(2))
            ->with(ErrorDetailsStamp::create(new RuntimeException('test')));

        $decoded = $serializer->decode($serializer->encode($envelope));

        $retryStamp = $decoded->last(RedeliveryStamp::class);
        self::assertInstanceOf(RedeliveryStamp::class, $retryStamp);
        self::assertSame(2, $retryStamp->getRetryCount());
        self::assertNull($decoded->last(ErrorDetailsStamp::class));
    }

    public function testDecodeThrowsForBadJson(): void
    {
        $serializer = new RosteringFileUploadedSerializer();

        $this->expectException(MessageDecodingFailedException::class);
        $this->expectExceptionMessage('json_decode error: Syntax error');

        $serializer->decode(['body' => '{bad json}']);
    }

    public function testDecodeThrowsWhenReferenceIdIsMissing(): void
    {
        $serializer = new RosteringFileUploadedSerializer();

        $this->expectException(MessageDecodingFailedException::class);
        $this->expectExceptionMessage('Reference ID missing.');

        $serializer->decode(['body' => '{"status":"ok"}']);
    }

    public function testEncodeThrowsForUnsupportedMessageType(): void
    {
        $serializer = new RosteringFileUploadedSerializer();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported message type for RosteringFileUploadedSerializer.');

        $serializer->encode(new Envelope(new stdClass()));
    }

    public function testEncodeThrowsWhenJsonEncodingFails(): void
    {
        $serializer = new RosteringFileUploadedSerializer();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to encode rostering uploaded message:');

        $serializer->encode(new Envelope(new RosteringFileUploadedMessage("\xB1\x31")));
    }
}
