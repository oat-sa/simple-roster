<?php

namespace App\Tests\Functional\Controller\ApiV1;

use App\Entity\Assignment;
use App\Entity\Infrastructure;
use App\Model\OAuth\Signature;
use App\Repository\InfrastructureRepository;
use App\Security\OAuth\SignatureGenerator;
use App\Tests\Traits\DatabaseFixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LtiControllerTest extends WebTestCase
{
    use DatabaseFixturesTrait;

    public function testItReturns401IfNotAuthenticated()
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/lti/outcome');

        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    public function testItReturns200IfTheAuthenticationWorksAndAssignmentExists()
    {
        $client = static::createClient();

        $infrastructure = $this->getInfrastructure();

        $time = time();
        $signature = $this->generateSignature($infrastructure, $time);

        $xmlBody = file_get_contents(__DIR__ . '/samples/valid_replace_result_body.xml');

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

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals(
            Assignment::STATE_COMPLETED,
            $this->getRepository(Assignment::class)->find(1)->getState()
        );
    }

    public function testItReturns404IfTheAuthenticationWorksButTheAssignmentDoesNotExist()
    {
        $client = static::createClient();

        $infrastructure = $this->getInfrastructure();

        $time = time();
        $signature = $this->generateSignature($infrastructure, $time);

        $xmlBody = file_get_contents(__DIR__ . '/samples/invalid_replace_result_body_wrong_assignment.xml');

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

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $this->assertEquals(
            Assignment::STATE_READY,
            $this->getRepository(Assignment::class)->find(1)->getState()
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
}
