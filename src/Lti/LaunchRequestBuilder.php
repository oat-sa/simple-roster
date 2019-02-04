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

    public function build(User $user, LineItem $lineItem, Infrastructure $infrastructure): LtiRequest
    {
        $launchUrl = $infrastructure->getLtiDirectorLink() . base64_encode($lineItem->getTaoUri());

        $parameterBag = new LtiLaunchParametersBagV1($user->getUsername(), rand(1, 100000000));

        $request = new LtiRequest($launchUrl, $parameterBag, $this->requestParametersSerializer);

        $this->oauthSigner->sign($request, $infrastructure->getKey(), $infrastructure->getSecret());

        return $request;
    }
}
