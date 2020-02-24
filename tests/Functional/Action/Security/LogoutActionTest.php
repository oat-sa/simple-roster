<?php

declare(strict_types=1);

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

namespace App\Tests\Functional\Action\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Traits\DatabaseTestingTrait;
use App\Tests\Traits\UserAuthenticatorTrait;
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
        $this->setUpDatabase(self::$kernel);
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');
    }

    public function testItLogsOutProperlyTheUser(): void
    {
        $userRepository = self::$container->get(UserRepository::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

        $this->logInAs($user, $this->kernelBrowser);

        $session = $this->kernelBrowser->getContainer()->get('session');

        $this->assertNotEmpty($session->all());

        $this->kernelBrowser->request(Request::METHOD_POST, '/api/v1/auth/logout');

        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->kernelBrowser->getResponse()->getStatusCode());

        $this->assertEmpty($session->all());
    }
}
