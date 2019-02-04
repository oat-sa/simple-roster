<?php

namespace App\Lti;

use Symfony\Component\Serializer\SerializerInterface;

class LtiRequest
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var ParameterBagInterface
     */
    private $ltiParameterBag;

    /**
     * @var OauthParametersBag
     */
    private $oauthBag;

    /**
     * @var SerializerInterface
     */
    private $requestParametersSerializer;

    public function __construct(string $url, ParameterBagInterface $ltiParameterBag, SerializerInterface $requestParametersSerializer)
    {
        $this->url = $url;
        $this->ltiParameterBag = $ltiParameterBag;
        $this->requestParametersSerializer = $requestParametersSerializer;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getLtiParameterBag(): ParameterBagInterface
    {
        return $this->ltiParameterBag;
    }

    public function getOauthParameterBag(): OauthParametersBag
    {
        return $this->oauthBag;
    }

    public function setOauthParameterBag(OauthParametersBag $oauthBag): void
    {
        $this->oauthBag = $oauthBag;
    }
}
