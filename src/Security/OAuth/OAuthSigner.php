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
            $encodedParameters[] = $this->encode((string)$name) . '=' . $this->encode((string)$value);
        }

        return implode('&', $encodedParameters);
    }

    private function encode(string $value): string
    {
        return str_replace(['%7E', '+'], ['~', ' '], rawurlencode($value));
    }
}
