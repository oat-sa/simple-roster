<?php

namespace OAT\SimpleRoster\Tests\Unit\Lti\Service;

use OAT\SimpleRoster\Lti\Service\ColumnGroupResolver;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ColumnGroupResolverTest extends TestCase
{
    public function testResolve(): void
    {
        $obj = new ColumnGroupResolver([$group1 = 'group1', $group2 = 'group2'], 2);

        $this->assertEquals($group1, $obj->resolve());
        $this->assertEquals($group1, $obj->resolve());
        $this->assertEquals($group2, $obj->resolve());
        $this->assertEquals($group2, $obj->resolve());

        $this->expectException(RuntimeException::class);
        $obj->resolve();
    }

    public function testResolveEmptyGroups(): void
    {
        $obj = new ColumnGroupResolver([], 2);

        $this->expectException(RuntimeException::class);
        $obj->resolve();
    }

    public function testResolveZeroChunkSize(): void
    {
        $this->expectException(RuntimeException::class);
        new ColumnGroupResolver(['group1', 'group2'], 0);
    }
}
