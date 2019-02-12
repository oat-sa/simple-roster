<?php declare(strict_types=1);

namespace App\Tests\Unit\Ingester\Registry;

use App\Ingester\Source\IngesterSourceInterface;
use App\Ingester\Registry\IngesterSourceRegistry;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class IngesterSourceRegistryTest extends TestCase
{
    /** @var IngesterSourceRegistry */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        $this->subject = new IngesterSourceRegistry();
    }

    public function testItIsConstructedEmpty()
    {
        $this->assertEmpty($this->subject->all());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Ingester source named 'invalid' cannot be found.
     */
    public function testItThrowsAnErrorWhenRetrievingAnInvalidIngesterSourceName()
    {
        $this->subject->get('invalid');
    }

    public function testItCanAddAnRetrieveIngesterSources()
    {
        $source1 = $this->createMock(IngesterSourceInterface::class);
        $source2 = $this->createMock(IngesterSourceInterface::class);

        $source1->expects($this->once())->method('getRegistryItemName')->willReturn('sourceName1');
        $source2->expects($this->once())->method('getRegistryItemName')->willReturn('sourceName2');

        $this->subject
            ->add($source1)
            ->add($source2);

        $this->assertCount(2, $this->subject->all());
        $this->assertSame($source1, $this->subject->get('sourceName1'));
        $this->assertSame($source2, $this->subject->get('sourceName2'));
    }
}
