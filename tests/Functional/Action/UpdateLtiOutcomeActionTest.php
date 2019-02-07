<?php

namespace App\Tests\Functional\Action;

use App\Entity\Assignment;
use App\Entity\Infrastructure;
use App\Model\OAuth\Signature;
use App\Repository\AssignmentRepository;
use App\Repository\InfrastructureRepository;
use App\Security\OAuth\SignatureGenerator;
use App\Tests\Traits\DatabaseFixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UpdateLtiOutcomeActionTest extends WebTestCase
{
    use DatabaseFixturesTrait;

    public function testItReturns401IfNotAuthenticated()
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/lti/outcome');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testItReturns401IfWrongAuthentication()
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

    public function testItReturns200IfTheAuthenticationWorksAndAssignmentExists()
    {
        $client = static::createClient();

        $infrastructure = $this->getInfrastructure();

        $time = time();
        $signature = $this->generateSignature($infrastructure, $time);

        $xmlBody = file_get_contents(__DIR__ . '/../../Resources/LtiOutcome/valid_replace_result_body.xml');

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

    public function testItReturns404IfTheAuthenticationWorksButTheAssignmentDoesNotExist()
    {
        $client = static::createClient();

        $infrastructure = $this->getInfrastructure();

        $time = time();
        $signature = $this->generateSignature($infrastructure, $time);

        $xmlBody = file_get_contents(__DIR__ . '/../../Resources/LtiOutcome/invalid_replace_result_body_wrong_assignment.xml');

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

    private function generateSignature(Infrastructure $infrastructure, $time): string
    {
        $signature = new Signature(
            'bodyHash',
            $infrastructure->getLtiKey(),
            'nonce',
            'HMAC-SHA1',
            $time,
            '1.0'
        );

        $signatureGenerator = new SignatureGenerator($signature, 'http://localhost/api/v1/lti/outcome', 'POST');

        return $signatureGenerator->getSignature($infrastructure->getLtiSecret());
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
