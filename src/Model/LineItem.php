<?php declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

class LineItem implements ModelInterface
{
    /**
     * @var string
     *
     * @Assert\NotBlank
     * @Assert\Url
     */
    private $taoUri;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    private $title;

    /**
     * @var string
     */
    private $startDateTime;

    /**
     * @var string
     */
    private $endDateTime;

    /**
     * @var string
     *
     * @Assert\NotBlank
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
