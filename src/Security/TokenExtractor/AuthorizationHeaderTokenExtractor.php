<?php declare(strict_types=1);

namespace App\Security\TokenExtractor;

use Symfony\Component\HttpFoundation\Request;

class AuthorizationHeaderTokenExtractor
{
    public const AUTHORIZATION_HEADER = 'Authorization';
    public const AUTHORIZATION_HEADER_PREFIX = 'Bearer';

    public function extract(Request $request): ?string
    {
        if (!$request->headers->has(self::AUTHORIZATION_HEADER)) {
            return null;
        }

        $authorizationHeader = $request->headers->get(self::AUTHORIZATION_HEADER);

        $headerParts = explode(' ', $authorizationHeader);

        if (!(2 === count($headerParts) && 0 === strcasecmp($headerParts[0], self::AUTHORIZATION_HEADER_PREFIX))) {
            return null;
        }

        return $headerParts[1];
    }
}
