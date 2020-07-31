<?php

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

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\RequestIdGeneratorSubscriber;
use App\Request\RequestIdStorage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestIdGeneratorSubscriberTest extends TestCase
{
    /** @var UuidFactoryInterface|MockObject */
    private $uuidFactory;

    /** @var RequestIdStorage */
    private $requestIdStorage;

    /** @var RequestEvent|MockObject */
    private $requestEvent;

    /** @var RequestIdGeneratorSubscriber */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uuidFactory = $this->createMock(UuidFactoryInterface::class);
        $this->requestIdStorage = new RequestIdStorage();
        $this->requestEvent = $this->createMock(RequestEvent::class);

        $this->subject = new RequestIdGeneratorSubscriber($this->uuidFactory, $this->requestIdStorage);
    }

    public function testItIsAnEventSubscriber(): void
    {
        $this->assertInstanceOf(EventSubscriberInterface::class, $this->subject);
    }

    public function testItSubscribesToKernelRequestEventWithHighestPriority(): void
    {
        $this->assertSame(
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

        $this->requestEvent
            ->method('getRequest')
            ->willReturn($request);

        $this->requestEvent
            ->method('isMasterRequest')
            ->willReturn(true);

        $this->subject->onKernelRequest($this->requestEvent);

        $this->assertSame('expectedRequestId', $request->attributes->get('requestId'));
        $this->assertSame('expectedRequestId', $this->requestIdStorage->getRequestId());
    }

    public function testItWillGenerateNewRequestIdIfCloudfrontHeaderIsNotPresent(): void
    {
        $request = Request::create('/test');

        $this->uuidFactory
            ->expects($this->once())
            ->method('uuid4')
            ->willReturn('expectedRequestId');

        $this->requestEvent
            ->method('getRequest')
            ->willReturn($request);

        $this->requestEvent
            ->method('isMasterRequest')
            ->willReturn(true);

        $this->subject->onKernelRequest($this->requestEvent);

        $this->assertSame('expectedRequestId', $request->attributes->get('requestId'));
        $this->assertSame('expectedRequestId', $this->requestIdStorage->getRequestId());
    }

    public function testItWillNotSetRequestIdOnSubRequests(): void
    {
        $this->requestEvent
            ->method('isMasterRequest')
            ->willReturn(false);

        $this->uuidFactory
            ->expects($this->never())
            ->method('uuid4');

        $this->subject->onKernelRequest($this->requestEvent);
    }
}
