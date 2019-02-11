<?php declare(strict_types=1);

namespace App\Tests\Integration\Responder;

use App\Responder\SerializerResponder;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

class SerializerResponderTest extends KernelTestCase
{
    /** @var bool  */
    private $debug = false;

    public function setUp()
    {
        parent::setUp();

        static::bootKernel();
    }

    public function testCreateDefaultJsonResponse(): void
    {
        $data = ['some' => 'data'];

        $response = $this->createResponderInstance()->createJsonResponse($data);

        $this->assertInstanceOf(JsonResponse::class, $response);
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

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertEquals('header', $response->headers->get('some'));
        $this->assertEquals(json_encode($data), $response->getContent());
    }

    public function testCreateDefaultErrorJsonResponse(): void
    {
        $exception = new Exception('error message');

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => 'error message',
                ]
            ],
            json_decode($response->getContent(), true)
        );
    }

    public function testCreateDefaultErrorJsonResponseWithDebug(): void
    {
        $this->debug = true;

        $exception = new Exception('error message');

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => 'error message',
                ]
            ],
            json_decode($response->getContent(), true)
        );
        $this->assertArrayHasKey(
            'trace',
            json_decode($response->getContent(), true)['error']
        );
    }

    public function testCreateCustomErrorJsonResponse(): void
    {
        $exception = new Exception('error message');

        $response = $this->createResponderInstance()->createErrorJsonResponse(
            $exception,
            Response::HTTP_NOT_IMPLEMENTED,
            ['some' => 'header']
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_NOT_IMPLEMENTED, $response->getStatusCode());
        $this->assertEquals('header', $response->headers->get('some'));
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => 'error message',
                ]
            ],
            json_decode($response->getContent(), true)
        );
    }

    public function testCreateHttpErrorJsonResponse(): void
    {
        $exception = $this->createHttpException('error message');

        $response = $this->createResponderInstance()->createErrorJsonResponse($exception);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_I_AM_A_TEAPOT, $response->getStatusCode());
        $this->assertEquals('exceptionHeader', $response->headers->get('some'));
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => 'error message',
                ]
            ],
            json_decode($response->getContent(), true)
        );
    }

    private function createResponderInstance(): SerializerResponder
    {
        return new SerializerResponder(
            static::$container->get(SerializerInterface::class),
            $this->debug
        );
    }

    private function createHttpException(string $message): Throwable
    {
        return new class ($message) extends Exception implements HttpExceptionInterface
        {
            public function getStatusCode()
            {
                return Response::HTTP_I_AM_A_TEAPOT;
            }

            public function getHeaders()
            {
                return ['some' => 'exceptionHeader'];
            }
        };
    }
}