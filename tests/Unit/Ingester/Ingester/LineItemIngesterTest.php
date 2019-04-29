<?php declare(strict_types=1);

namespace App\Tests\Unit\Ingester\Ingester;

use App\Ingester\Ingester\LineItemIngester;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class LineItemIngesterTest extends TestCase
{
    public function testRegistryItemName(): void
    {
        $subject = new LineItemIngester($this->createMock(ManagerRegistry::class));

        $this->assertEquals('line-item', $subject->getRegistryItemName());
    }
}
