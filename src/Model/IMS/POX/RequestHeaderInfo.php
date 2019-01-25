<?php declare(strict_types=1);

namespace App\Model\IMS\POX;

use Symfony\Component\Serializer\Annotation\SerializedName;

class RequestHeaderInfo
{
    /**
     * @var string
     * @SerializedName("imsx_version")
     */
    private $version;

    /**
     * @var integer
     * @SerializedName("messageIdentifier")
     */
    private $messageIdentifier;

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return RequestHeaderInfo
     */
    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return int
     */
    public function getMessageIdentifier(): int
    {
        return $this->messageIdentifier;
    }

    /**
     * @param int $messageIdentifier
     * @return RequestHeaderInfo
     */
    public function setMessageIdentifier(int $messageIdentifier): self
    {
        $this->messageIdentifier = $messageIdentifier;

        return $this;
    }
}