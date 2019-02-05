<?php declare(strict_types=1);

namespace App\Security\OAuth;

use App\Model\OAuth\Signature;
use InvalidArgumentException;

class SignatureGenerator
{
    /** @var Signature */
    private $signature;

    /** @var string */
    private $hostUrl;

    /** @var string */
    private $method;

    public function __construct(Signature $signature, string $hostUrl, string $method)
    {
        $this->signature = $signature;
        $this->hostUrl = $hostUrl;
        $this->method = $method;
    }

    public function getSignature(string $secret): string
    {
        switch ($this->signature->getSignatureMethod()) {
            case 'HMAC-SHA1':
                // TODO: proper comment why we need to attach this character
                $secret .= '&';

                return base64_encode(hash_hmac('sha1', $this->getBaseString(), $secret, true));

            default:
                throw new InvalidArgumentException(sprintf(
                    'Signature method is not supported: %s',
                    $this->signature->getSignatureMethod()
                ));
        }
    }

    private function getBaseString(): string
    {
        return implode('&', [
            urlencode($this->method),
            urlencode($this->hostUrl),
            urlencode($this->getParameters()),
        ]);
    }

    private function getParameters(): string
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

    private function encode(string $value): string
    {
        return urlencode(utf8_encode($value));
    }
}
