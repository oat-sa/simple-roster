<?php declare(strict_types=1);

namespace App\Model;

class LineItem implements ModelInterface
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
}
