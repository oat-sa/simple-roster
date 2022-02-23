<?php

namespace OAT\SimpleRoster\Tests\Unit\Model;

use Countable;
use IteratorAggregate;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Model\AssignmentCollection;
use PHPUnit\Framework\TestCase;

class AssignmentCollectionTest extends TestCase
{
    public function testItImplementsCountable(): void
    {
        self::assertInstanceOf(Countable::class, new AssignmentCollection());
    }

    public function testItImplementsIteratorAggregate(): void
    {
        self::assertInstanceOf(IteratorAggregate::class, new AssignmentCollection());
    }

    public function testIfLineItemCanBeAdded(): void
    {
        $assignment = (new Assignment())->setState(Assignment::STATE_CANCELLED);
        $collection = (new AssignmentCollection())->add($assignment);

        self::assertCount(1, $collection);
        self::assertSame($assignment, $collection->getIterator()->current());
    }

    public function testJsonSerialization(): void
    {
        $assignment1 = (new Assignment())->setState(Assignment::STATE_CANCELLED);
        $assignment2 = (new Assignment())->setState(Assignment::STATE_COMPLETED);

        $collection = new AssignmentCollection([$assignment1, $assignment2]);

        self::assertSame([$assignment1, $assignment2], $collection->jsonSerialize());
    }
}
