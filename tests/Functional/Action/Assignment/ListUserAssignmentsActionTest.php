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

namespace App\Tests\Functional\Action\Assignment;

use App\Entity\Assignment;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Traits\DatabaseFixturesTrait;
use App\Tests\Traits\UserAuthenticatorTrait;
use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ListUserAssignmentsActionTest extends WebTestCase
{
    use DatabaseFixturesTrait;
    use UserAuthenticatorTrait;

    public function testWithNonAuthenticatedUser(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/api/v1/assignments');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());

        $decodedResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(
            'Full authentication is required to access this resource.',
            $decodedResponse['error']['message']
        );
    }

    public function testItReturnListOfUserAssignmentsWhenCurrentDateMatchesLineItemAvailability(): void
    {
        Carbon::setTestNow(Carbon::createFromDate(2019, 1, 1));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');
        $client = self::createClient();

        $this->logInAs($user, $client);

        $client->request(Request::METHOD_GET, '/api/v1/assignments');

        $lineItem = $user->getLastAssignment()->getLineItem();

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertEquals([
            'assignments' => [
                [
                    'id' => $user->getLastAssignment()->getId(),
                    'username' => $user->getUsername(),
                    'state' => Assignment::STATE_READY,
                    'lineItem' => [
                        'uri' => $lineItem->getUri(),
                        'label' => $lineItem->getLabel(),
                        'startDateTime' => $lineItem->getStartAt()->getTimestamp(),
                        'endDateTime' => $lineItem->getEndAt()->getTimestamp(),
                        'infrastructure' => $lineItem->getInfrastructure()->getId(),
                    ],
                ],
            ],
        ], json_decode($client->getResponse()->getContent(), true));
    }

    public function testItReturnListOfUserAssignmentsWhenCurrentDateDoesNotMatchLineItemAvailability(): void
    {
        Carbon::setTestNow(Carbon::createFromDate(2022, 1, 1));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');
        $client = self::createClient();

        $this->logInAs($user, $client);

        $client->request(Request::METHOD_GET, '/api/v1/assignments');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertEquals(['assignments' => [],], json_decode($client->getResponse()->getContent(), true));
    }
}
