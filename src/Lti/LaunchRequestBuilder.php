<?php

namespace App\Lti;

use App\Model\Infrastructure;
use App\Model\LineItem;
use App\Model\User;
use Symfony\Component\Serializer\SerializerInterface;

class LaunchRequestBuilder
{
    /**
     * @var OauthSigner
     */
    private $oauthSigner;

    /**
     * @var SerializerInterface
     */
    private $requestParametersSerializer;

    public function __construct(OauthSigner $oauthSigner)
    {
        $this->oauthSigner = $oauthSigner;
    }

    public function setSerializer(SerializerInterface $requestParametersSerializer)
    {
        $this->requestParametersSerializer = $requestParametersSerializer;
    }

    public function build(User $user, LineItem $lineItem, Infrastructure $infrastructure): array
    {
        $launchUrl = $infrastructure->getLtiDirectorLink() . base64_encode($lineItem->getTaoUri());

        $parameterBag = new LtiLaunchParametersBagV1($user->getUsername(), rand(1, 100000000));

        $ltiRequest = new LtiRequest($launchUrl, $parameterBag, $this->requestParametersSerializer);

        $this->oauthSigner->sign($ltiRequest, $infrastructure->getKey(), $infrastructure->getSecret());

        $ltiRequestParameters = array_merge(
            $this->requestParametersSerializer->serialize($ltiRequest->getLtiParameterBag(), 'plain'),
            $this->requestParametersSerializer->serialize($ltiRequest->getOauthParameterBag(), 'plain')
        );;
        $ltiRequestParameters['ltiLink'] = $ltiRequest->getUrl();

        return $ltiRequestParameters;
    }
}
