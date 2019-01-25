<?php declare(strict_types=1);

namespace App\Model\OAuth;

// TODO: different name? Like SignatureCredentials?
class Signature
{
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

    /**
     * Signature constructor.
     *
     * @param string $bodyHash
     * @param string $consumerKey
     * @param string $nonce
     * @param string $signatureMethod
     * @param string $timestamp
     * @param string $version
     */
    public function __construct(string $bodyHash, string $consumerKey, string $nonce, string $signatureMethod, string $timestamp, string $version)
    {
        $this->bodyHash = $bodyHash;
        $this->consumerKey = $consumerKey;
        $this->nonce = $nonce;
        $this->signatureMethod = $signatureMethod;
        $this->timestamp = $timestamp;
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getBodyHash(): string
    {
        return $this->bodyHash;
    }

    /**
     * @return string
     */
    public function getConsumerKey(): string
    {
        return $this->consumerKey;
    }

    /**
     * @return string
     */
    public function getNonce(): string
    {
        return $this->nonce;
    }

    /**
     * @return string
     */
    public function getSignatureMethod(): string
    {
        return $this->signatureMethod;
    }

    /**
     * @return string
     */
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }
}
