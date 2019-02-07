<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Assignment;
use App\Security\OAuth\OAuthContext;
use App\Security\OAuth\OAuthSigner;
use Symfony\Component\HttpFoundation\Request;

class GetUserAssignmentLtiLinkService
{
    /** @var OAuthSigner */
    private $signer;

    public function __construct(OAuthSigner $signer)
    {
        $this->signer = $signer;
    }

    public function getAssignmentLtiLink(Assignment $assignment): string
    {
        $time = time();

        $context = new OAuthContext(
            '',
            $assignment->getLineItem()->getInfrastructure()->getLtiKey(),
            uniqid(),
            OAuthSigner::METHOD_MAC_SHA1,
            (string)$time,
            '1.0'
        );

        $ltiParameters = [
            'lti_message_type' => 'basic-lti-launch-request',
            'lti_version' => 'LTI-1p0',
            'context_id' => $assignment->getId(),
            'context_label' => $assignment->getLineItem()->getSlug(),
            'context_title' => $assignment->getLineItem()->getLabel(),
            'context_type' => 'CourseSection',
            'roles' => 'Learner',
            'user_id' => $assignment->getUser()->getId(),
            'resource_link_id' => 1234,
        ];

        $signature = $this->signer->sign(
            $context,
            $assignment->getLineItem()->getInfrastructure()->getLtiDirectorLink(),
            Request::METHOD_GET,
            $assignment->getLineItem()->getInfrastructure()->getLtiSecret(),
            $ltiParameters
        );

        $url = http_build_query(array_merge(
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
        ));

        return $assignment->getLineItem()->getInfrastructure()->getLtiDirectorLink() . '?' . $url;
    }
}
