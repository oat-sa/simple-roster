<?php declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\RequestIdGeneratorSubscriber;
use App\Request\RequestIdStorage;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Ramsey\Uuid\UuidFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestIdGeneratorSubscriberTest extends TestCase
{
    /** @var UuidFactoryInterface|PHPUnit_Framework_MockObject_MockObject */
    private $uuidFactory;

    /** @var RequestIdStorage */
    private $requestIdStorage;

    /** @var GetResponseEvent|PHPUnit_Framework_MockObject_MockObject */
    private $getResponseEvent;

    /** @var RequestIdGeneratorSubscriber */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        $this->uuidFactory = $this->createMock(UuidFactoryInterface::class);
        $this->requestIdStorage = new RequestIdStorage();
        $this->getResponseEvent = $this->createMock(GetResponseEvent::class);

        $this->subject = new RequestIdGeneratorSubscriber($this->uuidFactory, $this->requestIdStorage);
    }

    public function testItIsAnEventSubscriber(): void
    {
        $this->assertInstanceOf(EventSubscriberInterface::class, $this->subject);
    }

    public function testItSubscribesToKernelRequestEventWithHighestPriority(): void
    {
        $this->assertEquals(
            [KernelEvents::REQUEST => ['onKernelRequest', 255]],
            RequestIdGeneratorSubscriber::getSubscribedEvents()
        );
    }

    public function testItSetsCloudfrontHeaderValueAsRequestIdIfItIsPresent(): void
    {
        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_x-edge-request-id' => 'expectedRequestId']
        );

        $this->getResponseEvent
            ->method('getRequest')
            ->willReturn($request);

        $this->subject->onKernelRequest($this->getResponseEvent);

        $this->assertEquals('expectedRequestId', $request->attributes->get('requestId'));
        $this->assertEquals('expectedRequestId', $this->requestIdStorage->getRequestId());
    }

    public function testItWillGenerateNewRequestIdIfCloudfrontHeaderIsNotPresent(): void
    {
        $request = Request::create('/test');

        $this->uuidFactory
            ->expects($this->once())
            ->method('uuid4')
            ->willReturn('expectedRequestId');

        $this->getResponseEvent
            ->method('getRequest')
            ->willReturn($request);

        $this->subject->onKernelRequest($this->getResponseEvent);

        $this->assertEquals('expectedRequestId', $request->attributes->get('requestId'));
        $this->assertEquals('expectedRequestId', $this->requestIdStorage->getRequestId());
    }
}