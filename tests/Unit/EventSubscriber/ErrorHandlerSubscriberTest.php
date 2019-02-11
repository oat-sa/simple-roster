<?php declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\ErrorHandlerSubscriber;
use App\Responder\SerializerResponder;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ErrorHandlerSubscriberTest extends TestCase
{
    /** @var ErrorHandlerSubscriber */
    private $subject;

    /** @var SerializerResponder */
    private $responder;

    protected function setUp()
    {
        parent::setUp();

        $this->responder = $this->createMock(SerializerResponder::class);
        $this->subject = new ErrorHandlerSubscriber($this->responder);
    }

    public function testSubscribedEvents(): void
    {
        $this->assertEquals(
            [KernelEvents::EXCEPTION => 'onKernelException'],
            ErrorHandlerSubscriber::getSubscribedEvents()
        );
    }

    public function testItDoesNotSetResponseOnSubRequests(): void
    {
        $event = $this->createMock(GetResponseForExceptionEvent::class);
        $event
            ->method('isMasterRequest')
            ->willReturn(false);

        $event
            ->expects($this->never())
            ->method('setResponse');

        $this->subject->onKernelException($event);
    }

    public function testItSetsProperResponseFromResponderOnMasterRequest(): void
    {
        $expectedException = new Exception();
        $expectedResponse = new JsonResponse();

        $this->responder
            ->method('createErrorJsonResponse')
            ->with($expectedException)
            ->willReturn($expectedResponse);

        $event = $this->createMock(GetResponseForExceptionEvent::class);

        $event
            ->method('isMasterRequest')
            ->willReturn(true);

        $event
            ->method('getException')
            ->willReturn($expectedException);

        $event->expects($this->once())
            ->method('setResponse')
            ->with($expectedResponse);

        $this->subject->onKernelException($event);
    }
}
