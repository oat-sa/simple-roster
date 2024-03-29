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
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use OAT\SimpleRoster\Security\OAuth\OAuthContext;
use OAT\SimpleRoster\Security\OAuth\OAuthSigner;
use OAT\SimpleRoster\Tests\Traits\AssignmentStatusTestingTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use OAT\SimpleRoster\Tests\Traits\XmlTestingTrait;
use Ramsey\Uuid\UuidFactoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UpdateLti1p1OutcomeActionTest extends WebTestCase
{
    use DatabaseTestingTrait;
    use XmlTestingTrait;
    use AssignmentStatusTestingTrait;
    use LoggerTestingTrait;

    private KernelBrowser $kernelBrowser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();
        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        $this->setUpTestLogHandler('security');
    }

    public function testItReturns401IfNotAuthenticated(): void
    {
        $this->kernelBrowser->request('POST', '/api/v1/lti1p1/outcome');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());

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

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());

        $this->assertHasLogRecordWithMessage('Failed OAuth signature validation.', Logger::ERROR);
    }

    public function testItReturns200IfTheAuthenticationWorksAndAssignmentExists(): void
    {
        $ltiInstance = $this->getLtiInstance();

        $time = time();
        $signature = $this->generateSignature($ltiInstance, (string)$time);

        $uidGenerator = $this->createMock(UuidFactoryInterface::class);
        self::getContainer()->set('test.uid_generator', $uidGenerator);

        $messageIdentifier = 'e36f227c-2946-11e8-b467-0ed5f89f718b';

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
            $this->getValidReplaceResultRequestXml()
        );

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());
        self::assertSame(
            $this->getValidReplaceResultResponseXml($messageIdentifier),
            $this->kernelBrowser->getResponse()->getContent()
        );

        $this->assertAssignmentStatus(Assignment::STATE_READY);

        $this->assertHasLogRecord([
            'message' => 'Successful OAuth signature validation.',
            'context' => [
                'ltiInstance' => $ltiInstance,
            ],
        ], Logger::INFO);
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

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->kernelBrowser->getResponse()->getStatusCode());
        $this->assertAssignmentStatus(Assignment::STATE_READY);
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

        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti1p1/outcome?' . $queryParameters,
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
            ],
            $this->getValidReplaceResultRequestXmlWithWrongAssignment()
        );

        self::assertSame(Response::HTTP_NOT_FOUND, $this->kernelBrowser->getResponse()->getStatusCode());
        $this->assertAssignmentStatus(Assignment::STATE_READY);
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
        /** @var LtiInstanceRepository $repository */
        $repository = $this->getRepository(LtiInstance::class);

        $ltiInstance = $repository->find(1);

        self::assertInstanceOf(LtiInstance::class, $ltiInstance);

        return $ltiInstance;
    }

    private function getValidReplaceResultResponseXml(string $messageIdentifier): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<imsx_POXEnvelopeResponse xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
    <imsx_POXHeader>
        <imsx_POXResponseHeaderInfo>
            <imsx_version>V1.0</imsx_version>
            <imsx_messageIdentifier>$messageIdentifier</imsx_messageIdentifier>
            <imsx_statusInfo>
                <imsx_codeMajor>success</imsx_codeMajor>
                <imsx_severity>status</imsx_severity>
                <imsx_description>Assignment with Id 1 was updated</imsx_description>
                <imsx_messageRefIdentifier>1</imsx_messageRefIdentifier>
                <imsx_operationRefIdentifier>replaceResult</imsx_operationRefIdentifier>
            </imsx_statusInfo>
        </imsx_POXResponseHeaderInfo>
    </imsx_POXHeader>
    <imsx_POXBody>
        <replaceResultResponse />
    </imsx_POXBody>
</imsx_POXEnvelopeResponse>

XML;
    }
}
