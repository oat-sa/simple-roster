<?php declare(strict_types=1);

namespace App\Model\IMS\POX;

use Symfony\Component\Serializer\Annotation\SerializedName;

class Header
{
    /**
     * @var RequestHeaderInfo
     * @SerializedName("imsx_POXRequestHeaderInfo")
     */
    private $poxRequestHeaderInfo;

    /**
     * @return RequestHeaderInfo
     */
    public function getPoxRequestHeaderInfo(): RequestHeaderInfo
    {
        return $this->poxRequestHeaderInfo;
    }

    /**
     * @param RequestHeaderInfo $poxRequestHeaderInfo
     * @return Header
     */
    public function setPoxRequestHeaderInfo(RequestHeaderInfo $poxRequestHeaderInfo): self
    {
        $this->poxRequestHeaderInfo = $poxRequestHeaderInfo;

        return $this;
    }
}
