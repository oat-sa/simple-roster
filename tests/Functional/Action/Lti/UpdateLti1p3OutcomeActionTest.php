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
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\XmlTestingTrait;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidFactoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\UuidV6;

class UpdateLti1p3OutcomeActionTest extends WebTestCase
{
    use DatabaseTestingTrait;
    use SecurityTestingTrait;
    use XmlTestingTrait;

    /** @var KernelBrowser */
    private $kernelBrowser;

    /** @var RegistrationInterface */
    private $registration;

    /** @var AssignmentRepository */
    private $assignmentRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();
        $this->assignmentRepository = static::$container->get(AssignmentRepository::class);

        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithStartedAssignment.yml');

        /** @phpstan-ignore-next-line */
        $this->registration = static::$container
            ->get(RegistrationRepositoryInterface::class)
            ->find('testRegistration');
    }

    public function testItReturns401IfNotAuthenticated(): void
    {
        $this->kernelBrowser->request('POST', '/api/v1/lti1p3/outcome');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());
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
                'HTTP_AUTHORIZATION' => $authorization
            ],
            $this->getXmlRequestTemplate($assignmentId)
        );

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());
        self::assertSame(
            $this->getValidReplaceResultResponseXml($messageIdentifier, $assignmentId),
            $this->kernelBrowser->getResponse()->getContent()
        );
        self::assertSame(Assignment::STATUS_READY, $this->assignmentRepository->find($assignmentId)->getStatus());
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
                'HTTP_AUTHORIZATION' => $authorization
            ],
            $this->getXmlRequestTemplate(new UuidV6('00000001-0000-6000-0000-000000000000'))
        );

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());
        self::assertStringContainsString(
            'JWT access token scopes are invalid',
            (string)$this->kernelBrowser->getResponse()->getContent()
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

        $response = $this->kernelBrowser->getResponse();
        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertStringContainsString('A Token was not found in the TokenStorage', (string)$response->getContent());
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
                'HTTP_AUTHORIZATION' => 'Bearer invalid'
            ],
            $this->getXmlRequestTemplate(new UuidV6('00000001-0000-6000-0000-000000000000'))
        );

        $response = $this->kernelBrowser->getResponse();
        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertStringContainsString('The JWT string must have two dots', (string)$response->getContent());
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
                'HTTP_AUTHORIZATION' => $authorization
            ],
            'invalidXml'
        );

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->kernelBrowser->getResponse()->getStatusCode());
        self::assertSame(
            Assignment::STATUS_STARTED,
            $this->assignmentRepository->find(new UuidV6('00000001-0000-6000-0000-000000000000'))->getStatus()
        );
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
                'HTTP_AUTHORIZATION' => $authorization
            ],
            $this->getXmlRequestTemplate($nonExistingAssignmentId)
        );

        self::assertSame(Response::HTTP_NOT_FOUND, $this->kernelBrowser->getResponse()->getStatusCode());
        self::assertSame(
            Assignment::STATUS_STARTED,
            $this->assignmentRepository->find(new UuidV6('00000001-0000-6000-0000-000000000000'))->getStatus()
        );
    }
}
