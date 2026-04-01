<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Rostering;

use OAT\SimpleRoster\Service\Rostering\SeekableStreamFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SeekableStreamFactoryTest extends TestCase
{
    public function testItCreatesSeekableCopyFromNonSeekableStream(): void
    {
        $sourceStream = $this->createNonSeekableReadStream("a,b\n1,2\n");
        $subject = new SeekableStreamFactory();

        $seekableStream = $subject->create($sourceStream, 'test stream');
        $meta = stream_get_meta_data($seekableStream);

        self::assertTrue($meta['seekable']);
        self::assertSame("a,b\n1,2\n", stream_get_contents($seekableStream));

        fclose($sourceStream);
        fclose($seekableStream);
    }

    public function testItThrowsWhenSourceIsNotAResource(): void
    {
        $subject = new SeekableStreamFactory();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invalid stream resource');

        $subject->create(null, 'test stream');
    }

    /**
     * @return resource
     */
    private function createNonSeekableReadStream(string $content)
    {
        $streams = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        if ($streams === false) {
            self::fail('Unable to create non-seekable stream pair.');
        }

        [$writeStream, $readStream] = $streams;
        fwrite($writeStream, $content);
        fclose($writeStream);

        return $readStream;
    }
}

