<?php declare(strict_types=1);

namespace App\Ingester\Result;

class IngesterResult
{
    /** @var string */
    private $ingesterType;

    /** @var string */
    private $sourceType;

    /** @var array */
    private $successes = [];

    /** @var array */
    private $failures = [];

    /** @var bool */
    private $dryRun = true;

    public function __construct(string $ingesterType, string $sourceType, bool $dryRun = true)
    {
        $this->ingesterType = $ingesterType;
        $this->sourceType = $sourceType;
        $this->dryRun = $dryRun;
    }

    public function addSuccess(array $row): self
    {
        $this->successes[] = $row;

        return $this;
    }

    public function addFailure(array $row): self
    {
        $this->failures[] = $row;

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

    public function getSuccesses(): array
    {
        return $this->successes;
    }

    public function getFailures(): array
    {
        return $this->failures;
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
            sizeof($this->successes),
            sizeof($this->failures)
        );
    }
}