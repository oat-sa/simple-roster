<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Message;

class RosteringFileUploadedMessage
{
    public function __construct(
        public readonly string $referenceId,
        public readonly string $pendingKey
    ) {
    }
}
