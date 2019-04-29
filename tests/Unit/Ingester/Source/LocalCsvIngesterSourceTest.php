<?php declare(strict_types=1);

namespace App\Tests\Unit\Ingester\Source;

use App\Ingester\Source\LocalCsvIngesterSource;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class LocalCsvIngesterSourceTest extends TestCase
{
    public function testRegistryItemName(): void
    {
        $this->assertEquals('local', (new LocalCsvIngesterSource())->getRegistryItemName());
    }
}
