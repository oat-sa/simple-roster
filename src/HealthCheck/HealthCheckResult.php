<?php declare(strict_types=1);

namespace App\HealthCheck;

use JsonSerializable;

class HealthCheckResult implements JsonSerializable
{
    /** @var bool */
    private $isDoctrineConnectionAvailable;

    /** @var bool */
    private $isDoctrineCacheAvailable;

    public function __construct(bool $isDoctrineConnectionAvailable, bool $isDoctrineCacheAvailable)
    {
        $this->isDoctrineConnectionAvailable = $isDoctrineConnectionAvailable;
        $this->isDoctrineCacheAvailable = $isDoctrineCacheAvailable;
    }

    public function isDoctrineConnectionAvailable(): bool
    {
        return $this->isDoctrineConnectionAvailable;
    }

    public function isDoctrineCacheAvailable(): bool
    {
        return $this->isDoctrineCacheAvailable;
    }

    public function jsonSerialize()
    {
        return [
            'isDoctrineConnectionAvailable' => $this->isDoctrineConnectionAvailable,
            'isDoctrineCacheAvailable' => $this->isDoctrineCacheAvailable,
        ];
    }
}
