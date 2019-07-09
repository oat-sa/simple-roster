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

namespace App\Tests\Functional\Action\Security;

use App\Entity\User;
use App\Tests\Traits\DatabaseFixturesTrait;
use App\Tests\Traits\UserAuthenticatorTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LogoutActionTest extends WebTestCase
{
    use DatabaseFixturesTrait;
    use UserAuthenticatorTrait;

    public function testItLogsOutProperlyTheUser(): void
    {
        $kernelBrowser = self::createClient();

        $user = $this->getRepository(User::class)->find(1);

        $this->logInAs($user, $kernelBrowser);

        $session = $kernelBrowser->getContainer()->get('session');

        $this->assertNotEmpty($session->all());

        $kernelBrowser->request(Request::METHOD_POST, '/api/v1/auth/logout');

        $this->assertEquals(Response::HTTP_NO_CONTENT, $kernelBrowser->getResponse()->getStatusCode());

        $this->assertEmpty($session->all());
    }
}
