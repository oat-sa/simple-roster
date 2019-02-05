<?php declare(strict_types=1);

namespace App\Tests\Unit\Ingester\Ingester;

use App\Ingester\Ingester\LineItemIngester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class LineItemIngesterTest extends TestCase
{
    public function testRegistryItemName()
    {
        $subject = new LineItemIngester($this->createMock(EntityManagerInterface::class));

        $this->assertEquals('line-item', $subject->getRegistryItemName());
    }
}