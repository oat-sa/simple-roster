<?php declare(strict_types=1);

namespace App\Model;

use App\ODM\Validator\Constraints as ODMAssert;
use Symfony\Component\Validator\Constraints as Assert;

class Assignment implements ModelInterface
{
    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    private $id;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @Assert\Url
     * @ODMAssert\ExistingItem(itemClass="App\Model\LineItem")
     */
    private $lineItemTaoUri;

    /**
     * assignment can be taken if other constraints allows it (dates)
     */
    public const STATE_READY = 'ready';

    /**
     * the LTI link for this assignment has been queried, and the state changed as “started” at the same time
     */
    public const STATE_STARTED = 'started';

    /**
     * the test has been completed. We know that it has because simple-roster received the LTI-outcome request from the TAO delivery
     */
    public const STATE_COMPLETED = 'completed';

    /**
     * the assignment cannot be taken anymore
     */
    public const STATE_CANCELLED = 'cancelled';

    /**
     * @var string
     */
    private $state;

    public function __construct(string $id, string $lineItemTaoUri, string $state = self::STATE_READY)
    {
        $this->id = $id;
        $this->lineItemTaoUri = $lineItemTaoUri;
        $this->state = $state;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLineItemTaoUri(): string
    {
        return $this->lineItemTaoUri;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function isCancelled(): bool
    {
        return $this->state === self::STATE_CANCELLED;
    }
}
