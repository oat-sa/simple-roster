<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

use RuntimeException;

final class SeekableStreamFactory
{
    /**
     * @param resource $stream
     *
     * @return resource
     */
    public function create(mixed $stream, string $context)
    {
        if (!is_resource($stream)) {
            throw new RuntimeException(
                sprintf('Unable to create seekable stream for %s: invalid stream resource.', $context)
            );
        }

        $meta = stream_get_meta_data($stream);
        if (($meta['seekable'] ?? false) === true) {
            rewind($stream);

            return $stream;
        }

        $seekableStream = fopen('php://temp', 'rb+');
        if (false === $seekableStream) {
            throw new RuntimeException(sprintf('Unable to create seekable stream for %s.', $context));
        }

        if (false === stream_copy_to_stream($stream, $seekableStream)) {
            fclose($seekableStream);
            throw new RuntimeException(sprintf('Unable to copy %s into seekable stream.', $context));
        }

        rewind($seekableStream);

        return $seekableStream;
    }
}
