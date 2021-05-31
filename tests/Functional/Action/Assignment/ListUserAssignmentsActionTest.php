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
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\UserAuthenticatorTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ListUserAssignmentsActionTest extends WebTestCase
{
    use DatabaseTestingTrait;
    use UserAuthenticatorTrait;

    private KernelBrowser $kernelBrowser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();

        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');
    }

    public function testWithNonAuthenticatedUser(): void
    {
        $this->kernelBrowser->request(Request::METHOD_GET, '/api/v1/assignments');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame(
            'Full authentication is required to access this resource.',
            $decodedResponse
        );
    }

    public function testItReturnListOfUserAssignmentsWhenCurrentDateMatchesLineItemAvailability(): void
    {
        Carbon::setTestNow(Carbon::createFromDate(2019, 1, 1));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $authenticationResponse = $this->logInAs($user, $this->kernelBrowser);

        $this->kernelBrowser->request(
            Request::METHOD_GET,
            '/api/v1/assignments',
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $authenticationResponse['accessToken']]
        );

        $lineItem = $user->getLastAssignment()->getLineItem();

        $startDate = $lineItem->getStartAt();
        $endDate = $lineItem->getEndAt();

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());
        self::assertSame([
            'assignments' => [
                [
                    'id' => $user->getLastAssignment()->getId(),
                    'username' => $user->getUsername(),
                    'state' => Assignment::STATE_READY,
                    'attemptsCount' => $user->getLastAssignment()->getAttemptsCount(),
                    'lineItem' => [
                        'uri' => $lineItem->getUri(),
                        'label' => $lineItem->getLabel(),
                        'isActive' => $lineItem->isActive(),
                        'startDateTime' => $startDate instanceof DateTimeInterface ? $startDate->getTimestamp() : '',
                        'endDateTime' => $endDate instanceof DateTimeInterface ? $endDate->getTimestamp() : '',
                        'maxAttempts' => $lineItem->getMaxAttempts(),
                    ],
                ],
            ],
        ], json_decode($this->kernelBrowser->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testItReturnListOfUserAssignmentsEvenWhenCurrentDateDoesNotMatchLineItemAvailability(): void
    {
        Carbon::setTestNow(Carbon::createFromDate(2022, 1, 1));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $authenticationResponse = $this->logInAs($user, $this->kernelBrowser);

        $this->kernelBrowser->request(
            Request::METHOD_GET,
            '/api/v1/assignments',
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $authenticationResponse['accessToken']]
        );

        $lineItem = $user->getLastAssignment()->getLineItem();

        $startDate = $lineItem->getStartAt();
        $endDate = $lineItem->getEndAt();

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());
        self::assertSame([
            'assignments' => [
                [
                    'id' => $user->getLastAssignment()->getId(),
                    'username' => $user->getUsername(),
                    'state' => Assignment::STATE_READY,
                    'attemptsCount' => $user->getLastAssignment()->getAttemptsCount(),
                    'lineItem' => [
                        'uri' => $lineItem->getUri(),
                        'label' => $lineItem->getLabel(),
                        'isActive' => $lineItem->isActive(),
                        'startDateTime' => $startDate instanceof DateTimeInterface ? $startDate->getTimestamp() : '',
                        'endDateTime' => $endDate instanceof DateTimeInterface ? $endDate->getTimestamp() : '',
                        'maxAttempts' => $lineItem->getMaxAttempts(),
                    ],
                ],
            ],
        ], json_decode($this->kernelBrowser->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }
}
