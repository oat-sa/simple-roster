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

namespace OAT\SimpleRoster\Action\HealthCheck;

use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Service\HealthCheck\HealthCheckService;
use Symfony\Component\HttpFoundation\Response;

class HealthCheckAction
{
    private HealthCheckService $healthCheckService;
    private SerializerResponder $serializerResponder;

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
