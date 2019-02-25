<?php declare(strict_types=1);

namespace App\Tests\Integration\Responder;

use App\Responder\SerializerResponder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Exception;
use Throwable;

class SerializerResponderTest extends KernelTestCase
{
    /** @var bool  */
    private $debug = true;

    public function setUp()
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
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => SerializerResponder::DEFAULT_ERROR_MESSAGE,
                ]
            ],
            json_decode($response->getContent(), true)
        );
    }

    public function testCreateDefaultErrorJsonResponseWithDebug(): void
    {
        $this->debug = true;

        $exception = new Exception('custom error message');

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => 'custom error message',
                ]
            ],
            json_decode($response->getContent(), true)
        );
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
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => SerializerResponder::DEFAULT_ERROR_MESSAGE,
                ]
            ],
            json_decode($response->getContent(), true)
        );
    }

    public function testCreateCustomErrorJsonResponseWithDebug(): void
    {
        $this->debug = true;

        $exception = new Exception('custom error message');

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => 'custom error message',
                ]
            ],
            json_decode($response->getContent(), true)
        );
        $this->assertArrayHasKey(
            'trace',
            json_decode($response->getContent(), true)['error']
        );
    }

    public function testCreate4xxHttpErrorJsonResponse(): void
    {
        $this->debug = false;

        $exception = $this->createHttpException('custom error message', Response::HTTP_I_AM_A_TEAPOT);

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        $this->assertEquals(Response::HTTP_I_AM_A_TEAPOT, $response->getStatusCode());
        $this->assertEquals('exceptionHeader', $response->headers->get('some'));
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => 'custom error message',
                ]
            ],
            json_decode($response->getContent(), true)
        );
    }

    public function testCreate4xxHttpErrorJsonResponseWithDebug(): void
    {
        $this->debug = true;

        $exception = $this->createHttpException('custom error message', Response::HTTP_I_AM_A_TEAPOT);

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        $this->assertEquals(Response::HTTP_I_AM_A_TEAPOT, $response->getStatusCode());
        $this->assertEquals('exceptionHeader', $response->headers->get('some'));
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => 'custom error message',
                ]
            ],
            json_decode($response->getContent(), true)
        );
        $this->assertArrayHasKey(
            'trace',
            json_decode($response->getContent(), true)['error']
        );
    }

    public function testCreate5xxHttpErrorJsonResponse(): void
    {
        $this->debug = false;

        $exception = $this->createHttpException('custom error message', Response::HTTP_NOT_IMPLEMENTED);

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        $this->assertEquals(Response::HTTP_NOT_IMPLEMENTED, $response->getStatusCode());
        $this->assertEquals('exceptionHeader', $response->headers->get('some'));
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => SerializerResponder::DEFAULT_ERROR_MESSAGE,
                ]
            ],
            json_decode($response->getContent(), true)
        );
    }

    public function testCreate5xxHttpErrorJsonResponseWithDebug(): void
    {
        $this->debug = true;

        $exception = $this->createHttpException('custom error message', Response::HTTP_NOT_IMPLEMENTED);

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        $this->assertEquals(Response::HTTP_NOT_IMPLEMENTED, $response->getStatusCode());
        $this->assertEquals('exceptionHeader', $response->headers->get('some'));
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => 'custom error message',
                ]
            ],
            json_decode($response->getContent(), true)
        );
        $this->assertArrayHasKey(
            'trace',
            json_decode($response->getContent(), true)['error']
        );
    }

    private function createResponderInstance(): SerializerResponder
    {
        return new SerializerResponder(
            static::$container->get(SerializerInterface::class),
            $this->debug
        );
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
