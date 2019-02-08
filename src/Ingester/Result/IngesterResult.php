<?php declare(strict_types=1);

namespace App\Ingester\Result;

class IngesterResult
{
    /** @var string */
    private $ingesterType;

    /** @var string */
    private $sourceType;

    /** @var int */
    private $successCount = 0;

    /** @var array */
    private $failures = [];

    /** @var bool */
    private $dryRun;

    public function __construct(string $ingesterType, string $sourceType, bool $dryRun = true)
    {
        $this->ingesterType = $ingesterType;
        $this->sourceType = $sourceType;
        $this->dryRun = $dryRun;
    }

    public function addSuccess(): self
    {
        $this->successCount++;

        return $this;
    }

    public function addFailure(IngesterResultFailure $failure): self
    {
        $this->failures[$failure->getLineNumber()] = $failure;

        return $this;
    }

    public function getIngesterType(): string
    {
        return $this->ingesterType;
    }

    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    /**
     * @return IngesterResultFailure[]
     */
    public function getFailures(): array
    {
        return $this->failures;
    }

    public function hasFailures(): bool
    {
        return !empty($this->failures);
    }

    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    public function __toString(): string
    {
        return sprintf(
            "%sIngestion (type='%s', source='%s'): %s successes, %s failures.",
            $this->dryRun ? '[DRY_RUN] ' : '',
            $this->ingesterType,
            $this->sourceType,
            $this->successCount,
            count($this->failures)
        );
    }
}
