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

namespace OAT\SimpleRoster\Tests\Functional\Action\HealthCheck;

use OAT\SimpleRoster\Tests\Traits\ApiTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HealthCheckActionTest extends WebTestCase
{
    use ApiTestingTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();
    }

    public function testItReturns200WhenApplicationInHealthy(): void
    {
        $this->kernelBrowser->request(Request::METHOD_GET, '/api/v1');

        $this->assertApiStatusCode(Response::HTTP_OK);
        $this->assertApiResponse(
            [
                'isDoctrineConnectionAvailable' => true,
                'isDoctrineCacheAvailable' => true,
            ]
        );
    }

    public function testItReturns405OnInvalidMethod(): void
    {
        $this->kernelBrowser->request(Request::METHOD_POST, '/api/v1');

        $this->assertApiStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);
    }
}
