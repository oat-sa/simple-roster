<?php declare(strict_types=1);

namespace App\Tests\Unit\Ingester\Registry;

use App\Ingester\Ingester\IngesterInterface;
use App\Ingester\Registry\IngesterRegistry;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class IngesterRegistryTest extends TestCase
{
    /** @var IngesterRegistry */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        $this->subject = new IngesterRegistry();
    }

    public function testItIsConstructedEmpty()
    {
        $this->assertEmpty($this->subject->all());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Ingester named 'invalid' cannot be found.
     */
    public function testItThrowsAnErrorWhenRetrievingAnInvalidIngesterName()
    {
        $this->subject->get('invalid');
    }

    public function testItCanAddAnRetrieveIngesters()
    {
        $ingester1 = $this->createMock(IngesterInterface::class);
        $ingester2 = $this->createMock(IngesterInterface::class);

        $ingester1->expects($this->once())->method('getRegistryItemName')->willReturn('ingesterName1');
        $ingester2->expects($this->once())->method('getRegistryItemName')->willReturn('ingesterName2');

        $this->subject
            ->add($ingester1)
            ->add($ingester2);

        $this->assertCount(2, $this->subject->all());
        $this->assertSame($ingester1, $this->subject->get('ingesterName1'));
        $this->assertSame($ingester2, $this->subject->get('ingesterName2'));
    }
}