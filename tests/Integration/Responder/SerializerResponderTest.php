<?php

declare(strict_types=1);

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

namespace App\Tests\Integration\Responder;

use App\Responder\SerializerResponder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Exception;
use Throwable;

class SerializerResponderTest extends KernelTestCase
{
    /** @var bool|null  */
    private $debug = true;

    public function setUp(): void
    {
        parent::setUp();

        static::bootKernel();
    }

    public function testCreateDefaultJsonResponse(): void
    {
        $data = ['some' => 'data'];

        $response = $this->createResponderInstance()->createJsonResponse($data);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(json_encode($data), $response->getContent());
    }

    public function testCreateCustomJsonResponse(): void
    {
        $data = ['some' => 'data'];

        $response = $this->createResponderInstance()->createJsonResponse(
            $data,
            Response::HTTP_CREATED,
            ['some' => 'header']
        );

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertEquals('header', $response->headers->get('some'));
        $this->assertEquals(json_encode($data), $response->getContent());
    }

    public function testCreateDefaultErrorJsonResponse(): void
    {
        $this->debug = false;

        $exception = new Exception();

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);
        $this->assertEquals(SerializerResponder::DEFAULT_ERROR_MESSAGE, $decodedResponse['error']['message']);
    }

    public function testCreateDefaultErrorJsonResponseWithDebug(): void
    {
        $this->debug = true;

        $exception = new Exception('custom error message');

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);
        $this->assertEquals('custom error message', $decodedResponse['error']['message']);
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

        $this->assertEquals(Response::HTTP_NOT_IMPLEMENTED, $response->getStatusCode());
        $this->assertEquals('header', $response->headers->get('some'));

        $decodedResponse = json_decode($response->getContent(), true);
        $this->assertEquals(SerializerResponder::DEFAULT_ERROR_MESSAGE, $decodedResponse['error']['message']);
    }

    public function testCreateCustomErrorJsonResponseWithDebug(): void
    {
        $this->debug = true;

        $exception = new Exception('custom error message');

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);
        $this->assertEquals('custom error message', $decodedResponse['error']['message']);

        $this->assertArrayHasKey(
            'trace',
            $decodedResponse['error']
        );
    }

    public function testCreate4xxHttpErrorJsonResponse(): void
    {
        $this->debug = false;

        $exception = $this->createHttpException('custom error message', Response::HTTP_I_AM_A_TEAPOT);

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        $this->assertEquals(Response::HTTP_I_AM_A_TEAPOT, $response->getStatusCode());
        $this->assertEquals('exceptionHeader', $response->headers->get('some'));

        $decodedResponse = json_decode($response->getContent(), true);
        $this->assertEquals('custom error message', $decodedResponse['error']['message']);
    }

    public function testCreate4xxHttpErrorJsonResponseWithDebug(): void
    {
        $this->debug = true;

        $exception = $this->createHttpException('custom error message', Response::HTTP_I_AM_A_TEAPOT);

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        $this->assertEquals(Response::HTTP_I_AM_A_TEAPOT, $response->getStatusCode());
        $this->assertEquals('exceptionHeader', $response->headers->get('some'));

        $decodedResponse = json_decode($response->getContent(), true);
        $this->assertEquals('custom error message', $decodedResponse['error']['message']);

        $this->assertArrayHasKey(
            'trace',
            $decodedResponse['error']
        );
    }

    public function testCreate5xxHttpErrorJsonResponse(): void
    {
        // To test with default debug parameter value, assuming `false`
        $this->debug = null;

        $exception = $this->createHttpException('custom error message', Response::HTTP_INTERNAL_SERVER_ERROR);

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEquals('exceptionHeader', $response->headers->get('some'));

        $decodedResponse = json_decode($response->getContent(), true);
        $this->assertEquals(SerializerResponder::DEFAULT_ERROR_MESSAGE, $decodedResponse['error']['message']);
    }

    public function testCreate5xxHttpErrorJsonResponseWithDebug(): void
    {
        $this->debug = true;

        $exception = $this->createHttpException('custom error message', Response::HTTP_NOT_IMPLEMENTED);

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        $this->assertEquals(Response::HTTP_NOT_IMPLEMENTED, $response->getStatusCode());
        $this->assertEquals('exceptionHeader', $response->headers->get('some'));

        $decodedResponse = json_decode($response->getContent(), true);
        $this->assertEquals('custom error message', $decodedResponse['error']['message']);

        $this->assertArrayHasKey(
            'trace',
            $decodedResponse['error']
        );
    }

    private function createResponderInstance(): SerializerResponder
    {
        return null !== $this->debug
            ? new SerializerResponder(static::$container->get(SerializerInterface::class), $this->debug)
            : new SerializerResponder(static::$container->get(SerializerInterface::class));
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
