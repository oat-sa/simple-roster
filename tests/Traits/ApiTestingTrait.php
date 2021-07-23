<?php

/*
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
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Traits;

use Lcobucci\JWT\Parser;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Tests\Helpers\Authentication\AuthenticationResponse;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;

trait ApiTestingTrait
{
    protected KernelBrowser $kernelBrowser;

    protected function authenticateAs(User $user): AuthenticationResponse
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/token',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            (string)json_encode(['username' => $user->getUsername(), 'password' => $user->getPlainPassword()])
        );

        $decodedResponse = (array)json_decode($this->kernelBrowser->getResponse()->getContent(), true);
        $accessToken = (new Parser())->parse($decodedResponse['accessToken']);
        $refreshToken = (new Parser())->parse($decodedResponse['refreshToken']);

        return new AuthenticationResponse($accessToken, $refreshToken);
    }

    public function assertApiStatusCode(int $expectedHttpStatusCode): void
    {
        self::assertSame($expectedHttpStatusCode, $this->kernelBrowser->getResponse()->getStatusCode());
    }

    public function assertApiErrorResponseMessage(string $expectedErrorResponse): void
    {
        $decodedResponse = json_decode($this->kernelBrowser->getResponse()->getContent(), true);

        self::assertSame($expectedErrorResponse, $decodedResponse['error']['message']);
    }

    public function assertApiErrorResponse(string $expectedErrorResponse): void
    {
        $decodedResponse = json_decode($this->kernelBrowser->getResponse()->getContent(), true);

        self::assertSame($expectedErrorResponse, $decodedResponse['error']);
    }

    /**
     * @param mixed $expectedResponse
     */
    public function assertApiResponse($expectedResponse): void
    {
        self::assertSame($expectedResponse, json_decode($this->kernelBrowser->getResponse()->getContent(), true));
    }

    public function getDecodedJsonApiResponse(): array
    {
        return json_decode($this->kernelBrowser->getResponse()->getContent(), true);
    }
}
