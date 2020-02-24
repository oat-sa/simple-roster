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

namespace App\Tests\Functional\Action\Bulk;

use App\Entity\Assignment;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Request\ParamConverter\BulkOperationCollectionParamConverter;
use App\Tests\Traits\DatabaseTestingTrait;
use App\Tests\Traits\LoggerTestingTrait;
use Carbon\Carbon;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BulkCreateUsersAssignmentsActionTest extends WebTestCase
{
    use DatabaseTestingTrait;
    use LoggerTestingTrait;

    /** @var KernelBrowser */
    private $kernelBrowser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient([], ['HTTP_AUTHORIZATION' => 'Bearer ' . $_ENV['APP_API_KEY']]);

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

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode($this->kernelBrowser->getResponse()->getContent(), true);
        $this->assertEquals('API key authentication failure.', $decodedResponse['error']['message']);
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

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode($this->kernelBrowser->getResponse()->getContent(), true);
        $this->assertEquals(
            'Invalid JSON request body received. Error: Syntax error',
            $decodedResponse['error']['message']
        );
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

        $this->assertEquals(Response::HTTP_REQUEST_ENTITY_TOO_LARGE, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode($this->kernelBrowser->getResponse()->getContent(), true);
        $this->assertEquals(
            sprintf(
                "Bulk operation limit has been exceeded, maximum of '%s' allowed per request.",
                BulkOperationCollectionParamConverter::BULK_OPERATIONS_LIMIT
            ),
            $decodedResponse['error']['message']
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

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode($this->kernelBrowser->getResponse()->getContent(), true);
        $this->assertEquals('Empty request body received.', $decodedResponse['error']['message']);
    }

    public function testItDoesNotCreateNewAssignmentsWithInvalidUsersProvided(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

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

        $this->assertEquals(Response::HTTP_CREATED, $this->kernelBrowser->getResponse()->getStatusCode());

        $this->assertEquals(
            [
                'data' => [
                    'applied' => false,
                    'results' => [
                        $user->getUsername() => true,
                        'nonExistingUser1' => false,
                    ],
                ],
            ],
            json_decode($this->kernelBrowser->getResponse()->getContent(), true)
        );

        $this->assertCount(1, $this->getRepository(Assignment::class)->findAll());

        $this->assertHasLogRecordWithMessage(
            "Bulk assignments create error: User with username = 'nonExistingUser1' cannot be found.",
            Logger::ERROR
        );
    }

    public function testItCreatesNewAssignmentsWithValidUserProvided(): void
    {
        Carbon::setTestNow(Carbon::createFromDate(2019, 1, 1));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');
        $lastAssignment = $user->getLastAssignment();

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            $this->generateRequestPayload([$user->getUsername()])
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->kernelBrowser->getResponse()->getStatusCode());

        $this->assertEquals(
            [
                'data' => [
                    'applied' => true,
                    'results' => [
                        $user->getUsername() => true,
                    ],
                ],
            ],
            json_decode($this->kernelBrowser->getResponse()->getContent(), true)
        );

        $this->assertCount(2, $this->getRepository(Assignment::class)->findAll());

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $reloadedUser = $userRepository->getByUsernameWithAssignments('user1');

        $this->assertEquals(Assignment::STATE_READY, $reloadedUser->getLastAssignment()->getState());
        $this->assertNotEquals($lastAssignment->getId(), $reloadedUser->getLastAssignment()->getId());
        $this->assertCount(1, $reloadedUser->getAvailableAssignments());
    }

    public function testItLogsSuccessfulBulkOperations(): void
    {
        Carbon::setTestNow(Carbon::createFromDate(2019, 1, 1));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            $this->generateRequestPayload([$user->getUsername()])
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

        return (string)json_encode($payload);
    }
}
