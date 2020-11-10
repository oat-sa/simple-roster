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

namespace OAT\SimpleRoster\Security\TokenExtractor;

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

        $authorizationHeader = (string)$request->headers->get(self::AUTHORIZATION_HEADER);

        $headerParts = explode(' ', $authorizationHeader);

        if (!(2 === count($headerParts) && 0 === strcasecmp($headerParts[0], self::AUTHORIZATION_HEADER_PREFIX))) {
            return null;
        }

        return $headerParts[1];
    }
}
