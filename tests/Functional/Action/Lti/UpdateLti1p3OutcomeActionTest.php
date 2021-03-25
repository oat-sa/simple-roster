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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Functional\Action\Lti;

use OAT\Bundle\Lti1p3Bundle\Tests\Traits\SecurityTestingTrait;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use OAT\SimpleRoster\Tests\Traits\ApiTestingTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\XmlTestingTrait;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\UuidV6;

class UpdateLti1p3OutcomeActionTest extends WebTestCase
{
    use ApiTestingTrait;
    use DatabaseTestingTrait;
    use SecurityTestingTrait;
    use XmlTestingTrait;

    /** @var RegistrationInterface */
    private $registration;

    /** @var AssignmentRepository */
    private $assignmentRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();
        $this->assignmentRepository = self::$container->get(AssignmentRepository::class);

        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        /** @phpstan-ignore-next-line */
        $this->registration = self::$container
            ->get(RegistrationRepositoryInterface::class)
            ->find('testRegistration');
    }

    public function testItReturns401IfNotAuthenticated(): void
    {
        $this->kernelBrowser->request('POST', '/api/v1/lti1p3/outcome');

        $this->assertApiStatusCode(Response::HTTP_UNAUTHORIZED);
    }

    public function testItReturns200IfTheAuthenticationWorksAndAssignmentExists(): void
    {
        $accessToken = $this->createTestClientAccessToken(
            $this->registration,
            ['https://purl.imsglobal.org/spec/lti-bo/scope/basicoutcome']
        );
        $authorization = sprintf('Bearer %s', $accessToken);

        $uuidGenerator = $this->createMock(UuidFactoryInterface::class);
        self::$container->set('test.uid_generator', $uuidGenerator);

        $messageIdentifier = UuidV4::fromString('e36f227c-2946-11e8-b467-0ed5f89f718b');

        $uuidGenerator
            ->method('uuid4')
            ->willReturn($messageIdentifier);

        $assignmentId = new UuidV6('00000001-0000-6000-0000-000000000000');

        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti1p3/outcome',
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
                'HTTP_AUTHORIZATION' => $authorization,
            ],
            $this->getXmlRequestTemplate($assignmentId)
        );

        $this->assertApiStatusCode(Response::HTTP_OK);
        self::assertSame(
            $this->getValidReplaceResultResponseXml($messageIdentifier, $assignmentId),
            $this->kernelBrowser->getResponse()->getContent()
        );

        $assignment = $this->assignmentRepository->findById(new UuidV6('00000001-0000-6000-0000-000000000000'));
        self::assertSame(Assignment::STATE_READY, $assignment->getState());
    }

    public function testItReturns401IfWithInvalidScope(): void
    {
        $accessToken = $this->createTestClientAccessToken($this->registration, ['invalid']);
        $authorization = sprintf('Bearer %s', $accessToken);

        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti1p3/outcome',
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
                'HTTP_AUTHORIZATION' => $authorization,
            ],
            $this->getXmlRequestTemplate(new UuidV6('00000001-0000-6000-0000-000000000000'))
        );

        $this->assertApiStatusCode(Response::HTTP_UNAUTHORIZED);
        $this->assertApiErrorResponseMessage(
            'LTI service request authentication failed: JWT access token scopes are invalid'
        );
    }

    public function testItReturnsUnauthorizedResponseWithoutBearer(): void
    {
        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti1p3/outcome',
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
            ],
            $this->getXmlRequestTemplate(new UuidV6('00000001-0000-6000-0000-000000000000'))
        );

        $this->assertApiStatusCode(Response::HTTP_UNAUTHORIZED);
        $this->assertApiErrorResponseMessage('A Token was not found in the TokenStorage.');
    }

    public function testItReturnsUnauthorizedResponseWithInvalidToken(): void
    {
        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti1p3/outcome',
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
                'HTTP_AUTHORIZATION' => 'Bearer invalid',
            ],
            $this->getXmlRequestTemplate(new UuidV6('00000001-0000-6000-0000-000000000000'))
        );


        $this->assertApiStatusCode(Response::HTTP_UNAUTHORIZED);
        $this->assertApiErrorResponseMessage(
            'LTI service request authentication failed: The JWT string must have two dots'
        );
    }

    public function testItReturns400IfTheAuthenticationWorksButTheXmlIsInvalid(): void
    {
        $accessToken = $this->createTestClientAccessToken(
            $this->registration,
            ['https://purl.imsglobal.org/spec/lti-bo/scope/basicoutcome']
        );
        $authorization = sprintf('Bearer %s', $accessToken);

        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti1p3/outcome',
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
                'HTTP_AUTHORIZATION' => $authorization,
            ],
            'invalidXml'
        );

        $this->assertApiStatusCode(Response::HTTP_BAD_REQUEST);

        $assignment = $this->assignmentRepository->findById(new UuidV6('00000001-0000-6000-0000-000000000000'));
        self::assertSame(Assignment::STATE_READY, $assignment->getState());
    }

    public function testItReturns404IfTheAuthenticationWorksButTheAssignmentDoesNotExist(): void
    {
        $accessToken = $this->createTestClientAccessToken(
            $this->registration,
            ['https://purl.imsglobal.org/spec/lti-bo/scope/basicoutcome']
        );
        $authorization = sprintf('Bearer %s', $accessToken);
        $nonExistingAssignmentId = new UuidV6('00000999-0000-6000-0000-000000000000');

        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti1p3/outcome',
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
                'HTTP_AUTHORIZATION' => $authorization,
            ],
            $this->getXmlRequestTemplate($nonExistingAssignmentId)
        );

        $this->assertApiStatusCode(Response::HTTP_NOT_FOUND);

        $assignment = $this->assignmentRepository->findById(new UuidV6('00000001-0000-6000-0000-000000000000'));
        self::assertSame(Assignment::STATE_READY, $assignment->getState());
    }
}
