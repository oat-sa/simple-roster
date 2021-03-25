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

use Monolog\Logger;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\LtiInstance;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use OAT\SimpleRoster\Security\OAuth\OAuthContext;
use OAT\SimpleRoster\Security\OAuth\OAuthSigner;
use OAT\SimpleRoster\Tests\Traits\ApiTestingTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use OAT\SimpleRoster\Tests\Traits\XmlTestingTrait;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\UuidV6;

class UpdateLti1p1OutcomeActionTest extends WebTestCase
{
    use ApiTestingTrait;
    use DatabaseTestingTrait;
    use XmlTestingTrait;
    use LoggerTestingTrait;

    /** @var LtiInstanceRepository */
    private $ltiInstanceRepository;

    /** @var AssignmentRepository */
    private $assignmentRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();
        $this->ltiInstanceRepository = self::$container->get(LtiInstanceRepository::class);
        $this->assignmentRepository = self::$container->get(AssignmentRepository::class);

        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        $this->setUpTestLogHandler('security');
    }

    public function testItReturns401IfNotAuthenticated(): void
    {
        $this->kernelBrowser->request('POST', '/api/v1/lti1p1/outcome');

        $this->assertApiStatusCode(Response::HTTP_UNAUTHORIZED);

        $this->assertHasLogRecordWithMessage(
            "Invalid OAuth consumer key received, LTI instance with LTI key = '' cannot be found.",
            Logger::ERROR
        );
    }

    public function testItReturns401IfWrongAuthentication(): void
    {
        $ltiInstance = $this->getLtiInstance();

        $queryParameters = http_build_query([
            'oauth_body_hash' => 'bodyHash',
            'oauth_consumer_key' => $ltiInstance->getLtiKey(),
            'oauth_nonce' => 'nonce',
            'oauth_signature' => 'signature',
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0',
        ]);

        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti1p1/outcome?' . $queryParameters,
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
            ]
        );

        $this->assertApiStatusCode(Response::HTTP_UNAUTHORIZED);
        $this->assertHasLogRecordWithMessage('Failed OAuth signature validation.', Logger::ERROR);
    }

    public function testItReturns200IfTheAuthenticationWorksAndAssignmentExists(): void
    {
        $ltiInstance = $this->getLtiInstance();

        $time = time();
        $signature = $this->generateSignature($ltiInstance, (string)$time);

        $uidGenerator = $this->createMock(UuidFactoryInterface::class);
        self::$container->set('test.uid_generator', $uidGenerator);

        $messageIdentifier = UuidV4::fromString('e36f227c-2946-11e8-b467-0ed5f89f718b');

        $uidGenerator
            ->method('uuid4')
            ->willReturn($messageIdentifier);

        $queryParameters = http_build_query([
            'oauth_body_hash' => 'bodyHash',
            'oauth_consumer_key' => $ltiInstance->getLtiKey(),
            'oauth_nonce' => 'nonce',
            'oauth_signature' => $signature,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $time,
            'oauth_version' => '1.0',
        ]);

        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti1p1/outcome?' . $queryParameters,
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
            ],
            $this->getXmlRequestTemplate(new UuidV6('00000001-0000-6000-0000-000000000000'))
        );

        $this->assertApiStatusCode(Response::HTTP_OK);

        self::assertSame(
            $this->getValidReplaceResultResponseXml(
                $messageIdentifier,
                new UuidV6('00000001-0000-6000-0000-000000000000')
            ),
            $this->kernelBrowser->getResponse()->getContent()
        );

        $assignment = $this->assignmentRepository->findById(new UuidV6('00000001-0000-6000-0000-000000000000'));
        self::assertSame(Assignment::STATE_READY, $assignment->getState());

        $this->assertHasLogRecordWithMessage('Successful OAuth signature validation.', Logger::INFO);
    }

    public function testItReturns400IfTheAuthenticationWorksButTheXmlIsInvalid(): void
    {
        $ltiInstance = $this->getLtiInstance();

        $time = time();
        $signature = $this->generateSignature($ltiInstance, (string)$time);

        $xmlBody = 'test';

        $queryParameters = http_build_query([
            'oauth_body_hash' => 'bodyHash',
            'oauth_consumer_key' => $ltiInstance->getLtiKey(),
            'oauth_nonce' => 'nonce',
            'oauth_signature' => $signature,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $time,
            'oauth_version' => '1.0',
        ]);

        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti1p1/outcome?' . $queryParameters,
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
            ],
            $xmlBody
        );

        $this->assertApiStatusCode(Response::HTTP_BAD_REQUEST);

        $assignment = $this->assignmentRepository->findById(new UuidV6('00000001-0000-6000-0000-000000000000'));
        self::assertSame(Assignment::STATE_READY, $assignment->getState());
    }

    public function testItReturns404IfTheAuthenticationWorksButTheAssignmentDoesNotExist(): void
    {
        $ltiInstance = $this->getLtiInstance();

        $time = time();
        $signature = $this->generateSignature($ltiInstance, (string)$time);

        $queryParameters = http_build_query([
            'oauth_body_hash' => 'bodyHash',
            'oauth_consumer_key' => $ltiInstance->getLtiKey(),
            'oauth_nonce' => 'nonce',
            'oauth_signature' => $signature,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $time,
            'oauth_version' => '1.0',
        ]);

        $nonExistingAssignmentId = new UuidV6('00000999-0000-6000-0000-000000000000');

        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti1p1/outcome?' . $queryParameters,
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
            ],
            $this->getXmlRequestTemplate($nonExistingAssignmentId)
        );

        $this->assertApiStatusCode(Response::HTTP_NOT_FOUND);

        $assignment = $this->assignmentRepository->findById(new UuidV6('00000001-0000-6000-0000-000000000000'));
        self::assertSame(Assignment::STATE_READY, $assignment->getState());
    }

    private function generateSignature(LtiInstance $ltiInstance, string $time): string
    {
        $context = new OAuthContext(
            'bodyHash',
            $ltiInstance->getLtiKey(),
            'nonce',
            'HMAC-SHA1',
            $time,
            '1.0'
        );

        $oauthSigner = new OAuthSigner();

        return $oauthSigner->sign(
            $context,
            'http://localhost/api/v1/lti1p1/outcome',
            'POST',
            $ltiInstance->getLtiSecret()
        );
    }

    private function getLtiInstance(): LtiInstance
    {
        $ltiInstance = $this->ltiInstanceRepository->find(new UuidV6('00000001-0000-6000-0000-000000000000'));

        self::assertInstanceOf(LtiInstance::class, $ltiInstance);

        return $ltiInstance;
    }
}
