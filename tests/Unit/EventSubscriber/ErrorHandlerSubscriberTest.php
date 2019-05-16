<?php declare(strict_types=1);
/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\ErrorHandlerSubscriber;
use App\Responder\SerializerResponder;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ErrorHandlerSubscriberTest extends TestCase
{
    /** @var ErrorHandlerSubscriber */
    private $subject;

    /** @var SerializerResponder|MockObject */
    private $responder;

    protected function setUp(): void
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
