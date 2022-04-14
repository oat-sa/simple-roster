<?php

namespace OAT\SimpleRoster\Tests\Unit\Service\Bulk;

use OAT\SimpleRoster\Service\Bulk\CreateUserServiceContext;
use PHPUnit\Framework\TestCase;

class CreateUserServiceContextTest extends TestCase
{
    public function testGetters(): void
    {
        $context = new CreateUserServiceContext(
            ['test1', 'test2'],
            ['test3', 'test4'],
            20
        );

        self::assertEquals(['test1', 'test2'], $context->getPrefixes());
        self::assertEquals(['test3', 'test4'], $context->getPrefixGroup());
        self::assertEquals(20, $context->getBatchSize());
    }
}
