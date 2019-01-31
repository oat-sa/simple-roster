<?php declare(strict_types=1);

namespace App\Model;

use App\ODM\Annotations\Item;
use App\ODM\Validator\Constraints as ODMAssert;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Item(table="line_items", primaryKey="taoUri")
 */
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
    private $label;

    /**
     * @var \DateTimeImmutable
     */
    private $startDateTime;

    /**
     * @var \DateTimeImmutable
     */
    private $endDateTime;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ODMAssert\ExistingItem(itemClass="App\Model\Infrastructure")
     */
    private $infrastructureId;

    public function __construct(string $taoUri, string $label, string $infrastructureId, ?\DateTimeImmutable $startDateTime = null, ?\DateTimeImmutable $endDateTime = null)
    {
        $this->taoUri = $taoUri;
        $this->label = $label;
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

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getStartDateTime(): ?\DateTimeImmutable
    {
        return $this->startDateTime;
    }

    public function getEndDateTime(): ?\DateTimeImmutable
    {
        return $this->endDateTime;
    }
}
