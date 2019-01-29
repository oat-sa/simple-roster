<?php

namespace App\Lti;

class OauthParametersBag implements ParameterBagInterface
{
    /**
     * @var string
     */
    private $oauthConsumerKey;

    /**
     * @var string
     */
    private $oauthVersion = '1.0';

    /**
     * @var string
     */
    private $oauthNonce;

    /**
     * @var int
     */
    private $oauthTimestamp;

    /**
     * @var string
     */
    private $oauthSignatureMethod;

    /**
     * @var string
     */
    private $oauthSignature;

    public function __construct(string $consumerKey, string $oauthNonce, string $oauthSignatureMethod, int $oauthTimestamp)
    {
        $this->oauthConsumerKey = $consumerKey;
        $this->oauthNonce = $oauthNonce;
        $this->oauthSignatureMethod = $oauthSignatureMethod;
        $this->oauthTimestamp = $oauthTimestamp;
    }

    public function addSignature(string $signature): void
    {
        $this->oauthSignature = $signature;
    }
}
