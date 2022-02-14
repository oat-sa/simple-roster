<?php

namespace OAT\SimpleRoster\Tests\Unit\Model;

use Countable;
use IteratorAggregate;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Model\UserCollection;
use PHPUnit\Framework\TestCase;

class UserCollectionTest extends TestCase
{
    public function testItImplementsCountable(): void
    {
        self::assertInstanceOf(Countable::class, new UserCollection());
    }

    public function testItImplementsIteratorAggregate(): void
    {
        self::assertInstanceOf(IteratorAggregate::class, new UserCollection());
    }

    public function testIfLineItemCanBeAdded(): void
    {
        $user = (new User())->setUsername('test_one');
        $collection = (new UserCollection())->add($user);

        self::assertCount(1, $collection);
        self::assertSame($user, $collection->getIterator()->current());
    }

    public function testJsonSerialization(): void
    {
        $user1 = (new User())->setUsername('test_one');
        $user2 = (new User())->setUsername('test_two');

        $collection = new UserCollection([$user1, $user2]);

        self::assertSame([$user1, $user2], $collection->jsonSerialize());
    }
}
