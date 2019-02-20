<?php declare(strict_types=1);

namespace App\Request;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactoryInterface;

class RequestIdGenerator
{
    /** @var UuidFactoryInterface */
    private $uuidFactory;

    public function __construct(UuidFactoryInterface $uuidFactory)
    {
        $this->uuidFactory = $uuidFactory;
    }

    public function generate(): string
    {
        /** @var Uuid $uuid */
        $uuid = $this->uuidFactory->uuid4();

        return (string)$uuid;
    }
}
