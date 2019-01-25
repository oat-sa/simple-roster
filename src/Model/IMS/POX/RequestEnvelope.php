<?php declare(strict_types=1);

namespace App\Model\IMS\POX;

use Symfony\Component\Serializer\Annotation\SerializedName;

class RequestEnvelope
{
    /**
     * @var Header
     * @SerializedName("imsx_POXHeader")
     */
    private $poxHeader;

    /**
     * @var Body
     * @SerializedName("imsx_POXBody")
     */
    private $poxBody;

    /**
     * @return Header
     */
    public function getPoxHeader(): Header
    {
        return $this->poxHeader;
    }

    /**
     * @param Header $poxHeader
     * @return RequestEnvelope
     */
    public function setPoxHeader(Header $poxHeader): RequestEnvelope
    {
        $this->poxHeader = $poxHeader;

        return $this;
    }

    /**
     * @return Body
     */
    public function getPoxBody(): Body
    {
        return $this->poxBody;
    }

    /**
     * @param Body $poxBody
     * @return RequestEnvelope
     */
    public function setPoxBody(Body $poxBody): self
    {
        $this->poxBody = $poxBody;

        return $this;
    }
}
