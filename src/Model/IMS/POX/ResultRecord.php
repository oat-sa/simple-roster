<?php declare(strict_types=1);

namespace App\Model\IMS\POX;

use Symfony\Component\Serializer\Annotation\SerializedName;

class ResultRecord
{
    /**
     * @var SourcedGUID
     * @SerializedName("sourcedGUID")
     */
    private $sourcedGuid;

    /**
     * @return SourcedGUID
     */
    public function getSourcedGuid(): SourcedGUID
    {
        return $this->sourcedGuid;
    }

    /**
     * @param SourcedGUID $sourcedGuid
     * @return ResultRecord
     */
    public function setSourcedGuid(SourcedGUID $sourcedGuid): self
    {
        $this->sourcedGuid = $sourcedGuid;

        return $this;
    }
}
