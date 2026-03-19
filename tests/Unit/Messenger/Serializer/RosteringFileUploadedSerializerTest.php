<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Messenger\Serializer;

use OAT\SimpleRoster\Message\RosteringFileUploadedMessage;
use OAT\SimpleRoster\Messenger\Serializer\RosteringFileUploadedSerializer;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\Messenger\Envelope;

class RosteringFileUploadedSerializerTest extends TestCase
{
    public function testEncode(): void
    {
        $serializer = new RosteringFileUploadedSerializer();

        $encoded = $serializer->encode(new Envelope(new RosteringFileUploadedMessage('ref-123')));

        self::assertArrayHasKey('body', $encoded);
        self::assertArrayHasKey('headers', $encoded);
        self::assertSame([], $encoded['headers']);
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

    public function testDecodeThrowsForBadJson(): void
    {
        $serializer = new RosteringFileUploadedSerializer();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('json_decode error: Syntax error');

        $serializer->decode(['body' => '{bad json}']);
    }

    public function testDecodeThrowsWhenReferenceIdIsMissing(): void
    {
        $serializer = new RosteringFileUploadedSerializer();

        $this->expectException(RuntimeException::class);
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
}
