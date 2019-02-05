<?php declare(strict_types=1);

namespace App\Tests\Unit\Ingester\Ingester;

use App\Ingester\Ingester\UserIngester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class UserIngesterTest extends TestCase
{
    public function testRegistryItemName()
    {
        $subject = new UserIngester($this->createMock(EntityManagerInterface::class));

        $this->assertEquals('user', $subject->getRegistryItemName());
    }
}