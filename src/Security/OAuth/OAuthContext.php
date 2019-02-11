<?php declare(strict_types=1);

namespace App\Security\OAuth;

class OAuthContext
{
    public const VERSION_1_0 = '1.0';
    public const METHOD_MAC_SHA1 = 'HMAC-SHA1';

    /** @var string */
    private $bodyHash;

    /** @var string */
    private $consumerKey;

    /** @var string */
    private $nonce;

    /** @var string */
    private $signatureMethod;

    /** @var string */
    private $timestamp;

    /** @var string */
    private $version;

    public function __construct(
        string $bodyHash,
        string $consumerKey,
        string $nonce,
        string $signatureMethod,
        string $timestamp,
        string $version
    ) {
        $this->bodyHash = $bodyHash;
        $this->consumerKey = $consumerKey;
        $this->nonce = $nonce;
        $this->signatureMethod = $signatureMethod;
        $this->timestamp = $timestamp;
        $this->version = $version;
    }

    public function getBodyHash(): string
    {
        return $this->bodyHash;
    }

    public function getConsumerKey(): string
    {
        return $this->consumerKey;
    }

    public function getNonce(): string
    {
        return $this->nonce;
    }

    public function getSignatureMethod(): string
    {
        return $this->signatureMethod;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}
