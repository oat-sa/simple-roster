<?php declare(strict_types=1);

namespace App\Tests\Functional\Action\HealthCheck;

use App\Tests\Traits\DatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HealthCheckActionTest extends WebTestCase
{
    use DatabaseTrait;

    public function testItReturns200WhenApplicationInHealthy(): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_GET, '/api/v1/healthcheck');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $this->assertEquals(
            [
                'isDoctrineConnectionAvailable' => true,
                'isDoctrineCacheAvailable' => true,
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
    }

    public function testItReturns405OnInvalidMethod(): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_POST, '/api/v1/healthcheck');

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }
}
