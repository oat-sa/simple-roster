<?php

declare(strict_types=1);


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

namespace App\Lti\LoadBalancer;

use LogicException;

class LtiInstanceLoadBalancerFactory
{
    public const LOAD_BALANCING_STRATEGY_USERNAME = 'username';
    public const LOAD_BALANCING_STRATEGY_USER_GROUP_ID = 'userGroupId';

    private const VALID_LOAD_BALANCING_STRATEGIES = [
        self::LOAD_BALANCING_STRATEGY_USERNAME,
        self::LOAD_BALANCING_STRATEGY_USER_GROUP_ID,
    ];

    /** @var string[] */
    private $ltiInstances;

    public function __construct(array $ltiInstances)
    {
        $this->ltiInstances = $ltiInstances;
    }

    /**
     * @throws LogicException
     */
    public function __invoke(string $loadBalancingStrategy): LtiInstanceLoadBalancerInterface
    {
        switch ($loadBalancingStrategy) {
            case self::LOAD_BALANCING_STRATEGY_USERNAME:
                return new UsernameLtiInstanceLoadBalancer($this->ltiInstances);

            case self::LOAD_BALANCING_STRATEGY_USER_GROUP_ID:
                return new UserGroupIdLtiInstanceLoadBalancer($this->ltiInstances);

            default:
                throw new LogicException(
                    sprintf(
                        'Invalid load balancing strategy received. Possible values: %s',
                        implode(', ', self::VALID_LOAD_BALANCING_STRATEGIES)
                    )
                );
        }
    }
}
