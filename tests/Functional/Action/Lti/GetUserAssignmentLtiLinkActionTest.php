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

namespace App\Tests\Functional\Action\Lti;

use App\Entity\Assignment;
use App\Entity\User;
use App\Generator\NonceGenerator;
use App\Lti\LoadBalancer\LtiInstanceLoadBalancerFactory;
use App\Lti\Request\LtiRequest;
use App\Repository\UserRepository;
use App\Security\OAuth\OAuthContext;
use App\Tests\Traits\DatabaseTestingTrait;
use App\Tests\Traits\LoggerTestingTrait;
use App\Tests\Traits\UserAuthenticatorTrait;
use Carbon\Carbon;
use DateTimeZone;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GetUserAssignmentLtiLinkActionTest extends WebTestCase
{
    use DatabaseTestingTrait;
    use UserAuthenticatorTrait;
    use LoggerTestingTrait;

    /** @var KernelBrowser */
    private $kernelBrowser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();

        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        $this->setUpTestLogHandler();
    }

    public function testItReturns401IfNotAuthenticated(): void
    {
        $this->kernelBrowser->request('GET', '/api/v1/assignments/1/lti-link');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());
    }

    public function testItReturns405IfHttpMethodIsNotAllowed(): void
    {
        $this->kernelBrowser->request('POST', '/api/v1/assignments/1/lti-link');

        self::assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $this->kernelBrowser->getResponse()->getStatusCode());
    }

    public function testItReturns404IfAssignmentDoesNotBelongToAuthenticatedUser(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $this->logInAs($user, $this->kernelBrowser);

        $this->kernelBrowser->request('GET', '/api/v1/assignments/2/lti-link');

        self::assertSame(Response::HTTP_NOT_FOUND, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame("Assignment id '2' not found for user 'user1'.", $decodedResponse['error']['message']);
    }

    public function testItReturns409IfAssignmentDoesNotHaveASuitableState(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $user->getLastAssignment()->setState(Assignment::STATE_COMPLETED);
        $this->getEntityManager()->flush();

        $this->logInAs($user, $this->kernelBrowser);

        $this->kernelBrowser->request('GET', '/api/v1/assignments/1/lti-link');

        self::assertSame(Response::HTTP_CONFLICT, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame(
            "Assignment with id '1' does not have a suitable state.",
            $decodedResponse['error']['message']
        );
    }

    public function testItReturns409IfAssignmentIsNotAvailable(): void
    {
        Carbon::setTestNow(Carbon::createFromDate(2022, 1, 1));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $user->getLastAssignment()->setState(Assignment::STATE_COMPLETED);
        $this->getEntityManager()->flush();

        $this->logInAs($user, $this->kernelBrowser);

        $this->kernelBrowser->request('GET', '/api/v1/assignments/1/lti-link');

        self::assertSame(Response::HTTP_NOT_FOUND, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode($this->kernelBrowser->getResponse()->getContent(), true);
        self::assertSame(
            "Assignment id '1' not found for user 'user1'.",
            $decodedResponse['error']['message']
        );

        Carbon::setTestNow();
    }

    public function testItReturns409IfAssignmentHasReachedMaximumAttempts(): void
    {
        $this->loadFixtureByFilename('userWithAllAttemptsTaken.yml');

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('userWithAllAttemptsTaken');

        $this->logInAs($user, $this->kernelBrowser);

        $this->kernelBrowser->request('GET', '/api/v1/assignments/2/lti-link');

        self::assertSame(Response::HTTP_CONFLICT, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode($this->kernelBrowser->getResponse()->getContent(), true);
        self::assertEquals(
            "Assignment with id '2' has reached the maximum attempts.",
            $decodedResponse['error']['message']
        );
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function testItReturnsLtiLinkAndUpdatedAssignmentAndAttemptsCountWithUsernameLoadBalancerStrategy(): void
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 0, 0, 0, new DateTimeZone('UTC')));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $this->logInAs($user, $this->kernelBrowser);

        $_ENV['LTI_INSTANCE_LOAD_BALANCING_STRATEGY'] = LtiInstanceLoadBalancerFactory::STRATEGY_USERNAME;

        $this->kernelBrowser->request('GET', '/api/v1/assignments/1/lti-link');

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        self::assertSame(
            [
                'ltiLink' => 'http://lti-director.com/eyJkZWxpdmVyeSI6Imh0dHA6XC9cL2xpbmVpdGVtdXJpLmNvbSJ9',
                'ltiParams' => [
                    'oauth_body_hash' => '',
                    'oauth_consumer_key' => 'myKey',
                    'oauth_nonce' => (new NonceGenerator())->generate(),
                    'oauth_signature' => 'oxXzS9KaVG7kiu6SIqRXCYgZn7E=',
                    'oauth_signature_method' => OAuthContext::METHOD_MAC_SHA1,
                    'oauth_timestamp' => (string)Carbon::getTestNow()->getTimestamp(),
                    'oauth_version' => OAuthContext::VERSION_1_0,
                    'lti_message_type' => LtiRequest::LTI_MESSAGE_TYPE,
                    'lti_version' => LtiRequest::LTI_VERSION,
                    'context_id' => '1',
                    'roles' => LtiRequest::LTI_ROLE,
                    'user_id' => 'user1',
                    'lis_person_name_full' => 'user1',
                    'resource_link_id' => 1,
                    'lis_outcome_service_url' => 'http://localhost/api/v1/lti/outcome',
                    'lis_result_sourcedid' => 1,
                    'launch_presentation_return_url' => 'http://example.com/index.html',
                    'launch_presentation_locale' => 'en-EN',
                ],
            ],
            json_decode($this->kernelBrowser->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );

        /** @var Assignment $assignment */
        $assignment = $this->getRepository(Assignment::class)->find(1);
        self::assertSame(Assignment::STATE_STARTED, $assignment->getState());
        self::assertSame(2, $assignment->getAttemptsCount());
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function testItReturnsLtiLinkAndUpdatedAssignmentAndAttemptsCountWithUserGroupIdLoadBalancerStrategy(): void
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 0, 0, 0, new DateTimeZone('UTC')));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $this->logInAs($user, $this->kernelBrowser);

        $_ENV['LTI_INSTANCE_LOAD_BALANCING_STRATEGY'] = LtiInstanceLoadBalancerFactory::STRATEGY_USER_GROUP_ID;

        $this->kernelBrowser->request('GET', '/api/v1/assignments/1/lti-link');

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        self::assertSame(
            [
                'ltiLink' => 'http://lti-director.com/eyJkZWxpdmVyeSI6Imh0dHA6XC9cL2xpbmVpdGVtdXJpLmNvbSJ9',
                'ltiParams' => [
                    'oauth_body_hash' => '',
                    'oauth_consumer_key' => 'myKey',
                    'oauth_nonce' => (new NonceGenerator())->generate(),
                    'oauth_signature' => 'Wv2Cjd2fo3ZiznrdSNB5qrgd2OQ=',
                    'oauth_signature_method' => OAuthContext::METHOD_MAC_SHA1,
                    'oauth_timestamp' => (string)Carbon::getTestNow()->getTimestamp(),
                    'oauth_version' => OAuthContext::VERSION_1_0,
                    'lti_message_type' => LtiRequest::LTI_MESSAGE_TYPE,
                    'lti_version' => LtiRequest::LTI_VERSION,
                    'context_id' => 'group_1',
                    'roles' => LtiRequest::LTI_ROLE,
                    'user_id' => 'user1',
                    'lis_person_name_full' => 'user1',
                    'resource_link_id' => 1,
                    'lis_outcome_service_url' => 'http://localhost/api/v1/lti/outcome',
                    'lis_result_sourcedid' => 1,
                    'launch_presentation_return_url' => 'http://example.com/index.html',
                    'launch_presentation_locale' => 'en-EN',
                ],
            ],
            json_decode($this->kernelBrowser->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );

        /** @var Assignment $assignment */
        $assignment = $this->getRepository(Assignment::class)->find(1);
        self::assertSame(Assignment::STATE_STARTED, $assignment->getState());
        self::assertSame(2, $assignment->getAttemptsCount());
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function testItReturnsLoadBalancedLtiLinkAndUpdatedAssignmentStateToStartedAndIncrementsAttemptsCount(): void
    {
        $initialLoadBalancerStatus = $_ENV['LTI_ENABLE_INSTANCES_LOAD_BALANCER'];
        $initialLtiLaunchPresentationLocale = $_ENV['LTI_LAUNCH_PRESENTATION_LOCALE'];

        $_ENV['LTI_ENABLE_INSTANCES_LOAD_BALANCER'] = true;
        $_ENV['LTI_LAUNCH_PRESENTATION_LOCALE'] = 'it-IT';
        $_ENV['LTI_INSTANCE_LOAD_BALANCING_STRATEGY'] = LtiInstanceLoadBalancerFactory::STRATEGY_USERNAME;

        Carbon::setTestNow(Carbon::create(2019, 1, 1, 0, 0, 0, new DateTimeZone('UTC')));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $this->logInAs($user, $this->kernelBrowser);

        $this->kernelBrowser->request('GET', '/api/v1/assignments/1/lti-link');

        $_ENV['LTI_ENABLE_INSTANCES_LOAD_BALANCER'] = $initialLoadBalancerStatus;
        $_ENV['LTI_LAUNCH_PRESENTATION_LOCALE'] = $initialLtiLaunchPresentationLocale;

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        self::assertSame(
            [
                'ltiLink' => 'http://lb_infra_2/eyJkZWxpdmVyeSI6Imh0dHA6XC9cL2xpbmVpdGVtdXJpLmNvbSJ9',
                'ltiParams' => [
                    'oauth_body_hash' => '',
                    'oauth_consumer_key' => 'myKey',
                    'oauth_nonce' => (new NonceGenerator())->generate(),
                    'oauth_signature' => 'NDgJcOtOaGTc/t44toJNoYXdCd8=',
                    'oauth_signature_method' => OAuthContext::METHOD_MAC_SHA1,
                    'oauth_timestamp' => (string)Carbon::getTestNow()->getTimestamp(),
                    'oauth_version' => OAuthContext::VERSION_1_0,
                    'lti_message_type' => LtiRequest::LTI_MESSAGE_TYPE,
                    'lti_version' => LtiRequest::LTI_VERSION,
                    'context_id' => '1',
                    'roles' => LtiRequest::LTI_ROLE,
                    'user_id' => 'user1',
                    'lis_person_name_full' => 'user1',
                    'resource_link_id' => 1,
                    'lis_outcome_service_url' => 'http://localhost/api/v1/lti/outcome',
                    'lis_result_sourcedid' => 1,
                    'launch_presentation_return_url' => 'http://example.com/index.html',
                    'launch_presentation_locale' => 'it-IT',
                ],
            ],
            json_decode($this->kernelBrowser->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );

        /** @var Assignment $assignment */
        $assignment = $this->getRepository(Assignment::class)->find(1);
        self::assertSame(Assignment::STATE_STARTED, $assignment->getState());
        self::assertSame(2, $assignment->getAttemptsCount());
    }

    public function testItDoesNotUpdateStateAndAttemptsCountIfStateIsStarted(): void
    {
        $assignment = $this->getRepository(Assignment::class)->find(1);
        $assignment->setState(Assignment::STATE_STARTED);

        $this->getEntityManager()->flush();

        Carbon::setTestNow(Carbon::create(2019, 1, 1, 0, 0, 0, new DateTimeZone('UTC')));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $this->logInAs($user, $this->kernelBrowser);

        $this->kernelBrowser->request('GET', '/api/v1/assignments/1/lti-link');

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        /** @var Assignment $assignment */
        $assignment = $this->getRepository(Assignment::class)->find(1);
        self::assertSame(Assignment::STATE_STARTED, $assignment->getState());
        self::assertSame(1, $assignment->getAttemptsCount());
    }

    public function testItLogsSuccessfulLtiRequestCreation(): void
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 0, 0, 0, new DateTimeZone('UTC')));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        /** @var Assignment $assignment */
        $assignment = $this->getRepository(Assignment::class)->find(1);

        $this->logInAs($user, $this->kernelBrowser);

        $this->kernelBrowser->request('GET', '/api/v1/assignments/1/lti-link');
        $ltiRequestInResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame($this->getLogRecords()[0]['context']['lineItem'], $assignment->getLineItem());
        $this->assertHasRecordThatPasses(
            static function (array $record) use ($assignment, $ltiRequestInResponse) {
                /** @var LtiRequest $ltiRequest */
                $ltiRequest = $record['context']['ltiRequest'];

                return
                    $record['message'] === "LTI request was successfully generated for assignment with id='1'"
                    && $ltiRequest->jsonSerialize() === $ltiRequestInResponse
                    && $record['context']['lineItem'] === $assignment->getLineItem();
            },
            Logger::INFO
        );
    }
}
