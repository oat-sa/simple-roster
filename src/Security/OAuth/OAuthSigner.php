<?php declare(strict_types=1);

namespace App\Security\OAuth;

use InvalidArgumentException;

class OAuthSigner
{
    public function sign(
        OAuthContext $context,
        string $url,
        string $method,
        string $secret,
        array $additionalParameters = []
    ): string {
        if ($context->getSignatureMethod() !== OAuthContext::METHOD_MAC_SHA1) {
            throw new InvalidArgumentException(
                sprintf("Signature method '%s' is not supported", $context->getSignatureMethod())
            );
        }

        // @see package-tao/vendor/imsglobal/lti/src/OAuth/OAuthSignatureMethod_HMAC_SHA1.php
        $secret .= '&';
        $baseString = implode('&', [
            urlencode($method),
            urlencode($url),
            urlencode($this->getParameters($context, $additionalParameters)),
        ]);

        return base64_encode(hash_hmac('sha1', $baseString, $secret, true));
    }

    private function getParameters(OAuthContext $context, array $additionalParameters = []): string
    {
        $encodedParameters = [];
        $parameters = array_merge(
            [
                'oauth_body_hash' => $context->getBodyHash(),
                'oauth_consumer_key' => $context->getConsumerKey(),
                'oauth_nonce' => $context->getNonce(),
                'oauth_signature_method' => $context->getSignatureMethod(),
                'oauth_timestamp' => $context->getTimestamp(),
                'oauth_version' => $context->getVersion(),
            ],
            $additionalParameters
        );

        ksort($parameters, SORT_STRING);

        foreach ($parameters as $name => $value) {
            $encodedParameters[] = $this->encode($name) . '=' . $this->encode($value);
        }

        return implode('&', $encodedParameters);
    }

    private function encode($value): string
    {
        return str_replace(['%7E', '+'], ['~', ' '], rawurlencode((string)$value));
    }
}
