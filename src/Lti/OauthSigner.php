<?php

namespace App\Lti;

class OauthSigner
{
    public function sign(LtiRequest $request, string $consumerKey, string $consumerSecret): void
    {
        $nonce = uniqid('', true);
        $timestamp = (new \DateTime('now'))->getTimestamp();
        $method = 'HMAC-SHA1';

        $oauthBag = new OauthParametersBag($consumerKey, $nonce, $method, $timestamp);

        $request->setOauthParameterBag($oauthBag);

        $requestParameters = $request->getAllParameters();

        $requestParametersKeys = array_keys($requestParameters);
        sort($requestParametersKeys);

        $sortedPairs = [];
        foreach ($requestParametersKeys as $key) {
            if ($requestParameters[$key] !== null) {
                $sortedPairs[] = $key . '=' . rawurlencode($requestParameters[$key]);
            }
        }

        $baseString = "POST&" . urlencode($request->getUrl()) . "&" . rawurlencode(implode("&", $sortedPairs));
        $secret = urlencode($consumerSecret) . "&";
        $signature = base64_encode(hash_hmac("sha1", $baseString, $secret, true));

        $oauthBag->addSignature($signature);
    }
}
