<?php declare(strict_types=1);

namespace App\Action\HealthCheck;

use App\Responder\SerializerResponder;
use App\Service\HealthCheck\HealthCheckService;
use Symfony\Component\HttpFoundation\Response;

class HealthCheckAction
{
    /** @var HealthCheckService */
    private $healthCheckService;

    /** @var SerializerResponder */
    private $serializerResponder;

    public function __construct(HealthCheckService $healthCheckService, SerializerResponder $serializerResponder)
    {
        $this->healthCheckService = $healthCheckService;
        $this->serializerResponder = $serializerResponder;
    }

    public function __invoke(): Response
    {
        return $this->serializerResponder->createJsonResponse($this->healthCheckService->getHealthCheckResult());
    }
}
