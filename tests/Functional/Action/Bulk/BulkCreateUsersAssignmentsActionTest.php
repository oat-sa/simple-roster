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

namespace OAT\SimpleRoster\Tests\Functional\Action\Bulk;

use Carbon\Carbon;
use Monolog\Logger;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Request\ParamConverter\BulkOperationCollectionParamConverter;
use OAT\SimpleRoster\Tests\Traits\ApiTestingTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BulkCreateUsersAssignmentsActionTest extends WebTestCase
{
    use ApiTestingTrait;
    use DatabaseTestingTrait;
    use LoggerTestingTrait;

    /** @var UserRepository */
    private $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient([], ['HTTP_AUTHORIZATION' => 'Bearer ' . 'testApiKey']);
        $this->userRepository = self::$container->get(UserRepository::class);

        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        $this->setUpTestLogHandler();
    }

    public function testItThrowsUnauthorizedHttpExceptionIfRequestApiKeyIsInvalid(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/bulk/assignments',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer invalid'],
            '{}'
        );

        $this->assertApiStatusCode(Response::HTTP_UNAUTHORIZED);
        $this->assertApiErrorResponseMessage('API key authentication failure.');
    }

    public function testItThrowsBadRequestHttpExceptionIfRequestBodyIsInvalid(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            'invalid body content'
        );

        $this->assertApiStatusCode(Response::HTTP_BAD_REQUEST);
        $this->assertApiErrorResponseMessage('Invalid JSON request body received. Error: Syntax error');
    }

    public function testItThrowsRequestEntityTooLargeHttpExceptionIfRequestPayloadIsTooLarge(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            $this->generateRequestPayload(range(0, BulkOperationCollectionParamConverter::BULK_OPERATIONS_LIMIT))
        );

        $this->assertApiStatusCode(Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        $this->assertApiErrorResponseMessage(
            sprintf(
                "Bulk operation limit has been exceeded, maximum of '%s' allowed per request.",
                BulkOperationCollectionParamConverter::BULK_OPERATIONS_LIMIT
            )
        );
    }

    public function testItThrowsBadRequestHttpExceptionIfRequestPayloadIsValidButEmpty(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            $this->generateRequestPayload([])
        );

        $this->assertApiStatusCode(Response::HTTP_BAD_REQUEST);
        $this->assertApiErrorResponseMessage('Empty request body received.');
    }

    public function testItDoesNotCreateNewAssignmentsWithInvalidUsersProvided(): void
    {
        $user = $this->userRepository->findByUsernameWithAssignments('user1');

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            $this->generateRequestPayload([
                $user->getUsername(),
                'nonExistingUser1',
            ])
        );

        $this->assertApiStatusCode(Response::HTTP_CREATED);
        $this->assertApiResponse(
            [
                'data' => [
                    'applied' => false,
                    'results' => [
                        (string)$user->getUsername() => true,
                        'nonExistingUser1' => false,
                    ],
                ],
            ]
        );

        self::assertCount(1, $this->getRepository(Assignment::class)->findAll());

        $this->assertHasLogRecordWithMessage(
            "Bulk assignments create error: User with username = 'nonExistingUser1' cannot be found.",
            Logger::ERROR
        );
    }

    public function testItCreatesNewAssignmentsWithValidUserProvided(): void
    {
        Carbon::setTestNow(Carbon::createFromDate(2019, 1, 1));

        $user = $this->userRepository->findByUsernameWithAssignments('user1');
        $lastAssignment = $user->getLastAssignment();

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            $this->generateRequestPayload([$user->getUsername()])
        );

        $this->assertApiStatusCode(Response::HTTP_CREATED);
        $this->assertApiResponse(
            [
                'data' => [
                    'applied' => true,
                    'results' => [
                        (string)$user->getUsername() => true,
                    ],
                ],
            ]
        );

        self::assertCount(2, $this->getRepository(Assignment::class)->findAll());

        $reloadedUser = $this->userRepository->findByUsernameWithAssignments('user1');

        self::assertSame(Assignment::STATE_READY, $reloadedUser->getLastAssignment()->getState());
        self::assertNotEquals($lastAssignment->getId(), $reloadedUser->getLastAssignment()->getId());
        self::assertCount(1, $reloadedUser->getAvailableAssignments());
    }

    public function testItLogsSuccessfulBulkOperations(): void
    {
        Carbon::setTestNow(Carbon::createFromDate(2019, 1, 1));

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            $this->generateRequestPayload(['user1'])
        );

        $this->assertHasLogRecordWithMessage("Successful assignment creation (username = 'user1').", Logger::INFO);
    }

    private function generateRequestPayload(array $users): string
    {
        $payload = [];

        foreach ($users as $user) {
            $payload[] = [
                'identifier' => $user,
            ];
        }

        return (string)json_encode($payload, JSON_THROW_ON_ERROR, 512);
    }
}
