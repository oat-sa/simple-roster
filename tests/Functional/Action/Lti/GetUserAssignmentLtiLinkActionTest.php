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

namespace OAT\SimpleRoster\Tests\Functional\Action\Lti;

use Carbon\Carbon;
use Monolog\Logger;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Generator\NonceGenerator;
use OAT\SimpleRoster\Lti\LoadBalancer\LtiInstanceLoadBalancerFactory;
use OAT\SimpleRoster\Lti\Request\LtiRequest;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Security\OAuth\OAuthContext;
use OAT\SimpleRoster\Tests\Traits\ApiTestingTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\UuidV6;

class GetUserAssignmentLtiLinkActionTest extends WebTestCase
{
    use ApiTestingTrait;
    use DatabaseTestingTrait;
    use LoggerTestingTrait;

    /** @var UserRepository */
    private $userRepository;

    /** @var AssignmentRepository */
    private $assignmentRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();
        $this->userRepository = self::$container->get(UserRepository::class);
        $this->assignmentRepository = self::$container->get(AssignmentRepository::class);

        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        $this->setUpTestLogHandler();
    }

    public function testItReturns401IfNotAuthenticated(): void
    {
        $this->kernelBrowser->request('GET', '/api/v1/assignments/00000001-0000-6000-0000-000000000000/lti-link');

        $this->assertApiStatusCode(Response::HTTP_UNAUTHORIZED);
    }

    public function testItReturns405IfHttpMethodIsNotAllowed(): void
    {
        $this->kernelBrowser->request('POST', '/api/v1/assignments/00000001-0000-6000-0000-000000000000/lti-link');

        $this->assertApiStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testItReturns404IfAssignmentDoesNotBelongToAuthenticatedUser(): void
    {
        $user = $this->userRepository->findByUsernameWithAssignments('user1');

        $this->kernelBrowser->request(
            Request::METHOD_GET,
            '/api/v1/assignments/00000002-0000-6000-0000-000000000000/lti-link',
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->authenticateAs($user)->getAccessToken()]
        );

        $this->assertApiStatusCode(Response::HTTP_NOT_FOUND);
        $this->assertApiErrorResponseMessage(
            "Assignment id '00000002-0000-6000-0000-000000000000' not found for user 'user1'."
        );
    }

    public function testItReturns409IfAssignmentDoesNotHaveASuitableState(): void
    {
        Carbon::setTestNow();

        $user = $this->userRepository->findByUsernameWithAssignments('user1');

        $user->getLastAssignment()->setState(Assignment::STATE_COMPLETED);
        $this->getEntityManager()->flush();

        $this->kernelBrowser->request(
            Request::METHOD_GET,
            '/api/v1/assignments/00000001-0000-6000-0000-000000000000/lti-link',
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->authenticateAs($user)->getAccessToken()]
        );

        $this->assertApiStatusCode(Response::HTTP_CONFLICT);
        $this->assertApiErrorResponseMessage(
            "Assignment with id '00000001-0000-6000-0000-000000000000' does not have a suitable state."
        );
    }

    public function testItReturns409IfAssignmentIsNotAvailable(): void
    {
        Carbon::setTestNow(Carbon::create(2100));

        $user = $this->userRepository->findByUsernameWithAssignments('user1');

        $this->kernelBrowser->request(
            Request::METHOD_GET,
            '/api/v1/assignments/00000001-0000-6000-0000-000000000000/lti-link',
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->authenticateAs($user)->getAccessToken()]
        );

        $this->assertApiStatusCode(Response::HTTP_CONFLICT);
        $this->assertApiErrorResponseMessage(
            "Assignment with id '00000001-0000-6000-0000-000000000000' for user 'user1' is unavailable."
        );

        Carbon::setTestNow();
    }

    public function testItReturns409IfLineItemIsNotAvailable(): void
    {
        Carbon::setTestNow(Carbon::create(2019));

        $this->loadFixtureByFilename('userWithUnavailableAssignment.yml');

        $user = $this->userRepository->findByUsernameWithAssignments('username');

        $this->kernelBrowser->request(
            Request::METHOD_GET,
            '/api/v1/assignments/00000002-0000-6000-0000-000000000000/lti-link',
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->authenticateAs($user)->getAccessToken()]
        );

        $this->assertApiStatusCode(Response::HTTP_CONFLICT);
        $this->assertApiErrorResponseMessage(
            "Assignment with id '00000002-0000-6000-0000-000000000000' for user 'username' is unavailable."
        );

        Carbon::setTestNow();
    }

    public function testItReturns409IfAssignmentHasReachedMaximumAttempts(): void
    {
        $this->loadFixtureByFilename('userWithAllAttemptsTaken.yml');

        $user = $this->userRepository->findByUsernameWithAssignments('userWithAllAttemptsTaken');

        $this->kernelBrowser->request(
            Request::METHOD_GET,
            '/api/v1/assignments/00000003-0000-6000-0000-000000000000/lti-link',
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->authenticateAs($user)->getAccessToken()]
        );

        $this->assertApiStatusCode(Response::HTTP_CONFLICT);
        $this->assertApiErrorResponseMessage(
            "Assignment with id '00000003-0000-6000-0000-000000000000' has reached the maximum attempts."
        );
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function testItReturnsLtiLinkAndUpdatedAssignmentAndAttemptsCountWithUsernameLoadBalancerStrategy(): void
    {
        Carbon::setTestNow(Carbon::create(2019));

        $user = $this->userRepository->findByUsernameWithAssignments('user1');

        $_ENV['LTI_VERSION'] = LtiRequest::LTI_VERSION_1P1;
        $_ENV['LTI_INSTANCE_LOAD_BALANCING_STRATEGY'] = LtiInstanceLoadBalancerFactory::STRATEGY_USERNAME;

        $this->kernelBrowser->request(
            Request::METHOD_GET,
            '/api/v1/assignments/00000001-0000-6000-0000-000000000000/lti-link',
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->authenticateAs($user)->getAccessToken()]
        );

        $this->assertApiStatusCode(Response::HTTP_OK);
        $this->assertApiResponse(
            [
                'ltiLink' => 'https://lti-instance.taocolud.org/ltiDeliveryProvider/DeliveryTool/launch/' .
                    'eyJkZWxpdmVyeSI6Imh0dHA6XC9cL2xpbmVpdGVtdXJpLmNvbSJ9',
                'ltiVersion' => LtiRequest::LTI_VERSION_1P1,
                'ltiParams' => [
                    'oauth_body_hash' => '',
                    'oauth_consumer_key' => 'testLtiKey',
                    'oauth_nonce' => (new NonceGenerator())->generate(),
                    'oauth_signature' => 'Z1Rbz02G/N4ZgxVcWKgolBsqHcs=',
                    'oauth_signature_method' => OAuthContext::METHOD_MAC_SHA1,
                    'oauth_timestamp' => (string)Carbon::getTestNow()->getTimestamp(),
                    'oauth_version' => OAuthContext::VERSION_1_0,
                    'lti_message_type' => LtiRequest::LTI_MESSAGE_TYPE,
                    'lti_version' => 'LTI-1p0',
                    'context_id' => '00000001-0000-6000-0000-000000000000',
                    'roles' => LtiRequest::LTI_ROLE,
                    'user_id' => 'user1',
                    'lis_person_name_full' => 'user1',
                    'resource_link_id' => '00000001-0000-6000-0000-000000000000',
                    'lis_outcome_service_url' => 'http://localhost/api/v1/lti1p1/outcome',
                    'lis_result_sourcedid' => '00000001-0000-6000-0000-000000000000',
                    'launch_presentation_return_url' => 'http://example.com/index.html',
                    'launch_presentation_locale' => 'en-EN',
                ],
            ]
        );

        $assignment = $this->assignmentRepository->findById(new UuidV6('00000001-0000-6000-0000-000000000000'));

        self::assertSame(Assignment::STATE_STARTED, $assignment->getState());
        self::assertSame(2, $assignment->getAttemptsCount());

        Carbon::setTestNow();
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function testItReturnsLtiLinkAndUpdatedAssignmentAndAttemptsCountWithUserGroupIdLoadBalancerStrategy(): void
    {
        Carbon::setTestNow(Carbon::create(2019));

        $user = $this->userRepository->findByUsernameWithAssignments('user1');

        $_ENV['LTI_VERSION'] = LtiRequest::LTI_VERSION_1P1;
        $_ENV['LTI_INSTANCE_LOAD_BALANCING_STRATEGY'] = LtiInstanceLoadBalancerFactory::STRATEGY_USER_GROUP_ID;

        $this->kernelBrowser->request(
            Request::METHOD_GET,
            '/api/v1/assignments/00000001-0000-6000-0000-000000000000/lti-link',
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->authenticateAs($user)->getAccessToken()]
        );

        $this->assertApiStatusCode(Response::HTTP_OK);
        $this->assertApiResponse(
            [
                'ltiLink' => 'https://lti-instance.taocolud.org/ltiDeliveryProvider/DeliveryTool/launch/' .
                    'eyJkZWxpdmVyeSI6Imh0dHA6XC9cL2xpbmVpdGVtdXJpLmNvbSJ9',
                'ltiVersion' => LtiRequest::LTI_VERSION_1P1,
                'ltiParams' => [
                    'oauth_body_hash' => '',
                    'oauth_consumer_key' => 'testLtiKey',
                    'oauth_nonce' => (new NonceGenerator())->generate(),
                    'oauth_signature' => 'ZZDZ43z3rKcvJNlEx2R43/NfDJU=',
                    'oauth_signature_method' => OAuthContext::METHOD_MAC_SHA1,
                    'oauth_timestamp' => (string)Carbon::getTestNow()->getTimestamp(),
                    'oauth_version' => OAuthContext::VERSION_1_0,
                    'lti_message_type' => LtiRequest::LTI_MESSAGE_TYPE,
                    'lti_version' => 'LTI-1p0',
                    'context_id' => 'group_1',
                    'roles' => LtiRequest::LTI_ROLE,
                    'user_id' => 'user1',
                    'lis_person_name_full' => 'user1',
                    'resource_link_id' => '00000001-0000-6000-0000-000000000000',
                    'lis_outcome_service_url' => 'http://localhost/api/v1/lti1p1/outcome',
                    'lis_result_sourcedid' => '00000001-0000-6000-0000-000000000000',
                    'launch_presentation_return_url' => 'http://example.com/index.html',
                    'launch_presentation_locale' => 'en-EN',
                ],
            ]
        );

        $assignment = $this->assignmentRepository->findById(new UuidV6('00000001-0000-6000-0000-000000000000'));

        self::assertSame(Assignment::STATE_STARTED, $assignment->getState());
        self::assertSame(2, $assignment->getAttemptsCount());

        Carbon::setTestNow();
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function testItReturnsLoadBalancedLtiLinkAndUpdatedAssignmentStateToStartedAndIncrementsAttemptsCount(): void
    {
        $initialLtiLaunchPresentationLocale = $_ENV['LTI_LAUNCH_PRESENTATION_LOCALE'];

        $_ENV['LTI_VERSION'] = LtiRequest::LTI_VERSION_1P1;
        $_ENV['LTI_LAUNCH_PRESENTATION_LOCALE'] = 'it-IT';
        $_ENV['LTI_INSTANCE_LOAD_BALANCING_STRATEGY'] = LtiInstanceLoadBalancerFactory::STRATEGY_USERNAME;

        Carbon::setTestNow(Carbon::create(2019));

        $user = $this->userRepository->findByUsernameWithAssignments('user1');

        $this->kernelBrowser->request(
            Request::METHOD_GET,
            '/api/v1/assignments/00000001-0000-6000-0000-000000000000/lti-link',
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->authenticateAs($user)->getAccessToken()]
        );

        $_ENV['LTI_LAUNCH_PRESENTATION_LOCALE'] = $initialLtiLaunchPresentationLocale;

        $this->assertApiStatusCode(Response::HTTP_OK);
        $this->assertApiResponse(
            [
                'ltiLink' => 'https://lti-instance.taocolud.org/ltiDeliveryProvider/DeliveryTool/launch/' .
                    'eyJkZWxpdmVyeSI6Imh0dHA6XC9cL2xpbmVpdGVtdXJpLmNvbSJ9',
                'ltiVersion' => LtiRequest::LTI_VERSION_1P1,
                'ltiParams' => [
                    'oauth_body_hash' => '',
                    'oauth_consumer_key' => 'testLtiKey',
                    'oauth_nonce' => (new NonceGenerator())->generate(),
                    'oauth_signature' => 'bhQAw63bQbmIc/eNxof2cB5taJI=',
                    'oauth_signature_method' => OAuthContext::METHOD_MAC_SHA1,
                    'oauth_timestamp' => (string)Carbon::getTestNow()->getTimestamp(),
                    'oauth_version' => OAuthContext::VERSION_1_0,
                    'lti_message_type' => LtiRequest::LTI_MESSAGE_TYPE,
                    'lti_version' => 'LTI-1p0',
                    'context_id' => '00000001-0000-6000-0000-000000000000',
                    'roles' => LtiRequest::LTI_ROLE,
                    'user_id' => 'user1',
                    'lis_person_name_full' => 'user1',
                    'resource_link_id' => '00000001-0000-6000-0000-000000000000',
                    'lis_outcome_service_url' => 'http://localhost/api/v1/lti1p1/outcome',
                    'lis_result_sourcedid' => '00000001-0000-6000-0000-000000000000',
                    'launch_presentation_return_url' => 'http://example.com/index.html',
                    'launch_presentation_locale' => 'it-IT',
                ],
            ]
        );

        $assignment = $this->assignmentRepository->findById(new UuidV6('00000001-0000-6000-0000-000000000000'));

        self::assertSame(Assignment::STATE_STARTED, $assignment->getState());
        self::assertSame(2, $assignment->getAttemptsCount());
    }

    public function testItDoesNotUpdateStateAndAttemptsCountIfStateIsStarted(): void
    {
        $assignment = $this->assignmentRepository->findById(new UuidV6('00000001-0000-6000-0000-000000000000'));
        $assignment->setState(Assignment::STATE_STARTED);

        $this->getEntityManager()->flush();

        Carbon::setTestNow(Carbon::create(2019));

        $user = $this->userRepository->findByUsernameWithAssignments('user1');

        $this->kernelBrowser->request(
            Request::METHOD_GET,
            '/api/v1/assignments/00000001-0000-6000-0000-000000000000/lti-link',
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->authenticateAs($user)->getAccessToken()]
        );

        $this->assertApiStatusCode(Response::HTTP_OK);

        $assignment = $this->assignmentRepository->findById(new UuidV6('00000001-0000-6000-0000-000000000000'));

        self::assertSame(Assignment::STATE_STARTED, $assignment->getState());
        self::assertSame(1, $assignment->getAttemptsCount());

        Carbon::setTestNow();
    }

    public function testItLogsSuccessfulLtiRequestCreation(): void
    {
        Carbon::setTestNow(Carbon::create(2019));

        $user = $this->userRepository->findByUsernameWithAssignments('user1');
        $assignment = $this->assignmentRepository->findById(new UuidV6('00000001-0000-6000-0000-000000000000'));

        $this->kernelBrowser->insulate(true);
        $authenticationResponse = $this->authenticateAs($user);
        $this->kernelBrowser->insulate(false);
        //This trick with insulation switchOn/switchOff above is needed to have
        //log test handler not missing between requests

        $this->kernelBrowser->request(
            Request::METHOD_GET,
            '/api/v1/assignments/00000001-0000-6000-0000-000000000000/lti-link',
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $authenticationResponse->getAccessToken()]
        );

        $ltiRequestInResponse = $this->getDecodedJsonApiResponse();

        self::assertSame($this->getLogRecords()[0]['context']['lineItem'], $assignment->getLineItem());

        $this->assertHasRecordThatPasses(
            static function (array $record) use ($assignment, $ltiRequestInResponse) {
                /** @var LtiRequest $ltiRequest */
                $ltiRequest = $record['context']['ltiRequest'];

                return
                    $record['message'] === "LTI request was successfully generated for assignment with " .
                    "id='00000001-0000-6000-0000-000000000000'"
                    && $ltiRequest->jsonSerialize() === $ltiRequestInResponse
                    && $record['context']['lineItem'] === $assignment->getLineItem();
            },
            Logger::INFO
        );

        Carbon::setTestNow();
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function testItReturnsLti1p3Link(): void
    {
        Carbon::setTestNow(Carbon::create(2019));

        $user = $this->userRepository->findByUsernameWithAssignments('user1');

        $_ENV['LTI_VERSION'] = LtiRequest::LTI_VERSION_1P3;

        $this->kernelBrowser->request(
            Request::METHOD_GET,
            '/api/v1/assignments/00000001-0000-6000-0000-000000000000/lti-link',
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->authenticateAs($user)->getAccessToken()]
        );

        $this->assertApiStatusCode(Response::HTTP_OK);

        $response = $this->getDecodedJsonApiResponse();
        $decodedLtiLink = urldecode($response['ltiLink']);

        self::assertStringContainsString('iss=https://localhost/platform', $decodedLtiLink);
        self::assertStringContainsString('login_hint=user1', $decodedLtiLink);
        self::assertStringContainsString('target_link_uri=http://localhost/tool/launch', $decodedLtiLink);
        self::assertStringContainsString('lti_deployment_id=1', $decodedLtiLink);
        self::assertStringContainsString('client_id=test', $decodedLtiLink);

        self::assertSame(LtiRequest::LTI_VERSION_1P3, $response['ltiVersion']);
        self::assertSame([], $response['ltiParams']);

        $assignment = $this->assignmentRepository->findById(new UuidV6('00000001-0000-6000-0000-000000000000'));

        self::assertSame(Assignment::STATE_STARTED, $assignment->getState());
        self::assertSame(2, $assignment->getAttemptsCount());

        Carbon::setTestNow();
    }
}
