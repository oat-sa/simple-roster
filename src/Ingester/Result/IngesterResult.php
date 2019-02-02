<?php declare(strict_types=1);

namespace App\Ingester\Result;

class IngesterResult
{
    /** @var string */
    private $type;

    /** @var int */
    private $rowCount;

    public function __construct(string $type, int $rowCount)
    {
        $this->type = $type;
        $this->rowCount = $rowCount;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function getFeedback(): string
    {
        return sprintf(
            '%s elements of type %s have been ingested.',
            $this->rowCount,
            $this->type
        );
    }
}
