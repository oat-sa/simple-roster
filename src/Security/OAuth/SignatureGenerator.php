<?php declare(strict_types=1);

namespace App\Security\OAuth;

use App\Model\OAuth\Signature;

class SignatureGenerator
{
    /** @var Signature */
    private $signature;

    /** @var string */
    private $hostUrl;

    /** @var string */
    private $method;

    public function __construct(Signature $signature, $hostUrl, $method)
    {
        $this->signature = $signature;
        $this->hostUrl = $hostUrl;
        $this->method = $method;
    }

    /**
     * @param string $secret
     * @return string
     * @throws \OAuthException
     */
    public function getSignature(string $secret)
    {
        switch ($this->signature->getSignatureMethod()) {
            case OAUTH_SIG_METHOD_HMACSHA1:
                // TODO: proper comment why we need to attach this character
                $secret .= '&';

                return base64_encode(hash_hmac('sha1', $this->getBaseString(), $secret, true));

            default:
                throw new \OAuthException(sprintf(
                    'Signature method is not supported: %s',
                    $this->signature->getSignatureMethod()
                ));
        }
    }

    /**
     * @return string
     */
    private function getBaseString()
    {
        return implode('&', [
            urlencode($this->method),
            urlencode($this->hostUrl),
            urlencode($this->getParameters()),
        ]);
    }

    /**
     * @return string
     */
    private function getParameters()
    {
        $encodedParameters = [];
        $parameters = [
            'oauth_body_hash' => $this->signature->getBodyHash(),
            'oauth_consumer_key' => $this->signature->getConsumerKey(),
            'oauth_nonce' => $this->signature->getNonce(),
            'oauth_signature_method' => $this->signature->getSignatureMethod(),
            'oauth_timestamp' => $this->signature->getTimestamp(),
            'oauth_version' => $this->signature->getVersion(),
        ];

        ksort($parameters, SORT_STRING);

        foreach ($parameters as $name => $value) {
            $encodedParameters[] = $this->encode($name) . '=' . $this->encode($value);
        }
        return implode('&', $encodedParameters);
    }

    /**
     * @param $value
     * @return string
     */
    private function encode($value)
    {
        return urlencode(utf8_encode($value));
    }
}