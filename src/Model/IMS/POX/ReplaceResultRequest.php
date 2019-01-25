<?php declare(strict_types=1);

namespace App\Model\IMS\POX;

use Symfony\Component\Serializer\Annotation\SerializedName;

class ReplaceResultRequest
{
    /**
     * @var ResultRecord
     * @SerializedName("resultRecord")
     */
    private $resultRecord;

    /**
     * @return ResultRecord
     */
    public function getResultRecord(): ResultRecord
    {
        return $this->resultRecord;
    }

    /**
     * @param ResultRecord $resultRecord
     * @return ReplaceResultRequest
     */
    public function setResultRecord(ResultRecord $resultRecord): self
    {
        $this->resultRecord = $resultRecord;

        return $this;
    }
}
