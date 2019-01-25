<?php declare(strict_types=1);

namespace App\Model\IMS\POX;

use Symfony\Component\Serializer\Annotation\SerializedName;

class SourcedGUID
{
    /**
     * @var string
     * @SerializedName("sourcedId")
     */
    private $sourcedId;

    /**
     * @return string
     */
    public function getSourcedId(): string
    {
        return $this->sourcedId;
    }

    /**
     * @param string $sourcedId
     * @return SourcedGUID
     */
    public function setSourcedId(string $sourcedId): self
    {
        $this->sourcedId = $sourcedId;

        return $this;
    }
}
