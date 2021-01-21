<?php

/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Security\OAuth;

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
