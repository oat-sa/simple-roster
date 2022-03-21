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

use OAT\SimpleRoster\Responder\LtiInstance\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ListActionTest extends AbstractLtiInstanceTest
{
    protected string $url = '/api/v1/lti-instances';
    protected string $method = Request::METHOD_GET;

    public function testInvalidAuth(): void
    {
        $this->kernelBrowser->request(
            $this->method,
            $this->url,
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

    public function testValidRequest(): void
    {
        $this->kernelBrowser->request(
            $this->method,
            $this->url
        );

        self::assertEquals(
            Response::HTTP_OK,
            $this->kernelBrowser->getResponse()->getStatusCode()
        );

        self::assertEquals($this->getJsonList(), $this->kernelBrowser->getResponse()->getContent());
    }

    public function getJsonList(): string
    {
        $result = [];
        foreach ($this->data as $item) {
            $result[] = (new Model)->fillFromEntity($item);
        }

        return json_encode($result);
    }
}