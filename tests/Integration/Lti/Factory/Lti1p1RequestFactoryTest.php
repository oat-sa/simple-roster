<?php

/*
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
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Integration\Lti\Factory;

use Carbon\Carbon;
use OAT\SimpleRoster\Generator\NonceGenerator;
use OAT\SimpleRoster\Lti\Factory\Lti1p1RequestFactory;
use OAT\SimpleRoster\Lti\Request\LtiRequest;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class Lti1p1RequestFactoryTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    /** @var Lti1p1RequestFactory */
    private $subject;

    /** @var UserRepository */
    private $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        $this->subject = self::$container->get(Lti1p1RequestFactory::class);
        $this->userRepository = self::$container->get(UserRepository::class);
    }

    public function testItReturnsAssignmentLtiRequest(): void
    {
        Carbon::setTestNow(Carbon::create(2019));

        $assignment = $this->userRepository->findByUsernameWithAssignments('user1')->getLastAssignment();

        self::assertSame(
            [
                'ltiLink' => 'https://lti-instance.taocolud.org/ltiDeliveryProvider/DeliveryTool/launch/' .
                    'eyJkZWxpdmVyeSI6Imh0dHA6XC9cL2xpbmVpdGVtdXJpLmNvbSJ9',
                'ltiVersion' => LtiRequest::LTI_VERSION_1P1,
                'ltiParams' => [
                    'oauth_body_hash' => '',
                    'oauth_consumer_key' => 'testLtiKey',
                    'oauth_nonce' => (new NonceGenerator())->generate(),
                    'oauth_signature' => 'Z1Rbz02G/N4ZgxVcWKgolBsqHcs=',
                    'oauth_signature_method' => 'HMAC-SHA1',
                    'oauth_timestamp' => (string)Carbon::now()->timestamp,
                    'oauth_version' => '1.0',
                    'lti_message_type' => 'basic-lti-launch-request',
                    'lti_version' => 'LTI-1p0',
                    'context_id' => '00000001-0000-6000-0000-000000000000',
                    'roles' => 'Learner',
                    'user_id' => 'user1',
                    'lis_person_name_full' => 'user1',
                    'resource_link_id' => '00000001-0000-6000-0000-000000000000',
                    'lis_outcome_service_url' => 'http://localhost/api/v1/lti1p1/outcome',
                    'lis_result_sourcedid' => '00000001-0000-6000-0000-000000000000',
                    'launch_presentation_return_url' => 'http://example.com/index.html',
                    'launch_presentation_locale' => 'en-EN',
                ],
            ],
            $this->subject->create($assignment)->jsonSerialize()
        );

        Carbon::setTestNow();
    }
}
