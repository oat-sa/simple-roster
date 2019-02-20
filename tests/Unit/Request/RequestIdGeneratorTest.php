<?php declare(strict_types=1);

namespace App\Tests\Unit\Request;

use App\Request\RequestIdGenerator;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Ramsey\Uuid\UuidFactoryInterface;

class RequestIdGeneratorTest extends TestCase
{
    /** @var UuidFactoryInterface|PHPUnit_Framework_MockObject_MockObject */
    private $uuidFactory;

    /** @var RequestIdGenerator */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        $this->uuidFactory = $this->createMock(UuidFactoryInterface::class);
        $this->subject = new RequestIdGenerator($this->uuidFactory);
    }

    public function testItImplementsRequestIdGeneratorInterface(): void
    {
        $this->assertInstanceOf(RequestIdGenerator::class, $this->subject);
    }

    public function testItCanGenerateRequestId(): void
    {
        $this->uuidFactory
            ->expects($this->once())
            ->method('uuid4')
            ->willReturn('expectedId');

        $this->assertEquals('expectedId', $this->subject->generate());
    }
}
