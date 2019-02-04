<?php

namespace App\Lti;

use Symfony\Component\Serializer\SerializerInterface;

class OauthSigner
{
    /**
     * @var SerializerInterface
     */
    private $requestParametersSerializer;

    public function __construct(SerializerInterface $requestParametersSerializer)
    {
        $this->requestParametersSerializer = $requestParametersSerializer;
    }

    public function sign(LtiRequest $ltiRequest, string $consumerKey, string $consumerSecret): void
    {
        $nonce = uniqid('', true);
        $timestamp = (new \DateTime('now'))->getTimestamp();
        $method = 'HMAC-SHA1';

        $oauthBag = new OauthParametersBag($consumerKey, $nonce, $method, $timestamp);

        $ltiRequest->setOauthParameterBag($oauthBag);

        $ltiRequestParameters = array_merge(
            $this->requestParametersSerializer->normalize($ltiRequest->getLtiParameterBag()),
            $this->requestParametersSerializer->normalize($ltiRequest->getOauthParameterBag())
        );;

        $requestParametersKeys = array_keys($ltiRequestParameters);
        sort($requestParametersKeys);

        $sortedPairs = [];
        foreach ($requestParametersKeys as $key) {
            if ($ltiRequestParameters[$key] !== null) {
                $sortedPairs[] = $key . '=' . rawurlencode($ltiRequestParameters[$key]);
            }
        }

        $baseString = "POST&" . urlencode($ltiRequest->getUrl()) . "&" . rawurlencode(implode("&", $sortedPairs));
        $secret = urlencode($consumerSecret) . "&";
        $signature = base64_encode(hash_hmac("sha1", $baseString, $secret, true));

        $oauthBag->addSignature($signature);
    }
}
