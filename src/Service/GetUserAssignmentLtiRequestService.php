<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Assignment;
use App\Exception\AssignmentNotProcessableException;
use App\Generator\NonceGenerator;
use App\Lti\LoadBalancer\LtiInstanceLoadBalancer;
use App\Lti\Request\LtiRequest;
use App\Security\OAuth\OAuthContext;
use App\Security\OAuth\OAuthSigner;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class GetUserAssignmentLtiRequestService
{
    /** @var OAuthSigner */
    private $signer;

    /** @var NonceGenerator */
    private $generator;

    /** @var RouterInterface */
    private $router;

    /** @var LtiInstanceLoadBalancer */
    private $loadBalancer;

    /** @var string */
    private $ltiLaunchPresentationReturnUrl;

    /** @var bool */
    private $ltiInstancesLoadBalancerEnabled;

    public function __construct(
        OAuthSigner $signer,
        NonceGenerator $generator,
        RouterInterface $router,
        LtiInstanceLoadBalancer $loadBalancer,
        string $ltiLaunchPresentationReturnUrl,
        bool $ltiInstancesLoadBalancerEnabled
    ) {
        $this->signer = $signer;
        $this->generator = $generator;
        $this->router = $router;
        $this->loadBalancer = $loadBalancer;
        $this->ltiLaunchPresentationReturnUrl = $ltiLaunchPresentationReturnUrl;
        $this->ltiInstancesLoadBalancerEnabled = $ltiInstancesLoadBalancerEnabled;
    }

    /**
     * @throws AssignmentNotProcessableException
     */
    public function getAssignmentLtiRequest(Assignment $assignment): LtiRequest
    {
        $this->checkIfAssignmentCanBeProcessed($assignment);

        $context = new OAuthContext(
            '',
            $assignment->getLineItem()->getInfrastructure()->getLtiKey(),
            $this->generator->generate(),
            OAuthContext::METHOD_MAC_SHA1,
            (string)Carbon::now()->getTimestamp(),
            OAuthContext::VERSION_1_0
        );

        $ltiLink = $this->getAssignmentLtiLink($assignment);
        $ltiParameters = $this->getAssignmentLtiParameters($assignment);

        $signature = $this->signer->sign(
            $context,
            $ltiLink,
            Request::METHOD_POST,
            $assignment->getLineItem()->getInfrastructure()->getLtiSecret(),
            $ltiParameters
        );

        return new LtiRequest(
            $ltiLink,
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

    /**
     * @throws AssignmentNotProcessableException
     */
    private function checkIfAssignmentCanBeProcessed(Assignment $assignment): void
    {
        if (!in_array($assignment->getState(), [Assignment::STATE_READY, Assignment::STATE_STARTED])) {
            throw new AssignmentNotProcessableException(
                sprintf("Assignment with id '%s' does not have a suitable state.", $assignment->getId())
            );
        }
    }

    private function getAssignmentLtiLink(Assignment $assignment): string
    {
        $link = $this->ltiInstancesLoadBalancerEnabled
            ? $this->loadBalancer->getLoadBalancedLtiInstanceUrl($assignment->getUser()->getUsername())
            : $assignment->getLineItem()->getInfrastructure()->getLtiDirectorLink();

        return sprintf(
            '%s/%s',
            $link,
            base64_encode(json_encode(['delivery' => $assignment->getLineItem()->getUri()]))
        );
    }

    private function getAssignmentLtiParameters(Assignment $assignment): array
    {
        return [
            'lti_message_type' => LtiRequest::LTI_MESSAGE_TYPE,
            'lti_version' => LtiRequest::LTI_VERSION,
            'context_id' => $assignment->getLineItem()->getId(),
            'context_label' => $assignment->getLineItem()->getSlug(),
            'context_title' => $assignment->getLineItem()->getLabel(),
            'context_type' => LtiRequest::LTI_CONTEXT_TYPE,
            'roles' => LtiRequest::LTI_ROLE,
            'user_id' => $assignment->getUser()->getId(),
            'lis_person_name_full' => $assignment->getUser()->getUsername(),
            'resource_link_id' => $assignment->getId(),
            'lis_outcome_service_url' => $this->router->generate('updateLtiOutcome', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'lis_result_sourcedid' => $assignment->getId(),
            'launch_presentation_return_url' => $this->ltiLaunchPresentationReturnUrl
        ];
    }
}
