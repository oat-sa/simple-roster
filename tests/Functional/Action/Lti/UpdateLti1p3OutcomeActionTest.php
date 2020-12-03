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

use OAT\Bundle\Lti1p3Bundle\Tests\Traits\SecurityTestingTrait;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactoryInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UpdateLti1p3OutcomeActionTest extends WebTestCase
{
    use DatabaseTestingTrait;
    use SecurityTestingTrait;

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
        $credentials = $this->createTestClientAccessToken(
            $this->registration,
            ['https://purl.imsglobal.org/spec/lti-bo/scope/basicoutcome']
        );

        $uidGenerator = $this->createMock(UuidFactoryInterface::class);
        self::$container->set('test.uid_generator', $uidGenerator);

        $uidGenerator
            ->method('uuid4')
            ->willReturn('e36f227c-2946-11e8-b467-0ed5f89f718b');

        /** @var string $xmlRequestBody */
        $xmlRequestBody = file_get_contents(
            __DIR__ . '/../../../Resources/LtiOutcome/valid_replace_result_body.xml'
        );

        /** @var string $xmlResponseBody */
        $xmlResponseBody = file_get_contents(
            __DIR__ . '/../../../Resources/LtiOutcome/valid_replace_result_response_body.xml'
        );

        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti1p3/outcome',
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $credentials)
            ],
            $xmlRequestBody
        );

        self::assertEquals(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());
        self::assertEquals($xmlResponseBody, $this->kernelBrowser->getResponse()->getContent());
        self::assertEquals(Assignment::STATE_READY, $this->getAssignment()->getState());
    }

    public function testItReturns401IfWithInvalidScope(): void
    {
        $credentials = $this->createTestClientAccessToken(
            $this->registration,
            ['invalid']
        );

        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti1p3/outcome',
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $credentials)
            ],
            ''
        );

        self::assertEquals(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());
        $this->assertStringContainsString(
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
            ''
        );

        $response = $this->kernelBrowser->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertStringContainsString(
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
            ''
        );

        $response = $this->kernelBrowser->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertStringContainsString('The JWT string must have two dots', (string)$response->getContent());
    }

    public function testItReturns400IfTheAuthenticationWorksButTheXmlIsInvalid(): void
    {
        $credentials = $this->createTestClientAccessToken(
            $this->registration,
            ['https://purl.imsglobal.org/spec/lti-bo/scope/basicoutcome']
        );

        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti1p3/outcome',
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $credentials)
            ],
            'invalidXml'
        );

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->kernelBrowser->getResponse()->getStatusCode());
        self::assertEquals(Assignment::STATE_READY, $this->getAssignment()->getState());
    }

    public function testItReturns404IfTheAuthenticationWorksButTheAssignmentDoesNotExist(): void
    {
        $credentials = $this->createTestClientAccessToken(
            $this->registration,
            ['https://purl.imsglobal.org/spec/lti-bo/scope/basicoutcome']
        );

        /** @var string $xmlBody */
        $xmlBody = file_get_contents(
            __DIR__ . '/../../../Resources/LtiOutcome/invalid_replace_result_body_wrong_assignment.xml'
        );

        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti1p3/outcome',
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $credentials)
            ],
            $xmlBody
        );

        self::assertEquals(Response::HTTP_NOT_FOUND, $this->kernelBrowser->getResponse()->getStatusCode());
        self::assertEquals(Assignment::STATE_READY, $this->getAssignment()->getState());
    }

    private function getAssignment(): Assignment
    {
        /** @var AssignmentRepository $repository */
        $repository = $this->getRepository(Assignment::class);

        $assignment = $repository->find(1);

        self::assertInstanceOf(Assignment::class, $assignment);

        return $assignment;
    }
}
