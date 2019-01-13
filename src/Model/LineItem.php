<?php declare(strict_types=1);

namespace App\Model;

class LineItem extends AbstractModel
{
    /**
     * @var string
     */
    private $taoUri;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string|null
     */
    private $startDateTime;

    /**
     * @var string|null
     */
    private $endDateTime;

    /**
     * @var string
     */
    private $infrastructureId;

    public function __construct(string $taoUri, string $title, string $infrastructureId, string $startDateTime, string $endDateTime)
    {
        $this->taoUri = $taoUri;
        $this->title = $title;
        $this->infrastructureId = $infrastructureId;
        $this->startDateTime = $startDateTime;
        $this->endDateTime = $endDateTime;
    }

    /**
     * @inheritdoc
     */
    public function getTaoUri(): string
    {
        return $this->taoUri;
    }

    public function getInfrastructureId(): string
    {
        return $this->infrastructureId;
    }

    public function getTitle(): string
    {
        return $this->taoUri;
    }

    public function getStartDateTime(): ?string
    {
        return $this->startDateTime;
    }

    public function getEndDateTime(): ?string
    {
        return $this->endDateTime;
    }

    /**
     * @inheritdoc
     */
    public function validate(): void
    {
        if (!$this->taoUri) {
            $this->throwExceptionRequiredFieldEmpty('tao_uri');
        }
        if (!$this->title) {
            $this->throwExceptionRequiredFieldEmpty('title');
        }
        if (!$this->infrastructureId) {
            $this->throwExceptionRequiredFieldEmpty('infrastructure_id');
        }
    }
}
