<?php declare(strict_types=1);

namespace App\Tests\Unit\Ingester\Ingester;

use App\Ingester\Ingester\UserIngester;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class UserIngesterTest extends TestCase
{
    public function testRegistryItemName(): void
    {
        $subject = new UserIngester($this->createMock(ManagerRegistry::class));

        $this->assertEquals('user', $subject->getRegistryItemName());
    }
}
