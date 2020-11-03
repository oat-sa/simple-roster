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

namespace App\Security\Lti;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use OAT\Library\Lti1p3Core\Service\Server\Entity\Scope;

class ScopeRepository implements ScopeRepositoryInterface
{
    public function getScopeEntityByIdentifier($identifier)
    {
        if ($identifier !== 'https://purl.imsglobal.org/spec/lti-bo/scope/basicoutcomeeeee') {
            return null;
        }

        return new Scope($identifier);

    }

    public function finalizeScopes(array $scopes, $grantType, ClientEntityInterface $clientEntity, $userIdentifier = null)
    {
        return  $scopes;
    }

}
