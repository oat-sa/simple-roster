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

namespace App\Tests\Functional\Action\Lti;

use App\Entity\Assignment;
use App\Entity\User;
use App\Generator\NonceGenerator;
use App\Lti\LoadBalancer\LtiInstanceLoadBalancerFactory;
use App\Lti\Request\LtiRequest;
use App\Repository\UserRepository;
use App\Security\OAuth\OAuthContext;
use App\Tests\Traits\DatabaseFixturesTrait;
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
    use DatabaseFixturesTrait;
    use UserAuthenticatorTrait;
    use LoggerTestingTrait;

    /** @var KernelBrowser */
    private $kernelBrowser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
        $this->setUpFixtures();

        $this->kernelBrowser = self::createClient();

        $this->setUpTestLogHandler();
    }

    public function testItReturns401IfNotAuthenticated(): void
    {
        $this->kernelBrowser->request('GET', '/api/v1/assignments/1/lti-link');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());
    }

    public function testItReturns405IfHttpMethodIsNotAllowed(): void
    {
        $this->kernelBrowser->request('POST', '/api/v1/assignments/1/lti-link');

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $this->kernelBrowser->getResponse()->getStatusCode());
    }

    public function testItReturns404IfAssignmentDoesNotBelongToAuthenticatedUser(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

        $this->logInAs($user, $this->kernelBrowser);

        $this->kernelBrowser->request('GET', '/api/v1/assignments/2/lti-link');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode($this->kernelBrowser->getResponse()->getContent(), true);
        $this->assertEquals("Assignment id '2' not found for user 'user1'.", $decodedResponse['error']['message']);
    }

    public function testItReturns409IfAssignmentDoesNotHaveASuitableState(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

        $user->getLastAssignment()->setState(Assignment::STATE_COMPLETED);
        $this->getEntityManager()->flush();

        $this->logInAs($user, $this->kernelBrowser);

        $this->kernelBrowser->request('GET', '/api/v1/assignments/1/lti-link');

        $this->assertEquals(Response::HTTP_CONFLICT, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode($this->kernelBrowser->getResponse()->getContent(), true);
        $this->assertEquals(
            "Assignment with id '1' does not have a suitable state.",
            $decodedResponse['error']['message']
        );
    }

    public function testItReturnsLtiLinkAndUpdatedAssignmentStateToStartedWithUsernameLoadBalancerStrategy(): void
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 0, 0, 0, new DateTimeZone('UTC')));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

        $this->logInAs($user, $this->kernelBrowser);

        $_ENV['LTI_INSTANCE_LOAD_BALANCING_STRATEGY'] = LtiInstanceLoadBalancerFactory::LOAD_BALANCING_STRATEGY_USERNAME;

        $this->kernelBrowser->request('GET', '/api/v1/assignments/1/lti-link');

        $this->assertEquals(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        $this->assertEquals(
            [
                'ltiLink' => 'http://lti-director.com/eyJkZWxpdmVyeSI6Imh0dHA6XC9cL2xpbmVpdGVtdXJpLmNvbSJ9',
                'ltiParams' => [
                    'oauth_body_hash' => '',
                    'oauth_consumer_key' => 'myKey',
                    'oauth_nonce' => (new NonceGenerator())->generate(),
                    'oauth_signature' => 'OaXyh9RqBX8maLsHb2ToCRGGC7c=',
                    'oauth_signature_method' => OAuthContext::METHOD_MAC_SHA1,
                    'oauth_timestamp' => Carbon::getTestNow()->getTimestamp(),
                    'oauth_version' => OAuthContext::VERSION_1_0,
                    'lti_message_type' => LtiRequest::LTI_MESSAGE_TYPE,
                    'lti_version' => LtiRequest::LTI_VERSION,
                    'context_id' => '1',
                    'roles' => LtiRequest::LTI_ROLE,
                    'user_id' => 1,
                    'lis_person_name_full' => 'user1',
                    'resource_link_id' => 1,
                    'lis_outcome_service_url' => 'http://localhost/api/v1/lti/outcome',
                    'lis_result_sourcedid' => 1,
                    'launch_presentation_return_url' => 'http://example.com/index.html',
                    'launch_presentation_locale' => 'en-EN',
                ],
            ],
            json_decode($this->kernelBrowser->getResponse()->getContent(), true)
        );

        /** @var Assignment $assignment */
        $assignment = $this->getRepository(Assignment::class)->find(1);
        $this->assertEquals(Assignment::STATE_STARTED, $assignment->getState());
    }

    public function testItReturnsLtiLinkAndUpdatedAssignmentStateToStartedWithUserGroupIdLoadBalancerStrategy(): void
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 0, 0, 0, new DateTimeZone('UTC')));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

        $this->logInAs($user, $this->kernelBrowser);

        $_ENV['LTI_INSTANCE_LOAD_BALANCING_STRATEGY']
            = LtiInstanceLoadBalancerFactory::LOAD_BALANCING_STRATEGY_USER_GROUP_ID;

        $this->kernelBrowser->request('GET', '/api/v1/assignments/1/lti-link');

        $this->assertEquals(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        $this->assertEquals(
            [
                'ltiLink' => 'http://lti-director.com/eyJkZWxpdmVyeSI6Imh0dHA6XC9cL2xpbmVpdGVtdXJpLmNvbSJ9',
                'ltiParams' => [
                    'oauth_body_hash' => '',
                    'oauth_consumer_key' => 'myKey',
                    'oauth_nonce' => (new NonceGenerator())->generate(),
                    'oauth_signature' => 'tPlvsHl9eZ5MFEHBqemqLsjAtNo=',
                    'oauth_signature_method' => OAuthContext::METHOD_MAC_SHA1,
                    'oauth_timestamp' => Carbon::getTestNow()->getTimestamp(),
                    'oauth_version' => OAuthContext::VERSION_1_0,
                    'lti_message_type' => LtiRequest::LTI_MESSAGE_TYPE,
                    'lti_version' => LtiRequest::LTI_VERSION,
                    'context_id' => 'group_1',
                    'roles' => LtiRequest::LTI_ROLE,
                    'user_id' => 1,
                    'lis_person_name_full' => 'user1',
                    'resource_link_id' => 1,
                    'lis_outcome_service_url' => 'http://localhost/api/v1/lti/outcome',
                    'lis_result_sourcedid' => 1,
                    'launch_presentation_return_url' => 'http://example.com/index.html',
                    'launch_presentation_locale' => 'en-EN',
                ],
            ],
            json_decode($this->kernelBrowser->getResponse()->getContent(), true)
        );

        /** @var Assignment $assignment */
        $assignment = $this->getRepository(Assignment::class)->find(1);
        $this->assertEquals(Assignment::STATE_STARTED, $assignment->getState());
    }

    public function testItReturnsLoadBalancedLtiLinkAndUpdatedAssignmentStateToStarted(): void
    {
        $initialLoadBalancerStatus = $_ENV['LTI_ENABLE_INSTANCES_LOAD_BALANCER'];
        $initialLtiLaunchPresentationLocale = $_ENV['LTI_LAUNCH_PRESENTATION_LOCALE'];

        $_ENV['LTI_ENABLE_INSTANCES_LOAD_BALANCER'] = true;
        $_ENV['LTI_LAUNCH_PRESENTATION_LOCALE'] = 'it-IT';

        $_ENV['LTI_INSTANCE_LOAD_BALANCING_STRATEGY'] = LtiInstanceLoadBalancerFactory::LOAD_BALANCING_STRATEGY_USERNAME;

        Carbon::setTestNow(Carbon::create(2019, 1, 1, 0, 0, 0, new DateTimeZone('UTC')));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

        $this->logInAs($user, $this->kernelBrowser);

        $this->kernelBrowser->request('GET', '/api/v1/assignments/1/lti-link');

        $_ENV['LTI_ENABLE_INSTANCES_LOAD_BALANCER'] = $initialLoadBalancerStatus;
        $_ENV['LTI_LAUNCH_PRESENTATION_LOCALE'] = $initialLtiLaunchPresentationLocale;

        $this->assertEquals(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        $this->assertEquals(
            [
                'ltiLink' => 'http://lb_infra_2/eyJkZWxpdmVyeSI6Imh0dHA6XC9cL2xpbmVpdGVtdXJpLmNvbSJ9',
                'ltiParams' => [
                    'oauth_body_hash' => '',
                    'oauth_consumer_key' => 'myKey',
                    'oauth_nonce' => (new NonceGenerator())->generate(),
                    'oauth_signature' => 'kfwT0UTPN2CZvmjvOJOPUSoJPp8=',
                    'oauth_signature_method' => OAuthContext::METHOD_MAC_SHA1,
                    'oauth_timestamp' => Carbon::getTestNow()->getTimestamp(),
                    'oauth_version' => OAuthContext::VERSION_1_0,
                    'lti_message_type' => LtiRequest::LTI_MESSAGE_TYPE,
                    'lti_version' => LtiRequest::LTI_VERSION,
                    'context_id' => '1',
                    'roles' => LtiRequest::LTI_ROLE,
                    'user_id' => 1,
                    'lis_person_name_full' => 'user1',
                    'resource_link_id' => 1,
                    'lis_outcome_service_url' => 'http://localhost/api/v1/lti/outcome',
                    'lis_result_sourcedid' => 1,
                    'launch_presentation_return_url' => 'http://example.com/index.html',
                    'launch_presentation_locale' => 'it-IT',
                ],
            ],
            json_decode($this->kernelBrowser->getResponse()->getContent(), true)
        );

        /** @var Assignment $assignment */
        $assignment = $this->getRepository(Assignment::class)->find(1);
        $this->assertEquals(Assignment::STATE_STARTED, $assignment->getState());
    }

    public function testItLogsSuccessfulLtiRequestCreation(): void
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 0, 0, 0, new DateTimeZone('UTC')));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

        /** @var Assignment $assignment */
        $assignment = $this->getRepository(Assignment::class)->find(1);

        $this->logInAs($user, $this->kernelBrowser);

        $this->kernelBrowser->request('GET', '/api/v1/assignments/1/lti-link');
        $ltiRequestInResponse = json_decode($this->kernelBrowser->getResponse()->getContent(), true);

        $this->assertSame($this->getLogRecords()[0]['context']['lineItem'], $assignment->getLineItem());
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
