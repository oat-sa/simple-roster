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
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Request\ParamConverter\BulkOperationCollectionParamConverter;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BulkUpdateUsersAssignmentsStateActionTest extends WebTestCase
{
    use DatabaseTestingTrait;
    use LoggerTestingTrait;

    /** @var KernelBrowser */
    private $kernelBrowser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient([], ['HTTP_AUTHORIZATION' => 'Bearer testApiKey']);
        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        $this->setUpTestLogHandler();
    }

    public function testItThrowsUnauthorizedHttpExceptionIfRequestApiKeyIsInvalid(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_PATCH,
            '/api/v1/bulk/assignments',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer invalid'],
            '{}'
        );

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame('API key authentication failure.', $decodedResponse['error']['message']);
    }

    public function testItThrowsBadRequestHttpExceptionIfInvalidRequestBodyReceived(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_PATCH,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            'invalid body content'
        );

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame(
            'Invalid JSON request body received. Error: Syntax error',
            $decodedResponse['error']['message']
        );
    }

    public function testItThrowsBadRequestHttpExceptionIfEmptyRequestBodyReceived(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_PATCH,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            json_encode([], JSON_THROW_ON_ERROR, 512)
        );

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame('Empty request body received.', $decodedResponse['error']['message']);
    }

    public function testItThrowsRequestEntityTooLargeHttpExceptionIfRequestPayloadIsTooLarge(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_PATCH,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            $this->generateRequestPayload(range(0, BulkOperationCollectionParamConverter::BULK_OPERATIONS_LIMIT))
        );

        self::assertSame(
            Response::HTTP_REQUEST_ENTITY_TOO_LARGE,
            $this->kernelBrowser->getResponse()->getStatusCode()
        );

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame(
            sprintf(
                "Bulk operation limit has been exceeded, maximum of '%s' allowed per request.",
                BulkOperationCollectionParamConverter::BULK_OPERATIONS_LIMIT
            ),
            $decodedResponse['error']['message']
        );
    }

    public function testItDoesNotUpdateAssignmentsStateWithInvalidUsersProvided(): void
    {
        Carbon::setTestNow(Carbon::createFromDate(2019, 1, 1));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $this->kernelBrowser->request(
            Request::METHOD_PATCH,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            $this->generateRequestPayload([
                $user->getUsername(),
                'nonExistingUser1',
            ])
        );

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        self::assertSame(
            [
                'data' => [
                    'applied' => false,
                    'results' => [
                        (string)$user->getUsername() => true,
                        'nonExistingUser1' => false,
                    ],
                ],
            ],
            json_decode($this->kernelBrowser->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );

        self::assertCount(1, $this->getRepository(Assignment::class)->findAll());

        $this->getEntityManager()->refresh($user->getLastAssignment());
        self::assertSame(Assignment::STATUS_READY, $user->getLastAssignment()->getStatus());
        self::assertCount(1, $user->getAvailableAssignments());

        $this->assertHasLogRecordWithMessage(
            "Bulk assignments cancellation error: User with username = 'nonExistingUser1' cannot be found.",
            Logger::ERROR
        );
    }

    public function testItUpdatesAssignmentStateWithValidUserProvided(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $this->kernelBrowser->request(
            Request::METHOD_PATCH,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            $this->generateRequestPayload([$user->getUsername()])
        );

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        self::assertSame(
            [
                'data' => [
                    'applied' => true,
                    'results' => [
                        (string)$user->getUsername() => true,
                    ],
                ],
            ],
            json_decode($this->kernelBrowser->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );

        self::assertCount(1, $this->getRepository(Assignment::class)->findAll());

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $reloadedUser = $userRepository->findByUsernameWithAssignments('user1');

        self::assertSame(Assignment::STATUS_CANCELLED, $reloadedUser->getLastAssignment()->getStatus());
        self::assertCount(0, $reloadedUser->getAvailableAssignments());
    }

    public function testItLogsSuccessfulBulkOperations(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $this->kernelBrowser->request(
            Request::METHOD_PATCH,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            $this->generateRequestPayload([$user->getUsername()])
        );

        $this->assertHasLogRecordWithMessage(
            "Successful assignment cancellation (assignmentId = '1', username = 'user1').",
            Logger::INFO
        );
    }

    private function generateRequestPayload(array $users): string
    {
        $payload = [];

        foreach ($users as $user) {
            $payload[] = [
                'identifier' => $user,
                'attributes' => [
                    'state' => Assignment::STATUS_CANCELLED,
                ],
            ];
        }

        return (string)json_encode($payload, JSON_THROW_ON_ERROR, 512);
    }
}
