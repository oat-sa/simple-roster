<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Assignment;
use App\Generator\NonceGenerator;
use App\Lti\Request\LtiRequest;
use App\Security\OAuth\OAuthContext;
use App\Security\OAuth\OAuthSigner;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class GetUserAssignmentLtiLinkService
{
    /** @var OAuthSigner */
    private $signer;

    /** @var NonceGenerator */
    private $generator;

    /** @var RouterInterface */
    private $router;

    public function __construct(OAuthSigner $signer, NonceGenerator $generator, RouterInterface $router)
    {
        $this->signer = $signer;
        $this->generator = $generator;
        $this->router = $router;
    }

    public function getAssignmentLtiRequest(Assignment $assignment): LtiRequest
    {
        $time = Carbon::now()->getTimestamp();

        $context = new OAuthContext(
            '',
            $assignment->getLineItem()->getInfrastructure()->getLtiKey(),
            $this->generator->generate(),
            OAuthContext::METHOD_MAC_SHA1,
            (string)$time,
            OAuthContext::VERSION_1_0
        );

        $ltiParameters = $this->getAssignmentLtiParameters($assignment);

        $signature = $this->signer->sign(
            $context,
            $assignment->getLineItem()->getInfrastructure()->getLtiDirectorLink(),
            Request::METHOD_POST,
            $assignment->getLineItem()->getInfrastructure()->getLtiSecret(),
            $ltiParameters
        );

        return new LtiRequest(
            $assignment->getLineItem()->getInfrastructure()->getLtiDirectorLink(),
            array_merge(
                [
                    'oauth_body_hash' => $context->getBodyHash(),
                    'oauth_consumer_key' => $context->getConsumerKey(),
                    'oauth_nonce' => $context->getNonce(),
                    'oauth_signature' => $signature,
                    'oauth_signature_method' => $context->getSignatureMethod(),
                    'oauth_timestamp' => $context->getTimestamp(),
                    'oauth_version' => $context->getVersion(),
                ],
                $ltiParameters
            )
        );
    }

    private function getAssignmentLtiParameters(Assignment $assignment): array
    {
        return [
            'lti_message_type' => LtiRequest::LTI_MESSAGE_TYPE,
            'lti_version' => LtiRequest::LTI_VERSION,
            'context_id' => $assignment->getId(),
            'context_label' => $assignment->getLineItem()->getSlug(),
            'context_title' => $assignment->getLineItem()->getLabel(),
            'context_type' => LtiRequest::LTI_CONTEXT_TYPE,
            'roles' => LtiRequest::LTI_ROLE,
            'user_id' => $assignment->getUser()->getUsername(),
            'resource_link_id' => 1234,
            'lis_outcome_service_url' => $this->router->generate('updateLtiOutcome', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'lis_result_sourcedid' => sprintf(
                'xxx:::%s:::%s:::%s',
                $assignment->getId(),
                $assignment->getUser()->getUsername(),
                $assignment->getId()
            )

        ];
    }
}
