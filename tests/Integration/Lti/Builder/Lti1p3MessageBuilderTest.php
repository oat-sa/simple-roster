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
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Lti\Builder\Lti1p3MessageBuilder;
use OAT\SimpleRoster\Lti\Request\LtiRequest;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\UuidV6;

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

        $this->subject = self::getContainer()->get(Lti1p3MessageBuilder::class);
    }

    public function testItCanBuildLtiMessage(): void
    {
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        /** @var RegistrationRepository $registrationRepository */
        $registrationRepository = self::getContainer()->get(RegistrationRepository::class);
        /** @var Registration $registration */
        $registration = $registrationRepository->find('testRegistration');

        /** @var User $user */
        $user = $this->getRepository(User::class)->find(new UuidV6('00000001-0000-6000-0000-000000000000'));
        $assignment = $user->getLastAssignment();

        $this->subject
            ->withMessagePayloadClaim(new LaunchPresentationClaim())
            ->withMessagePayloadClaim(new ContextClaim('id'))
            ->withMessagePayloadClaim(new BasicOutcomeClaim('id', 'uri'));

        $ltiMessage = $this->subject->build($registration, $assignment);
        $ltiParameters = $ltiMessage->getParameters();

        self::assertSame('http://localhost/lti1p3/oidc/initiation', $ltiMessage->getUrl());
        self::assertSame('https://localhost/platform', $ltiParameters->get('iss'));
        self::assertSame('user1::00000001-0000-6000-0000-000000000000', $ltiParameters->get('login_hint'));
        self::assertSame('http://localhost/tool/launch', $ltiParameters->get('target_link_uri'));
        self::assertSame('1', $ltiParameters->get('lti_deployment_id'));
        self::assertSame('test', $ltiParameters->get('client_id'));

        /** @var Parser $tokenParser */
        $tokenParser = self::getContainer()->get('test.jwt_parser');
        $token = $tokenParser->parse($ltiParameters->get('lti_message_hint'));

        self::assertTrue($token->hasClaim('https://purl.imsglobal.org/spec/lti/claim/launch_presentation'));
        self::assertTrue($token->hasClaim('https://purl.imsglobal.org/spec/lti/claim/context'));
        self::assertTrue($token->hasClaim('https://purl.imsglobal.org/spec/lti-bo/claim/basicoutcome'));
        self::assertTrue($token->hasClaim('https://purl.imsglobal.org/spec/lti/claim/roles'));
        self::assertSame([LtiRequest::LTI_ROLE], $token->getClaim('https://purl.imsglobal.org/spec/lti/claim/roles'));
    }
}
