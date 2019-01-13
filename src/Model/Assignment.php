<?php

namespace App\Model;

class Assignment extends AbstractModel
{
    /**
     * @var string
     */
    private $lineItemTaoUri;

    /**
     * assignment can be taken if other constraints allows it (dates)
     */
    const STATE_READY = 'ready';

    /**
     * the LTI link for this assignment has been queried, and the state changed as “started” at the same time
     */
    const STATE_STARTED = 'started';

    /**
     * the test has been completed. We know that it has because simple-roster received the LTI-outcome request from the TAO delivery
     */
    const STATE_COMPLETED = 'completed';

    /**
     * the assignment cannot be taken anymore
     */
    const STATE_CANCELLED = 'cancelled';

    /**
     * @var string
     */
    private $state = self::STATE_READY;

    public function __construct(string $lineItemTaoUri, string $state = self::STATE_READY)
    {
        $this->lineItemTaoUri = $lineItemTaoUri;
        $this->state = $state;
    }

    public function validate(): void
    {

    }

    public function getLineItemTaoUri(): string
    {
        return $this->lineItemTaoUri;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setLineItemTaoUri(string $lineItemTaoUri): void
    {
        $this->lineItemTaoUri = $lineItemTaoUri;
    }
}
