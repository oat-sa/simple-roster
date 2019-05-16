<?php declare(strict_types=1);
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

namespace App\Tests\Functional\Action\Lti;

use App\Entity\Assignment;
use App\Entity\Infrastructure;
use App\Security\OAuth\OAuthContext;
use App\Repository\AssignmentRepository;
use App\Repository\InfrastructureRepository;
use App\Security\OAuth\OAuthSigner;
use App\Tests\Traits\DatabaseFixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UpdateLtiOutcomeActionTest extends WebTestCase
{
    use DatabaseFixturesTrait;

    public function testItReturns401IfNotAuthenticated(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/lti/outcome');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testItReturns401IfWrongAuthentication(): void
    {
        $client = static::createClient();

        $infrastructure = $this->getInfrastructure();

        $queryParameters = http_build_query([
            'oauth_body_hash' => 'bodyHash',
            'oauth_consumer_key' => $infrastructure->getLtiKey(),
            'oauth_nonce' => 'nonce',
            'oauth_signature' => 'signature',
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0',
        ]);

        $client->request(
            'POST',
            '/api/v1/lti/outcome? '. $queryParameters,
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
            ]
        );

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testItReturns200IfTheAuthenticationWorksAndAssignmentExists(): void
    {
        $client = static::createClient();

        $infrastructure = $this->getInfrastructure();

        $time = time();
        $signature = $this->generateSignature($infrastructure, (string)$time);

        /** @var string $xmlBody **/
        $xmlBody = file_get_contents(__DIR__ . '/../../../Resources/LtiOutcome/valid_replace_result_body.xml');

        $queryParameters = http_build_query([
            'oauth_body_hash' => 'bodyHash',
            'oauth_consumer_key' => $infrastructure->getLtiKey(),
            'oauth_nonce' => 'nonce',
            'oauth_signature' => $signature,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $time,
            'oauth_version' => '1.0',
        ]);

        $client->request(
            'POST',
            '/api/v1/lti/outcome? '. $queryParameters,
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
            ],
            $xmlBody
        );

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $this->assertEquals(
            Assignment::STATE_COMPLETED,
            $this->getAssignment()->getState()
        );
    }

    public function testItReturns400IfTheAuthenticationWorksButTheXmlIsInvalid(): void
    {
        $client = static::createClient();

        $infrastructure = $this->getInfrastructure();

        $time = time();
        $signature = $this->generateSignature($infrastructure, (string)$time);

        $xmlBody = 'test';

        $queryParameters = http_build_query([
            'oauth_body_hash' => 'bodyHash',
            'oauth_consumer_key' => $infrastructure->getLtiKey(),
            'oauth_nonce' => 'nonce',
            'oauth_signature' => $signature,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $time,
            'oauth_version' => '1.0',
        ]);

        $client->request(
            'POST',
            '/api/v1/lti/outcome? '. $queryParameters,
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
            ],
            $xmlBody
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());

        $this->assertEquals(
            Assignment::STATE_READY,
            $this->getAssignment()->getState()
        );
    }

    public function testItReturns404IfTheAuthenticationWorksButTheAssignmentDoesNotExist(): void
    {
        $client = static::createClient();

        $infrastructure = $this->getInfrastructure();

        $time = time();
        $signature = $this->generateSignature($infrastructure, (string)$time);

        /** @var string $xmlBody */
        $xmlBody = file_get_contents(
            __DIR__ . '/../../../Resources/LtiOutcome/invalid_replace_result_body_wrong_assignment.xml'
        );

        $queryParameters = http_build_query([
            'oauth_body_hash' => 'bodyHash',
            'oauth_consumer_key' => $infrastructure->getLtiKey(),
            'oauth_nonce' => 'nonce',
            'oauth_signature' => $signature,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $time,
            'oauth_version' => '1.0',
        ]);

        $client->request(
            'POST',
            '/api/v1/lti/outcome? '. $queryParameters,
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
            ],
            $xmlBody
        );

        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());

        $this->assertEquals(
            Assignment::STATE_READY,
            $this->getAssignment()->getState()
        );
    }

    private function generateSignature(Infrastructure $infrastructure, string $time): string
    {
        $context = new OAuthContext(
            'bodyHash',
            $infrastructure->getLtiKey(),
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
            $infrastructure->getLtiSecret()
        );
    }

    private function getInfrastructure(): Infrastructure
    {
        /** @var InfrastructureRepository $repository */
        $repository = $this->getRepository(Infrastructure::class);

        return $repository->find(1);
    }

    private function getAssignment(): Assignment
    {
        /** @var AssignmentRepository $repository */
        $repository = $this->getRepository(Assignment::class);

        return $repository->find(1);
    }
}
