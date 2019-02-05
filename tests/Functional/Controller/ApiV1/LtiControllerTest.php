<?php

namespace App\Tests\Functional\Controller\ApiV1;

use App\Entity\Infrastructure;
use App\Entity\User;
use App\Model\OAuth\Signature;
use App\Repository\InfrastructureRepository;
use App\Security\OAuth\SignatureGenerator;
use App\Tests\Traits\DatabaseFixturesTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function testItWorks()
    {
        $client = static::createClient();

        $this->createInfrastructure('consumerKey', 'consumerSecret');

        $time = time();
        $signature = $this->generateSignature($time);

        $xmlBody = file_get_contents(__DIR__ . '/samples/valid_replace_result_body.xml');

        $client->request(
            'POST',
            '/api/v1/lti/outcome?oauth_body_hash=bodyHash&oauth_consumer_key=consumerKey&oauth_nonce=nonce&oauth_signature=' . $signature . '&oauth_signature_method=HMAC-SHA1&oauth_timestamp=' . $time . '&oauth_version=1.0',
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/xml',
            ],
            $xmlBody
        );

        var_dump($client->getResponse()->getContent());
        exit;

        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    private function generateSignature($time): string
    {
        $signature = new Signature(
            'bodyHash',
            'consumerKey',
            'nonce',
            'HMAC-SHA1',
            $time,
            '1.0'
        );

        $signatureGenerator = new SignatureGenerator($signature, 'http://localhost/api/v1/lti/outcome', 'POST');

        return $signatureGenerator->getSignature('consumerSecret');
    }

    private function createInfrastructure(string $ltiKey, string $ltiSecret)
    {
        $infrastructure = new Infrastructure();
        $infrastructure
            ->setLabel('label')
            ->setLtiDirectorLink('director')
            ->setLtiKey($ltiKey)
            ->setLtiSecret($ltiSecret);

        /** @var InfrastructureRepository $repository */
        $repository = $this->getRepository(Infrastructure::class);

        $repository->persist($infrastructure);
        $repository->flush();
    }
}
