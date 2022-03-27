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
 *  Copyright (c) 2022 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Functional\Action\LtiInstance;

use OAT\SimpleRoster\Entity\LtiInstance;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DeleteActionTest extends AbstractLtiInstanceTest
{
    protected string $url = '/api/v1/lti-instances';
    protected string $method = Request::METHOD_DELETE;

    public function testInvalidAuth(): void
    {
        $this->kernelBrowser->request(
            $this->method,
            $this->url . '/1',
            [],
            [],
            ['PHP_AUTH_USER' => 'wrong', 'PHP_AUTH_PW' => 'invalid'],
            ''
        );

        self::assertEquals(
            Response::HTTP_UNAUTHORIZED,
            $this->kernelBrowser->getResponse()->getStatusCode()
        );
    }

    public function testInvalidIndex(): void
    {
        $this->kernelBrowser->request(
            $this->method,
            $this->url . '/777'
        );

        self::assertEquals(
            Response::HTTP_NOT_FOUND,
            $this->kernelBrowser->getResponse()->getStatusCode()
        );
    }

    public function testValidRequest(): void
    {
        $index = current($this->data)->getId();

        $this->kernelBrowser->request(
            $this->method,
            $this->url . "/{$index}"
        );

        self::assertEquals(
            Response::HTTP_ACCEPTED,
            $this->kernelBrowser->getResponse()->getStatusCode()
        );

        /**@var LtiInstanceRepository */
        $repository = $this->getRepository(LtiInstance::class);

        self::assertNull($repository->find($index));

        self::assertEquals(count($this->data) - 1, count($repository->findAll()));
    }
}
