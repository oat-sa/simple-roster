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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Integration\Lti\Builder;

use Lcobucci\JWT\Parser;
use OAT\Bundle\Lti1p3Bundle\Repository\RegistrationRepository;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\BasicOutcomeClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\LaunchPresentationClaim;
use OAT\Library\Lti1p3Core\Registration\Registration;
use OAT\SimpleRoster\DataTransferObject\LoginHintDto;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Lti\Builder\Lti1p3MessageBuilder;
use OAT\SimpleRoster\Lti\Request\LtiRequest;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class Lti1p3MessageBuilderTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    /** @var Lti1p3MessageBuilder */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->setUpDatabase();

        $this->subject = self::$container->get(Lti1p3MessageBuilder::class);
    }

    public function testItCanBuildLtiMessage(): void
    {
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        /** @var RegistrationRepository $registrationRepository */
        $registrationRepository = self::$container->get(RegistrationRepository::class);
        /** @var Registration $registration */
        $registration = $registrationRepository->find('demo');

        /** @var User $user */
        $user = $this->getRepository(User::class)->find(1);
        $assignment = $user->getLastAssignment();

        $this->subject
            ->withMessagePayloadClaim(new LaunchPresentationClaim())
            ->withMessagePayloadClaim(new ContextClaim('id'))
            ->withMessagePayloadClaim(new BasicOutcomeClaim('id', 'uri'));

        $ltiMessage = $this->subject->build($registration, $assignment);
        $ltiParameters = $ltiMessage->getParameters();

        self::assertSame('http://localhost:8888/lti1p3/oidc/initiation', $ltiMessage->getUrl());
        self::assertSame('https://simple-roster.localhost/platform', $ltiParameters['iss']);
        self::assertSame('user1::1', $ltiParameters['login_hint']);
        self::assertSame('http://localhost:8888/tool/launch', $ltiParameters['target_link_uri']);
        self::assertSame('1', $ltiParameters['lti_deployment_id']);
        self::assertSame('demo', $ltiParameters['client_id']);

        /** @var Parser $tokenParser */
        $tokenParser = self::$container->get('test.jwt_parser');
        $token = $tokenParser->parse($ltiParameters['lti_message_hint']);

        self::assertTrue($token->hasClaim('https://purl.imsglobal.org/spec/lti/claim/launch_presentation'));
        self::assertTrue($token->hasClaim('https://purl.imsglobal.org/spec/lti/claim/context'));
        self::assertTrue($token->hasClaim('https://purl.imsglobal.org/spec/lti-bo/claim/basicoutcome'));
        self::assertTrue($token->hasClaim('https://purl.imsglobal.org/spec/lti/claim/roles'));
        self::assertSame([LtiRequest::LTI_ROLE], $token->getClaim('https://purl.imsglobal.org/spec/lti/claim/roles'));
    }
}
