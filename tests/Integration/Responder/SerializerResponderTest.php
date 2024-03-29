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

namespace OAT\SimpleRoster\Tests\Integration\Responder;

use Exception;
use OAT\SimpleRoster\Kernel;
use OAT\SimpleRoster\Responder\SerializerResponder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

class SerializerResponderTest extends KernelTestCase
{
    private bool $debug = true;

    public function setUp(): void
    {
        parent::setUp();

        static::bootKernel();
    }

    public function testCreateDefaultJsonResponse(): void
    {
        $data = ['some' => 'data'];

        $response = $this->createResponderInstance()->createJsonResponse($data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(json_encode($data, JSON_THROW_ON_ERROR, 512), $response->getContent());
    }

    public function testCreateCustomJsonResponse(): void
    {
        $data = ['some' => 'data'];

        $response = $this->createResponderInstance()->createJsonResponse(
            $data,
            Response::HTTP_CREATED,
            ['some' => 'header']
        );

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertSame('header', $response->headers->get('some'));
        self::assertSame(json_encode($data, JSON_THROW_ON_ERROR, 512), $response->getContent());
    }

    public function testCreateDefaultErrorJsonResponse(): void
    {
        $this->debug = false;

        $exception = new Exception();

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(SerializerResponder::DEFAULT_ERROR_MESSAGE, $decodedResponse['error']['message']);
    }

    public function testCreateDefaultErrorJsonResponseWithDebug(): void
    {
        $this->debug = true;

        $exception = new Exception('custom error message');

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('custom error message', $decodedResponse['error']['message']);
    }

    public function testCreateCustomErrorJsonResponse(): void
    {
        $this->debug = false;

        $exception = new Exception();

        $response = $this->createResponderInstance()->createErrorJsonResponse(
            $exception,
            Response::HTTP_NOT_IMPLEMENTED,
            ['some' => 'header']
        );

        self::assertSame(Response::HTTP_NOT_IMPLEMENTED, $response->getStatusCode());
        self::assertSame('header', $response->headers->get('some'));

        $decodedResponse = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(SerializerResponder::DEFAULT_ERROR_MESSAGE, $decodedResponse['error']['message']);
    }

    public function testCreateCustomErrorJsonResponseWithDebug(): void
    {
        $this->debug = true;

        $exception = new Exception('custom error message');

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('custom error message', $decodedResponse['error']['message']);

        self::assertArrayHasKey(
            'trace',
            $decodedResponse['error']
        );
    }

    public function testCreate4xxHttpErrorJsonResponse(): void
    {
        $this->debug = false;

        $exception = $this->createHttpException('custom error message', Response::HTTP_I_AM_A_TEAPOT);

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        self::assertSame(Response::HTTP_I_AM_A_TEAPOT, $response->getStatusCode());
        self::assertSame('exceptionHeader', $response->headers->get('some'));

        $decodedResponse = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('custom error message', $decodedResponse['error']['message']);
    }

    public function testCreate4xxHttpErrorJsonResponseWithDebug(): void
    {
        $this->debug = true;

        $exception = $this->createHttpException('custom error message', Response::HTTP_I_AM_A_TEAPOT);

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        self::assertSame(Response::HTTP_I_AM_A_TEAPOT, $response->getStatusCode());
        self::assertSame('exceptionHeader', $response->headers->get('some'));

        $decodedResponse = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('custom error message', $decodedResponse['error']['message']);

        self::assertArrayHasKey(
            'trace',
            $decodedResponse['error']
        );
    }

    public function testCreate5xxHttpErrorJsonResponse(): void
    {
        $this->debug = false;

        $exception = $this->createHttpException('custom error message', Response::HTTP_INTERNAL_SERVER_ERROR);

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertSame('exceptionHeader', $response->headers->get('some'));

        $decodedResponse = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(SerializerResponder::DEFAULT_ERROR_MESSAGE, $decodedResponse['error']['message']);
    }

    public function testCreate5xxHttpErrorJsonResponseWithDebug(): void
    {
        $this->debug = true;

        $exception = $this->createHttpException('custom error message', Response::HTTP_NOT_IMPLEMENTED);

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        self::assertSame(Response::HTTP_NOT_IMPLEMENTED, $response->getStatusCode());
        self::assertSame('exceptionHeader', $response->headers->get('some'));

        $decodedResponse = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('custom error message', $decodedResponse['error']['message']);

        self::assertArrayHasKey(
            'trace',
            $decodedResponse['error']
        );
    }

    private function createResponderInstance(): SerializerResponder
    {
        $kernel = new Kernel('test', $this->debug);

        return new SerializerResponder(self::getContainer()->get(SerializerInterface::class), $kernel);
    }

    private function createHttpException(string $message, int $statusCode): Throwable
    {
        return new class ($message, $statusCode) extends Exception implements HttpExceptionInterface
        {
            public function getStatusCode()
            {
                return $this->code;
            }

            public function getHeaders()
            {
                return ['some' => 'exceptionHeader'];
            }
        };
    }
}
