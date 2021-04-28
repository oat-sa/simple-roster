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

namespace OAT\SimpleRoster\Tests\Functional\Action\Assignment;

use Carbon\Carbon;
use DateTimeInterface;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Tests\Traits\ApiTestingTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ListUserAssignmentsActionTest extends WebTestCase
{
    use ApiTestingTrait;
    use DatabaseTestingTrait;

    /** @var UserRepository */
    private $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();
        $this->userRepository = self::$container->get(UserRepository::class);

        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');
    }

    public function testWithNonAuthenticatedUser(): void
    {
        $this->kernelBrowser->request(Request::METHOD_GET, '/api/v1/assignments');

        $this->assertApiStatusCode(Response::HTTP_UNAUTHORIZED);
        $this->assertApiResponse('Full authentication is required to access this resource.');
    }

    public function testItReturnListOfUserAssignmentsWhenCurrentDateMatchesLineItemAvailability(): void
    {
        Carbon::setTestNow(Carbon::createFromDate(2019, 1, 1));

        $user = $this->userRepository->findByUsernameWithAssignments('user1');

        $this->kernelBrowser->request(
            Request::METHOD_GET,
            '/api/v1/assignments',
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->authenticateAs($user)->getAccessToken()]
        );

        $lineItem = $user->getLastAssignment()->getLineItem();
        $startDate = $lineItem->getStartAt();
        $endDate = $lineItem->getEndAt();

        $this->assertApiStatusCode(Response::HTTP_OK);
        $this->assertApiResponse(
            [
                'assignments' => [
                    [
                        'id' => (string)$user->getLastAssignment()->getId(),
                        'username' => $user->getUsername(),
                        'status' => Assignment::STATUS_READY,
                        'attemptsCount' => $user->getLastAssignment()->getAttemptsCount(),
                        'lineItem' => [
                            'uri' => $lineItem->getUri(),
                            'label' => $lineItem->getLabel(),
                            'status' => $lineItem->getStatus(),
                            'startDateTime' => $startDate instanceof DateTimeInterface
                                ? $startDate->getTimestamp()
                                : '',
                            'endDateTime' => $endDate instanceof DateTimeInterface ? $endDate->getTimestamp() : '',
                            'maxAttempts' => $lineItem->getMaxAttempts(),
                            'groupId' => $lineItem->getGroupId()
                        ],
                    ],
                ],
            ]
        );
    }

    public function testItReturnListOfUserAssignmentsEvenWhenCurrentDateDoesNotMatchLineItemAvailability(): void
    {
        Carbon::setTestNow(Carbon::createFromDate(2022, 1, 1));

        $user = $this->userRepository->findByUsernameWithAssignments('user1');

        $this->kernelBrowser->request(
            Request::METHOD_GET,
            '/api/v1/assignments',
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->authenticateAs($user)->getAccessToken()]
        );

        $lineItem = $user->getLastAssignment()->getLineItem();
        $startDate = $lineItem->getStartAt();
        $endDate = $lineItem->getEndAt();

        $this->assertApiStatusCode(Response::HTTP_OK);
        $this->assertApiResponse(
            [
                'assignments' => [
                    [
                        'id' => (string)$user->getLastAssignment()->getId(),
                        'username' => $user->getUsername(),
                        'status' => Assignment::STATUS_READY,
                        'attemptsCount' => $user->getLastAssignment()->getAttemptsCount(),
                        'lineItem' => [
                            'uri' => $lineItem->getUri(),
                            'label' => $lineItem->getLabel(),
                            'status' => $lineItem->getStatus(),
                            'startDateTime' => $startDate instanceof DateTimeInterface
                                ? $startDate->getTimestamp()
                                : '',
                            'endDateTime' => $endDate instanceof DateTimeInterface ? $endDate->getTimestamp() : '',
                            'maxAttempts' => $lineItem->getMaxAttempts(),
                            'groupId' => $lineItem->getGroupId(),
                        ],
                    ],
                ],
            ]
        );
    }
}
