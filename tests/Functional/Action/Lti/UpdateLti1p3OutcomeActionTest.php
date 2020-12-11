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
use OAT\SimpleRoster\Tests\Traits\AssignmentStatusTestingTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\XmlTestingTrait;
use Ramsey\Uuid\UuidFactoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UpdateLti1p3OutcomeActionTest extends WebTestCase
{
    use DatabaseTestingTrait;
    use SecurityTestingTrait;
    use XmlTestingTrait;
    use AssignmentStatusTestingTrait;

    /** @var KernelBrowser */
    private $kernelBrowser;

    /** @var RegistrationInterface */
    private $registration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();
        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        /** @phpstan-ignore-next-line */
        $this->registration = static::$container
            ->get(RegistrationRepositoryInterface::class)
            ->find('testRegistration');
    }

    public function testItReturns401IfNotAuthenticated(): void
    {
        $this->kernelBrowser->request('POST', '/api/v1/lti1p3/outcome');

        self::assertEquals(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());
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

        $messageIdentifier = 'e36f227c-2946-11e8-b467-0ed5f89f718b';

        $uuidGenerator
            ->method('uuid4')
            ->willReturn($messageIdentifier);

        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti1p3/outcome',
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
                'HTTP_AUTHORIZATION' => $authorization
            ],
            $this->getValidReplaceResultRequestXml()
        );

        self::assertEquals(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());
        self::assertEquals(
            $this->getValidReplaceResultResponseXml($messageIdentifier),
            $this->kernelBrowser->getResponse()->getContent()
        );
        $this->assertAssignmentStatus(Assignment::STATE_READY);
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
            $this->getValidReplaceResultRequestXml()
        );

        self::assertEquals(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());
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
            $this->getValidReplaceResultRequestXml()
        );

        $response = $this->kernelBrowser->getResponse();
        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertStringContainsString(
            'A Token was not found in the TokenStorage',
            (string)$response->getContent()
        );
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
            $this->getValidReplaceResultRequestXml()
        );

        $response = $this->kernelBrowser->getResponse();
        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
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

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->kernelBrowser->getResponse()->getStatusCode());
        $this->assertAssignmentStatus(Assignment::STATE_READY);
    }

    public function testItReturns404IfTheAuthenticationWorksButTheAssignmentDoesNotExist(): void
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
            $this->getValidReplaceResultRequestXmlWithWrongAssignment()
        );

        self::assertEquals(Response::HTTP_NOT_FOUND, $this->kernelBrowser->getResponse()->getStatusCode());
        $this->assertAssignmentStatus(Assignment::STATE_READY);
    }
}
