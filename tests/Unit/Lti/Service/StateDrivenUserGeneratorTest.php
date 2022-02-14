<?php

namespace OAT\SimpleRoster\Tests\Unit\Lti\Service;

use OAT\SimpleRoster\Lti\Service\GroupResolverInterface;
use OAT\SimpleRoster\Lti\Service\StateDrivenUserGenerator;
use PHPUnit\Framework\TestCase;

class StateDrivenUserGeneratorTest extends TestCase
{
    public function testMakeBatch(): void
    {
        $obj = new StateDrivenUserGenerator("slug", "pref", $shift = 2);
        $res = $obj->makeBatch($count = 25);

        $this->assertEquals($count, count($res));
    }

    public function testMakeBaseGeneration(): void
    {
        $mockGroupResolver = $this->createMock(GroupResolverInterface::class);
        $mockGroupResolver->method('resolve')->willReturn($group = 'group');

        $obj = new StateDrivenUserGenerator("slug", "pref", $shift = 5, $mockGroupResolver);

        foreach (range($shift, $shift + 3) as $index) {
            $user = $obj->make();

            $this->assertEquals('slug_pref_' . $index, $user->getName());
            $this->assertEquals($group, $user->getGroup());
            $this->assertNotEmpty($user->getPassword());
        }
    }

    public function testMakeWithoutGroupResolver(): void
    {
        $obj = new StateDrivenUserGenerator("slug", "pref", 2);

        $user = $obj->make();

        $this->assertEquals('slug_pref_2', $user->getName());
        $this->assertEquals('', $user->getGroup());
    }
}
