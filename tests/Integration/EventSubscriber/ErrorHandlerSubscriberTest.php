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

namespace App\Tests\Integration\EventSubscriber;

use App\EventSubscriber\ErrorHandlerSubscriber;
use App\Responder\SerializerResponder;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ErrorHandlerSubscriberTest extends KernelTestCase
{
    /** @var ErrorHandlerSubscriber */
    private $subject;

    /** @var SerializerResponder */
    private $responder;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        $this->responder = self::$container->get(SerializerResponder::class);
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
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);

        $throwable = new ServiceUnavailableHttpException();

        $exceptionEvent = new ExceptionEvent(
            static::$kernel,
            $requestMock,
            HttpKernelInterface::SUB_REQUEST,
            $throwable
        );

        $this->subject->onKernelException($exceptionEvent);

        $this->assertNull($exceptionEvent->getResponse());
    }

    public function testItSetsProperResponseFromResponderOnMasterRequest(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);

        $throwable = new ServiceUnavailableHttpException();

        $exceptionEvent = new ExceptionEvent(
            static::$kernel,
            $requestMock,
            HttpKernelInterface::MASTER_REQUEST,
            $throwable
        );

        $this->subject->onKernelException($exceptionEvent);

        $expectedJsonResponse = $this->responder->createErrorJsonResponse($throwable);

        $this->assertEquals($expectedJsonResponse, $exceptionEvent->getResponse());
    }
}
