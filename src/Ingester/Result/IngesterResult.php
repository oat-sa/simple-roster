<?php

/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Ingester\Result;

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
