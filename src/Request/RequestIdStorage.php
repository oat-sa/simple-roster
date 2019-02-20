<?php declare(strict_types=1);

namespace App\Request;

use LogicException;

class RequestIdStorage
{
    /** @var string */
    private $requestId;

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * @throws LogicException
     */
    public function setRequestId(string $requestId): self
    {
        if ($this->requestId) {
            throw new LogicException('Request ID cannot not be set more than once per request.');
        }

        $this->requestId = $requestId;

        return $this;
    }
}
