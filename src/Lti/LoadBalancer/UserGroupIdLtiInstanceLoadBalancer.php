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

namespace OAT\SimpleRoster\Lti\LoadBalancer;

use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\LtiInstance;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Lti\Exception\IndeterminableLtiInstanceUrlException;
use OAT\SimpleRoster\Lti\Exception\IndeterminableLtiRequestContextIdException;

class UserGroupIdLtiInstanceLoadBalancer extends AbstractLtiInstanceLoadBalancer
{
    /**
     * @throws IndeterminableLtiInstanceUrlException
     */
    public function getLtiInstance(User $user): LtiInstance
    {
        if (!$user->hasGroupId()) {
            throw new IndeterminableLtiInstanceUrlException(
                sprintf("User with id='%s' doesn't have group id.", $user->getId())
            );
        }

        return $this->computeLtiInstanceByString((string)$user->getGroupId());
    }

    /**
     * @throws IndeterminableLtiRequestContextIdException
     */
    public function getLtiRequestContextId(Assignment $assignment): string
    {
        $user = $assignment->getUser();
        if (!$user->hasGroupId()) {
            throw new IndeterminableLtiRequestContextIdException(
                sprintf("User with id='%s' doesn't have group id.", $user->getId())
            );
        }

        return (string)$user->getGroupId();
    }
}
