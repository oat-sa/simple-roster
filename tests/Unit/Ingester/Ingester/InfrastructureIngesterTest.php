<?php declare(strict_types=1);

namespace App\Tests\Unit\Ingester\Ingester;

use App\Ingester\Ingester\InfrastructureIngester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class InfrastructureIngesterTest extends TestCase
{
    public function testRegistryItemName()
    {
        $subject = new InfrastructureIngester($this->createMock(EntityManagerInterface::class));

        $this->assertEquals('infrastructure', $subject->getRegistryItemName());
    }
}