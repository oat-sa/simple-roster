<?php declare(strict_types=1);

namespace App\Model\IMS\POX;

use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Component\Serializer\Annotation\SerializedName;

class Body
{
    /**
     * @var ReplaceResultRequest|null
     * @SerializedName("replaceResultRequest")
     */
    private $replaceResultRequest;

    /**
     * @return ReplaceResultRequest|null
     */
    public function getReplaceResultRequest(): ?ReplaceResultRequest
    {
        return $this->replaceResultRequest;
    }

    /**
     * @param ReplaceResultRequest|null $replaceResultRequest
     * @return Body
     */
    public function setReplaceResultRequest(?ReplaceResultRequest $replaceResultRequest): self
    {
        $this->replaceResultRequest = $replaceResultRequest;

        return $this;
    }

    /**
     * @return bool
     */
    public function isReplaceResultRequest(): bool
    {
        return $this->replaceResultRequest === null;
    }
}
