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

    public function testDecodeThrowsBecauseSerializerIsWriteOnly(): void
    {
        $serializer = new RosteringFileUploadedSerializer();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Transport & serializer not meant for receiving messages');

        $serializer->decode(['body' => '{}']);
    }

    public function testEncodeThrowsForUnsupportedMessageType(): void
    {
        $serializer = new RosteringFileUploadedSerializer();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported message type for RosteringFileUploadedSerializer.');

        $serializer->encode(new Envelope(new stdClass()));
    }
}
