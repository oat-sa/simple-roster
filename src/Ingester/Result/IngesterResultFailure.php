<?php declare(strict_types=1);

namespace App\Ingester\Result;

class IngesterResultFailure
{
    /** @var int */
    private $lineNumber;

    /** @var array */
    private $data;

    /** @var string */
    private $reason;

    public function __construct(int $lineNumber, array $data, string $reason)
    {
        $this->lineNumber = $lineNumber;
        $this->data = $data;
        $this->reason = $reason;
    }

    public function getLineNumber(): int
    {
        return $this->lineNumber;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
