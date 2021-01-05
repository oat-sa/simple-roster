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

namespace OAT\SimpleRoster\Tests\Functional\Action\Security;

use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Service\JWT\TokenStorage;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\UserAuthenticatorTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LogoutActionTest extends WebTestCase
{
    use DatabaseTestingTrait;
    use UserAuthenticatorTrait;

    /** @var KernelBrowser */
    private $kernelBrowser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();

        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');
    }

    public function testItLogsOutProperlyTheUser(): void
    {
        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $this->logInAs($user, $this->kernelBrowser);

        $this->kernelBrowser->request(Request::METHOD_POST, '/api/v1/auth/logout');

        self::assertSame(Response::HTTP_NO_CONTENT, $this->kernelBrowser->getResponse()->getStatusCode());
    }

    public function testItRemovesTokenOnLogout(): void
    {
        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $this->logInAs($user, $this->kernelBrowser);

        $cachePool = self::$container->get(TokenStorage::class);

        self::assertNotNull($cachePool->getStoredToken('user1')->get());

        $this->kernelBrowser->request(Request::METHOD_POST, '/api/v1/auth/logout');

        self::assertSame(Response::HTTP_NO_CONTENT, $this->kernelBrowser->getResponse()->getStatusCode());

        self::assertNull($cachePool->getStoredToken('user1')->get());
    }
}
