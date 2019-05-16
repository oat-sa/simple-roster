<?php declare(strict_types=1);
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
