<?php declare(strict_types=1);

namespace App\Ingester\Result;

class IngesterResult
{
    /** @var string */
    private $type;

    /** @var int */
    private $rowCount;

    /** @var bool */
    private $dryRun = true;

    public function __construct(string $type, int $rowCount, bool $dryRun = true)
    {
        $this->type = $type;
        $this->rowCount = $rowCount;
        $this->dryRun = $dryRun;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s%s elements of type %s have been ingested.',
            $this->dryRun ? '[DRY_RUN] ' : '',
            $this->rowCount,
            $this->type
        );
    }
}
