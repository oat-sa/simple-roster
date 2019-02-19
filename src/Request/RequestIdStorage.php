<?php declare(strict_types=1);

namespace App\Request;

class RequestIdStorage
{
    /** @var string */
    private $requestId;

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function setRequestId(string $requestId): self
    {
        $this->requestId = $requestId;

        return $this;
    }
}
