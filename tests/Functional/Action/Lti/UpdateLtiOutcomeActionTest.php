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

namespace App\Tests\Functional\Action\Lti;

use App\Entity\Assignment;
use App\Repository\AssignmentRepository;
use App\Security\OAuth\OAuthContext;
use App\Security\OAuth\OAuthSigner;
use App\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UpdateLtiOutcomeActionTest extends WebTestCase
{
    use DatabaseTestingTrait;

    /** @var KernelBrowser */
    private $kernelBrowser;

    /** @var string */
    private $testLtiKey;

    /** @var string */
    private $testLtiSecret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();
        $this->testLtiKey = self::$container->getParameter('app.lti.key');
        $this->testLtiSecret = self::$container->getParameter('app.lti.secret');

        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');
    }

    public function testItReturns401IfNotAuthenticated(): void
    {
        $this->kernelBrowser->request('POST', '/api/v1/lti/outcome');

        self::assertEquals(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());
    }

    public function testItReturns401IfWrongAuthentication(): void
    {
        $queryParameters = http_build_query([
            'oauth_body_hash' => 'bodyHash',
            'oauth_consumer_key' => $this->testLtiKey,
            'oauth_nonce' => 'nonce',
            'oauth_signature' => 'signature',
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0',
        ]);

        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti/outcome?' . $queryParameters,
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
            ]
        );

        self::assertEquals(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());
    }

    public function testItReturns200IfTheAuthenticationWorksAndAssignmentExists(): void
    {
        $time = time();
        $signature = $this->generateSignature((string)$time);

        /** @var string $xmlBody * */
        $xmlBody = file_get_contents(__DIR__ . '/../../../Resources/LtiOutcome/valid_replace_result_body.xml');

        $queryParameters = http_build_query([
            'oauth_body_hash' => 'bodyHash',
            'oauth_consumer_key' => $this->testLtiKey,
            'oauth_nonce' => 'nonce',
            'oauth_signature' => $signature,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $time,
            'oauth_version' => '1.0',
        ]);

        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti/outcome?' . $queryParameters,
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
            ],
            $xmlBody
        );

        self::assertEquals(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        self::assertEquals(
            Assignment::STATE_READY,
            $this->getAssignment()->getState()
        );
    }

    public function testItReturns400IfTheAuthenticationWorksButTheXmlIsInvalid(): void
    {
        $time = time();
        $signature = $this->generateSignature((string)$time);

        $xmlBody = 'test';

        $queryParameters = http_build_query([
            'oauth_body_hash' => 'bodyHash',
            'oauth_consumer_key' => $this->testLtiKey,
            'oauth_nonce' => 'nonce',
            'oauth_signature' => $signature,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $time,
            'oauth_version' => '1.0',
        ]);

        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti/outcome?' . $queryParameters,
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
            ],
            $xmlBody
        );

        self::assertEquals(Response::HTTP_BAD_REQUEST, $this->kernelBrowser->getResponse()->getStatusCode());

        self::assertEquals(
            Assignment::STATE_READY,
            $this->getAssignment()->getState()
        );
    }

    public function testItReturns404IfTheAuthenticationWorksButTheAssignmentDoesNotExist(): void
    {
        $time = time();
        $signature = $this->generateSignature((string)$time);

        /** @var string $xmlBody */
        $xmlBody = file_get_contents(
            __DIR__ . '/../../../Resources/LtiOutcome/invalid_replace_result_body_wrong_assignment.xml'
        );

        $queryParameters = http_build_query([
            'oauth_body_hash' => 'bodyHash',
            'oauth_consumer_key' => $this->testLtiKey,
            'oauth_nonce' => 'nonce',
            'oauth_signature' => $signature,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $time,
            'oauth_version' => '1.0',
        ]);

        $this->kernelBrowser->request(
            'POST',
            '/api/v1/lti/outcome?' . $queryParameters,
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
            ],
            $xmlBody
        );

        self::assertEquals(Response::HTTP_NOT_FOUND, $this->kernelBrowser->getResponse()->getStatusCode());

        self::assertEquals(
            Assignment::STATE_READY,
            $this->getAssignment()->getState()
        );
    }

    private function generateSignature(string $time): string
    {
        $context = new OAuthContext(
            'bodyHash',
            $this->testLtiKey,
            'nonce',
            'HMAC-SHA1',
            $time,
            '1.0'
        );

        $oauthSigner = new OAuthSigner();

        return $oauthSigner->sign(
            $context,
            'http://localhost/api/v1/lti/outcome',
            'POST',
            $this->testLtiSecret
        );
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
